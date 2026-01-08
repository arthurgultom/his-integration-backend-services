"""
AR Overdue Data Sync Script
Syncs AR overdue data from AS400 to MySQL database
Python 2.7 Compatible - Refactored Version
"""

import pyodbc
import datetime
import mysql.connector
from mysql.connector import errorcode


# ============================================================================
# Configuration
# ============================================================================
AS400_DSN = 'DSN=MDS17PRODDSN;UID=CRM;PWD=PASSWORD'
MYSQL_CONFIG = {
    'host': '34.128.71.69',
    'user': 'root',
    'password': 'EuXbrmzvVBjDSB99',
    'database': 'hino_bi_db'
}


# ============================================================================
# Date Setup
# ============================================================================
today = datetime.date.today()
yesterday = today - datetime.timedelta(days=2)

year = str(today.year)
month = str(today.month)
day = str(today.day)

year_yesterday = str(yesterday.year)
month_yesterday = str(yesterday.month)
day_yesterday = str(yesterday.day)

print 'Year:', year
print 'Month:', month_yesterday
print 'Day:', day_yesterday


# ============================================================================
# AS400 Queries
# ============================================================================
count_query = """
    SELECT COUNT(*) as total 
    FROM HMI17P001.ARFP02 T01 
    LEFT JOIN hmi17p001.arfp00 T02 
        ON T01.compa2 = T02.compa0 
        AND T01.custa2 = T02.custa0 
    LEFT JOIN hmi17p001.arfp35 t04 
        ON T01.compa2 = T04.compaz 
        AND T01.refpa2 = T04.refpaz 
        AND T01.REF#A2 = T04.REF#AZ 
    WHERE T01.desca2 IN ('INVOICE', 'CREDIT') 
        AND T01.tryya2 = {0}
        AND T01.trmma2 = {1}
""".format(year, month)

data_query = """
    SELECT 
        T04.CUSTAZ AS dealer_code,
        namea0 AS cust_name,
        T01.tcdea2 AS term_code,
        t04.refpaz as transaction_prefix,
        t04.REF#AZ as transaction_number,
        T01.refpa2 || DIGITS(T01.REF#A2) AS invoice_no,
        T01.tryya2||'-'||T01.trmma2||'-'||T01.trdda2 AS invoice_date,
        T01.tryya2 as inv_year,
        T01.trmma2 as inv_month,
        T01.trdda2 as inv_day,
        T04.iduyaz||'-'||T04.idumaz||'-'||T04.idudaz AS installment_date,
        T04.iduyaz as due_year,
        T04.idumaz as due_month,
        T04.idudaz as due_day,
        T01.debta2 AS amount_inv,
        T04.balaaz AS balance,
        t04.duamaz as due_amount,
        t04.rcamaz as received_amount,
        t04.prdcaz as product_code
    FROM HMI17P001.ARFP02 T01 
    LEFT JOIN hmi17p001.arfp00 T02 
        ON T01.compa2 = T02.compa0 
        AND T01.custa2 = T02.custa0 
    LEFT JOIN hmi17p001.arfp35 t04 
        ON T01.compa2 = T04.compaz 
        AND T01.refpa2 = T04.refpaz 
        AND T01.REF#A2 = T04.REF#AZ 
    WHERE T01.desca2 IN ('INVOICE', 'CREDIT') 
        AND T01.tryya2 = {0}
        AND T01.trmma2 = {1}
""".format(year, month)


# ============================================================================
# Fetch Data from AS400
# ============================================================================
try:
    print "Connecting to AS400..."
    conn_as400 = pyodbc.connect(AS400_DSN)
    cursor_as400 = conn_as400.cursor()
    
    # Get total count
    cursor_as400.execute(count_query)
    total_rows = cursor_as400.fetchone()[0]
    print 'Total rows to process:', total_rows
    
    # Fetch all data
    cursor_as400.execute(data_query)
    ar_data = cursor_as400.fetchall()
    
    cursor_as400.close()
    conn_as400.close()
    print "AS400 data fetched successfully"
    
except Exception as e:
    print "Error connecting to AS400:", str(e)
    exit(1)


# ============================================================================
# Insert to MySQL Database
# ============================================================================
try:
    print "Connecting to MySQL..."
    conn_mysql = mysql.connector.connect(**MYSQL_CONFIG)
    cursor_mysql = conn_mysql.cursor()
    
    # Delete existing data for current month
    delete_query = "DELETE FROM dealer_ar_overdue WHERE YEAR(invoice_date) = %s AND MONTH(invoice_date) = %s"
    cursor_mysql.execute(delete_query, (year, month))
    conn_mysql.commit()
    print "Deleted existing data for {0}-{1}".format(year, month)
    
    # Insert new data
    insert_query = """
        INSERT INTO dealer_ar_overdue (
            dealer_code, cust_name, term_code, transaction_prefix, 
            transaction_number, invoice_no, invoice_date, invoice_year, 
            invoice_month, invoice_day, installment_due_date, 
            installment_due_year, installment_due_month, installment_due_day,
            invoice_amount, balance_amount, due_amount, received_amount, 
            product_code
        ) VALUES (
            %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 
            %s, %s, %s, %s, %s, %s, %s, %s, %s
        )
    """
    
    success_count = 0
    error_count = 0
    
    for row in ar_data:
        try:
            # Convert all values to string to handle decimal/None types
            row_data = tuple(str(val) if val is not None else '' for val in row)
            
            cursor_mysql.execute(insert_query, row_data)
            conn_mysql.commit()
            success_count += 1
            
            if success_count % 100 == 0:
                print "Processed {0}/{1} rows...".format(success_count, total_rows)
            
        except Exception as e:
            error_count += 1
            print "Error inserting row:", str(e)
            continue
    
    print "Insert completed: {0} success, {1} errors".format(success_count, error_count)
    
    cursor_mysql.close()
    conn_mysql.close()
    
except Exception as e:
    print "Error with MySQL operations:", str(e)
    exit(1)


# ============================================================================
# Update Last Update Timestamp
# ============================================================================
try:
    print "Updating last update timestamp..."
    conn_update = mysql.connector.connect(**MYSQL_CONFIG)
    cursor_update = conn_update.cursor()
    
    update_query = """
        DELETE FROM bi_lastupdate WHERE bi_report = 'dealer_ar_overdue';
        INSERT INTO bi_lastupdate 
        SELECT 'dealer_ar_overdue' as bi_report, NOW() as last_update;
    """
    
    for result in cursor_update.execute(update_query, multi=True):
        if result.with_rows:
            print result.fetchall()
    
    conn_update.commit()
    cursor_update.close()
    conn_update.close()
    
    print "Last update timestamp updated successfully"
    print "=" * 60
    print "AR Overdue sync completed successfully!"
    
except Exception as e:
    print "Error updating timestamp:", str(e)
    exit(1)

"""
AR Overdue Data Sync Script
Syncs AR overdue data from AS400 to MySQL database
"""
import pyodbc
import datetime
import mysql.connector
from mysql.connector import errorcode
from difflib import SequenceMatcher
import sys

try:
  conn2   = mysql.connector.connect(host='34.128.71.69',user='root',password='EuXbrmzvVBjDSB99',database='hino_bi_db')
  #conn2   = mysql.connector.connect(host='10.17.51.35',user='mysqlwb',password='mysqlwb',database='hino_bi_db')
except:
  print("I am unable to connect to the database hino_bi_db.")

cur = conn2.cursor()

cur.execute("delete from dealer_ar_overdue where year(invoice_date)="+(YY)+" and  month(invoice_date)="+(MM)+"");
conn2.commit()

okchars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_-+=\/,.<> '

for z in range(0,y):
  #dataItem1 = testArr[z][0].strip();
  dataItem1 = testArr[z][0]
  dataItem2 = testArr[z][1]
  dataItem3 = testArr[z][2]
  dataItem4 = testArr[z][3]
  dataItem5 = testArr[z][4]
  dataItem6 = testArr[z][5]
  dataItem7 = testArr[z][6]
  dataItem8 = testArr[z][7]
  dataItem9 = testArr[z][8]
  dataItem10 = testArr[z][9]
  dataItem11 = testArr[z][10]
  dataItem12 = testArr[z][11]
  dataItem13 = testArr[z][12]
  dataItem14 = testArr[z][13]
  dataItem15 = testArr[z][14]
  dataItem16 = testArr[z][15]
  dataItem17 = testArr[z][16]
  dataItem18 = testArr[z][17]
  dataItem19 = testArr[z][18]
  dataItem20 = testArr[z][19]

  try:
    #args = ['',dataItem1, dataItem2, dataItem3,dataItem4, dataItem5, dataItem6,dataItem7, dataItem8,dataItem9,dataItem10,dataItem11,'','','','','','']
    #result_args = cur.callproc('DATASYNC_DEALER_AR_OVERDUE_CURRENT_MONTH_3S', args);
    cur.execute("insert into dealer_ar_overdue (dealer_code, cust_name, term_code, transaction_prefix, transaction_number,invoice_no, invoice_date, invoice_year, invoice_month, invoice_day, installment_due_date, installment_due_year, installment_due_month, installment_due_day,invoice_amount, due_amount, received_amount, balance_amount, product_code) VALUES ('"+dataItem1+"', , '"+dataItem2+"', '"+dataItem3+"', '"+dataItem4+"', '"+dataItem5+"', '"+dataItem6+"', "+dataItem7+", "+dataItem8+", "+dataItem9+", "+dataItem10+", "+dataItem11+", "+dataItem12+", "+dataItem13+", "+dataItem14+", "+dataItem15+", "+dataItem16+", "+dataItem17+","+dataItem18+", "+dataItem19+")");
    conn2.commit()
    print("sukses")
  except Exception as e:
    print(str(e))
    #print("I am unable to connect to the databasex.")
    break

try:
   conn3  = mysql.connector.connect(host='34.128.71.69',user='root',password='EuXbrmzvVBjDSB99',database='hino_bi_db')
except:
    print ("I am unable to connect to the database hino_bi_db.")

cur3 = conn3.cursor()

query = """
    delete from bi_lastupdate where bi_report = 'dealer_ar_overdue';
  insert into bi_lastupdate select 'dealer_ar_overdue' as bi_report, now() as last_update;
"""

for result in cur3.execute(query, multi=True):
    if result.with_rows:
        print(result.fetchall())

conn3.commit()
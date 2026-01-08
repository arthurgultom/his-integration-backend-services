"""
AR Overdue Data Sync Script
Syncs AR overdue data from AS400 to MySQL database
"""

import pyodbc
import datetime
import time
import calendar
conn = pyodbc.connect('DSN=MDS17PRODDSN;UID=CRM;PWD=PASSWORD')
cursor = conn.cursor()
y=0;
today = datetime.date.today()
one_day = datetime.timedelta(days=2)
yesterday = today - one_day
dodate = str(yesterday)
#YY = str(2025)
YY = str(today.year)
#MM=str(9)
MM=str(today.month)
DD = str(today.day)

#YY = '2025'
#MM='2'

YY1 = str(yesterday.year)
#MM1 = str(9)
MM1 = str(yesterday.month)
DD1 = str(yesterday.day)

todaydate = str(today.year) +  str(today.month) + str(today.day)

print('Year:', YY)
print('Mon :', MM1)
print('Day :', DD1)
cursor.execute("SELECT COUNT(*) as total FROM HMI17P001.ARFP02 T01 LEFT JOIN hmi17p001.arfp00 T02 ON T01.compa2 = T02.compa0 AND T01.custa2 = T02.custa0 LEFT JOIN hmi17p001.arfp35 t04 ON T01.compa2 = T04.compaz AND T01.refpa2 = T04.refpaz AND T01.REF#A2 = T04.REF#AZ WHERE T01.desca2 IN ('INVOICE', 'CREDIT') AND T01.tryya2 = "+str(YY)+" AND T01.trmma2 = "+str(MM)+"")
#AND T05.MVYYJF="+str(YY1)+" and T05.MVMMJF="+str(MM1)+" and T05.MVDDJF="+str(DD1)+"

for row in cursor:
  y=row[0]

testArr = range(y)


print(y)

cursor.execute("SELECT T04.CUSTAZ AS dealer_code,namea0 AS cust_name,T01.tcdea2 AS term_code,t04.refpaz as transaction_prefix,t04.REF#AZ as transaction_number, T01.refpa2 || DIGITS(T01.REF#A2) AS invoice_no,T01.tryya2||'-'||T01.trmma2||'-'||T01.trdda2 AS invoice_date,T01.tryya2 as inv_year,T01.trmma2 as inv_month,T01.trdda2 as inv_day,T04.iduyaz||'-'||T04.idumaz||'-'||T04.idudaz AS installment_date,T04.iduyaz as due_year,T04.idumaz as due_month,T04.idudaz as due_day,T01.debta2 AS amount_inv,T04.balaaz AS balance,t04.duamaz as due_amount,t04.rcamaz as received_amount,t04.prdcaz as product_code FROM HMI17P001.ARFP02 T01 LEFT JOIN hmi17p001.arfp00 T02 ON T01.compa2 = T02.compa0 AND T01.custa2 = T02.custa0 LEFT JOIN hmi17p001.arfp35 t04 ON T01.compa2 = T04.compaz AND T01.refpa2 = T04.refpaz AND T01.REF#A2 = T04.REF#AZ WHERE T01.desca2 IN ('INVOICE', 'CREDIT') AND T01.tryya2 = "+str(YY)+" AND T01.trmma2 = "+str(MM)+"")

# AND T01.DOYYI7 ="+str(YY)+" AND T01.DOMMI7 ="+str(MM)+" 
#OR (T05.FROMJF= '5' AND T05.TOJF ='1') OR (T05.FROMJF= '8' AND T05.TOJF ='1') 
x=0
for row in cursor:
#print row;
  testArr[x] = range(24)
  testArr[x][0] = row[0]
  testArr[x][1] = row[1]
  testArr[x][2] = row[2]
  testArr[x][3] = row[3]
  testArr[x][4] = row[4]
  testArr[x][5] = row[5]
  testArr[x][6] = row[6]
  testArr[x][7] = row[7]
  testArr[x][8] = row[8]
  testArr[x][9] = row[9]
  testArr[x][10] = row[10]
  testArr[x][11] = row[11]
  testArr[x][12] = row[12]
  testArr[x][13] = row[13]
  testArr[x][14] = row[14]
  testArr[x][15] = row[15]
  testArr[x][16] = row[16]
  testArr[x][17] = row[17]
  testArr[x][18] = row[18]
  #testArr[x][19] = row[19]
  #testArr[x][20] = row[20]
  #testArr[x][21] = row[21]
  #testArr[x][22] = row[22]
  #testArr[x][23] = row[23]
  #testArr[x][24] = row[24]
  #testArr[x][25] = row[25]


  x=x+1


cursor.close()
conn.close()
#------------------------------------------insert to postgres------------------------------------------


import mysql.connector
from mysql.connector import errorcode
from difflib import SequenceMatcher
import sys

try:
  conn2   = mysql.connector.connect(host='34.128.71.69',user='root',password='EuXbrmzvVBjDSB99',database='hino_bi_db')
  #conn2   = mysql.connector.connect(host='10.17.51.35',user='mysqlwb',password='mysqlwb',database='hino_bi_db')
except:
  print("I am unable to connect to the database hino_bi_db.")

cur = conn2.cursor()

cur.execute("delete from dealer_ar_overdue where year(invoice_date)="+(YY)+" and  month(invoice_date)="+(MM)+"");
conn2.commit()

okchars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_-+=\/,.<> '

for z in range(0,y):
  # Convert all values to string to avoid decimal concatenation error
  dataItem1 = str(testArr[z][0]) if testArr[z][0] is not None else ''
  dataItem2 = str(testArr[z][1]) if testArr[z][1] is not None else ''
  dataItem3 = str(testArr[z][2]) if testArr[z][2] is not None else ''
  dataItem4 = str(testArr[z][3]) if testArr[z][3] is not None else ''
  dataItem5 = str(testArr[z][4]) if testArr[z][4] is not None else ''
  dataItem6 = str(testArr[z][5]) if testArr[z][5] is not None else ''
  dataItem7 = str(testArr[z][6]) if testArr[z][6] is not None else ''
  dataItem8 = str(testArr[z][7]) if testArr[z][7] is not None else ''
  dataItem9 = str(testArr[z][8]) if testArr[z][8] is not None else ''
  dataItem10 = str(testArr[z][9]) if testArr[z][9] is not None else ''
  dataItem11 = str(testArr[z][10]) if testArr[z][10] is not None else ''
  dataItem12 = str(testArr[z][11]) if testArr[z][11] is not None else ''
  dataItem13 = str(testArr[z][12]) if testArr[z][12] is not None else ''
  dataItem14 = str(testArr[z][13]) if testArr[z][13] is not None else ''
  dataItem15 = str(testArr[z][14]) if testArr[z][14] is not None else ''
  dataItem16 = str(testArr[z][15]) if testArr[z][15] is not None else ''
  dataItem17 = str(testArr[z][16]) if testArr[z][16] is not None else ''
  dataItem18 = str(testArr[z][17]) if testArr[z][17] is not None else ''
  dataItem19 = str(testArr[z][18]) if testArr[z][18] is not None else ''

  try:
    # Use parameterized query to avoid SQL injection and type conversion issues
    sql = """INSERT INTO dealer_ar_overdue 
             (dealer_code, cust_name, term_code, transaction_prefix, transaction_number,
              invoice_no, invoice_date, invoice_year, invoice_month, invoice_day, 
              installment_due_date, installment_due_year, installment_due_month, installment_due_day,
              invoice_amount, balance_amount, due_amount, received_amount, product_code) 
             VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"""
    
    cur.execute(sql, (dataItem1, dataItem2, dataItem3, dataItem4, dataItem5, 
                      dataItem6, dataItem7, dataItem8, dataItem9, dataItem10,
                      dataItem11, dataItem12, dataItem13, dataItem14, dataItem15,
                      dataItem16, dataItem17, dataItem18, dataItem19))
    conn2.commit()
    print("sukses")
  except Exception as e:
    print(str(e))
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
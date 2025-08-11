import pyodbc
import datetime
import time
import calendar
conn = pyodbc.connect('DSN=MDS17PRODDSN;UID=crm;PWD=password')
cursor = conn.cursor()
y=0;
today = datetime.date.today()
one_day = datetime.timedelta(days=10)
yesterday = today - one_day
dodate = str(yesterday)
YY = str(yesterday.year)
MM = str(dodate[5:	7])
DD = str(dodate[8:	10])

YY1 = str(today.year)
MM1 = str(today.month)
DD1 = str(yesterday.day)

todaydate = str(today.year) +  str(today.month) + str(today.day)

print('Year:', YY1)
print('Mon :', MM1)
print('Day :', DD1)
#cursor.execute("SELECT  count(*)as total FROM IDS2101D.ARFP42 T01  WHERE SUBSTR(T01.CUSTAZ,1,1)='V' AND PRDCAZ='10' AND T01.SPYYAZ="+str(YY1)+"")
cursor.execute("SELECT  count(*)as total FROM HMI17P001.ARFP35 T01  WHERE T01.BALAAZ<>0 AND PRDCAZ='10' AND T01.IDUYAZ>=2018")

for row in cursor:
	y=row[0]
	
testArr = range(y)

print(y)

#cursor.execute("SELECT T01.CUSTAZ,T01.PRDCAZ,T01.TCDEAZ,T01.REFPAZ,T01.REF#AZ,T01.IDUYAZ,T01.IDUMAZ,T01.IDUDAZ,T01.DUAMAZ,T01.RCAMAZ,T01.BALAAZ,T01.SPYYAZ,T01.SPMMAZ  FROM IDS2101D.ARFP35 T01  WHERE SUBSTR(T01.CUSTAZ,1,1)='V' AND PRDCAZ='10' AND T01.SPYYAZ="+str(YY1)+" ")
cursor.execute("SELECT T01.CUSTAZ,T01.PRDCAZ,T01.TCDEAZ,T01.REFPAZ,T01.REF#AZ,T01.IDUYAZ,T01.IDUMAZ,T01.IDUDAZ,T01.DUAMAZ,T01.RCAMAZ,T01.BALAAZ  FROM HMI17P001.ARFP35 T01  WHERE  T01.BALAAZ<>0 AND PRDCAZ='10'  AND T01.IDUYAZ>=2018 ")

x=0
for row in cursor:
#print row;
	testArr[x] = range(11)
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
	#testArr[x][11] = row[11]
	#testArr[x][12] = row[12]

	x=x+1

cursor.close()
conn.close()

#------------------------------------------insert to postgres------------------------------------------

import mysql.connector
from mysql.connector import errorcode
from difflib import SequenceMatcher
import sys

try:
	#conn2 	= mysql.connector.connect(host='localhost',user='root',password='',database='vos_incentive')
	conn2 	= mysql.connector.connect(host='10.17.51.35',user='mysqlwb',password='mysqlwb',database='hino_bi_db')
except:
	print("I am unable to connect to the database hino_bi_db.")
	
cur = conn2.cursor()

cur.execute("delete from dealer_ar_overdue_current_month_3s");
conn2.commit()

okchars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_-+=\/,.<> '

for z in range(0,y):
	#dataItem1 = testArr[z][0].strip();
	dataItem1 = ''.join(e for e in str(testArr[z][0]).replace("'", " ") if e in okchars)
	dataItem2 = ''.join(e for e in str(testArr[z][1]).replace("'", " ") if e in okchars)
	dataItem3 = ''.join(e for e in str(testArr[z][2]).replace("'", " ") if e in okchars)
	dataItem4 = ''.join(e for e in str(testArr[z][3]).replace("'", " ") if e in okchars)
	dataItem5 = ''.join(e for e in str(testArr[z][4]).replace("'", " ") if e in okchars)
	dataItem6 = ''.join(e for e in str(testArr[z][5]).replace("'", " ") if e in okchars)
	dataItem7 = ''.join(e for e in str(testArr[z][6]).replace("'", " ") if e in okchars)
	dataItem8 = ''.join(e for e in str(testArr[z][7]).replace("'", " ") if e in okchars)
	dataItem9 = ''.join(e for e in str(testArr[z][8]).replace("'", " ") if e in okchars)
	dataItem10 = ''.join(e for e in str(testArr[z][9]).replace("'", " ") if e in okchars)
	dataItem11 = ''.join(e for e in str(testArr[z][10]).replace("'", " ") if e in okchars)
	#dataItem12 = ''.join(e for e in str(testArr[z][11]).replace("'", " ") if e in okchars)
	#dataItem13 = ''.join(e for e in str(testArr[z][12]).replace("'", " ") if e in okchars)
	
	try:
		args = ['',dataItem1, dataItem2, dataItem3,dataItem4, dataItem5, dataItem6,dataItem7, dataItem8,dataItem9,dataItem10,dataItem11,'','','','','','']
		result_args = cur.callproc('DATASYNC_DEALER_AR_OVERDUE_CURRENT_MONTH_3S', args);
		conn2.commit()
		print("sukses")
	except:
		print(args)
		print("Error on execute DATASYNC_DEALER_AR_OVERDUE_CURRENT_MONTH_3S procedure.")
		break

try:
   conn3  = mysql.connector.connect(host='10.17.51.35',user='mysqlwb',password='mysqlwb',database='hino_bi_db')
except:
    print ("I am unable to connect to the database hino_bi_db.")
	
cur3 = conn3.cursor()

query = """
    delete from bi_lastupdate where bi_report = 'dealer_ar_overdue_current_month_3s';
	insert into bi_lastupdate select 'dealer_ar_overdue_current_month_3s' as bi_report, now() as last_update;
"""

for result in cur3.execute(query, multi=True):
    if result.with_rows:
        print(result.fetchall())

conn3.commit()


import pyodbc
import datetime
import time
import calendar
conn = pyodbc.connect('DRIVER={IBM i Access ODBC Driver};SYSTEM=10.17.51.22;DATABASE=HMI17P001;UID=crm;PWD=password')
cursor = conn.cursor()
y=0;
today = datetime.date.today()
one_day = datetime.timedelta(days=1)
yesterday = today - one_day
dodate = str(yesterday)
YY = str(today.year)
MM = str(dodate[5:	7])
DD = str(dodate[8:	10])

YY1 = str(today.year)
MM1 = str(today.month)
DD1 = str(yesterday.day)

todaydate = str(today.year) +  str(today.month) + str(today.day)

print('Year:', YY1)
print('Mon :', MM1)
print('Day :', DD1)
cursor.execute("SELECT  count(*)as total FROM HMI17P001.ARFP00 T01 LEFT JOIN HMI17P001.ARFP01 T02 ON T01.CUSTA0=T02.CUSTA1 WHERE SUBSTR(T01.CUSTA0,1,1)='V' AND T01.CSTSA0='A'")

for row in cursor:
	y=row[0]

testArr = [[None for _ in range(8)] for _ in range(y)]

print(y)

cursor.execute("SELECT T01.CUSTA0,T01.LSDDA0,T01.LSMMA0,T01.LSYYA0,T02.TCDEA1,T01.CLIMA0,T01.BALOA0,T02.TLIMA1 FROM HMI17P001.ARFP00 T01 LEFT JOIN HMI17P001.ARFP01 T02 ON T01.CUSTA0=T02.CUSTA1 WHERE SUBSTR(T01.CUSTA0,1,1)='V' AND T01.CSTSA0='A'")

x=0
for row in cursor:
#print row;
	#testArr[x] = range(8)
	testArr[x][0] = row[0]
	testArr[x][1] = row[1]
	testArr[x][2] = row[2]
	testArr[x][3] = row[3]
	testArr[x][4] = row[4]
	testArr[x][5] = row[5]
	testArr[x][6] = row[6]
	testArr[x][7] = row[7]

	x=x+1

cursor.close()
conn.close()
#------------------------------------------insert to postgres------------------------------------------

import pymysql
#from mysql.connector import errorcode
from difflib import SequenceMatcher
import sys

try:
	#conn2 	= mysql.connector.connect(host='localhost',user='root',password='',database='vos_incentive')
	conn2 	= pymysql.connect(host='10.17.51.35',user='mysqlwb',password='mysqlwb',database='hino_bi_db')
except:
	print("error connection 10.17.51.35 hino bi db.")

cur = conn2.cursor()
cur.execute("delete from eom_mst_credit_limit");
conn2.commit()

okchars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_-+=\/,.<> '

for z in range(0,y):
	#dataItem1 = testArr[z][0].strip();
	dataItem1 = ''.join(e for e in str(testArr[z][0]).replace("'", " ") if e in okchars)
	#dataItem2 = testArr[z][1].replace("'", " ").isalnum;
	dataItem2 = ''.join(e for e in str(testArr[z][1]).replace("'", " ") if e in okchars)
	dataItem3 = ''.join(e for e in str(testArr[z][2]).replace("'", " ") if e in okchars)
	dataItem4 = ''.join(e for e in str(testArr[z][3]).replace("'", " ") if e in okchars)
	dataItem5 = ''.join(e for e in str(testArr[z][4]).replace("'", " ") if e in okchars)
	dataItem6 = ''.join(e for e in str(testArr[z][5]).replace("'", " ") if e in okchars)
	dataItem7 = ''.join(e for e in str(testArr[z][6]).replace("'", " ") if e in okchars)
	dataItem8 = ''.join(e for e in str(testArr[z][7]).replace("'", " ") if e in okchars)

	try:
		args = ['',dataItem1, dataItem2, dataItem3,dataItem4, dataItem5,'', dataItem6,dataItem7, dataItem8,'','','','']

		result_args = cur.callproc('SYNC_CREDIT_LIMIT', args);

		conn2.commit()
		#print "sukses"
	except:
		print(dataItem1)
		print(dataItem2)
		print(dataItem3)
		print(dataItem4)
		print(dataItem5)
		print(dataItem6)
		print(dataItem7)
		print(dataItem8)

		print(args)
		print("Error on execute SYNC_CREDIT_LIMIT procedure.")
		break;

try:
   conn3  = pymysql.connect(host='10.17.51.35',user='mysqlwb',password='mysqlwb',database='hino_bi_db')
except:
    print ("I am unable to connect to the database hino_bi_db.")

cur3 = conn3.cursor()

# Execute delete statement
delete_query = "delete from bi_lastupdate where bi_report = 'master_credit_limit'"
cur3.execute(delete_query)

# Execute insert statement
insert_query = "insert into bi_lastupdate select 'master_credit_limit' as bi_report, now() as last_update"
cur3.execute(insert_query)

conn3.commit()
print("Successfully updated bi_lastupdate table")

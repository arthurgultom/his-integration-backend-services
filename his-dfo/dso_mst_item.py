print("Start Copying..")

import pyodbc
import time
import datetime
import calendar

# conn = pyodbc.connect('DSN=ISERIESDSN;UID=crm;PWD=password')
conn = pyodbc.connect('DRIVER={IBM i Access ODBC Driver};SYSTEM=10.17.51.22;DATABASE=HMI17U001;UID=mdstest2;PWD=password2')
# conn = pyodbc.connect('DRIVER={iSeries Access ODBC Driver};SYSTEM=10.17.51.22;DATABASE=HMI17U001;UID=mdstest2;PWD=mdstest2')
cursor = conn.cursor()
y=0
z=''
x=0
today = datetime.date.today()
one_day = datetime.timedelta(days=1)
yesterday = today - one_day
yeard = time.strftime("%Y")
monthd = time.strftime("%m")
dayd = time.strftime("%d")
print("Year:", yeard)
print("Mon :", monthd)
print("Day :", dayd)

todaydate = yeard+monthd+dayd

cursor.execute("""select 
ITEMI4,
TOCII4,
DESCI4,
UOMI4,
DIVI4,
ACTVI4,
'',
CLASI4,
'',
WSLEI4,DISCI4
from HMI17U001.INFP04 where DIVI4<>'10'
and COMPI4='001' """)

data = cursor.fetchall()
y = len(data)
	
testArr = [None] * y
dataItem = [None] * 11

for row in data:
	testArr[x] = [None] * 11
	for t in range(0,11):
		testArr[x][t] = row[t]
	x=x+1

cursor.close()
conn.close()

#------------------------------------------insert to postgres------------------------------------------

# import mysql.connector
# from mysql.connector import errorcode
import pymysql
from difflib import SequenceMatcher
import sys

try:
   # conn2 = mysql.connector.connect(host='10.17.111.18',user='mysqlwb',password='mysqlwb',database='his_db_final_3')
   conn2 = pymysql.connect(host='34.128.71.69',user='root',password='EuXbrmzvVBjDSB99',database='his_db_final_3_dev')
except:
    print("I am unable to connect to the databases.")
	
cur = conn2.cursor()


cur.execute("delete from dso_mst_item_part")
conn2.commit()

okchars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_-+=\/,.<> '

for z in range(0,y):
	for index in range(0,11):
	
		dataItem[index] = ''.join(e for e in str(testArr[z][index]).replace("'", " ") if e in okchars)
		if( dataItem[index] == 'None' ):
			dataItem[index] ='0'
		
	try:
		cur.execute("insert into dso_mst_item_part(part_code,part_interchange,part_name,uom,divisi,ACTVI4,ACTVI5,CLASI5,SUPPI5,pricelist,disc_code,created_date,modified_by,modified_date,created_by) VALUES('"+dataItem[0]+"','"+dataItem[1]+"','"+dataItem[2]+"','"+dataItem[3]+"','"+dataItem[4]+"','"+dataItem[5]+"','"+dataItem[6]+"','"+dataItem[7]+"','"+dataItem[8]+"','"+dataItem[9]+"','"+dataItem[10]+"',now(),'admin',now(),'admin')")
		conn2.commit()
		print("sukses")
	except:
		print(dataItem[0])
		print(dataItem[1])
		print(dataItem[2])
		print(dataItem[4])
		print(dataItem[5])
		print(dataItem[6])
		print(dataItem[7])
		print(dataItem[8])
		print(dataItem[9])
		#print "insert into dso_mst_item_part(part_code,part_interchange,part_name,uom,divisi,ACTVI4,ACTVI5,CLASI5,SUPPI5,created_date,modified_by,modified_date,created_by) VALUES('"+dataItem[0]+"','"+dataItem[1]+"','"+dataItem[2]+"','"+dataItem[3]+"','"+dataItem[4]+"','"+dataItem[5]+"','"+dataItem[6]+"','"+dataItem[7]+"','"+dataItem[8]+"',now(),'admin',now(),'admin')";
		break

print("Stop Copying..")

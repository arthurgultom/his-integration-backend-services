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
YY = str(today.year)
MM=str(today.month)
DD = str(today.day)

YY1 = str(yesterday.year)
MM1 = str(yesterday.month)
DD1 = str(yesterday.day)

todaydate = str(today.year) +  str(today.month) + str(today.day)

print('Year:', YY)
print('Mon :', MM1)
print('Day :', DD1)
cursor.execute("SELECT count(*)as total FROM HMI17P001.INFL0700 T01 LEFT JOIN HMI17P001.ARFL0000 T02 ON T01.DLR#I7=T02.CUSTA0 LEFT JOIN HMI17P001.INFL3700 T03 ON T01.VIN#I7=T03.VIN#J1 LEFT JOIN HMI17P001.ZZFL3400 T04 ON T01.VIN#I7= T04.VIN#34 LEFT JOIN HMI17P001.INFL5200 T05 ON T01.VIN#I7=T05.VIN#JF WHERE T01.COMPI7 ='001' AND (T05.FROMJF= '1' AND T05.TOJF ='5') AND T01.DOYYI7 ="+str(YY1)+" AND T01.DOMMI7 ="+str(MM1)+" ")
#AND T05.MVYYJF="+str(YY1)+" and T05.MVMMJF="+str(MM1)+" and T05.MVDDJF="+str(DD1)+"

for row in cursor:
	y=row[0]
	
testArr = range(y)

print(y)

cursor.execute("SELECT T01.VIN#I7,T03.ITEMJ1,T01.MODLI7,T01.DLR#I7,T01.VIN#I7,T01.ENG#I7,T01.COLRI7,T01.DODDI7,T01.DOMMI7,T01.DOYYI7,T01.REF2I7,T01.REFPI7,T01.REF#I7,T02.NAMEA0,T05.FROMJF,T05.TOJF,T05.MVYYJF, T05.MVMMJF,T05.MVDDJF,T01.ETAYI7,T01.ETAMI7,T01.ETADI7,T01.INVPI7,T01.INV#I7 FROM HMI17P001.INFL0700 T01 LEFT JOIN HMI17P001.ARFL0000 T02 ON T01.DLR#I7=T02.CUSTA0  LEFT JOIN HMI17P001.INFL3700 T03 ON T01.VIN#I7=T03.VIN#J1  LEFT JOIN HMI17P001.ZZFL3400 T04 ON T01.VIN#I7= T04.VIN#34 LEFT JOIN HMI17P001.INFL5200 T05 ON T01.VIN#I7=T05.VIN#JF WHERE T01.COMPI7 ='001' AND (T05.FROMJF= '1' AND T05.TOJF ='5') AND T01.DOYYI7 ="+str(YY1)+" AND T01.DOMMI7 ="+str(MM1)+"  ")

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
	testArr[x][19] = row[19]
	testArr[x][20] = row[20]
	testArr[x][21] = row[21]
	testArr[x][22] = row[22]
	testArr[x][23] = row[23]
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
	conn2 	= mysql.connector.connect(host='10.17.51.35',user='mysqlwb',password='mysqlwb',database='hino_bi_db')
except:
	print("I am unable to connect to the database hino_bi_db.")
	
cur = conn2.cursor()

cur.execute("delete from bi_vehicle_wholesales_dealer where year(inv_date)="+(YY1)+" and  month(inv_date)="+str(MM1)+"  and engine<>''");
conn2.commit()

okchars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_-+=\/,.<> '

for z in range(0,y):
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
	dataItem12 = ''.join(e for e in str(testArr[z][11]).replace("'", " ") if e in okchars)
	dataItem13 = ''.join(e for e in str(testArr[z][12]).replace("'", " ") if e in okchars)
	dataItem14 = ''.join(e for e in str(testArr[z][13]).replace("'", " ") if e in okchars)
	dataItem15 = ''.join(e for e in str(testArr[z][14]).replace("'", " ") if e in okchars)
	dataItem16 = ''.join(e for e in str(testArr[z][15]).replace("'", " ") if e in okchars)
	dataItem17 = ''.join(e for e in str(testArr[z][16]).replace("'", " ") if e in okchars)
	dataItem18 = ''.join(e for e in str(testArr[z][17]).replace("'", " ") if e in okchars)
	dataItem19 = ''.join(e for e in str(testArr[z][18]).replace("'", " ") if e in okchars)
	dataItem20 = ''.join(e for e in str(testArr[z][19]).replace("'", " ") if e in okchars)
	dataItem21 = ''.join(e for e in str(testArr[z][20]).replace("'", " ") if e in okchars)
	dataItem22 = ''.join(e for e in str(testArr[z][21]).replace("'", " ") if e in okchars)
	dataItem23 = ''.join(e for e in str(testArr[z][22]).replace("'", " ") if e in okchars)
	dataItem24 = ''.join(e for e in str(testArr[z][23]).replace("'", " ") if e in okchars)
	#dataItem25 = ''.join(e for e in str(testArr[z][24]).replace("'", " ") if e in okchars)
	#dataItem26 = ''.join(e for e in str(testArr[z][25]).replace("'", " ") if e in okchars)
	
	try:
		dodate = str(dataItem10) + "-" + str(dataItem9) + "-" + str(dataItem8)
		mvndate = str(dataItem17) + "-" + str(dataItem18) + "-" + str(dataItem19)
		etadate = str(dataItem20) + "-" + str(dataItem21) + "-" + str(dataItem22)
		invoiceno = str(dataItem23) + " " + str(dataItem24) 
		chasis=  dataItem5[14:	20]
		#omnumber = dataItem12 + dataItem13
		omnumber = str(dataItem12) + str(dataItem13)
		
		args = ['','',dataItem1, dataItem2, dataItem3,dataItem4, chasis, dataItem6, dataItem7,dodate, '',dataItem11,omnumber,dataItem14,'','','','','','','',dataItem15,dataItem16,mvndate,etadate,invoiceno]
		#args = ['','',dataItem1, dataItem2, dataItem3,dataItem4, chasis, dataItem6, dataItem7,dodate, '',dataItem11,omnumber,dataItem14,'','','','','','','',dataItem15,dataItem16,mvndate,etadate,invoiceno,invoicedate]
	
		result_args = cur.callproc('UPDATE_DEALER_STOCK_STATUS_1_5', args);

		conn2.commit()
		#print "sukses"
	except:
		print(args)
		#print "INSERT INTO item_parts_copy (COMPI4,ITEMI4,DESCI4,RETLI4,WSLEI4,TWSLI4,DWSLI4,DISCI4,COLRI5,WHSI5,STTEI5,ACTVI5,PRDCI5,DIVI5,CAT1I5,CAT2I5,CAT3I5,CAT4I5,CAT5I5,SOHI5,SOAI5,SOBI5,SOFI5,SOOI5,SORI5,SITI5,WIPI5,ISSI5,SQCI5,SIBI5 ) VALUES ('"+dataItem1+"','"+dataItem2+"','"+dataItem3+"',0,'"+dataItem4+"','"+dataItem5+"',0,0,'','','','','',0,0,'','','','','','"+dataItem6+"','"+dataItem7+"','"+dataItem8+"','"+dataItem9+"','"+dataItem10+"','"+dataItem11+"','"+dataItem12+"','"+dataItem13+"','"+dataItem14+"','"+dataItem15+"')";
		print("Error on execute UPDATE_DEALER_STOCK_STATUS_1_5 procedure.")
		break;
		
	
	
# rows = cur.fetchall()
# for row in rows:
    # print "   ", row['item_no']	





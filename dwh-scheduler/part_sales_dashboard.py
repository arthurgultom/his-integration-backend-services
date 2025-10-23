print ("Start Copying")
import pyodbc
import time
import datetime
import calendar
conn = pyodbc.connect('DRIVER={IBM i Access ODBC Driver};SYSTEM=10.17.51.22;DATABASE=HMI17P001;UID=crm;PWD=password')
cursor = conn.cursor()
y=0;
z='';
x=0;
yeard = time.strftime("%Y")
today = datetime.date.today()
one_day = datetime.timedelta(days=1)
yesterday = today - one_day
YY = str(today.year)
MM = str(today.month)
DD = str(today.day)

print(YY)
print(MM)

cursor.execute("""SELECT 
REFPE1 ||' ' ||REF#E1 as DO_NO,
CORDE0 AS PO_NO,
INYYE1||'/'||INMME1||'/'||INDDE1 as INV_DATE,
'-' as INV_NO ,
'-' as Tax_No   ,
ITMSE1 as PART_NO,
SUPQE1 as QTY,
'PC' as UOM,
WSLEE1 AS PRICELIST,
DECIMAL(100-(((INVVE1/SUPQE1)/WSLEE1)*100),18,0) AS  DISC,
DECIMAL(INVVE1/SUPQE1,18,0) AS  AFTER,
'IDR' as WAERS,
INVVE1 AS GROSS_AMT,
REFPE1||REF#E1  as Picking_List,
INYYE0||'/'||INMME0||'/'||INDDE0 as DELIVERYDATE,
ODYYE0||'/'||ODMME0||'/'||ODDDE0 AS ORDERDC,
TCMPE0 AS EXPEDITION ,
USREE0||' - ',
CUSTA0 as DEALER_NAME,
ORPE1 as ORG_PL_PREF,
OR#E1 as ORG_PL_NO,
INVPE1 as INV_NO_BY_DO_PREF,
INV#E1 as INV_NO_BY_DO,
PRNTI4 as PART_MASKING,
DESCI4 as PART_NAME,
SLSTE0 as SALES_TYPE,
TAXBE1 AS TAX,
DIVE1 AS DIV,
ICSTE1 as COST_INVOICE,
CAT1I4 as CAT_ONE

FROM HMI17P001.OEFL0100   T01 LEFT JOIN 
HMI17P001.INFP04 T02   ON

T01.ITMSE1        =       T02.ITEMI4   AND 
T01.COMPE1        =       T02.COMPI4    LEFT JOIN 

HMI17P001.ARFL0000 T03 ON

T01.COMPE1        =       T03.COMPA0  AND
T01.CUSTE1        =       T03.CUSTA0  LEFT JOIN 

HMI17P001.OEFL0000 T04 ON

T01.COMPE1        =       T04.COMPE0  AND 
T01.REFPE1        =       T04.REFPE0 AND
T01.REF#E1        =       T04.REF#E0 

WHERE    COMPE1   =     '001'

AND     INYYE1  ="""+str(YY)+"""
AND     INMME1  ="""+str(MM)+"""
AND    PRDCE0   ='40'
""")

data = cursor.fetchall()
y	 = len(data)

testArr = [None] * y
dataItem = [None] * 30

for row in data:
        testArr[x] = [None] * 30
        for t in range(0,30):
                testArr[x][t] = row[t]
        x=x+1

cursor.close()
conn.close()


#------------------------------------------insert to postgres------------------------------------------


import mysql.connector
from difflib import SequenceMatcher
import sys

try:
   	conn2 = mysql.connector.connect(host='10.17.51.35',user='mysqlwb',password='mysqlwb',database='hino_bi_db')
except:
    print ("I am unable to connect to the database hino_bi_db.")

cur = conn2.cursor()

cur.execute("delete from bi_part_sales where year(inv_date)="+(YY)+" and month(inv_date)=="+(MM)+" and do_no<>'-'");
conn2.commit()
okchars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_-+=\/,.<> '

for z in range(0,y):
	for index in range(0,30):
		dataItem[index] = ''.join(e for e in str(testArr[z][index]).replace("'", " ") if e in okchars)
		if( dataItem[index] == 'None' ):
			dataItem[index] ='0';

	try:
		cur.execute("insert into bi_part_sales(id_invoice,do_no,po_no,inv_date,inv_summary,seri_pajak,part_no,qty,uom,pricelist,discount,after_discount,waers,gross,picking_list,delivery_date_hmsi,dc_order_date,expedisi,keyedby,dealer_name,original_pl_pref,original_pl_no,inv_by_do_pref,inv_by_do,part_masking,part_name,sales_type, tax,division,cost,item_category_one) VALUES('','"+dataItem[0]+"','""','"+dataItem[2]+"','"+dataItem[3]+"','"+dataItem[4]+"','"+dataItem[5]+"',"+dataItem[6]+",'"+dataItem[7]+"',"+dataItem[8]+","+dataItem[9]+","+dataItem[10]+",'"+dataItem[11]+"',"+dataItem[12]+",'"+dataItem[13]+"','"+dataItem[14]+"','"+dataItem[15]+"','"+dataItem[16]+"','"+dataItem[17]+"','"+dataItem[18]+"','"+dataItem[19]+"','"+dataItem[20]+"','"+dataItem[21]+"','"+dataItem[22]+"','"+dataItem[23]+"','"+dataItem[24]+"','"+dataItem[25]+"','"+dataItem[26]+"','"+dataItem[27]+"','"+dataItem[28]+"','"+dataItem[29]+"')")

		conn2.commit()
		print ("sukses")
	except:
		print ("failed")
		print ("insert into bi_part_sales(id_invoice,do_no,po_no,inv_date,inv_summary,seri_pajak,part_no,qty,uom,pricelist,discount,after_discount,waers,gross,picking_list,delivery_date_hmsi,dc_order_date,expedisi,keyedby,dealer_name,original_pl_pref,original_pl_no,inv_by_do_pref,inv_by_do,part_masking,part_name,sales_type,tax,division) VALUES('','"+dataItem[0]+"','"+dataItem[1]+' '",'"+dataItem[2]+"','"+dataItem[3]+"','"+dataItem[4]+"','"+dataItem[5]+"',"+dataItem[6]+",'"+dataItem[7]+"',"+dataItem[8]+","+dataItem[9]+","+dataItem[10]+",'"+dataItem[11]+"',"+dataItem[12]+",'"+dataItem[13]+"','"+dataItem[14]+"','"+dataItem[15]+"','"+dataItem[16]+"','"+dataItem[17]+"','"+dataItem[18]+"','"+dataItem[19]+"','"+dataItem[20]+"','"+dataItem[21]+"','"+dataItem[22]+"','"+dataItem[23]+"','"+dataItem[24]+"','"+dataItem[25]+"','"+dataItem[26]+"','"+dataItem[27]+"','"+dataItem[28]+"','"+dataItem[29]+"')")
		break;
print ("Stop Copying..")

try:
   conn3 = mysql.connector.connect(host='10.17.51.35',user='mysqlwb',password='mysqlwb',database='hino_bi_db')
except:
    print ("I am unable to connect to the database hino_bi_db.")

cur3 = conn3.cursor()

# Execute delete statement
delete_query = "delete from bi_lastupdate where bi_report = 'bi_part_sales'"
cur3.execute(delete_query)

# Execute insert statement
insert_query = "insert into bi_lastupdate select 'bi_part_sales' as bi_report, now() as last_update"
cur3.execute(insert_query)

conn3.commit()
print("Successfully updated bi_lastupdate table")
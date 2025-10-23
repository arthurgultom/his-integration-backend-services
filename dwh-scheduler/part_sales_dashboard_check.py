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

#todaydate =  yeard+monthd+dayd

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
y        = len(data)

testArr = [None] * y
dataItem = [None] * 30

for row in data:
  testArr[x] = [None] * 30
  for t in range(0,30):
    testArr[x][t] = row[t]
  x=x+1

cursor.close()
conn.close()

print("\n--- Query Results ---")
if testArr:
    for row in testArr:
        print(row)
    print(f"\nTotal rows fetched: {len(testArr)}")
else:
    print("No data was returned from the query.")

import sys
from difflib import SequenceMatcher
import pymysql
import calendar
import datetime
import time
import pyodbc

print("[STEP 1] Starting warranty claim detail processing...")
print(f"[STEP 1] Process started at: {datetime.datetime.now()}")

print("[STEP 2] Establishing database connections...")
# conn = pyodbc.connect('DSN=ISERIESDSN;UID=crm;PWD=password')
try:
    conn = pyodbc.connect('DRIVER={IBM i Access ODBC Driver};SYSTEM=10.17.51.22;DATABASE=HMI17U001;UID=mdstest2;PWD=password2')
    print("[STEP 2] ✓ Successfully connected to IBM iSeries database")
except Exception as e:
    print(f"[STEP 2] ✗ Failed to connect to IBM iSeries database: {e}")
    sys.exit(1)

cursor = conn.cursor()
print("[STEP 2] ✓ Database cursor created")

print("[STEP 3] Initializing variables and date parameters...")
y = 0
z = ''
x = 0
yeard = time.strftime("%Y")
today = datetime.date.today()
one_day = datetime.timedelta(days=1)
yesterday = today - one_day
YY = str(today.year)
MM = str(today.month)
DD = str(today.day)
BLN = datetime.date.today().strftime('%m')

print(f"[STEP 3] ✓ Date parameters set - Year: {YY}, Month: {MM}, Day: {DD}")
print(f"[STEP 3] ✓ Processing data for period: {MM}/{YY}")

print("[STEP 4] Executing warranty claim query...")
print(f"[STEP 4] Query parameters - Year: {YY}, Month: {MM}")
print("[STEP 4] Fetching warranty claims with warranty types: NO, PD, NJ, PJ, TN, TP, ZF, ZP, GW, GP, G1, 2H, 1H, VN, VP, VS, WP, WO, AN, AP, RN, RP, BN, GY, GX")

try:
    cursor.execute("""SELECT
    T01.WARTW0,
    T01.DLRCW0,
    T01.DLR#W0,
    T03.CLMRWQ,
    t01.WARPW0 || DIGITS(t01.WAR#W0) AS CLAIM#,
    T01.MODLW0,
    T01.VIN#W0,
    T01.ENG#W0,
    T01.MHRSW0,
    digits(T05.soyyi7)|| '/' || digits(T05.sommi7)|| '/' || digits(T05.soddi7) AS DELIVEDATE,
    digits(T01.FDYYW0)|| '/' || digits(T01.FDMMW0)|| '/' || digits(T01.FDDDW0) AS REPAIRDATE,
    T02.TEXTW7 AS PROBLEM,
    T01.CAUSW0,
    T06.DESCI4,
    T06.WSLEI4,
    T06.PSUPI4,
    T01.PROBW0 AS WARR_CODE,
    T01.COD2W0,
    T01.POPCW0 AS OP_CODE,
    T01.LHRSW0,
    T01.LCSTW0 AS LABOURHMMI,
    FCRDWQ AS SUPP_CRDT,
    EXCSWQ AS RATE,
    FCRDWQ * EXCSWQ AS CRDT_AMNT,
    T01.LCSTW0,
    T01.MCSTW0,
    T01.SCSTW0,
    t01.MCSTW0 + t01.LCSTW0 + t01.scstw0 AS STOTAL,
    T01.STAXW0,
    T05.LADDI7,
    T05.LAMMI7,
    T05.LAYYI7,
    T01.APDDW0,
    T01.APMMW0,
    T01.APYYW0,
    T01.PPMMW0,
    T01.PPYYW0,
    T01.DCDDW0,
    T01.DCMMW0,
    T01.DCYYW0,
    T03.WARCWQ,
    T07.WAR#XO,
    'WR-'|| SUBSTRING(T01.DLR#W0, 2, 3)|| digits(T01.PPMMW0)||SUBSTRING(T01.PPYYW0,3,2) AS WR_DOC_NO,MDLYI7,
    T01.CRD#W0,T07.REFXO
    FROM
    HMI17U001.WAFP00 T01 LEFT JOIN (
    SELECT
    * FROM	HMI17U001.WAFP07 T02
    WHERE T02.TXTFW7 = 'D'
    AND T02.LINEW7 = 1
    AND T02.LINEW7 = 1 ) AS T02 ON
    T01.COMPW0 = T02.COMPW7
    AND T01.WARPW0 = T02.WARPW7
    AND T01.WAR#W0 = T02.WAR#W7
    LEFT JOIN HMI17U001.WAFP26 T03 ON
    T01.COMPW0 = T03.COMPWQ
    AND T01.WARPW0 = T03.WARPWQ
    AND T01.WARPW0 = T03.WARPWQ
    AND T01.WAR#W0 = T03.WAR#WQ
    LEFT JOIN HMI17U001.INFP07 T05 ON
    T01.VIN#W0 = T05.VIN#I7
    LEFT JOIN HMI17U001.INFP04 T06 ON
    T01.CAUSW0 = T06.ITEMI4
    LEFT JOIN HMI17U001.WAFP60 T07 ON
    T01.COMPW0 = T07.COMPXO
    AND T01.WARPW0 = T07.WARPXO
    AND T01.WARPW0 = T07.WARPXO
    AND T01.WAR#W0 = T07.WAR#XO
    WHERE
    T01.COMPW0 = '001'
    AND PPYYW0 ="""+str(YY)+"""
    AND T01.ppMMw0 = """+str(MM)+"""
    
    AND APMMW0 <> 0
    AND APYYW0 <> 0
    AND T01.WARTW0 IN ( 'NO' , 'PD', 'NJ' , 'PJ', 'TN', 'TP', 'ZF', 'ZP', 'GW', 'GP', 'G1' , '2H', '1H',
    'VN' , 'VP', 'VS', 'WP', 'WO', 'AN', 'AP', 'RN', 'RP','BN','GY','GX')
    	AND T01.CRD#W0 <> 0""")
    print("[STEP 4] ✓ Query executed successfully")
except Exception as e:
    print(f"[STEP 4] ✗ Query execution failed: {e}")
    cursor.close()
    conn.close()
    sys.exit(1)

print("[STEP 5] Fetching data from database...")
data = cursor.fetchall()
y = len(data)
print(f"[STEP 5] ✓ Retrieved {y} warranty claim records")

print("[STEP 6] Processing retrieved data into arrays...")
# Fix range assignment issue - convert to lists
testArr = [None] * y
dataItem = [None] * 46

print(f"[STEP 6] Processing {y} records...")
for row in data:
    testArr[x] = [None] * 46
    for t in range(0, 46):
        testArr[x][t] = row[t]
    x = x+1
    if x % 100 == 0:  # Log progress every 100 records
        print(f"[STEP 6] Processed {x}/{y} records...")

print(f"[STEP 6] ✓ Successfully processed all {y} records into arrays")

print("[STEP 7] Closing IBM iSeries database connection...")
cursor.close()
conn.close()
print("[STEP 7] ✓ IBM iSeries database connection closed")


# ------------------------------------------insert to mysql------------------------------------------

print("[STEP 8] Establishing MySQL database connection...")
try:
    conn2 = pymysql.connect(host='34.128.71.69',user='root',password='EuXbrmzvVBjDSB99', database='hino_bi_db_dev')
    print("[STEP 8] ✓ Successfully connected to MySQL database (hino_bi_db_dev)")
except Exception as e:
    print(f"[STEP 8] ✗ Failed to connect to MySQL database: {e}")
    sys.exit(1)

cur = conn2.cursor()
print("[STEP 8] ✓ MySQL cursor created")

print("[STEP 9] Initializing data cleaning parameters...")
okchars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_-+=\/,.<> '
print("[STEP 9] ✓ Character filter set for data cleaning")

print(f"[STEP 10] Starting data insertion into MySQL table 'warranty_claim_detail'...")
print(f"[STEP 10] Processing {y} records for insertion...")

successful_inserts = 0
failed_inserts = 0

for z in range(0, y):
    print(f"[STEP 10] Processing record {z+1}/{y} - Dealer: {testArr[z][2] if testArr[z][2] else 'N/A'}")
    
    # Clean and prepare data for each field
    for index in range(0, 46):
        dataItem[index] = ''.join(e for e in str(testArr[z][index]).replace("'", " ") if e in okchars)
        if dataItem[index] == 'None' or dataItem[index] == 'None ':
            dataItem[index] = '0'
    
    print(f"[STEP 10] - Data cleaned for record {z+1}, attempting database insertion...")
    
    try:
        cur.execute("INSERT INTO warranty_claim_detail (WARTW0, DLRCW0, `DLR#W0`, CLMRWQ, `CLAIM#`, MODLW0, `VIN#W0`, `ENG#W0`, MHRSW0, DELIVEDATE, REPAIRDATE, PROBLEM, CAUSW0, DESCI4, WSLEI4, PSUPI4, WARR_CODE, COD2W0, OP_CODE, LHRSW0, LABOURHMMI, SUPP_CRDT, RATE, CRDT_AMNT, LCSTW0, MCSTW0, SCSTW0, STOTAL, STAXW0, LADDI7, LAMMI7, LAYYI7, APDDW0, APMMW0, APYYW0, PPMMW0, PPYYW0, DCDDW0, DCMMW0, DCYYW0, WARCWQ, `WAR#XO`, WR_DOC_NO,MODEL_YEAR,CLAIM_TYPE_REPORT, `CRD#W0`,REFXO) VALUES('"+dataItem[0]+"','"+dataItem[1]+"','"+dataItem[2]+"','"+dataItem[3]+"','"+dataItem[4]+"','"+dataItem[5]+"','"+dataItem[6]+"','"+dataItem[7]+"','"+dataItem[8]+"','"+dataItem[9]+"','"+dataItem[10]+"','"+dataItem[11]+"','"+dataItem[12]+"','"+dataItem[13]+"','"+dataItem[14]+"','"+dataItem[15]+"','"+dataItem[16]+"','"+dataItem[17]+"','"+dataItem[18]+"','"+dataItem[19]+"','"+dataItem[20]+"','"+dataItem[21]+"','"+dataItem[22]+"','"+dataItem[23]+"','"+dataItem[24]+"','"+dataItem[25]+"','"+dataItem[26]+"','"+dataItem[27]+"','"+dataItem[28]+"','"+dataItem[29]+"','"+dataItem[30]+"','"+dataItem[31]+"','"+dataItem[32]+"','"+dataItem[33]+"','"+dataItem[34]+"','"+dataItem[35]+"','"+dataItem[36]+"','"+dataItem[37]+"','"+dataItem[38]+"','"+dataItem[39]+"','"+dataItem[40]+"','"+dataItem[41]+"','"+dataItem[42]+"','"+dataItem[43]+"','WCL', '"+dataItem[44]+"','"+dataItem[45]+"')")
        conn2.commit()
        successful_inserts += 1
        print(f"[STEP 10] ✓ Successfully inserted record {z+1}/{y} - Claim: {dataItem[4]}")
        
        # Log progress every 50 successful inserts
        if successful_inserts % 50 == 0:
            print(f"[STEP 10] Progress: {successful_inserts} records successfully inserted...")
            
    except Exception as e:
        failed_inserts += 1
        print(f"[STEP 10] ✗ Failed to insert record {z+1}/{y}: {e}")
        print(f"[STEP 10] - Problematic data: Dealer={dataItem[2]}, Claim={dataItem[4]}")
        break

print(f"[STEP 11] Data insertion completed!")
print(f"[STEP 11] ✓ Successfully inserted: {successful_inserts} records")
print(f"[STEP 11] ✗ Failed insertions: {failed_inserts} records")
print(f"[STEP 11] Total processed: {successful_inserts + failed_inserts}/{y} records")

print("[STEP 12] Closing MySQL database connection...")
cur.close()
conn2.close()
print("[STEP 12] ✓ MySQL database connection closed")

print(f"[FINAL] Warranty claim detail processing completed at: {datetime.datetime.now()}")
print(f"[FINAL] Process summary: {successful_inserts} successful, {failed_inserts} failed out of {y} total records")


import sys
from difflib import SequenceMatcher
import mysql.connector
import calendar
import datetime
import time
import pyodbc

print("[STEP 1] Starting warranty claim detail processing...")
print(f"[STEP 1] Process started at: {datetime.datetime.now()}")

print("[STEP 2] Establishing database connections...")
try:
    conn = pyodbc.connect('DSN=MDS17PRODDSN;UID=crm;PWD=password')
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

print(f"[STEP 3] Date parameters set - Year: {YY}, Month: {MM}, Day: {DD}")
print(f"[STEP 3] Processing data for period: {MM}/{YY}")

print("[STEP 4] Executing warranty claim query...")
print(f"[STEP 4] Query parameters - Year: {YY}, Month: {MM}")
print("[STEP 4] Fetching warranty claims with conditions: WARCW0 IN ('1F','2F','3F','4F','5F','6F','7F','8F','C1','CM','WS1','WS2','0')")

try:
    cursor.execute("""SELECT
        T01.DLR#W0,
        T02.NAMEA0,
        WARPW0 || DIGITS(WAR#W0) AS CLAIM#,
        T01.STSW0,
        T01.DLRCW0,
        T01.MODLW0,
        '1' AS QTY,
        T04.MFAMIP,
        substr(warcw0,1,2)|| wartw0 AS ACCT,
        substr(dlrcw0,1,2)|| wartw0 AS ACCT2,
        T01.VIN#W0,
        T03.SER#I7,
        T01.LCSTW0,
        T01.SCSTW0,
        T01.MCSTW0,
        MCSTW0 + LCSTW0 + scstw0 AS STOTAL,
        T01.STAXW0,
        (MCSTW0 + LCSTW0 + scstw0)+ STAXW0 AS TOTAMOUNT,
        T01.MHRSW0,
        T01.FDYYW0,
        T01.FDMMW0,
        T01.FDDDW0,
        T01.PPYYW0,
        T01.PPMMW0,
        T01.SBYYW0,
        T01.SBMMW0,
        T01.SBDDW0,
        T01.APYYW0,
        T01.APMMW0,
        T01.APDDW0,
        T01.QTYW0,
        T03.WRDDI7,
        T03.WRMMI7,
        T03.WRYYI7,'WR-'|| SUBSTRING(T01.DLR#W0, 2, 3)|| digits(T01.PPMMW0)||SUBSTRING(T01.PPYYW0,3,2) AS WR_DOC_NO,
        T01.CRD#W0
        FROM HMI17U001.WAFP00 T01 
        LEFT JOIN HMI17U001.ARFP00 T02 
        ON T01.DLR#W0 = T02.CUSTA0 AND T01.COMPW0 = T02.COMPA0 
        LEFT JOIN HMI17U001.INFP07 T03 ON T01.COMPW0 = T03.COMPI7
        AND T01.VIN#W0 = T03.VIN#I7 LEFT JOIN HMI17U001.INFP25 T04 ON T03.COMPI7 = T04.COMPIP
        AND T03.MODLI7 = T04.MODLIP WHERE
        COMPW0 = '001' 
        
        AND WARCW0 IN ('1F','2F','3F','4F','5F','6F','7F','8F','C1','CM','WS1','WS2','0') 
        AND PCRDW0 <> 'N'
        AND PPYYW0 = '"""+str(YY)+"""'
        AND PPMMW0 = '"""+str(MM)+"""'
        AND CRDPW0 = 'Y' 
		""")
    print("[STEP 4] Query executed successfully")
except Exception as e:
    print(f"[STEP 4] Query execution failed: {e}")
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
dataItem = [None] * 36

print(f"[STEP 6] Processing {y} records...")
for row in data:
    testArr[x] = [None] * 36
    for t in range(0, 36):
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
    conn2 = mysql.connector.connect(host='10.17.111.18',user='mysqlwb',password='mysqlwb',database='his_db_final_3')
    print("[STEP 8] ✓ Successfully connected to MySQL database (his_db_final_3)")
except Exception as e:
    print(f"[STEP 8] ✗ Failed to connect to MySQL database: {e}")
    sys.exit(1)

cur = conn2.cursor()
print("[STEP 8] ✓ MySQL cursor created")

print("[STEP 9] Initializing data cleaning parameters...")
okchars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_-+=\/,.<> '
print("[STEP 9] ✓ Character filter set for data cleaning")

print(f"[STEP 10] Starting data insertion into MySQL table 'fsp_ws_claim_detail'...")
print(f"[STEP 10] Processing {y} records for insertion...")

successful_inserts = 0
failed_inserts = 0

for z in range(0, y):
    print(f"[STEP 10] Processing record {z+1}/{y} - Dealer: {testArr[z][0] if testArr[z][0] else 'N/A'}")
    
    # Clean and prepare data for each field
    for index in range(0, 36):
        dataItem[index] = ''.join(e for e in str(testArr[z][index]).replace("'", " ") if e in okchars)
        if dataItem[index] == 'None' or dataItem[index] == 'None ':
            dataItem[index] = '0'
    
    print(f"[STEP 10] - Data cleaned for record {z+1}, attempting database insertion...")
    
    try:
        cur.execute("INSERT INTO fsp_ws_claim_detail (`DLR#W0`, NAMEA0, `CLAIM#`, STSW0, DLRCW0, MODLW0, QTY, MFAMIP, ACCT, ACCT2, `VIN#W0`, `SER#I7`, LCSTW0, SCSTW0, MCSTW0, STOTAL, STAXW0, TOTAMOUNT, MHRSW0, FDYYW0, FDMMW0, FDDDW0, PPYYW0, PPMMW0, SBYYW0, SBMMW0, SBDDW0, APYYW0, APMMW0, APDDW0, VIN_YEAR, TAKEOF_DTE, DO_DATE, DLVRY_DATE, WR_DOC_NO, `CRD#W0`) VALUES('"+dataItem[0]+"','"+dataItem[1]+"','"+dataItem[2]+"','"+dataItem[3]+"','"+dataItem[4]+"','"+dataItem[5]+"','"+dataItem[6]+"','"+dataItem[7]+"','"+dataItem[8]+"','"+dataItem[9]+"','"+dataItem[10]+"','"+dataItem[11]+"','"+dataItem[12]+"','"+dataItem[13]+"','"+dataItem[14]+"','"+dataItem[15]+"','"+dataItem[16]+"','"+dataItem[17]+"','"+dataItem[18]+"','"+dataItem[19]+"','"+dataItem[20]+"','"+dataItem[21]+"','"+dataItem[22]+"','"+dataItem[23]+"','"+dataItem[24]+"','"+dataItem[25]+"','"+dataItem[26]+"','"+dataItem[27]+"','"+dataItem[28]+"','"+dataItem[29]+"','"+dataItem[30]+"','"+dataItem[31]+"','"+dataItem[32]+"','"+dataItem[33]+"','"+dataItem[34]+"','"+dataItem[35]+"')")
        conn2.commit()
        successful_inserts += 1
        print(f"[STEP 10] ✓ Successfully inserted record {z+1}/{y} - Claim: {dataItem[2]}")
        
        # Log progress every 50 successful inserts
        if successful_inserts % 50 == 0:
            print(f"[STEP 10] Progress: {successful_inserts} records successfully inserted...")
            
    except Exception as e:
        failed_inserts += 1
        print(f"[STEP 10] ✗ Failed to insert record {z+1}/{y}: {e}")
        print(f"[STEP 10] - Problematic data: Dealer={dataItem[0]}, Claim={dataItem[2]}")
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


import time as times
import calendar
import pyodbc
import locale
import mysql.connector
from mysql.connector import errorcode

from difflib import SequenceMatcher
import sys
import smtplib
from os.path import basename
from email.mime.application import MIMEApplication
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from email.utils import COMMASPACE, formatdate
from dateutil.relativedelta import *
from dateutil.easter import *
from dateutil.rrule import *
from dateutil.parser import *
from datetime import *

mydates     =  datetime.now()
monthsx     =  mydates.strftime("%m")
yearxs      =  mydates.strftime("%Y")

datebefore  = date(int(yearxs),int(monthsx),1)+relativedelta(months=-2);
monthbef    = datebefore.strftime("%m");
yearbef     = datebefore.strftime("%Y")

#----------------------------------Get Saldo Awal Data-------------------------------------------------------------------------------

def ProcessSendMail(dealerName, regional) :
    print(f"[ProcessSendMail] Starting email process for dealer: {dealerName}, regional: {regional}")
    
    print(f"[ProcessSendMail] Step 1: Establishing database connections...")
    conn        = pyodbc.connect('DRIVER={IBM i Access ODBC Driver};SYSTEM=10.17.51.22;DATABASE=HMI17P001;UID=mdstest2;PWD=password2')
    # conn        = pyodbc.connect('DRIVER={iSeries Access ODBC Driver};SYSTEM=10.17.51.22;DATABASE=HMI17P001;UID=mdstest2;PWD=password2')
    print(f"[ProcessSendMail] - iSeries connection established")
    
    conn2       = mysql.connector.connect(host='10.17.111.18',user='mysqlwb',password='IntegrationS4h1s2025',database='his_db_final_3')
    print(f"[ProcessSendMail] - MySQL connection established")
    
    print(f"[ProcessSendMail] Step 2: Creating database cursors...")
    cursor = conn.cursor()
    curhisx = conn2.cursor()
    curhisxemail = conn2.cursor()
    print(f"[ProcessSendMail] - All cursors created successfully")

    print(f"[ProcessSendMail] Step 3: Initializing data structures...")
    y=0
    ind=0

    arrayDoc = {}
    print(f"[ProcessSendMail] Step 4: Retrieving customer codes for dealer: {dealerName}")
    curhisx.execute("SELECT Cust_Code,cust_email FROM hgs_mst_customersaldo_final where Cust_Name = '"+dealerName+"' ");
    dealerList ='';

    print(f"[ProcessSendMail] Step 5: Processing customer codes and building dealer list...")
    for rowt in curhisx:
        custCode = rowt[0]
        dealerList =dealerList+"'"+rowt[0]+"',"
        if custCode[2:-1] not in arrayDoc :
            arrayDoc[custCode[2:-1]] ={};
            arrayDoc[custCode[2:-1]][10]='No Balance';
            arrayDoc[custCode[2:-1]][50]='No Balance';
            arrayDoc[custCode[2:-1]][20]='No Balance';
            arrayDoc[custCode[2:-1]][40]='No Balance';
        ind=ind+1;
    dealerList = dealerList[:-1];
    print(f"[ProcessSendMail] - Found {ind} customer codes for dealer: {dealerName}")
    print(f"[ProcessSendMail] - Dealer list: {dealerList}")

    print(f"[ProcessSendMail] Step 6: Counting balance records from iSeries...")
    cursor.execute("SELECT count(*)as total FROM (SELECT CUSTA1  FROM  IDS2101D.ARFP31 SAWAL WHERE SAWAL.SPMMA1="+monthbef+" AND SAWAL.SPYYA1="+yearbef+" AND CUSTA1 in ("+dealerList+")  GROUP BY CUSTA1,PRDCA1 )T")

    for row in cursor:
        y=row[0]
        testArr = range(y)
        print(f"[ProcessSendMail] - Found {y} balance records to process")

    print(f"[ProcessSendMail] Step 7: Retrieving detailed balance data from iSeries...")
    cursor.execute("SELECT CUSTA1,PRDCA1,SUM(CBALA1)+SUM(ADUEA1)+SUM(OD30A1)+SUM(OD60A1)+SUM(OD90A1) FROM IDS2101D.ARFP31 SAWAL WHERE SAWAL.SPYYA1="+yearbef+" AND SAWAL.SPMMA1="+monthbef+"  AND CUSTA1 in ("+dealerList+") GROUP BY CUSTA1,PRDCA1 ")

    print(f"[ProcessSendMail] Step 8: Processing balance data and building test array...")
    x=0
    testArr = []
    for row in cursor:
        # testArr[x] = list(range(3))
        # testArr[x][0] = row[0]
        # testArr[x][1] = row[1]
        # testArr[x][2] = row[2]
        testArr.append([])
        testArr[x]=(row[0],row[1],row[2])
        x=x+1

        custCode2 = row[0]
        if row[2]!=0:
            arrayDoc[custCode2[2:-1]][int(row[1])]='Balance Exists';
            print(f"[ProcessSendMail] - Customer {custCode2[2:-1]} has balance for product {row[1]}: {row[2]}")
        else:
            print(f"[ProcessSendMail] - Customer {custCode2[2:-1]} has no balance for product {row[1]}")
    print(f"[ProcessSendMail] - Processed {x} balance records total")

    #------------------------------------------Get Saldo Awal Data------------------------------------------
    print(f"[ProcessSendMail] Step 9: Calculating date parameters for report generation...")
    dateafter = date(int(yearxs),int(monthsx),1)+relativedelta(months=-1);
    months = dateafter.strftime("%B")
    yearx = dateafter.strftime("%Y")
    mth = dateafter.strftime("%m")
    print(f"[ProcessSendMail] - Report period: {months} {yearx} (Month: {mth})")

    print(f"[ProcessSendMail] Step 10: Starting main processing loop for {x} customer records...")
    for z in range(0,x):
        print(f"[ProcessSendMail] - Processing record {z+1}/{x}...")
        saldoawal = testArr[z][2]
        customer_code = testArr[z][0]
        prdcate = testArr[z][1]
        print(f"[ProcessSendMail] - Customer: {customer_code}, Product Category: {prdcate}, Opening Balance: {saldoawal}")

        print(f"[ProcessSendMail] - Determining product description for category: {prdcate}")
        if (prdcate=='10') :
            prddesc = 'Unit Sales';
        elif (prdcate=='20') :
            prddesc = 'Service';
        elif (prdcate=='40') :
            prddesc = 'Sparepart';
        else:
            prddesc = 'Others';
        print(f"[ProcessSendMail] - Product description: {prddesc}")

        print(f"[ProcessSendMail] - Retrieving customer email information for: {customer_code}")
        curhisxemail.execute("SELECT cust_email, Cust_Code, Cust_Name FROM hgs_mst_customersaldo_final where Cust_Code='"+customer_code+"'");
        emailcustomer ='';
        codeCustomer ='';
        nameCustomer ='';

        for rowcustemail in curhisxemail:
            emailcustomer = rowcustemail[0]
            codeCustomer  = rowcustemail[1]
            nameCustomer  = rowcustemail[2]
        print(f"[ProcessSendMail] - Customer details: {nameCustomer} ({codeCustomer}), Email: {emailcustomer}")

        print(f"[ProcessSendMail] - Retrieving transaction details from iSeries for period {mth}/{yearx}...")
        cursor.execute("SELECT TRDDA2,TRMMA2,TRYYA2,REFPA2,REF#A2,TTDSA2,DEBTA2,CREDA2,DBCRA2, DOCPA2, DOC#A2 FROM IDS2101D.ARFP02 SADTL WHERE SADTL.PPYYA2="+yearx+" AND SADTL.PPMMA2="+mth+" AND SADTL.CUSTA2='"+customer_code+"' AND SADTL.PRDCA2='"+prdcate+"' AND DESCA2 IN ('DISCOUNT','INVOICE','JOURNAL','CREDIT','RECEIPT')  ")

        print(f"[ProcessSendMail] - Processing transaction details...")
        x=0
        debitamount=0
        creditamount=0
        setValInvoice=""
        transaction_count = 0
        for rowdetail in cursor:
            transaction_count += 1
            # 0 (TRDDA2)  =
            # 1 (TRMMA2)  =
            # 2 (TRMMA2)  = Year
            # 3 (REFPA2)  = Initial Transaction
            # 4 (REF#A2)  = No Invoice
            # 5 (TTDSA2)  =
            # 6 (DEBTA2)  =
            # 7 (CREDA2)  =
            # 8 (DBCRA2)  =
            # 9 (DOCPA2)  =
            # 10 (DOC#A2) =

            if(rowdetail[8]=='C'):
                creditval   = rowdetail[7];
                debitvale   = 0;
                creditamount=creditamount+rowdetail[7]
            else:
                creditval   = 0;
                debitvale   = rowdetail[6];
                debitamount=debitamount+rowdetail[6]

            if(rowdetail[3] == "J"):
                setValInvoice = str(".") + str(rowdetail[9]) + str(rowdetail[10])
            else :
                setValInvoice = ""

        saldoakhir = saldoawal+debitamount-creditamount
        filename = customer_code+"_Customer_Balance_"+months+"_"+yearx+"_"+prdcate+".pdf"

        def computeMD5hash(string):
            import hashlib
            from hashlib import md5
            m = hashlib.md5()
            m.update(string.encode('utf-8'))
            md5string=m.digest()
            return md5string

        md5save = computeMD5hash(customer_code+yearx+str(mth))

        print(f"[ProcessSendMail] - Final balance calculated: {saldoakhir}")
        
        years =  int(yearx)
        args = [customer_code,mth,years,saldoakhir,filename,md5save,prdcate]
        # curhis.callproc('insRCDSALDOCUSTOMER_aftercorrupt', args)

        print(f"[ProcessSendMail] - Committing database transaction...")
        conn2.commit()

        print(f"[ProcessSendMail] - Generating PDF file: {filename}")
        #pdf.output(filename,'F')
        fname2 = 'C:\\www\\htdocs\\his_sksaldo\\pymail\\saldocustomer\\'+filename
        # pdf.output(fname2,'F')
        print(f"[ProcessSendMail] - PDF saved to: {fname2}")

        # getLastIDrow = curhis.callproc('getIdSaldoMax_aftercorrupt')
        # for resultIDlas in curhis.stored_results():
        #     resultIDlas2=resultIDlas.fetchall()

        # for resultIDlas3 in resultIDlas2:
        #     LastID = resultIDlas3[0]

        # md5id = computeMD5hash(str(LastID))
        
    print(f"[ProcessSendMail] Step 12: Cleaning up database connections...")
    cursor.close()
    conn.close()
    print(f"[ProcessSendMail] - All database connections closed")
    print(f"[ProcessSendMail] Email process completed for dealer: {dealerName}")

print('Start Process The Data')

try:
    conn22 = mysql.connector.connect(host='10.17.111.18',user='mysqlwb',password='IntegrationS4h1s2025',database='his_db_final_3')
    print("Database connection successful!")
    
    # Execute the main query
    curhisxx = conn22.cursor()
    curhisxx.execute("SELECT Cust_Name,cust_email,cust_regional FROM hgs_mst_customersaldo_final group by Cust_Name order by Cust_Name ASC LIMIT 1;")
    print("Main query executed successfully!")
    for dealer in curhisxx:
        print(f"Processing dealer: {dealer[0]}")
        
        # Only proceed with email sending if log insertion was successful
        ProcessSendMail(dealer[0], dealer[2])
except Exception as err:
    print(str(err))

print('Stop Process Get Data')

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

def flagRegional(regional):
    picRegional   = ""
    picEmail      = ""
    if regional == 1 :
        picRegional    = str('Lely Susanti')
        picEmail       = str('farah.parahita@hino.co.id')
    elif regional == 2 :
        picRegional    = str('Mirfat')
        picEmail       = str('farah.parahita@hino.co.id')
    else :
        picRegional    = str('Eny Kirana Damanik')
        picEmail       = str('farah.parahita@hino.co.id')

    return [picRegional, picEmail]

# insert log send dealer
def insertLogSend(dealerName) :
    conLog      = mysql.connector.connect(host='34.128.71.69',user='root',password='EuXbrmzvVBjDSB99',database='his_db_final_3_dev')
    curhisLog   = conLog.cursor()

    dateafter   = date(int(yearxs),int(monthsx),1)+relativedelta(months=-1);
    mth         =  dateafter.strftime("%m")
    yth         =  dateafter.strftime("%Y")

    try:
        # Check if record already exists to prevent duplicates
        check_query = "SELECT COUNT(*) FROM hgs_mst_customersaldo_final_flag WHERE cust_name = %s AND month = %s AND year = %s"
        curhisLog.execute(check_query, (str(dealerName), str(mth), str(yth)))
        existing_count = curhisLog.fetchone()[0]
        
        if existing_count > 0:
            print(f"Log entry already exists for dealer: {dealerName} (Month: {mth}, Year: {yth}) - Skipping insert")
            curhisLog.close()
            conLog.close()
            return True  # Return success even if record exists (no need to insert)
        else:
            # Insert new record only if it doesn't exist
            curhisLog.execute("INSERT INTO hgs_mst_customersaldo_final_flag values('','','"+str(dealerName)+"', '', '"+str(mth)+"', '"+str(yth)+"','0', '"+str(mydates)+"', '' )")
            conLog.commit()  # Commit the transaction to save changes
            print(f"Successfully inserted log for dealer: {dealerName}")
            curhisLog.close()
            conLog.close()  # Close the connection properly
            return True  # Return success after successful insert
            
    except Exception as e:
        print(f"Error processing log for dealer {dealerName}:", e)
        conLog.rollback()  # Rollback on error
        curhisLog.close()
        conLog.close()
        return False  # Return failure on error

# update log send dealer
def updateSendMail(dealerName) :
    conUpdate = mysql.connector.connect(host='34.128.71.69',user='root',password='EuXbrmzvVBjDSB99',database='his_db_final_3_dev')
    curhisUpdate = conUpdate.cursor()

    dateafter = date(int(yearxs),int(monthsx),1)+relativedelta(months=-1);
    mth = dateafter.strftime("%m")

    curhisUpdate.execute("update hgs_mst_customersaldo_final_flag set Status = 1 where Cust_Name = '"+dealerName+"' and Month = '"+mth+"' and Year = '"+yearxs+"'");
    curhisUpdate.close()

# check status 0 send mail (0 = belum terkirim)
def checkFlagSendMail() :
    conFlag     = mysql.connector.connect(host='34.128.71.69',user='root',password='EuXbrmzvVBjDSB99',database='his_db_final_3_dev')
    curhisGet   = conFlag.cursor()
    curhisDel   = conFlag.cursor()

    curhisGet.execute("SELECT Cust_Code, Cust_Name, cust_email, cust_regional FROM hgs_mst_customersaldo_final_flag where Month = '"+monthsx+"' and Year ='"+yearxs+"' and Status = 0;");
    for rowt in curhisGet:
        curhisDel.execute("DELETE FROM rcd_saldo_transaction_final WHERE Month = '"+monthsx * 1+"' and Year ='"+yearxs+"' and customer_id = '"+rowt[1]+"'");
        ProcessSendMail(rowt[1], rowt[3])
    curhisGet.close()

def ProcessSendMail(dealerName, regional) :
    print(f"[ProcessSendMail] Starting email process for dealer: {dealerName}, regional: {regional}")
    
    print(f"[ProcessSendMail] Step 1: Establishing database connections...")
    # conn        = pyodbc.connect('DRIVER={IBM i Access ODBC Driver};SYSTEM=10.17.51.22;DATABASE=HMI17U001;UID=mdstest2;PWD=password2')
    conn        = pyodbc.connect('DRIVER={iSeries Access ODBC Driver};SYSTEM=10.17.51.22;DATABASE=HMI17U001;UID=mdstest2;PWD=password2')
    print(f"[ProcessSendMail] - IBM iSeries connection established")
    
    conn2       = mysql.connector.connect(host='34.128.71.69',user='root',password='EuXbrmzvVBjDSB99',database='his_db_final_3_dev')
    print(f"[ProcessSendMail] - MySQL connection established")
    
    print(f"[ProcessSendMail] Step 2: Creating database cursors...")
    cursor = conn.cursor()
    curhis = conn2.cursor()
    curhisx = conn2.cursor()
    curhisxemail = conn2.cursor()
    curhisxemail2 = conn2.cursor()
    curhisxemail3 = conn2.cursor()
    print(f"[ProcessSendMail] - All cursors created successfully")

    print(f"[ProcessSendMail] Step 3: Getting regional contact information...")
    picRegional = flagRegional(regional)[0]
    picEmail    = flagRegional(regional)[1]
    print(f"[ProcessSendMail] - Regional PIC: {picRegional}, Email: {picEmail}")

    print(f"[ProcessSendMail] Step 4: Initializing data structures...")
    y=0
    ind=0

    arrayDoc = {}
    print(f"[ProcessSendMail] Step 5: Retrieving customer codes for dealer: {dealerName}")
    curhisx.execute("SELECT Cust_Code,cust_email FROM hgs_mst_customersaldo_final where Cust_Name = '"+dealerName+"' ");
    dealerList ='';

    print(f"[ProcessSendMail] Step 6: Processing customer codes and building dealer list...")
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

    print(f"[ProcessSendMail] Step 7: Counting balance records from iSeries...")
    cursor.execute("SELECT count(*)as total FROM (SELECT CUSTA1  FROM  IDS2101D.ARFP31 SAWAL WHERE SAWAL.SPMMA1="+monthbef+" AND SAWAL.SPYYA1="+yearbef+" AND CUSTA1 in ("+dealerList+")  GROUP BY CUSTA1,PRDCA1 )T")

    for row in cursor:
        y=row[0]
        testArr = range(y)
        print(f"[ProcessSendMail] - Found {y} balance records to process")

    print(f"[ProcessSendMail] Step 8: Retrieving detailed balance data from iSeries...")
    cursor.execute("SELECT CUSTA1,PRDCA1,SUM(CBALA1)+SUM(ADUEA1)+SUM(OD30A1)+SUM(OD60A1)+SUM(OD90A1) FROM IDS2101D.ARFP31 SAWAL WHERE SAWAL.SPYYA1="+yearbef+" AND SAWAL.SPMMA1="+monthbef+"  AND CUSTA1 in ("+dealerList+") GROUP BY CUSTA1,PRDCA1 ")

    print(f"[ProcessSendMail] Step 9: Processing balance data and building test array...")
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
            '''arrayDoc[rowt[0][2:-1]] ={};
            arrayDoc[rowt[0][2:-1]][10]='No Balance';
            arrayDoc[rowt[0][2:-1]][50]='No Balance';
            arrayDoc[rowt[0][2:-1]][20]='No Balance';
            arrayDoc[rowt[0][2:-1]][40]='No Balance';
            print("ADDNEW");'''
    print(f"[ProcessSendMail] - Processed {x} balance records total")

    #------------------------------------------Get Saldo Awal Data------------------------------------------
    print(f"[ProcessSendMail] Step 10: Calculating date parameters for report generation...")
    dateafter = date(int(yearxs),int(monthsx),1)+relativedelta(months=-1);
    months = dateafter.strftime("%B")
    yearx = dateafter.strftime("%Y")
    mth = dateafter.strftime("%m")
    print(f"[ProcessSendMail] - Report period: {months} {yearx} (Month: {mth})")

    print(f"[ProcessSendMail] Step 11: Starting main processing loop for {x} customer records...")
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

        html = """
        <p align="right">
        <B><font size="15">PT HINO MOTOR SALES INDONESIA</font></B>
        <font size="12">Jl. M.T. Haryono Kav.9 Bidara Cina Jakarta-13330 Indonesia</font>
        </p>
        <hr/>
        <p align="center">
        <B><U><font size="13" face="Helvetica">Customer Balance """+months+" "+yearx+"""( """+prddesc+""" - """+customer_code+""" )</font></U></B>
        </p>
        <br/>
        <font size="10" face="Times"><br/>Dear our valued customer, <br/>Below we list the details of your transaction for current month. <br/>In case any unmatch transaction please confirm to us through this following e-mail: <u>"""+picEmail+"""</u> or <u>bayu.adhi@hino.co.id</u>.</font>
        <table border="0" align="center" width="95%">
        <thead><tr><th width="20%" align="center">Reference</th><th width="20%" align="center">Date</th><th width="20%" align="center">Notes</th><th width="20%" align="center">DEBIT</th><th width="20%" align="center">CREDIT</th></tr></thead>
        <tbody>"""

        print(f"[ProcessSendMail] - Generating HTML report template for {nameCustomer}...")
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

            html = html + """ <tr><td width="20%" align="center"><font face="Courier" size="8"> """+ str(rowdetail[3])+str(rowdetail[4]) + str(setValInvoice)+"""</font></td><td width="20%" align="center"><font face="Courier" size="8">"""+str(rowdetail[0])+"/"+str(rowdetail[1])+"/"+str(rowdetail[2])+"""</font></td><td width="20%" align="center"><font face="Courier" size="8">"""+str(rowdetail[5])+"""</font></td><td width="20%" align="right"><font face="Courier" size="8">"""+'{0:,}'.format(debitvale)+"""</font></td><td width="20%" align="right"><font face="Courier" size="8">"""+'{0:,}'.format(creditval)+"""</font></td></tr> """

        html = html + """
        </tbody>
        </table>"""

        saldoakhir = saldoawal+debitamount-creditamount
        html = html+""" <table border="0" align="center" width="95%">
        <tr><td width="20%" align="left"><font face="Courier" size="9"><b>INITIAL BALANCE</b></td><td width="80%" align="right"><font face="Courier" size="9"><b>"""+'{0:,}'.format(saldoawal)+""" </b></td></tr><tr><td width="20%" align="left"><font face="Courier" size="9"><b>TOTAL DEBIT</b></td><td width="80%" align="right"><font face="Courier" size="9"><b>"""+'{0:,}'.format(debitamount)+""" </b></td></tr><tr><td width="20%" align="left"><font face="Courier" size="9"><b>TOTAL CREDIT</b></td><td width="80%" align="right"><font face="Courier" size="9"><b>"""+'{0:,}'.format(creditamount)+""" </b></td></tr><tr><td width="20%" align="left"><font face="Courier" size="9"><b>LAST BALANCE</b></td><td width="80%" align="right"><font face="Courier" size="9"><b>"""+'{0:,}'.format(saldoakhir)+""" </b></td></tr></table>"""

        html = html+"""
        <p align="left">
        <font size="10" face="times">Best Regards,</font><br/><br/>
        <B><font size="10" face="times">Credit Control Division</font></B>
        </p>"""

        from fpdf import FPDF, HTMLMixin

        class MyFPDF(FPDF, HTMLMixin):
            pass

        pdf=MyFPDF()
        #First page
        pdf.add_page()
        #pdf.image('hino-2.jpg',8,15,75,10)

        pdf.write_html(html)
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
        curhis.callproc('insRCDSALDOCUSTOMER_aftercorrupt', args)

        print(f"[ProcessSendMail] - Committing database transaction...")
        conn2.commit()

        print(f"[ProcessSendMail] - Generating PDF file: {filename}")
        #pdf.output(filename,'F')
        # fname2 = '/home/ms_sahrul_mustakim/sksaldo/'+filename
        fname2 = 'C:\\www\\htdocs\\his_sksaldo_dev\\pymail\\saldocustomer\\'+filename
        pdf.output(fname2,'F')
        print(f"[ProcessSendMail] - PDF saved to: {fname2}")

        # getLastIDrow = curhis.callproc('getIdSaldoMax_aftercorrupt')
        # for resultIDlas in curhis.stored_results():
        #     resultIDlas2=resultIDlas.fetchall()

        # for resultIDlas3 in resultIDlas2:
        #     LastID = resultIDlas3[0]

        # md5id = computeMD5hash(str(LastID))

        print(f"[ProcessSendMail] - Preparing email for customer: {nameCustomer}")
        def send_mail(send_from, send_to, subject, text, files, server):
            print(f"[ProcessSendMail] - Creating email message structure...")
            msg = MIMEMultipart(
                From=send_from,
                To=COMMASPACE.join(send_to),
                Date=formatdate(localtime=True),
                Subject=subject
            )

            email_subject = 'Customer Balance  - '+ months+" "+yearx +"( "+prddesc+" )"
            msg['Subject'] = email_subject
            msg['From'] = send_from
            msg['To'] = COMMASPACE.join(send_to)
            print(f"[ProcessSendMail] - Email subject: {email_subject}")
            print(f"[ProcessSendMail] - Email recipients: {COMMASPACE.join(send_to)}")

            html = """
            <html>
            <head>
            <style type="text/css" media="screen">
            table{
                background-color: #f3eded;
                empty-cells:hide;
            }
            .classname {
                display: 'block';
                position: 'relative';
                background-color: '#8ff0f3';
                width: 200px;
                height: 50px;
                text-align: center;
                text-decoration: none;
                color: white;
                font-size: 12px;
                margin-top: 49px;
                border-radius: 4px;
                margin-left: 178px;
            }
            .classname2 {
                display: 'block';
                position: 'relative';
                background-color: '#2B7ABD';
                width: 200px;
                height: 50px;
                text-align: center;
                text-decoration: none;
                color: white;
                font-size: 12px;
                margin-top: 49px;
                border-radius: 4px;
                margin-left: 178px;
            }
            </style>
            </head>
            <body >
            <table width="100%"><tr><td>
            <font face="tahoma" size="2">
                Dear Sir/Madam.<br><br><br>
                Here with we attach the <b>Customer Balance Report</b> .<br><br>

                Period  :  """+months+""" """+ str(yearx)+ """ <br>

                Please sent your confirmation due to this balance statement by click on link below :
                <br/>
                <br/>
                <a href='https://global.hino.co.id/his/'>Click to Give Confirmation</a>
                <br/>
                <br/>
                If any additional information please dont be hesitate to inform us to FAD team ("""+picRegional+""": """+picEmail+""")<br><br>
                <b>Finance Accounting Division</b><br></font>
                <i><b> <font face="monotype" size="2" color="#0073e5">*This email automatically generated by Hino Integrated System</font></i></b><br>

                </td></tr></table>
                <frameset cols="85%, 15%">
                <frame src="https://global.hino.co.id/his/" name="frame1">

                </frameset>
            </body>
            </html>
            """
            print(f"[ProcessSendMail] - Attaching HTML content to email...")
            msg.attach(MIMEText(html, 'html'))

            print(f"[ProcessSendMail] - Attaching PDF files to email...")
            for f in files or []:
                print(f"[ProcessSendMail] - Attaching file: {basename(f)}")
                with open(f, "rb") as fil:
                    msg.attach(MIMEApplication(
                        fil.read(),
                        Content_Disposition='attachment; filename="%s"' % basename(f),
                        Name=basename(f)
                    ))

            print(f"[ProcessSendMail] - Connecting to SMTP server (AWS SES)...")
            smtp = smtplib.SMTP("10.17.51.51", 587)
            # smtp.starttls()
            smtp.sendmail("noreply@halohino.co.id", send_to, msg.as_string())
            smtp.quit()
            smtp.close()
            print(f"[ProcessSendMail] - Email sent successfully!")

            print(f"[ProcessSendMail] - Updating send mail status for dealer: {dealerName}")
            updateSendMail(dealerName)

        print(f"[ProcessSendMail] - Attempting to send email for customer: {nameCustomer}")
        try:
            # send_mail('noreply@halohino.co.id', [emailcustomer, picEmail, 'farah.parahita@hino.co.id', 'amalia.oktoviani@hino.co.id'], 'Test SMTP Summary', '<b>aaaaaaaaaaaaaaa</b>', files=['/home/ms_sahrul_mustakim/sksaldo/'+filename],server='')
            send_mail('noreply@halohino.co.id', [emailcustomer, picEmail, 'farah.parahita@hino.co.id', 'amalia.oktoviani@hino.co.id'], 'Test SMTP Summary', '<b>aaaaaaaaaaaaaaa</b>', files=['C:\\www\\htdocs\\his_sksaldo_dev\\pymail\\saldocustomer\\'+filename],server='')
            print(f"[ProcessSendMail] - Email process completed successfully for customer: {nameCustomer}")
        except Exception as err:
            print(f"[ProcessSendMail] - ERROR: Email sending failed for customer {nameCustomer}: {str(err)}")
            print(f"[ProcessSendMail] - Logging error to database...")
            dateafter = date(int(yearxs), int(monthsx), 1) + relativedelta(months=-1)
            mth = dateafter.strftime("%m")

            curhisxemail3.execute(
                "update hgs_mst_customersaldo_final_flag set messages = '"+str(err)+"' where Cust_Name = '"
                + dealerName
                + "' and Month = '"
                + mth
                + "' and Year = '"
                + yearxs
                + "'"
            )
            curhisxemail3.close()
            print(f"[ProcessSendMail] - Error logged to database for dealer: {dealerName}")

    print(f"[ProcessSendMail] Step 12: Cleaning up database connections...")
    cursor.close()
    conn.close()
    print(f"[ProcessSendMail] - All database connections closed")
    print(f"[ProcessSendMail] Email process completed for dealer: {dealerName}")

print('Start Process The Data')

try:
    conn22 = mysql.connector.connect(host='34.128.71.69',user='root',password='EuXbrmzvVBjDSB99',database='his_db_final_3_dev')
    print("Database connection successful!")
    
    # Execute the main query
    curhisxx = conn22.cursor()
    curhisxx.execute("SELECT Cust_Name,cust_email,cust_regional FROM hgs_mst_customersaldo_final group by Cust_Name order by Cust_Name ASC;")
    print("Main query executed successfully!")
    for dealer in curhisxx:
        print(f"Processing dealer: {dealer[0]}")
        
        # First, insert log entry and wait for completion
        log_success = insertLogSend(dealer[0])
        
        if log_success:
            print(f"Log insertion completed for {dealer[0]}, proceeding with email process...")
            # Only proceed with email sending if log insertion was successful
            ProcessSendMail(dealer[0], dealer[2])
        else:
            print(f"Log insertion failed for {dealer[0]}, skipping email process")
except Exception as err:
    print(str(err))

print('Stop Process Get Data')
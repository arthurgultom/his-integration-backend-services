import time
import warnings
import calendar
import pyodbc
import locale
import html
import numpy as np
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

mydates 	=   datetime.now()
monthsx	    =	mydates.strftime("%m")
yearxs      =   mydates.strftime("%Y")

datebefore = date(int(yearxs),int(monthsx),1)+relativedelta(months=-2);
monthbef  = datebefore.strftime("%m")
yearbef  =  datebefore.strftime("%Y")

warnings.filterwarnings("ignore")
def fxn():
    warnings.warn("deprecated", DeprecationWarning)

with warnings.catch_warnings():
    warnings.simplefilter("ignore")
    fxn()
	

getDealer 	=	sys.argv
countArr  	=	len(sys.argv)

arr = np.array(getDealer)
setData = arr[1:countArr]
setName = ""

if(countArr == 7) :
	setName = setData[0] + ' ' + setData[1] +' '+setData[2] +' '+setData[3] +' '+setData[4] +' '+setData[5]

if(countArr == 6) :
	setName = setData[0] + ' ' + setData[1] +' '+setData[2] +' '+setData[3] +' '+setData[4]
	
elif(countArr == 5) :
	setName = setData[0] + ' ' + setData[1] +' '+setData[2] +' '+setData[3]
	
elif(countArr == 4) :
	setName = setData[0] + ' ' + setData[1] +' '+setData[2]
	
elif(countArr == 3) :
	setName = setData[0] + ' ' + setData[1]
	
# print(setName)
# exit(1)

conn22      = mysql.connector.connect(host='10.152.9.94',user='root',password='EuXbrmzvVBjDSB99',database='his_db_final_3_dev')
curhisxx    = conn22.cursor()

def flagRegional(regional):
    
    picRegional   = ""
    picEmail      = ""
    
    if regional == 1 :
         
        picRegional    = str('Lely Susanti')    
        picEmail       = str('farah.parahita@hino.co.id')
           
    elif regional == 2 :        
        
        # picRegional    = str('Dian Padma Hapsari')    
        # picEmail       = str('dian.hapsari@hino.co.id')
        # picRegional    = str('Anabella Selvira')    
        # picEmail       = str('anabella.selvira@hino.co.id')
        picRegional    = str('Mirfat')    
        picEmail       = str('farah.parahita@hino.co.id')
        
    else :
        
        # picRegional    = str('Irma Novia')    
        # picEmail       = str('irma.novia@hino.co.id')
        # picRegional    = str('Anabella Selvira')    
        # picEmail       = str('anabella.selvira@hino.co.id')
        picRegional    = str('Eny Kirana Damanik')    
        picEmail       = str('farah.parahita@hino.co.id')
        
    return [picRegional, picEmail]
    
# insert log send dealer
def insertLogSend(dealerName) :
    
    conLog      = mysql.connector.connect(host='10.152.9.94',user='root',password='EuXbrmzvVBjDSB99',database='his_db_final_3_dev')
    curhisLog   = conLog.cursor()
    
    dateafter   = date(int(yearxs),int(monthsx),1)+relativedelta(months=-1);
    mth         = dateafter.strftime("%m")
    
    curhisLog.execute(""" 
        INSERT INTO hgs_mst_customersaldo_final_flag
        values('','','"""+str(dealerName)+"""', '', '"""+str(mth)+"""', '"""+str(yearxs)+"""','0', '"""+str(mydates)+"""', '' )
    """)
    curhisLog.close()
    

# update log send dealer
def updateSendMail(dealerName) :
    conUpdate       = mysql.connector.connect(host='10.152.9.94',user='root',password='EuXbrmzvVBjDSB99',database='his_db_final_3_dev')
    curhisUpdate    = conUpdate.cursor()
    
    dateafter   = date(int(yearxs),int(monthsx),1)+relativedelta(months=-1);
    mth         = dateafter.strftime("%m")
    
    curhisUpdate.execute("update hgs_mst_customersaldo_final_flag set Status = 1 where Cust_Name = '"+dealerName+"' and Month = '"+mth+"' and Year = '"+yearxs+"'");
    curhisUpdate.close()
    
# check status 0 send mail (0 = belum terkirim)
def checkFlagSendMail() :
    
    conFlag     = mysql.connector.connect(host='10.152.9.94',user='root',password='EuXbrmzvVBjDSB99',database='his_db_final_3_dev')
    curhisGet   = conFlag.cursor()
    curhisDel   = conFlag.cursor()
    
    curhisGet.execute("SELECT Cust_Code, Cust_Name, cust_email, cust_regional FROM hgs_mst_customersaldo_final_flag where Month = '"+monthsx+"' and Year ='"+yearxs+"' and Status = 0;");
    for rowt in curhisGet:
    
        # curhisDel.execute("DELETE FROM rcd_saldo_transaction_final WHERE Month = '"+monthsx * 1+"' and Year ='"+yearxs+"' and customer_id = '"+rowt[1]+"'");
        # print('check flag ' +rowt[1])
        
        ProsesSendMail(rowt[1], rowt[3])
        
    curhisGet.close()
    
def ProsesSendMail(dealerName, regional) :
    
    conn        = pyodbc.connect('DRIVER={iSeries Access ODBC Driver};SYSTEM=10.17.51.22;DATABASE=HMI17U001;UID=mdstest2;PWD=mdstest2')
    conn2       = mysql.connector.connect(host='10.152.9.94',user='root',password='EuXbrmzvVBjDSB99',database='his_db_final_3_dev')
    
    cursor = conn.cursor()
    curhis = conn2.cursor()
    curhisx = conn2.cursor()
    curhisxemail = conn2.cursor()
    curhisxemail2 = conn2.cursor()
    curhisxemail3 = conn2.cursor()
    
    picRegional = flagRegional(regional)[0]
    picEmail    = flagRegional(regional)[1]
        
    y=0
    ind =0

    arrayDoc = {}


    curhisx.execute("SELECT Cust_Code,cust_email FROM hgs_mst_customersaldo_final where Cust_Name = '"+dealerName+"' ");

    dealerList ='';

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

    cursor.execute("SELECT count(*)as total FROM (SELECT CUSTA1  FROM  IDS2101D.ARFP31 SAWAL WHERE SAWAL.SPMMA1="+monthbef+" AND SAWAL.SPYYA1="+yearbef+" AND CUSTA1 in ("+dealerList+")  GROUP BY CUSTA1,PRDCA1 )T")



    for row in cursor:
        y=row[0]    
        testArr = range(y)


    cursor.execute("SELECT CUSTA1,PRDCA1,SUM(CBALA1)+SUM(ADUEA1)+SUM(OD30A1)+SUM(OD60A1)+SUM(OD90A1) FROM IDS2101D.ARFP31 SAWAL WHERE SAWAL.SPYYA1="+yearbef+" AND SAWAL.SPMMA1="+monthbef+"  AND CUSTA1 in ("+dealerList+") GROUP BY CUSTA1,PRDCA1 ")


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
                
            
        else:       
            '''arrayDoc[rowt[0][2:-1]] ={};
            arrayDoc[rowt[0][2:-1]][10]='No Balance';
            arrayDoc[rowt[0][2:-1]][50]='No Balance';
            arrayDoc[rowt[0][2:-1]][20]='No Balance';
            arrayDoc[rowt[0][2:-1]][40]='No Balance';
            print "ADDNEW";'''
        



    #------------------------------------------Get Saldo Awal Data------------------------------------------

    mydate  =   datetime.now()
    dateafter = date(int(yearxs),int(monthsx),1)+relativedelta(months=-1);
    months  =   dateafter.strftime("%B")
    yearx  =  dateafter.strftime("%Y")
    mth  =  dateafter.strftime("%m")

    for z in range(0,x):

        saldoawal       = testArr[z][2]
        customer_code   = testArr[z][0]
        prdcate     = testArr[z][1]
        
        if (prdcate=='10') :
            prddesc = 'Unit Sales';
        elif (prdcate=='20') :
            prddesc = 'Service';
        elif (prdcate=='40') :
            prddesc = 'Sparepart';
        else:
            prddesc = 'Others';
        
            
        curhisxemail.execute("SELECT cust_email, Cust_Code, Cust_Name FROM hgs_mst_customersaldo_final where Cust_Code='"+customer_code+"' ");
        emailcustomer ='';
        codeCustomer ='';
        nameCustomer ='';

        for rowcustemail in curhisxemail:
            
            emailcustomer = rowcustemail[0]
            codeCustomer  = rowcustemail[1]
            nameCustomer  = rowcustemail[2]
            
        
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
        <tbody>
        """

        cursor.execute("SELECT TRDDA2,TRMMA2,TRYYA2,REFPA2,REF#A2,TTDSA2,DEBTA2,CREDA2,DBCRA2, DOCPA2, DOC#A2  FROM IDS2101D.ARFP02 SADTL WHERE SADTL.PPYYA2="+yearx+" AND SADTL.PPMMA2="+mth+" AND SADTL.CUSTA2='"+customer_code+"' AND SADTL.PRDCA2='"+prdcate+"' AND DESCA2 IN ('DISCOUNT','INVOICE','JOURNAL','CREDIT','RECEIPT')  ")
        
        x=0
        debitamount=0
        creditamount=0
        setValInvoice=""
        for rowdetail in cursor:
        #print row;

            if(rowdetail[8]=='C'):
                creditval   = rowdetail[7];
                debitvale   = 0;
                creditamount=creditamount+rowdetail[7]
            else:
                creditval   = 0; 
                debitvale   = rowdetail[6];
                debitamount=debitamount+rowdetail[6]
                
            if(rowdetail[3] == "J"):
                setValInvoice = str(""".""") + str(rowdetail[9]) + str(rowdetail[10])
            else :
                setValInvoice = ""
                
            html =html + """ <tr><td width="20%" align="center"><font face="Courier" size="8"> """+ str(rowdetail[3])+str(rowdetail[4]) + str(setValInvoice)+"""</font></td><td width="20%" align="center"><font face="Courier" size="8">"""+str(rowdetail[0])+"/"+str(rowdetail[1])+"/"+str(rowdetail[2])+"""</font></td><td width="20%" align="center"><font face="Courier" size="8">"""+str(rowdetail[5])+"""</font></td><td width="20%" align="right"><font face="Courier" size="8">"""+'{0:,}'.format(debitvale)+"""</font></td><td width="20%" align="right"><font face="Courier" size="8">"""+'{0:,}'.format(creditval)+"""</font></td></tr> """

            
            

        html =html + """ 
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
        #pdf.image('hino-2.jpg',8,15,75,10);
        
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
        
        saldoakhir = saldoakhir;
        years =  int(yearx);
        args = [customer_code,mth,years,saldoakhir,filename,'ss',prdcate]   
        #print args;
        result_args = curhis.callproc('insRCDSALDOCUSTOMER_aftercorrupt', args);

        conn2.commit()
        
        #pdf.output(filename,'F')
        fname2 =  'C:\\www\\htdocs\\his_sksaldo_dev\\pymail\\saldocustomer\\'+filename
        
        pdf.output(fname2,'F')

        #pdf.save(fname2)
        
        
        getLastIDrow = curhis.callproc('getIdSaldoMax_aftercorrupt')

        for resultIDlas in curhis.stored_results():
            resultIDlas2=resultIDlas.fetchall()
        
        for resultIDlas3 in resultIDlas2:
            LastID = resultIDlas3[0]
            
        
        md5id = computeMD5hash(str(LastID))
        
        
        def send_mail(send_from, send_to, subject, text, files,server):

            msg = MIMEMultipart(
                From=send_from,
                To=COMMASPACE.join(send_to),
                Date=formatdate(localtime=True),
                Subject=subject
            )
            
            
            msg['Subject'] = 'Customer Balance  - '+ months+" "+yearx +"( "+prddesc+" )"
            msg['From'] = send_from
            msg['To'] = COMMASPACE.join(send_to)
            
            html = """\
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
                    <a href='https://global.hino.co.id/his/'  >Click to Give Confirmation</a>
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
            msg.attach(MIMEText(html, 'html'))
            
            for f in files or []:
            
                with open(f, "rb") as fil:
                    msg.attach(MIMEApplication(
                        fil.read(),
                        Content_Disposition='attachment; filename="%s"' % basename(f),
                        Name=basename(f)
                    ))

            #smtp = smtplib.SMTP("10.17.51.51", 587)
            ## smtp.starttls()
            #smtp.sendmail("noreply@halohino.co.id", send_to, msg.as_string())
            #smtp.quit()
            #smtp.close()
            
            # smtp = smtplib.SMTP("email-smtp.ap-southeast-1.amazonaws.com", 587)
            # smtp.starttls()
            # smtp.login('AKIAQEUMQYRSYZUJWAD2','BCO7lpocXEkYLqA4aq1WoDmYcSKrvAJ/1e0Ob2MVchhZ')
            # smtp.sendmail('noreply@halohino.co.id', send_to, msg.as_string())
            # smtp.close()
            
            updateSendMail(dealerName)
        
        try:    
            send_mail('noreply@halohino.co.id', [emailcustomer, picEmail, 'amalia.oktoviani@hino.co.id', 'farah.parahita@hino.co.id'], 'Test SMTP Summary', '<b>aaaaaaaaaaaaaaa</b>', files=['C:\\www\\htdocs\\his_sksaldo_dev\\pymail\\saldocustomer\\'+filename],server='')
        except Exception as err:
            print(str(err))
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
        
    
    cursor.close()
    conn.close()

    for key in arrayDoc.keys():
        

        curhisxemail2.execute("SELECT cust_email FROM hgs_mst_customersaldo_final where mid(Cust_Code,3,3)='"+key+"' group by cust_email ");
        emailcustomer2 ='';
        for rowcustemail2 in curhisxemail2:
            emailcustomer2 =rowcustemail2[0];
            
            
        curhisxemail2.execute("SELECT cust_name FROM hgs_mst_customersaldo_final where mid(Cust_Code,3,3)='"+key+"' group by cust_name ");
        custname2 ='';
        for rowcustemail3 in curhisxemail2:
            custname2 =rowcustemail3[0];
            
        
        def send_mail2(send_from, send_to, subject, text, files=None,server="outlook.office365.com"):
            

            msg = MIMEMultipart(
                From=send_from,
                To=COMMASPACE.join(send_to),
                Date=formatdate(localtime=True),
                Subject=subject
            )
            
            
            msg['Subject'] = 'Customer Balance  - '+ months+" "+yearx
            msg['From'] = picEmail
            msg['To'] = COMMASPACE.join(send_to)
            
            html = """\
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
                Here are the information regarding your balance<br><br>
              
                Period  :  """+months+""" """+ str(yearx)+ """ <br>
                Customer Name  :  """+custname2+"""  <br><br>
              
                <b>Unit Sales Transaction</b>   : """+arrayDoc[key][10]+"""<br>
                <b>Sparepart Transaction</b>    : """+arrayDoc[key][40]+"""<br>
                <b>Service Transaction</b>      : """+arrayDoc[key][20]+"""<br>
                <b>Other Transaction</b>        : """+arrayDoc[key][50]+"""<br><br>
                If any additional information please dont be hesitate to inform us to FAD team ("""+picRegional+""" : """+picEmail+""")<br><br><br>
               Best Regard,<br><br>
               <b>Finance Accounting Division</b><br></font>
              <i><b> <font face="monotype" size="2" color="#0073e5">*This email automatically generated by Hino Integrated System</font></i></b><br>
             
               </td></tr></table>
            <frameset cols="85%, 15%">
            <frame src="https://global.hino.co.id/his/" name="frame1">
            
            </frameset>
            
          </body>
        </html>
        """
            msg.attach(MIMEText(html, 'html'))
            
            

            for f in files or []:
            
                with open(f, "rb") as fil:
                    msg.attach(MIMEApplication(
                        fil.read(),
                        Content_Disposition='attachment; filename="%s"' % basename(f),
                        Name=basename(f)
                    ))

            smtp = smtplib.SMTP("email-smtp.ap-southeast-1.amazonaws.com", 587)
            smtp.starttls()
            smtp.login('AKIAQEUMQYRSYZUJWAD2','BCO7lpocXEkYLqA4aq1WoDmYcSKrvAJ/1e0Ob2MVchhZ')
            smtp.sendmail('noreply@halohino.co.id', send_to, msg.as_string())
            smtp.close()
            
        # send_mail2(picEmail, [emailcustomer2], 'Test SMTP Balance Summary', '<b>aaaaaaaaaaaaaaa</b>',server="")
    
try:
    curhisxx.execute("SELECT Cust_Code, Cust_Name, cust_email,cust_regional FROM hgs_mst_customersaldo_final where Cust_Name = '"+setName+"' limit 1")
    for dealer in curhisxx:
        insertLogSend(str(dealer[1]))
        ProsesSendMail(str(dealer[1]), str(dealer[3]))
except Exception as err:
    print(str(err))
		
		

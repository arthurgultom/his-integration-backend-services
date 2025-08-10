from sqlite3 import Cursor
import mysql.connector
import pymysql

import threading
import requests

##----------------------------------------Connect To 35 (hino_bi_db)----------------------------------------------------## 

## HMSI SELALU DIHATI ##
## COPYRIGHT HMSI Juli 2022

HOST	= '10.17.51.36/apidealer/api/fetch/dealer'

try:
    # connect to db hino_bi_db
    # hino_bi_db = mysql.connector.connect(
    #     host="10.17.51.35",
    #     user="mysqlwb",
    #     password="mysqlwb",
    #     database="hino_bi_db"
    # )
    hino_bi_db = pymysql.connect(host='34.128.71.69',user='root',password='EuXbrmzvVBjDSB99', database='hino_bi_db_dev')
    print("Connect To hino_bi_db success") 

except Exception as err:
    print(err)
    
print("Start Process...")


hino_bi    		= hino_bi_db.cursor()


# get WR DOC NO null, DEALER_ID null
hino_bi.execute("""
    select DISTINCT(`DLR#W0`) from fsp_ws_claim_detail where DEALER_ID IS NULL and PPMMW0 = MONTH(CURDATE()) and PPYYW0 = YEAR(CURDATE());
""")

DataFSP     	= hino_bi.fetchall()
y           	= len(DataFSP)

     
# function untuk mendapatkan ids_dealer_code_sales di server 35 DB hino_bi_db table fsp_ws_claim_detail
def getDataFSP(dealerCode) :

	payload = {
	  'type': 'FSP',
	  'code': dealerCode
	}

	req = requests.post('http://10.17.51.36/apidealer/api/fetch/dealer', data=payload)
	return req.json()

# function untuk mendapatkan ids_dealer_code_sales di server 35 DB hino_bi_db table warranty_claim_detail
def getDataWarranty(dealerCode) :

	payload = {
	  'type': 'WARRANTY',
	  'code': dealerCode
	}

	req = requests.post('http://10.17.51.36/apidealer/api/fetch/dealer', data=payload)
	return req.json()

# function untuk mendapatkan ids_dealer_code_sales di server 32 DB his_db_final_3 table mst_dealer_fsp_extend
def getDataExtend(dealerCode) :

	payload = {
	  'type': 'EXTEND',
	  'code': dealerCode
	}

	req = requests.post('http://10.17.51.36/apidealer/api/fetch/dealer', data=payload)
	return req.json()
    
# function untuk update data ke table
def updateData(dealerWO, getDealer):    
    
    hino_bi.execute("""
        UPDATE fsp_ws_claim_detail SET 
        DEALER_ID = '"""+getDealer+"""', WR_DOC_NO = CONCAT(
            'WR-',
            MID( DEALER_ID, 3, 3 ),(
                CASE

                    WHEN LENGTH( PPMMW0 ) = 1 THEN
                    CONCAT( '0', PPMMW0 ) ELSE PPMMW0 END
            ),
            RIGHT ( PPYYW0, 2 )
        )
        WHERE `DLR#W0` = '"""+dealerWO+"""' and PPMMW0 = MONTH(CURDATE()) and PPYYW0 = YEAR(CURDATE())
    """)
        
    hino_bi_db.commit()
    
    print(dealerWO + ' Success Update Data')
    
    
if(y==0):

    print("No Data to Update...")
    hino_bi_db.close()
    
else:  
	
    # looping data FSP
    for row in list(DataFSP):
       
        parsingFsp      = getDataFSP(row[0]) #parsing response FSP
        
        
        if(getDataFSP(row[0])['status'] == False): #check status dlr#w0 di table m_dealer
            
            parsingWarranty = getDataWarranty(row[0]) #parsing response Warranty
            print(row[0] + ' FSP - Not Found')
            
            if(parsingWarranty['status'] == True): #check status dlr#w0 di table m_dealer
            
                print(row[0] + ' WARRANTY - Found')
                updateData(row[0], parsingWarranty['data']['ids_dealer_code_sales']) #update data
                
            else: 
            
                parsingExtend   = getDataExtend(row[0]) #parsing response Extend
                print(row[0] +  ' WARRANTY - Not Found')
                
                if(parsingExtend['status'] == False): #check status dlr#w0 di table mst_dealer_fsp_extend
                
                    print(row[0] +  ' EXTEND - Not Found')
                    
                else:
                
                    print(row[0] + ' EXTEND - Found')
                    updateData(row[0], parsingExtend['data']['ids_dealer_code_sales']) #update data
        else:
            
            print(row[0] + ' FSP - Found')
            updateData(row[0], parsingFsp['data']['ids_dealer_code_sales']) #update data
        
print("End Process...")
        

 
    







    




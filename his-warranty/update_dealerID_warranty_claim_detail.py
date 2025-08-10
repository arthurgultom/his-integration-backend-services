from sqlite3 import Cursor
import mysql.connector
import threading
import requests

##--------------------------------------------Connect To 35 (hino_bi_db)---------------------------------------## 

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
    # print("Connect To hino_bi_db success") 
    hino_bi_db = pymysql.connect(host='34.128.71.69',user='root',password='EuXbrmzvVBjDSB99', database='hino_bi_db_dev')
    print("Connect To hino_bi_db success")

except Exception as err:
    print(err)
    
print("Start Process...")


hino_bi    		= hino_bi_db.cursor()


# get WR DOC NO null, DEALER_ID null
hino_bi.execute("""
    SELECT DISTINCT(`DLR#W0`) FROM warranty_claim_detail WHERE DEALER_ID IS NULL and PPMMW0 = MONTH(CURDATE()) and PPYYW0 = YEAR(CURDATE());
""")

DataWarranty     = hino_bi.fetchall()
y           	= len(DataWarranty)


# function untuk mendapatkan ids_dealer_code_sales di server 35 DB hino_bi_db table warranty_claim_detail
def getDataWarranty(dealerCode) :

	payload = {
	  'type': 'WARRANTY',
	  'code': dealerCode
	}

	req = requests.post('http://10.17.51.36/apidealer/api/fetch/dealer', data=payload)
	return req.json()
    
# function untuk update data ke table
def updateData(dealerWO, getDealer):
    
    hino_bi.execute("""
        UPDATE warranty_claim_detail SET 
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
    for row in list(DataWarranty):
       
        parsingWarranty = getDataWarranty(row[0]) #parsing response Warranty
        
        if(parsingWarranty['status'] == False): #check status dlr#w0 di table m_dealer        
            
            print(row[0] + ' WARRANTY - Not Found')
            
        else:
            
            print(row[0] + ' WARRANTY - Found')
            updateData(row[0], parsingWarranty['data']['ids_dealer_code_sales']) #update data
            

print("End Process...")
        

 
    







    




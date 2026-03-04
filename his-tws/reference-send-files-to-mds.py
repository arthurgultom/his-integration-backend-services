import ftplib
from dotenv import load_dotenv
import os
from controllers.utilities.SendEmail import sendEmail

load_dotenv('.env')
app_path = os.getenv('APP_PATH')
path_folder_server = os.getenv('IDS_PATH')
IDS_HOST = os.getenv('IDS_HOST')
IDS_USERNAME = os.getenv('IDS_USERNAME')
IDS_PASSWORD = os.getenv('IDS_PASSWORD')

def send_file_ids(path_folder_local, filename):
    try:
        ftp = ftplib.FTP(IDS_HOST)
        ftp.login(IDS_USERNAME, IDS_PASSWORD)
        ftp.cwd(path_folder_server)

        f = open(f"{app_path}{path_folder_local}{filename}", 'rb')

        ftp.storbinary(f"STOR {filename}", f)
        ftp.quit()
    except ftplib.all_errors as err:
        datasend = {
            "name": 'IT PIC Payment Portal',
            "module": 'Payment Portal Warning',
            "components": 'Send CSV AP to ids',
            "components_messages": str(err)
        }
        sendEmail('payment-portal-warning', ['farah.parahita@hino.co.id','rizka.fajriah@hino.co.id','muhammad.taufik@hino.co.id','muhammad.kahfi@hino.co.id','renni.widyastuti@hino.co.id'], [], datasend, False, []) 
        # print(f'Error: ' + str(err))

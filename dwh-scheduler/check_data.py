import pyodbc
import time
import datetime
import calendar
conn = pyodbc.connect('DRIVER={IBM i Access ODBC Driver};SYSTEM=10.17.51.22;DATABASE=HMI17P001;UID=crm;PWD=password')
cursor = conn.cursor()

query = "SELECT SUM(GROSS_AMT) AS actual FROM partssales where INV_DATE >= '2025/08' GROUP BY 1"
cursor.execute(query)

data = cursor.fetchall()
y = len(data)

cursor.close()
conn.close()

print("\n--- Query Results ---")
if y > 0:
    for row in data:
        print(row)
    print(f"\nTotal rows fetched: {y}")
else:
    print("No data was returned from the query.")

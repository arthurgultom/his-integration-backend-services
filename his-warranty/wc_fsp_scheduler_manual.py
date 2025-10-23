import sys
from sqlite3 import Cursor
import datetime
import time
import pymysql

print("[STEP 1] Starting warranty FSP scheduler processing...")
print(f"[STEP 1] Process started at: {datetime.datetime.now()}")

# Initialize date variables
dateTime = datetime.datetime.now()
date = dateTime.strftime("%Y-%m-%d")

# Calculate previous month with proper year rollover handling
if dateTime.month == 1:
    # If current month is January, previous month is December of previous year
    prev_month = 12
    prev_year = dateTime.year - 1
else:
    # Otherwise, just subtract 1 from current month
    prev_month = dateTime.month - 1
    prev_year = dateTime.year

current_month = str(prev_month).zfill(2)  # Ensure 2-digit format (01, 02, etc.)
current_year = str(prev_year)

print(f"[STEP 1] Processing data for PREVIOUS MONTH period: {current_month}/{current_year}")
print(f"[STEP 1] Current date: {date}")
print(f"[STEP 1] Current period: {dateTime.strftime('%m/%Y')}, Processing: {current_month}/{current_year}")

#--------------------------------------------Connect To MySQL (his_db_final_3)---------------------------------------------------------------------------------
print("[STEP 2] Establishing MySQL database connection (his_db_final_3)...")
try:
    # Connect to his_db_final_3 database
    con_hino_bi = pymysql.connect(host='10.17.111.18',user='mysqlwb',password='mysqlwb',database='his_db_final_3')
    print("[STEP 2] ✓ Successfully connected to his_db_final_3")
except Exception as e:
    print(f"[STEP 2] ✗ Failed to connect to his_db_final_3: {e}")
    sys.exit(1)


bidb = con_hino_bi.cursor()
print("[STEP 2] ✓ Database cursor created")

print("[STEP 3] Executing warranty FSP claim aggregation query...")
print("[STEP 3] Combining data from warranty_claim_detail and fsp_ws_claim_detail tables")
print(f"[STEP 3] Query parameters - Month: {current_month}, Year: {current_year}")

try:
    bidb.execute("""
    SELECT
    claim.dealer_id,
    claim.WR_DOC_NO,
    sum( claim.subtotal ) AS subtotal,
    sum( ppn ) AS ppn,
    sum( total ) AS total,
    sum( pph23_wcl_labour ) AS pph23_wcl_labour,
    sum( pph23_ws_fsp_labour ) AS pph23_ws_fsp_labour,
    closing_period,
    sum( grand_total ) AS grand_total,
    wr_reg_punishment,
    correction,
    pemotongan_tools,
    deposito,
    other,
    other_remark,
    attachment,
    sum( wc_unit ) AS wc_unit,
    sum( wc_amount ) AS wc_amount,
    sum( fsp_unit ) AS fsp_unit,
    sum( fsp_amount ) AS fsp_amount,
    sum( ws_unit ) AS ws_unit,
    sum( ws_amount ) AS ws_amount,
    claim.STATUS,
    sum( claim.total_claim ) as total_claim
    from (
    SELECT DEALER_ID AS dealer_id,
    CONCAT('WR-',MID( DEALER_ID, 3, 3 ),(case WHEN LENGTH( PPMMW0 )= 1 THEN CONCAT( '0', PPMMW0 ) ELSE PPMMW0 END),RIGHT ( DCYYW0, 2 )) AS WR_DOC_NO,
    SUM( a.STOTAL ) AS subtotal,
    FLOOR( CASE MID(dealer_id,3,3) WHEN 'ROP' THEN 0 WHEN 'RMM' THEN 0 ELSE SUM( a.STOTAL ) * 0.11 END) AS ppn ,
    (SUM( a.STOTAL )+ SUM( a.STAXW0 )) AS total,
    sum( a.LCSTW0 )* 0.02 AS pph23_wcl_labour,
    0 AS pph23_ws_fsp_labour,
    CONCAT( PPYYW0, '-',( CASE WHEN LENGTH( PPMMW0 )= 1 THEN CONCAT( '0', PPMMW0 ) ELSE PPMMW0 END ), '-', 25 ) AS closing_period,
    ((FLOOR(SUM( a.STOTAL )+ (SUM( a.STOTAL ) * 0.11))- sum( a.LCSTW0 )* 0.02)) AS grand_total,
    0 AS wr_reg_punishment,
    0 AS correction,
    0 AS pemotongan_tools,
    0 AS deposito,
    0 AS other,
    '' AS other_remark,
    '' AS attachment,
    COUNT( a.`VIN#W0` ) AS wc_unit,
    sum( a.STOTAL ) AS wc_amount,
    0 AS fsp_unit,
    0 AS fsp_amount,
    0 AS ws_unit,
    0 AS ws_amount,
    0 AS STATUS,
    0 AS total_claim 
    from `warranty_claim_detail` a
    WHERE a.PPMMW0 = '"""+current_month+"""' and a.PPYYW0 = '"""+current_year+"""' 
    GROUP BY WR_DOC_NO 

    union

    select b.dealer_id AS dealer_id,
    CONCAT('WR-',MID( DEALER_ID, 3, 3 ),(CASE WHEN LENGTH( PPMMW0 )= 1 THEN CONCAT( '0', PPMMW0 ) ELSE PPMMW0 END),RIGHT ( PPYYW0, 2 )) AS WR_DOC_NO,
    SUM( b.STOTAL ) AS subtotal,
    FLOOR ( CASE MID(dealer_id,3,3) WHEN 'ROP' THEN 0 WHEN 'RMM' THEN 0 ELSE SUM( b.STOTAL ) * 0.11 END) AS ppn,
    (SUM( b.STOTAL )+ SUM( STAXW0 )) AS total,
    0 AS pph23_wcl_labour,
    sum( b.LCSTW0 )* 0.02 AS pph23_ws_fsp_labour,
    CONCAT( PPYYW0, '-',( CASE WHEN LENGTH( PPMMW0 )= 1 THEN CONCAT( '0', PPMMW0 ) ELSE PPMMW0 END ), '-', '25' ) AS closing_period,
    ((FLOOR(SUM( b.STOTAL )+ (SUM( b.STOTAL ) * 0.11))- sum( b.LCSTW0 )* 0.02 )) AS grand_total,
    0 AS wr_reg_punishment,
    0 AS correction,
    0 AS pemotongan_tools,
    0 AS deposito,
    0 AS other,
    '' AS other_remark,
    '' AS attachment,
    0 AS wc_unit,
    0 AS wc_amount,
     COUNT(b.`VIN#W0`)  AS fsp_unit ,
      sum(b.STOTAL)  AS fsp_amount ,
    0 AS ws_unit ,
      0 AS ws_amount ,
    0 AS STATUS,
    0 AS total_claim 
    FROM fsp_ws_claim_detail b 
    WHERE  b.PPMMW0 = '"""+current_month+"""' and b.PPYYW0 = '"""+current_year+"""' and right(ACCT2,2) not in ('1S','2S')  
    GROUP BY WR_DOC_NO 

    union

    SELECT b.dealer_id AS dealer_id,
    CONCAT('WR-',MID( DEALER_ID, 3, 3 ),(CASE WHEN LENGTH( PPMMW0 )= 1 THEN CONCAT( '0', PPMMW0 ) ELSE PPMMW0 END),RIGHT ( PPYYW0, 2 )) AS WR_DOC_NO,
    SUM( b.STOTAL ) AS subtotal,
    FLOOR ( CASE MID(dealer_id,3,3) WHEN 'ROP' THEN 0 WHEN 'RMM' THEN 0 ELSE SUM( b.STOTAL ) * 0.11 END) AS ppn,
    (SUM( b.STOTAL )+ SUM( STAXW0 )) AS total,
    0 AS pph23_wcl_labour,
    sum( b.LCSTW0 )* 0.02 AS pph23_ws_fsp_labour,
    CONCAT( PPYYW0, '-',( CASE WHEN LENGTH( PPMMW0 )= 1 THEN CONCAT( '0', PPMMW0 ) ELSE PPMMW0 END ), '-', '25' ) AS closing_period,
    ((FLOOR(SUM( b.STOTAL )+ (SUM( b.STOTAL ) * 0.11))- sum( b.LCSTW0 )* 0.02 )) AS grand_total,
    0 AS wr_reg_punishment,
    0 AS correction,
    0 AS pemotongan_tools,
    0 AS deposito,
    0 AS other,
    '' AS other_remark,
    '' AS attachment,
    0 AS wc_unit,
    0 AS wc_amount,
    0 AS fsp_unit ,
      0 AS fsp_amount ,
    COUNT(b.`VIN#W0`) AS ws_unit ,
      sum(b.STOTAL)  AS ws_amount ,
    0 AS STATUS,
    0 AS total_claim 
    FROM
    fsp_ws_claim_detail b 
    WHERE b.PPMMW0 = '"""+current_month+"""' and b.PPYYW0 = '"""+current_year+"""' and right(ACCT2,2)  in ('1S','2S') 
    GROUP BY WR_DOC_NO 
    ORDER BY WR_DOC_NO
    ) claim 
    where dealer_id IS NOT NULL 
    GROUP by WR_DOC_NO;
    """)
    print("[STEP 3] ✓ Query executed successfully")
except Exception as e:
    print(f"[STEP 3] ✗ Query execution failed: {e}")
    bidb.close()
    con_hino_bi.close()
    sys.exit(1)

print("[STEP 4] Fetching aggregated warranty FSP claim data...")
data = bidb.fetchall()
y = len(data)
print(f"[STEP 4] ✓ Retrieved {y} warranty FSP claim records")

# Process the fetched data
x = 0
Arr = data

print("[STEP 5] Checking data availability...")
if y == 0:
    print("[STEP 5] ⚠ No data available to migrate for current period")
    print(f"[STEP 5] Period: {current_month}/{current_year}")
    bidb.close()
    con_hino_bi.close()
    print("[FINAL] Process completed - No data to process")
else:
    print(f"[STEP 5] ✓ Found {y} records to process")


    # Connect to warranty database (his_db_final_3)
    print("[STEP 6] Establishing connection to warranty database (his_db_final_3)...")
    try:
        conn_warranty = pymysql.connect(host='10.17.111.18',user='mysqlwb',password='mysqlwb',database='his_db_final_3')
        print("[STEP 6] ✓ Successfully connected to his_db_final_3")
    except Exception as e:
        print(f"[STEP 6] Failed to connect to his_db_final_3: {e}")
        bidb.close()
        con_hino_bi.close()
        sys.exit(1)

    print("[STEP 7] Starting warranty FSP claim adjustment migration...")
    print(f"[STEP 7] Processing {y} records for insertion into warranty_fsp_claim_adjusment_test table")
    
    successful_inserts = 0
    failed_inserts = 0
    
    try:
        # Data structure mapping for reference:
        # 0=dealer_id, 1=wr_doc_no, 2=subtotal, 3=ppn, 4=total, 5=wcl_labour, 6=fsp_labour,
        # 7=closing, 8=grand_total, 9=reg_punishment, 10=correction, 11=tools, 12=deposito,
        # 13=other, 14=other_remark, 15=attachment, 16=wc_unit, 17=wc_amount, 18=fsp_unit,
        # 19=fsp_amount, 20=ws_unit, 21=ws_amount, 22=status, 23=total_claim
        
        warranty = conn_warranty.cursor()
        print("[STEP 7] Warranty database cursor created")
        
        for index, row in enumerate(Arr, 1):
            print(f"[STEP 7] Processing record {index}/{y} - WR Doc: {row[1]}, Dealer: {row[0]}")
            
            try:
                # Insert into warranty_fsp_claim_adjusment_test table
                warranty.execute("""
                INSERT INTO warranty_fsp_claim_adjusment_test VALUES (
                    %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 
                    %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 
                    %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 
                    %s, %s
                )
                """, (
                    str(row[1]),    # wr_doc_no
                    str(row[0]),    # dealer_id
                    '',             # empty field
                    row[7],         # closing_period
                    str(row[2]),    # subtotal
                    str(row[3]),    # ppn
                    str(row[4]),    # total
                    str(row[6]),    # fsp_labour
                    str(row[5]),    # wcl_labour
                    str(row[9]),    # reg_punishment
                    str(row[10]),   # correction
                    str(row[11]),   # tools
                    str(row[12]),   # deposito
                    str(row[13]),   # other
                    str(row[14]),   # other_remark
                    str(row[8]),    # grand_total
                    str(row[23]),   # total_claim
                    str(row[15]),   # attachment
                    date,           # created_date
                    '',             # created_by
                    date,           # updated_date
                    '',             # updated_by
                    str(row[20]),   # ws_unit
                    str(row[21]),   # ws_amount
                    str(row[16]),   # wc_unit
                    str(row[17]),   # wc_amount
                    str(row[18]),   # fsp_unit
                    str(row[19]),   # fsp_amount
                    '',             # additional_field_1
                    '0',            # additional_field_2
                    '',             # additional_field_3
                    '0'             # additional_field_4
                ))
                
                conn_warranty.commit()
                successful_inserts += 1
                print(f"[STEP 7] Successfully inserted record {index}/{y}")
                
                # Log progress every 10 successful inserts
                if successful_inserts % 10 == 0:
                    print(f"[STEP 7] Progress: {successful_inserts} records successfully inserted...")
                    
            except Exception as insert_error:
                failed_inserts += 1
                print(f"[STEP 7] Failed to insert record {index}/{y}: {insert_error}")
                print(f"[STEP 7] - Problematic data: WR Doc={row[1]}, Dealer={row[0]}")
                continue
        
        print(f"[STEP 7] ✓ Migration completed - Success: {successful_inserts}, Failed: {failed_inserts}")
        
        # Update CLAIM_TYPE_REPORT fields in source tables
        print("[STEP 8] Updating CLAIM_TYPE_REPORT fields in source tables...")
        try:
            print("[STEP 8] Updating FSP claim types (non-1S/2S accounts)...")
            bidb.execute(""" 
                UPDATE fsp_ws_claim_detail 
                SET CLAIM_TYPE_REPORT='FSP' 
                WHERE right(ACCT2,2) not in ('1S','2S')
            """)
            
            print("[STEP 8] Updating WS claim types (1S/2S accounts)...")
            bidb.execute(""" 
                UPDATE fsp_ws_claim_detail 
                SET CLAIM_TYPE_REPORT='WS' 
                WHERE right(ACCT2,2) in ('1S','2S')
            """)
            
            print("[STEP 8] Updating WC claim types...")
            bidb.execute(""" 
                UPDATE warranty_claim_detail 
                SET CLAIM_TYPE_REPORT='WC'
            """)
            
            con_hino_bi.commit()
            print("[STEP 8] ✓ Successfully updated all CLAIM_TYPE_REPORT fields")
            
        except Exception as update_error:
            print(f"[STEP 8] ✗ Failed to update CLAIM_TYPE_REPORT fields: {update_error}")
        
        # Final summary
        print("[FINAL] ✓ Warranty FSP scheduler migration completed successfully!")
        print(f"[FINAL] Summary:")
        print(f"[FINAL] - Total records processed: {y}")
        print(f"[FINAL] - Successful insertions: {successful_inserts}")
        print(f"[FINAL] - Failed insertions: {failed_inserts}")
        print(f"[FINAL] - Success rate: {(successful_inserts/y*100):.1f}%")
        
    except Exception as e:
        print(f"[ERROR] Migration process failed: {e}")
        print(f"[ERROR] Partial results - Success: {successful_inserts}, Failed: {failed_inserts}")
    
    finally:
        # Clean up database connections
        print("[CLEANUP] Closing database connections...")
        try:
            warranty.close()
            conn_warranty.close()
            print("[CLEANUP] ✓ Warranty database connection closed")
        except:
            pass
            
        try:
            bidb.close()
            con_hino_bi.close()
            print("[CLEANUP] ✓ BI database connection closed")
        except:
            pass

end_time = datetime.datetime.now()
print(f"[FINAL] Process ended at: {end_time}")
print(f"[FINAL] Total execution time: {end_time - dateTime}")
print("[FINAL] Warranty FSP scheduler process completed.")





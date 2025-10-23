import pymysql
from datetime import date, timedelta

def get_working_days(start_date, end_date):
    """Menghitung jumlah hari kerja (selain Minggu) antara dua tanggal."""
    if start_date > end_date:
        return 0
    
    working_days = 0
    current_date = start_date
    while current_date <= end_date:
        # weekday() mengembalikan 6 untuk hari Minggu
        if current_date.weekday() != 6:
            working_days += 1
        current_date += timedelta(days=1)
    return working_days

def main():
    try:
        conn = pymysql.connect(
            host='34.128.71.69',
            user='root',
            password='EuXbrmzvVBjDSB99',
            database='his_db_final_3_dev'
        )
        cursor = conn.cursor()
        print("Koneksi ke database berhasil.")

        # Ambil semua data yang relevan
        query = "SELECT lt_no, revisi_date FROM tws_trs_technical WHERE revisi_date IS NOT NULL AND status = 0"
        cursor.execute(query)
        all_data = cursor.fetchall()

        today = date.today()
        processed_count = 0

        print(f"Ditemukan {len(all_data)} data dengan status 0. Memeriksa kondisi hari...")

        for row in all_data:
            lt_no, revisi_date = row
            
            # Hitung hari kerja yang telah berlalu
            working_days_passed = get_working_days(revisi_date, today)

            # Kondisi: sudah melebihi 5 hari kerja
            if working_days_passed > 5:
                print(f"Proses data LT No: {lt_no} (revisi: {revisi_date}, hari kerja berlalu: {working_days_passed})")
                
                # 1. Update status di tws_trs_technical
                update_query = "UPDATE tws_trs_technical SET status = 1 WHERE lt_no = %s"
                cursor.execute(update_query, (lt_no,))
                
                # 2. Insert ke tws_trs_technical_response
                insert_query = """
                INSERT INTO tws_trs_technical_response (lt_no, tanggal_kirim, nama_user, isi_pesan)
                VALUES (%s, NOW(), %s, %s)
                """
                cursor.execute(insert_query, (lt_no, 'system', 'Auto send to HMSI after 5 hari kerja'))
                
                conn.commit()
                processed_count += 1
                print(f"-> Berhasil: Status diupdate dan response ditambahkan.")

        print(f"\nProses selesai. Total {processed_count} data telah diproses.")

    except pymysql.MySQLError as e:
        print(f"Error database: {e}")
        if 'conn' in locals() and conn.open:
            conn.rollback()
    except Exception as e:
        print(f"Terjadi error: {e}")
    finally:
        if 'conn' in locals() and conn.open:
            cursor.close()
            conn.close()
            print("Koneksi database ditutup.")

if __name__ == "__main__":
    main()

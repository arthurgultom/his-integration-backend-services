import os
import sys
import json
import time
from datetime import datetime

try:
    import pymysql
except Exception:
    pymysql = None

try:
    import MySQLdb
except Exception:
    MySQLdb = None

try:
    import mysql.connector
except Exception:
    mysql = None
    mysql_connector = None
else:
    mysql_connector = mysql.connector

try:
    import urllib2
except Exception:
    urllib2 = None

try:
    import ssl
except Exception:
    ssl = None

try:
    import requests
except Exception:
    requests = None
else:
    try:
        import urllib3
        urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
    except Exception:
        try:
            requests.packages.urllib3.disable_warnings()
        except Exception:
            pass

try:
    from urllib import request as urllib_request
    from urllib import error as urllib_error
except Exception:
    urllib_request = None
    urllib_error = None

def _clean_env_value(value):
    if value is None:
        return ""
    v = str(value).strip()
    if len(v) >= 2 and ((v[0] == '"' and v[-1] == '"') or (v[0] == "'" and v[-1] == "'") or (v[0] == "`" and v[-1] == "`")):
        v = v[1:-1]
    return v.strip()


def _get_env_first(keys, default=""):
    for k in keys:
        v = os.getenv(k)
        if v is None:
            continue
        v = _clean_env_value(v)
        if v != "":
            return v
    return default


def _load_env():
    base_dir = os.path.dirname(os.path.abspath(__file__))
    env_here = os.path.join(base_dir, ".env")

    try:
        from dotenv import load_dotenv
        if os.path.exists(env_here):
            load_dotenv(env_here)
        else:
            load_dotenv()
        return
    except Exception:
        pass

    if not os.path.exists(env_here):
        return

    try:
        with open(env_here, "r") as f:
            for line in f:
                line = line.strip()
                if not line or line.startswith("#"):
                    continue
                if "=" not in line:
                    continue
                key, value = line.split("=", 1)
                key = key.strip()
                value = _clean_env_value(value)
                if key and key not in os.environ:
                    os.environ[key] = value
    except Exception:
        pass


def _connect_mysql(host, port, database, user, password):
    if pymysql is not None:
        return pymysql.connect(
            host=host,
            user=user,
            password=password,
            database=database,
            port=port,
            charset="utf8",
            autocommit=False,
        )

    if MySQLdb is not None:
        return MySQLdb.connect(
            host=host,
            user=user,
            passwd=password,
            db=database,
            port=port,
            charset="utf8",
        )

    if mysql_connector is not None:
        return mysql_connector.connect(
            host=host,
            user=user,
            password=password,
            database=database,
            port=port,
        )

    raise RuntimeError("Missing MySQL driver. Install pymysql (recommended), mysql-connector-python, or MySQLdb.")


def _ensure_app_url_base(app_url):
    app_url = _clean_env_value(app_url)
    if not app_url:
        return ""
    if not app_url.endswith("/"):
        return app_url + "/"
    return app_url


def _http_post_json(url, payload, timeout_sec):
    headers = {"Accept": "application/json", "Content-Type": "application/json"}
    data_str = json.dumps(payload)
    data_bytes = None
    try:
        data_bytes = data_str.encode("utf-8")
    except Exception:
        data_bytes = data_str

    if requests is not None:
        try:
            resp = requests.post(url, data=data_bytes, headers=headers, timeout=timeout_sec, verify=False)
            return int(resp.status_code), resp.text
        except Exception as e:
            return 0, str(e)

    if urllib_request is None:
        if urllib2 is None:
            raise RuntimeError("No HTTP client available")

    if urllib_request is not None:
        req = urllib_request.Request(url, data=data_bytes, headers=headers)
        ctx = None
        if ssl is not None and hasattr(ssl, "_create_unverified_context"):
            try:
                ctx = ssl._create_unverified_context()
            except Exception:
                ctx = None
        try:
            if ctx is not None:
                try:
                    resp = urllib_request.urlopen(req, timeout=timeout_sec, context=ctx)
                except TypeError:
                    resp = urllib_request.urlopen(req, timeout=timeout_sec)
            else:
                resp = urllib_request.urlopen(req, timeout=timeout_sec)
            code = getattr(resp, "getcode", lambda: 200)()
            body = resp.read()
            try:
                resp.close()
            except Exception:
                pass
            try:
                body_text = body.decode("utf-8")
            except Exception:
                body_text = body
            return int(code), body_text
        except Exception as e:
            if urllib_error is not None and isinstance(e, urllib_error.HTTPError):
                try:
                    body = e.read()
                except Exception:
                    body = ""
                try:
                    body_text = body.decode("utf-8")
                except Exception:
                    body_text = body
                return int(getattr(e, "code", 0) or 0), body_text
            return 0, str(e)

    req = urllib2.Request(url, data_bytes, headers)
    ctx = None
    if ssl is not None and hasattr(ssl, "_create_unverified_context"):
        try:
            ctx = ssl._create_unverified_context()
        except Exception:
            ctx = None
    try:
        if ctx is not None:
            try:
                resp = urllib2.urlopen(req, timeout=timeout_sec, context=ctx)
            except TypeError:
                resp = urllib2.urlopen(req, timeout=timeout_sec)
        else:
            resp = urllib2.urlopen(req, timeout=timeout_sec)
        code = getattr(resp, "getcode", lambda: 200)()
        body = resp.read()
        try:
            resp.close()
        except Exception:
            pass
        try:
            body_text = body.decode("utf-8")
        except Exception:
            body_text = body
        return int(code), body_text
    except Exception as e:
        try:
            body = e.read()
        except Exception:
            body = ""
        try:
            body_text = body.decode("utf-8")
        except Exception:
            body_text = body
        return 0, body_text


def _split_emails(value):
    if not value:
        return []
    v = value.strip()
    if not v:
        return []
    parts = []
    buf = []
    for ch in v:
        if ch in [",", ";", " "]:
            if buf:
                parts.append("".join(buf))
                buf = []
        else:
            buf.append(ch)
    if buf:
        parts.append("".join(buf))
    cleaned = []
    seen = set()
    for p in parts:
        p = p.strip()
        if not p:
            continue
        if p not in seen:
            seen.add(p)
            cleaned.append(p)
    return cleaned


def _is_truthy(value):
    if value is None:
        return False
    v = str(value).strip().lower()
    return v in ["1", "true", "yes", "y", "on"]


def _append_jsonl(file_path, record):
    if not file_path:
        return
    try:
        line = json.dumps(record, ensure_ascii=False)
    except Exception:
        try:
            line = json.dumps(record)
        except Exception:
            line = str(record)
    try:
        with open(file_path, "a") as f:
            f.write(line)
            f.write("\n")
    except Exception:
        pass


def main():
    _load_env()

    connection_type = _get_env_first(["CONNECTION"], "MYSQL").upper()
    if connection_type != "MYSQL":
        print("Unsupported CONNECTION={}".format(connection_type))
        return 1

    host = _get_env_first(["HOST", "DB_HOST"])
    port_str = _get_env_first(["PORT", "DB_PORT"], "3306")
    database = _get_env_first(["DATABASE", "DB_NAME"])
    fin_role_str = _get_env_first(["FIN_ROLE"], "")
    app_url = _ensure_app_url_base(_get_env_first(["APP_URL"], ""))

    user = _get_env_first(["USERNAME", "USER", "DB_USERNAME", "DB_USER", "MYSQL_USER"])
    password = _get_env_first(["PASSWORD", "DB_PASSWORD", "MYSQL_PASSWORD"])

    endpoint = _get_env_first(
        ["EMAIL_ENDPOINT"],
        "https://his-be-notification.hino.co.id/email-services/ebt-pum-reminder-outstanding",
    )

    timeout_sec_str = _get_env_first(["HTTP_TIMEOUT_SECONDS"], "30")
    extra_to_emails = _split_emails(_get_env_first(["PUM_REMINDER_EXTRA_EMAILS", "EXTRA_TO_EMAILS"], ""))

    debug_mode = ("--debug" in sys.argv) or _is_truthy(_get_env_first(["DEBUG", "DRY_RUN"], "0"))
    debug_log_path = _get_env_first(["DEBUG_LOG_PATH"], "")
    debug_log_to_db = _is_truthy(_get_env_first(["DEBUG_LOG_TO_DB", "DRY_RUN_INSERT_LOG"], "0"))
    if debug_mode and not debug_log_path:
        base_dir = os.path.dirname(os.path.abspath(__file__))
        debug_log_path = os.path.join(base_dir, "auto-send-email-pum-outstanding.debug.log")

    try:
        port = int(port_str)
    except Exception:
        port = 3306

    try:
        timeout_sec = int(timeout_sec_str)
    except Exception:
        timeout_sec = 30

    try:
        fin_role = int(fin_role_str)
    except Exception:
        fin_role = None

    if not host or not database:
        print("Missing env HOST and/or DATABASE")
        return 1

    if not user or not password:
        print("Missing DB credentials env (USERNAME/PASSWORD or DB_USERNAME/DB_PASSWORD)")
        return 1

    conn = None
    cursor = None
    processed = 0
    sent = 0
    skipped = 0
    failed = 0

    try:
        conn = _connect_mysql(host, port, database, user, password)
        cursor = conn.cursor()

        status1_sql = """
            SELECT
                am.adv_mon_id,
                am.sppd_id
            FROM fin_trs_advance_money am
            JOIN ebt_trs_sppd s ON s.sppd_id = am.sppd_id
            WHERE am.status = 1
              AND s.arrival_date IS NOT NULL
              AND CURDATE() >= s.arrival_date
        """
        cursor.execute(status1_sql)
        rows_status1 = cursor.fetchall()
        for r1 in rows_status1:
            adv_mon_id_status1 = r1[0]
            sppd_id_status1 = r1[1]
            remark_message = "The application cannot be processed. Please submit a reimbursement request via HRIS website"
            isi_pesan = "Auto Rejected - " + remark_message
            tanggal_kirim = datetime.now().isoformat()

            approver_id = None
            approver_name = ""
            try:
                cursor.execute(
                    """
                    SELECT a.approver_id, u.full_name
                    FROM fin_trs_approval a
                    JOIN hgs_mst_user u ON u.user_id = a.approver_id
                    WHERE a.doc_no = %s
                    ORDER BY a.order_approval ASC
                    LIMIT 1
                    """,
                    (adv_mon_id_status1,),
                )
                r = cursor.fetchone()
                if r:
                    approver_id = r[0]
                    approver_name = r[1] or ""
            except Exception:
                pass

            nama_user = "({}) {}".format("" if approver_id is None else str(approver_id), approver_name)

            if debug_mode:
                _append_jsonl(
                    debug_log_path,
                    {
                        "ts": datetime.now().isoformat(),
                        "event": "AUTO_REJECT_SIMULATION",
                        "adv_mon_id": adv_mon_id_status1,
                        "sppd_id": sppd_id_status1,
                        "remark_message": remark_message,
                        "isi_pesan": isi_pesan,
                        "approver_id": approver_id,
                        "approver_name": approver_name,
                        "nama_user": nama_user,
                    },
                )
            else:
                try:
                    cursor.execute(
                        """
                        INSERT INTO fin_trs_advance_money_response (adv_mon_id, tanggal_kirim, nama_user, isi_pesan)
                        VALUES (%s, %s, %s, %s)
                        """,
                        (adv_mon_id_status1, tanggal_kirim, nama_user, isi_pesan),
                    )
                except Exception:
                    pass

                if approver_id is not None:
                    try:
                        cursor.execute(
                            """
                            UPDATE fin_trs_approval
                            SET approver_flag = '3', approver_date = %s
                            WHERE doc_no = %s AND approver_id = %s
                            """,
                            (tanggal_kirim, adv_mon_id_status1, approver_id),
                        )
                    except Exception:
                        pass

                try:
                    cursor.execute(
                        "UPDATE ebt_trs_sppd SET advance_money = '0' WHERE sppd_id = %s",
                        (sppd_id_status1,),
                    )
                except Exception:
                    pass

                try:
                    cursor.execute(
                        "UPDATE fin_trs_advance_money SET status = '3', sppd_id = '' WHERE adv_mon_id = %s",
                        (adv_mon_id_status1,),
                    )
                except Exception:
                    pass

                try:
                    conn.commit()
                except Exception:
                    pass

        candidates_sql = """
            SELECT
                am.adv_mon_id,
                am.sppd_id,
                am.created_by,
                am.towards,
                am.currency_id,
                am.grand_total,
                s.arrival_date,
                DATEDIFF(CURDATE(), s.arrival_date) AS days_diff,
                tt.trip_name,
                u.full_name AS pic_name,
                u.mail_address AS pic_email,
                (
                    SELECT CONCAT(
                        (SELECT c1.city_name
                         FROM ebt_trs_sppd_destination d1
                         JOIN hgs_mst_city c1 ON d1.`from` = c1.city_id
                         WHERE d1.sppd_id = am.sppd_id
                         ORDER BY d1.departure_date ASC
                         LIMIT 1),
                        ' - ',
                        (SELECT c2.city_name
                         FROM ebt_trs_sppd_destination d2
                         JOIN hgs_mst_city c2 ON d2.`to` = c2.city_id
                         WHERE d2.sppd_id = am.sppd_id
                         ORDER BY d2.departure_date DESC
                         LIMIT 1)
                    )
                ) AS business_trip_destination
            FROM fin_trs_advance_money am
            JOIN ebt_trs_sppd s ON s.sppd_id = am.sppd_id
            LEFT JOIN ebt_mst_trip_type tt ON tt.trip_id = s.trip_id
            LEFT JOIN hgs_mst_user u ON u.user_id = am.created_by
            WHERE am.status = 2
              AND am.paid_status = 0
              AND s.arrival_date IS NOT NULL
              AND DATEDIFF(CURDATE(), s.arrival_date) >= 10
              AND (
                    MOD(DATEDIFF(CURDATE(), s.arrival_date) - 10, 5) = 0
                    OR NOT EXISTS (
                        SELECT 1
                        FROM fin_trs_pum_outstanding_email_log l
                        WHERE l.adv_mon_id = am.adv_mon_id
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM fin_trs_pum_outstanding_email_log l2
                        WHERE l2.adv_mon_id = am.adv_mon_id
                          AND l2.trigger_day = DATEDIFF(CURDATE(), s.arrival_date)
                          AND l2.status = 'FAILED'
                    )
                  )
        """

        cursor.execute(candidates_sql)
        rows = cursor.fetchall()
        if not rows:
            print("No candidates.")
            try:
                conn.commit()
            except Exception:
                pass
            return 0

        if debug_mode:
            _append_jsonl(
                debug_log_path,
                {
                    "ts": datetime.now().isoformat(),
                    "event": "START",
                    "candidates": len(rows),
                    "db": {"host": host, "port": port, "database": database},
                    "endpoint": endpoint,
                },
            )

        for row in rows:
            processed += 1

            adv_mon_id = row[0]
            created_by = row[2]
            towards = row[3] or ""
            currency_id = row[4] or ""
            grand_total = row[5] or 0
            arrival_date = row[6]
            days_diff = row[7] or 0
            trip_name = row[8] or ""
            pic_name = row[9] or ""
            pic_email = row[10] or ""
            destination = row[11] or ""

            try:
                trigger_day = int(days_diff)
            except Exception:
                trigger_day = 0

            log_exists_sql = """
                SELECT 1
                FROM fin_trs_pum_outstanding_email_log
                WHERE adv_mon_id = %s
                  AND trigger_day = %s
                  AND status = 'SUCCESS'
                LIMIT 1
            """
            cursor.execute(log_exists_sql, (adv_mon_id, trigger_day))
            if cursor.fetchone():
                skipped += 1
                if debug_mode:
                    _append_jsonl(
                        debug_log_path,
                        {
                            "ts": datetime.now().isoformat(),
                            "event": "SKIP_ALREADY_LOGGED",
                            "adv_mon_id": adv_mon_id,
                            "trigger_day": trigger_day,
                            "days_diff": days_diff,
                            "arrival_date": str(arrival_date),
                        },
                    )
                continue

            recipient_emails = []
            recipient_emails.extend(_split_emails(pic_email))

            try:
                approver_sql = """
                    SELECT u2.mail_address
                    FROM fin_trs_approval a
                    JOIN hgs_mst_user u2 ON u2.user_id = a.approver_id
                    WHERE a.doc_no = %s
                    ORDER BY a.order_approval ASC
                    LIMIT 1
                """
                cursor.execute(approver_sql, (adv_mon_id,))
                r = cursor.fetchone()
                if r and r[0]:
                    recipient_emails.extend(_split_emails(r[0]))
            except Exception:
                pass

            if fin_role is not None:
                try:
                    fin_sql = "SELECT mail_address FROM hgs_mst_user WHERE role_id = %s AND mail_address IS NOT NULL AND mail_address <> ''"
                    cursor.execute(fin_sql, (fin_role,))
                    for r in cursor.fetchall():
                        if r and r[0]:
                            recipient_emails.extend(_split_emails(r[0]))
                except Exception:
                    pass

            if extra_to_emails:
                recipient_emails.extend(extra_to_emails)

            seen = set()
            deduped = []
            for e in recipient_emails:
                if e and e not in seen:
                    seen.add(e)
                    deduped.append(e)
            recipient_emails = deduped

            doc_no = str(adv_mon_id)
            link_app_url = ""
            if app_url:
                link_app_url = "{}index.php?r=advancemoney/update&id={}".format(app_url, doc_no)

            allowance_amount = "{}{}".format(currency_id, grand_total)

            payload = {
                "email": recipient_emails,
                "body": {
                    "pic_name": pic_name,
                    "adv_mon_id": doc_no,
                    "doc_desc": towards,
                    "business_trip_type": trip_name,
                    "business_trip_destination": destination,
                    "end_trip_date": str(arrival_date),
                    "allowance_amount": str(allowance_amount),
                    "link_app_url": link_app_url,
                },
            }

            if debug_mode:
                _append_jsonl(
                    debug_log_path,
                    {
                        "ts": datetime.now().isoformat(),
                        "event": "DEBUG_PAYLOAD",
                        "adv_mon_id": adv_mon_id,
                        "trigger_day": trigger_day,
                        "days_diff": days_diff,
                        "arrival_date": str(arrival_date),
                        "created_by": created_by,
                        "recipients": recipient_emails,
                        "payload": payload,
                    },
                )
                if debug_log_to_db:
                    try:
                        insert_log_sql = """
                            INSERT INTO fin_trs_pum_outstanding_email_log
                                (adv_mon_id, trigger_day, arrival_date, days_diff, recipients, payload_json, http_status, response_body, status, error_message, created_at)
                            VALUES
                                (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW())
                        """
                        cursor.execute(
                            insert_log_sql,
                            (
                                adv_mon_id,
                                trigger_day,
                                arrival_date,
                                days_diff,
                                ",".join(recipient_emails),
                                json.dumps(payload),
                                0,
                                "",
                                "DEBUG",
                                "Debug mode - email not sent",
                            ),
                        )
                        conn.commit()
                    except Exception:
                        try:
                            conn.rollback()
                        except Exception:
                            pass
                if not recipient_emails:
                    failed += 1
                else:
                    sent += 1
                time.sleep(0.05)
                continue

            if not recipient_emails:
                failed += 1
                insert_log_sql = """
                    INSERT INTO fin_trs_pum_outstanding_email_log
                        (adv_mon_id, trigger_day, arrival_date, days_diff, recipients, payload_json, http_status, response_body, status, error_message, created_at)
                    VALUES
                        (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW())
                    ON DUPLICATE KEY UPDATE
                        recipients = VALUES(recipients),
                        payload_json = VALUES(payload_json),
                        http_status = VALUES(http_status),
                        response_body = VALUES(response_body),
                        status = VALUES(status),
                        error_message = VALUES(error_message),
                        created_at = VALUES(created_at)
                """
                cursor.execute(
                    insert_log_sql,
                    (
                        adv_mon_id,
                        trigger_day,
                        arrival_date,
                        days_diff,
                        "",
                        json.dumps(payload),
                        0,
                        "",
                        "FAILED",
                        "No recipients resolved",
                    ),
                )
                conn.commit()
                continue

            http_status = 0
            response_body = ""
            status = "FAILED"
            error_message = ""

            try:
                http_status, response_body = _http_post_json(endpoint, payload, timeout_sec)
                if http_status >= 200 and http_status < 300:
                    status = "SUCCESS"
                else:
                    status = "FAILED"
                    error_message = "Non-2xx response"
            except Exception as e:
                status = "FAILED"
                error_message = str(e)

            insert_log_sql = """
                INSERT INTO fin_trs_pum_outstanding_email_log
                    (adv_mon_id, trigger_day, arrival_date, days_diff, recipients, payload_json, http_status, response_body, status, error_message, created_at)
                VALUES
                    (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW())
                ON DUPLICATE KEY UPDATE
                    recipients = VALUES(recipients),
                    payload_json = VALUES(payload_json),
                    http_status = VALUES(http_status),
                    response_body = VALUES(response_body),
                    status = VALUES(status),
                    error_message = VALUES(error_message),
                    created_at = VALUES(created_at)
            """
            cursor.execute(
                insert_log_sql,
                (
                    adv_mon_id,
                    trigger_day,
                    arrival_date,
                    days_diff,
                    ",".join(recipient_emails),
                    json.dumps(payload),
                    http_status,
                    response_body,
                    status,
                    error_message,
                ),
            )
            conn.commit()

            if status == "SUCCESS":
                sent += 1
            else:
                failed += 1

            time.sleep(0.2)

        print("Done. processed={} sent={} skipped={} failed={}".format(processed, sent, skipped, failed))
        if debug_mode:
            print("DEBUG mode enabled. Payloads were written to: {}".format(debug_log_path))
        return 0

    except Exception as e:
        try:
            if conn:
                conn.rollback()
        except Exception:
            pass
        print("Error: {}".format(e))
        return 1
    finally:
        try:
            if cursor:
                cursor.close()
        except Exception:
            pass
        try:
            if conn:
                conn.close()
        except Exception:
            pass


if __name__ == "__main__":
    sys.exit(main())

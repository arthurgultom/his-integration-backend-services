import ftplib
import os
import sys
from datetime import datetime
from pathlib import Path

def _load_env():
    try:
        from dotenv import load_dotenv
        base_dir = Path(__file__).resolve().parent
        env_here = base_dir / ".env"
        if env_here.exists():
            load_dotenv(env_here)
        else:
            load_dotenv()
    except Exception:
        pass

def _resolve_path(base_path, candidate):
    if not candidate:
        return None
    p = Path(candidate)
    if p.is_absolute():
        return p
    if base_path:
        return Path(base_path) / candidate
    return p

def _append_log(log_path, filename, status, message):
    ts = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    log_line = f"{ts},{filename},{status},{message}\n"
    log_path.parent.mkdir(parents=True, exist_ok=True)
    with log_path.open("a", encoding="utf-8") as f:
        f.write(log_line)

def main():
    _load_env()
    app_path = os.getenv("APP_PATH", "")
    local_dir_env = os.getenv("IDS_LOCAL_PATH", "")
    remote_dir = os.getenv("IDS_PATH", "/")
    host = os.getenv("IDS_HOST")
    username = os.getenv("IDS_USERNAME")
    password = os.getenv("IDS_PASSWORD")
    log_file_env = os.getenv("AUTO_SEND_LOG_PATH", "auto-send-files-to-mds.log")
    internal_prefixes_env = os.getenv("AUTO_DELETE_INTERNAL_PREFIXES", "BPN,JTK,MDN,SMT,HMS")

    base_dir = Path(__file__).resolve().parent
    log_path = _resolve_path(app_path, log_file_env)
    if not log_path:
        log_path = base_dir / "auto-send-files-to-mds.log"
    log_path = Path(log_path)

    local_dir = _resolve_path(app_path, local_dir_env)
    if not local_dir:
        local_dir = base_dir / "files-outgoing"
    local_dir = Path(local_dir)
    local_dir.mkdir(parents=True, exist_ok=True)

    files = [p for p in local_dir.iterdir() if p.is_file()]
    if not files:
        _append_log(log_path, "-", "SKIP", "No files to upload.")
        print("Tidak ada file untuk diupload.")
        return

    internal_prefixes = {p.strip().upper() for p in internal_prefixes_env.split(",") if p.strip()}
    upload_files = []
    for f in files:
        prefix = f.name[1:4].upper() if len(f.name) >= 4 and f.name[0].upper() == "W" else ""
        if prefix and prefix in internal_prefixes:
            try:
                f.unlink()
                _append_log(log_path, f.name, "DELETED", f"Internal prefix={prefix}; deleted locally.")
                print(f"DELETE: {f.name}")
            except Exception as del_err:
                _append_log(log_path, f.name, "FAILED", f"Internal prefix={prefix}; failed delete local: {del_err}")
                print(f"GAGAL DELETE: {f.name} ({del_err})")
        else:
            upload_files.append(f)

    if not upload_files:
        _append_log(log_path, "-", "SKIP", "No files to upload after internal deletions.")
        print("Tidak ada file untuk diupload (setelah filter internal).")
        return

    if not host or not username or not password:
        msg = "Missing FTP credentials in env (IDS_HOST, IDS_USERNAME, IDS_PASSWORD)."
        print(msg)
        _append_log(log_path, "-", "FAILED", msg)
        sys.exit(1)

    try:
        ftp = ftplib.FTP(host)
        ftp.login(username, password)
        if remote_dir:
            ftp.cwd(remote_dir)
    except ftplib.all_errors as e:
        msg = f"FTP connection error: {e}"
        print(msg)
        _append_log(log_path, "-", "FAILED", msg)
        sys.exit(1)

    for f in upload_files:
        try:
            with f.open("rb") as fh:
                ftp.storbinary(f"STOR {f.name}", fh)
            _append_log(log_path, f.name, "SUCCESS", "Uploaded and deleted locally.")
            try:
                f.unlink()
            except Exception as del_err:
                _append_log(log_path, f.name, "WARNING", f"Uploaded but failed delete local: {del_err}")
            print(f"OK: {f.name}")
        except ftplib.all_errors as e:
            _append_log(log_path, f.name, "FAILED", str(e))
            print(f"GAGAL: {f.name} ({e})")
        except Exception as e:
            _append_log(log_path, f.name, "FAILED", str(e))
            print(f"GAGAL: {f.name} ({e})")

    try:
        ftp.quit()
    except Exception:
        pass

if __name__ == "__main__":
    main()

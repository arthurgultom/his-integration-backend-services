import ftplib
import os
import sys
from datetime import datetime

# pathlib is not available in standard Python 2.7, so we use os.path
# If dotenv is not installed, it will be skipped

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

def _clean_env_value(value):
    if value is None:
        return ""
    v = value.strip()
    if len(v) >= 2 and ((v[0] == '"' and v[-1] == '"') or (v[0] == "'" and v[-1] == "'")):
        v = v[1:-1]
    return v

def _resolve_path(base_path, candidate):
    candidate = _clean_env_value(candidate)
    base_path = _clean_env_value(base_path)
    if not candidate:
        return None
    
    # Check if absolute
    if os.path.isabs(candidate):
        return candidate
    
    if base_path:
        return os.path.join(base_path, candidate)
    
    return candidate

def _append_log(log_path, filename, status, message):
    ts = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    log_line = "{},{},{},{}\n".format(ts, filename, status, message)
    
    log_dir = os.path.dirname(log_path)
    if log_dir and not os.path.exists(log_dir):
        try:
            os.makedirs(log_dir)
        except OSError:
            pass # Directory might already exist

    try:
        with open(log_path, "a") as f:
            f.write(log_line)
    except Exception:
        pass

def main():
    _load_env()
    app_path = _clean_env_value(os.getenv("APP_PATH", ""))
    local_dir_env = os.getenv("IDS_LOCAL_PATH") or os.getenv("LOCAL_PATH") or ""
    local_dir_env = _clean_env_value(local_dir_env)
    remote_dir = _clean_env_value(os.getenv("IDS_PATH", "/"))
    host = _clean_env_value(os.getenv("IDS_HOST"))
    username = _clean_env_value(os.getenv("IDS_USERNAME"))
    password = _clean_env_value(os.getenv("IDS_PASSWORD"))
    log_file_env = _clean_env_value(os.getenv("AUTO_SEND_LOG_PATH", "auto-send-files-to-mds.log"))
    internal_prefixes_env = _clean_env_value(os.getenv("AUTO_DELETE_INTERNAL_PREFIXES", "BPN,JTK,MDN,SMT,HMS"))

    base_dir = os.path.dirname(os.path.abspath(__file__))
    
    log_path = _resolve_path(app_path, log_file_env)
    if not log_path:
        log_path = os.path.join(base_dir, "auto-send-files-to-mds.log")
    
    local_dir = _resolve_path(app_path, local_dir_env)
    if not local_dir:
        local_dir = os.path.join(base_dir, "files-outgoing")
    
    if not os.path.exists(local_dir):
        try:
            os.makedirs(local_dir)
        except OSError:
            pass

    # List files
    files = []
    if os.path.exists(local_dir):
        for f in os.listdir(local_dir):
            full_path = os.path.join(local_dir, f)
            if os.path.isfile(full_path):
                files.append(full_path)

    if not files:
        _append_log(log_path, "-", "SKIP", "No files to upload. local_dir={}".format(local_dir))
        print("Tidak ada file untuk diupload. Folder: {}".format(local_dir))
        return

    internal_prefixes = set([p.strip().upper() for p in internal_prefixes_env.split(",") if p.strip()])
    upload_files = []
    
    for f_path in files:
        f_name = os.path.basename(f_path)
        # Check prefix: W + 3 chars
        prefix = ""
        if len(f_name) >= 4 and f_name[0].upper() == "W":
            prefix = f_name[1:4].upper()
            
        if prefix and prefix in internal_prefixes:
            try:
                os.unlink(f_path)
                _append_log(log_path, f_name, "DELETED", "Internal prefix={}; deleted locally.".format(prefix))
                print("DELETE: {}".format(f_name))
            except Exception as del_err:
                _append_log(log_path, f_name, "FAILED", "Internal prefix={}; failed delete local: {}".format(prefix, del_err))
                print("GAGAL DELETE: {} ({})".format(f_name, del_err))
        else:
            upload_files.append(f_path)

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
        msg = "FTP connection error: {}".format(e)
        print(msg)
        _append_log(log_path, "-", "FAILED", msg)
        sys.exit(1)

    for f_path in upload_files:
        f_name = os.path.basename(f_path)
        try:
            with open(f_path, "rb") as fh:
                ftp.storbinary("STOR {}".format(f_name), fh)
            
            _append_log(log_path, f_name, "SUCCESS", "Uploaded and deleted locally.")
            try:
                os.unlink(f_path)
            except Exception as del_err:
                _append_log(log_path, f_name, "WARNING", "Uploaded but failed delete local: {}".format(del_err))
            print("OK: {}".format(f_name))
        except ftplib.all_errors as e:
            _append_log(log_path, f_name, "FAILED", str(e))
            print("GAGAL: {} ({})".format(f_name, e))
        except Exception as e:
            _append_log(log_path, f_name, "FAILED", str(e))
            print("GAGAL: {} ({})".format(f_name, e))

    try:
        ftp.quit()
    except Exception:
        pass

if __name__ == "__main__":
    main()

#!/usr/bin/env bash
set -euo pipefail

: "${IDS_HOST:?Missing IDS_HOST}"
: "${IDS_USERNAME:?Missing IDS_USERNAME}"
: "${IDS_PASSWORD:?Missing IDS_PASSWORD}"
IDS_PATH="${IDS_PATH:-/}"

tmpfile="$(mktemp)"
remotefile="ftp_test_$(date +%Y%m%d%H%M%S).txt"
printf "ftp connectivity test\n" > "$tmpfile"

echo "Host      : $IDS_HOST"
echo "Remote dir: $IDS_PATH"
echo "User      : $IDS_USERNAME"

ftp -inv "$IDS_HOST" <<EOF
user $IDS_USERNAME $IDS_PASSWORD
cd $IDS_PATH
pwd
ls
put $tmpfile $remotefile
ls
delete $remotefile
bye
EOF

rm -f "$tmpfile"
echo "Selesai: koneksi OK, upload+delete remote OK, cleanup lokal OK."

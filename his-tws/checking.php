<?php
// =========================
// CONFIG (sesuaikan di sini)
// =========================

$DB_HOST = 'localhost';
$DB_NAME = 'his_db_final_3';
$DB_USER = 'root';
$DB_PASS = 'SuperIntegrationS4h1s2025';

// Tabel history / log sinkronisasi (sama dengan kemarin)
$HISTORY_TABLE = 'tws_sync_file';

// Folder yang berisi file after dismantled
// (harus sama dengan yang dipakai saat sinkronisasi)
$FOLDER_PATH = 'C:/www/htdocs/his/protected/attachment/after_dismantled';

// Filter opsional: hanya cek id_afterdismantled dalam range tertentu
// Set null kalau mau cek semua
$ID_MIN = null; // contoh: 100000;
$ID_MAX = null; // contoh: 120000;

// Hanya cek baris dengan status sukses (1) atau semuanya?
$ONLY_SUCCESS = true; // true: hanya status=1, false: semua baris

// DRY RUN:
// - true  => tidak update DB, hanya simulasi + report
// - false => update DB beneran
$DRY_RUN = false;

// =========================
// END CONFIG
// =========================

if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}

function h($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function fail($message)
{
    echo '<pre style="color:#b00020">' . h($message) . '</pre>';
    exit(1);
}

if (!is_dir($FOLDER_PATH)) {
    fail('Folder tidak ditemukan: ' . $FOLDER_PATH);
}

try {
    $dsn = 'mysql:host=' . $DB_HOST . ';dbname=' . $DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));
} catch (Exception $e) {
    fail('Koneksi DB gagal: ' . $e->getMessage());
}

$where = array();
$params = array();

if ($ONLY_SUCCESS) {
    $where[] = 'status = 1';
}

if ($ID_MIN !== null) {
    $where[] = 'id_afterdismantled >= :min';
    $params[':min'] = (int)$ID_MIN;
}
if ($ID_MAX !== null) {
    $where[] = 'id_afterdismantled <= :max';
    $params[':max'] = (int)$ID_MAX;
}

$whereSql = '';
if (count($where) > 0) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

$safeHistoryTable = str_replace('`', '``', $HISTORY_TABLE);
$sql = 'SELECT id, id_afterdismantled, lt_no, filename, corrupt FROM `' . $safeHistoryTable . '` ' . $whereSql . ' ORDER BY id ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$total = count($rows);
$ok = 0;
$corrupt = 0;
$notFound = 0;
$failedUpdate = 0;

$updateStmt = $pdo->prepare(
    'UPDATE `' . $safeHistoryTable . '` SET corrupt = :corrupt WHERE id = :id'
);

echo '<pre>';

echo "Folder : {$FOLDER_PATH}\n";
echo "Table  : {$HISTORY_TABLE}\n";
echo 'Range  : ' . ($ID_MIN === null ? '-' : (string)$ID_MIN) . ' .. ' . ($ID_MAX === null ? '-' : (string)$ID_MAX) . "\n";
echo 'Filter : ' . ($ONLY_SUCCESS ? 'status=1 saja' : 'semua status') . "\n";
echo 'DryRun : ' . ($DRY_RUN ? 'YES' : 'NO') . "\n";
echo "Rows   : {$total}\n\n";

foreach ($rows as $r) {
    $rowId = isset($r['id']) ? (int)$r['id'] : 0; // primary key di tws_sync_file
    $idAfter = isset($r['id_afterdismantled']) ? (int)$r['id_afterdismantled'] : 0;
    $ltNo = isset($r['lt_no']) ? (string)$r['lt_no'] : '';
    $filename = isset($r['filename']) ? (string)$r['filename'] : '';
    $currentCorrupt = isset($r['corrupt']) ? (int)$r['corrupt'] : 0;

    $fullPath = rtrim($FOLDER_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if ($filename === '') {
        $notFound++;
        echo "[NO_FILENAME] sync_id={$rowId} id={$idAfter} lt_no={$ltNo} (kolom filename kosong)\n";
        $newCorrupt = 1;
    } elseif (!is_file($fullPath) || !is_readable($fullPath)) {
        $notFound++;
        echo "[NOTFOUND] sync_id={$rowId} id={$idAfter} lt_no={$ltNo} filename={$filename}\n";
        $newCorrupt = 1;
    } else {
        // Cek sederhana: ukuran file > 0 dan bisa dibuka
        $size = @filesize($fullPath);
        $handle = @fopen($fullPath, 'rb');

        if ($size === false || $size === 0 || $handle === false) {
            $corrupt++;
            echo "[CORRUPT] sync_id={$rowId} id={$idAfter} lt_no={$ltNo} filename={$filename} (size={$size})\n";
            $newCorrupt = 1;
        } else {
            // Tambahan cek untuk file gambar: pastikan getimagesize berhasil
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if ($ext === 'jpg' || $ext === 'jpeg' || $ext === 'png' || $ext === 'gif') {
                $imgInfo = @getimagesize($fullPath);
                if ($imgInfo === false) {
                    $corrupt++;
                    echo "[CORRUPT_IMG] sync_id={$rowId} id={$idAfter} lt_no={$ltNo} filename={$filename} (size={$size})\n";
                    $newCorrupt = 1;
                    fclose($handle);
                } else {
                    // Validasi tambahan: tipe gambar harus sesuai dengan ekstensi
                    $imgType = isset($imgInfo[2]) ? (int)$imgInfo[2] : 0; // 2 = image type constant
                    $expectedType = 0;
                    if ($ext === 'jpg' || $ext === 'jpeg') {
                        $expectedType = IMAGETYPE_JPEG;
                    } elseif ($ext === 'png') {
                        $expectedType = IMAGETYPE_PNG;
                    } elseif ($ext === 'gif') {
                        $expectedType = IMAGETYPE_GIF;
                    }

                    if ($expectedType !== 0 && $imgType !== 0 && $imgType !== $expectedType) {
                        // Ekstensi tidak cocok dengan tipe gambar sebenarnya
                        $corrupt++;
                        echo "[CORRUPT_EXT] sync_id={$rowId} id={$idAfter} lt_no={$ltNo} filename={$filename} (size={$size}) type={$imgType} expected={$expectedType}\n";
                        $newCorrupt = 1;
                        fclose($handle);
                    } else {
                        fclose($handle);
                        $ok++;
                        echo "[OK_IMG] sync_id={$rowId} id={$idAfter} lt_no={$ltNo} filename={$filename} (size={$size})\n";
                        $newCorrupt = 0;
                    }
                }
            } else {
                // Bukan gambar, pakai cek sederhana saja
                fclose($handle);
                $ok++;
                echo "[OK] sync_id={$rowId} id={$idAfter} lt_no={$ltNo} filename={$filename} (size={$size})\n";
                $newCorrupt = 0;
            }
        }
    }

    // Update kolom corrupt hanya jika berbeda
    if (!$DRY_RUN && $newCorrupt !== $currentCorrupt) {
        try {
            $updateStmt->execute(array(
                ':corrupt' => $newCorrupt,
                ':id' => $rowId,
            ));
        } catch (Exception $e) {
            $failedUpdate++;
            echo "  -> [UPDATE_FAILED] sync_id={$rowId} error=" . $e->getMessage() . "\n";
        }
    }
}

echo "\nSummary:\n";
echo "  OK (corrupt=0)    : {$ok}\n";
echo "  Corrupt / error    : {$corrupt}\n";
echo "  Not found / kosong : {$notFound}\n";
echo "  Update gagal       : {$failedUpdate}\n";

echo '</pre>';

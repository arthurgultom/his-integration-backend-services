<?php
// =========================
// CONFIG (sesuaikan di sini)
// =========================

$DB_HOST = 'localhost';
$DB_NAME = 'his_db_final_3';
$DB_USER = 'root';
$DB_PASS = 'SuperIntegrationS4h1s2025';

// Tabel utama (sumber data after dismantled)
$TABLE_NAME = 'tws_trs_technical_file_afterdismantled';

// Tabel history / log sinkronisasi
$HISTORY_TABLE = 'tws_sync_file';

// Folder yang berisi file (semua file ada di 1 folder, tanpa subfolder)
// Pastikan PHP process (web server) punya permission read folder tsb.
$FOLDER_PATH = 'C:/www/htdocs/his/protected/attachment/after_dismantled';

// Range id (opsional). Set null untuk tidak pakai range.
$ID_MIN = 100000;
$ID_MAX = 118773;

// DRY RUN:
// - true  => tidak update DB, hanya simulasi + report
// - false => update DB beneran
$DRY_RUN = true;

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

// Scan folder dan buat mapping: id => [file1, file2, ...]
$idToFiles = array();
$dir = opendir($FOLDER_PATH);
if ($dir === false) {
    fail('Tidak bisa membuka folder: ' . $FOLDER_PATH);
}

while (($entry = readdir($dir)) !== false) {
    if ($entry === '.' || $entry === '..') {
        continue;
    }

    $fullPath = rtrim($FOLDER_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $entry;
    if (!is_file($fullPath)) {
        continue;
    }

    // Ambil prefix id dari nama file: diawali deretan angka (id) lalu 1 karakter non-digit sebagai pemisah
    // Contoh cocok: "100000-xxx.jpg", "100000 Picture1.jpeg", "100000_Picture1.jpeg", termasuk jika pakai dash Unicode
    if (preg_match('/^(\d+)\D/u', $entry, $m) !== 1) {
        continue;
    }

    $id = (int)$m[1];
    if (!isset($idToFiles[$id])) {
        $idToFiles[$id] = array();
    }
    $idToFiles[$id][] = $entry;
}
closedir($dir);

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
if ($ID_MIN !== null) {
    $where[] = 'id >= :min';
    $params[':min'] = (int)$ID_MIN;
}
if ($ID_MAX !== null) {
    $where[] = 'id <= :max';
    $params[':max'] = (int)$ID_MAX;
}
$whereSql = '';
if (count($where) > 0) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

$safeTable = str_replace('`', '``', $TABLE_NAME);
$sql = 'SELECT id, lt_no, filename FROM `' . $safeTable . '` ' . $whereSql . ' ORDER BY created_date ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$updateStmt = $pdo->prepare(
    'UPDATE `' . $safeTable . '` SET filename = :filename, modified_date = NOW() WHERE id = :id'
);

// Siapkan prepared statement untuk insert ke tabel history
$safeHistoryTable = str_replace('`', '``', $HISTORY_TABLE);
$historyInsertStmt = $pdo->prepare(
    'INSERT INTO `' . $safeHistoryTable . '` (id_afterdismantled, lt_no, filename_original, filename, notes, status) 
     VALUES (:id_afterdismantled, :lt_no, :filename_original, :filename, :notes, :status)'
);

// Pre-load data history untuk cek apakah id sudah pernah sukses (status = 1)
$processedIds = array();
try {
    $historyCheckSql = 'SELECT id_afterdismantled, status FROM `' . $safeHistoryTable . '`';
    $historyCheckStmt = $pdo->query($historyCheckSql);
    $historyRows = $historyCheckStmt->fetchAll();
    foreach ($historyRows as $hr) {
        $hid = (int)$hr['id_afterdismantled'];
        $hstatus = (int)$hr['status'];
        // Simpan status tertinggi (1 dianggap sudah sukses)
        if (!isset($processedIds[$hid]) || $hstatus === 1) {
            $processedIds[$hid] = $hstatus;
        }
    }
} catch (Exception $e) {
    // Jika tabel history belum ada atau error lain, lanjut saja tanpa pre-check
}

$total = count($rows);
$updated = 0;
$skippedNotFound = 0;
$skippedMultiple = 0;
$skippedAlreadySame = 0;
$skippedHistory = 0;

echo '<pre>';
echo "Folder: {$FOLDER_PATH}\n";
echo "Table : {$TABLE_NAME}\n";
echo 'Range : ' . ($ID_MIN === null ? '-' : (string)$ID_MIN) . ' .. ' . ($ID_MAX === null ? '-' : (string)$ID_MAX) . "\n";
echo 'DryRun: ' . ($DRY_RUN ? 'YES' : 'NO') . "\n";
echo "Rows  : {$total}\n\n";

foreach ($rows as $r) {
    $id = (int)$r['id'];
    $ltNo = isset($r['lt_no']) ? (string)$r['lt_no'] : '';
    $dbFilename = isset($r['filename']) ? (string)$r['filename'] : '';

    // Cek history: jika sudah pernah berhasil (status = 1), skip supaya tidak double proses
    if (isset($processedIds[$id]) && (int)$processedIds[$id] === 1) {
        $skippedHistory++;
        echo "[SKIP_HISTORY] id={$id} (lt_no: {$ltNo}) sudah pernah sukses, lewati.\n";
        continue;
    }

    if (!isset($idToFiles[$id]) || count($idToFiles[$id]) === 0) {
        $skippedNotFound++;
        echo "[NOTFOUND] id={$id} (db: {$dbFilename})\n";

        // Catat ke history sebagai gagal (status = 0)
        if (!$DRY_RUN) {
            $historyInsertStmt->execute(array(
                ':id_afterdismantled' => $id,
                ':lt_no' => $ltNo,
                ':filename_original' => $dbFilename,
                ':filename' => $dbFilename,
                ':notes' => 'File dengan prefix id tidak ditemukan di folder',
                ':status' => 0,
            ));
        }

        continue;
    }

    // Jika ada lebih dari 1 file untuk id yang sama, ambil yang modified time paling baru
    $filesForId = $idToFiles[$id];
    if (count($filesForId) > 1) {
        echo "[MULTIPLE] id={$id} candidates (ambil yang modified paling baru):\n";

        $latestFile = null;
        $latestMtime = null;
        foreach ($filesForId as $f) {
            $full = rtrim($FOLDER_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $f;
            $mtime = @filemtime($full);
            echo "  - {$f}" . ($mtime !== false ? ('  mtime=' . date('Y-m-d H:i:s', $mtime)) : '  mtime=?') . "\n";

            if ($mtime === false) {
                continue;
            }
            if ($latestMtime === null || $mtime > $latestMtime) {
                $latestMtime = $mtime;
                $latestFile = $f;
            }
        }

        if ($latestFile === null) {
            // Tidak bisa baca mtime satupun, treat sebagai notfound/multiple tanpa pilihan
            $skippedMultiple++;
            echo "  -> tidak bisa menentukan file terbaru (mtime gagal), skip id={$id}\n";

            // Catat ke history sebagai gagal
            if (!$DRY_RUN) {
                $historyInsertStmt->execute(array(
                    ':id_afterdismantled' => $id,
                    ':lt_no' => $ltNo,
                    ':filename_original' => $dbFilename,
                    ':filename' => $dbFilename,
                    ':notes' => 'Multiple file untuk id, tetapi gagal membaca mtime semua kandidat',
                    ':status' => 0,
                ));
            }

            continue;
        }

        echo "  -> chosen: {$latestFile}\n";
        $actualFilename = $latestFile;
    } else {
        $actualFilename = $filesForId[0];
    }

    if ($actualFilename === $dbFilename) {
        $skippedAlreadySame++;
        echo "[SAME] id={$id} filename sudah sama ({$dbFilename}), tidak ada perubahan.\n";

        // Tetap catat ke history sebagai sukses jika belum pernah tercatat
        if (!$DRY_RUN) {
            $historyInsertStmt->execute(array(
                ':id_afterdismantled' => $id,
                ':lt_no' => $ltNo,
                ':filename_original' => $dbFilename,
                ':filename' => $dbFilename,
                ':notes' => 'Filename sudah sama antara DB dan folder, tidak ada perubahan',
                ':status' => 1,
            ));
        }

        continue;
    }

    echo "[UPDATE] id={$id}  db: {$dbFilename}  =>  folder: {$actualFilename}\n";

    if (!$DRY_RUN) {
        // Update tabel utama
        $updateStmt->execute(array(
            ':filename' => $actualFilename,
            ':id' => $id,
        ));

        // Insert ke history sebagai sukses
        $historyInsertStmt->execute(array(
            ':id_afterdismantled' => $id,
            ':lt_no' => $ltNo,
            ':filename_original' => $dbFilename,
            ':filename' => $actualFilename,
            ':notes' => 'Update filename dari DB mengikuti nama file di folder',
            ':status' => 1,
        ));
    }

    $updated++;
}

echo "\nSummary:\n";
echo "  Updated           : {$updated}\n";
echo "  Skipped (notfound) : {$skippedNotFound}\n";
echo "  Skipped (multiple) : {$skippedMultiple}\n";
echo "  Skipped (same)     : {$skippedAlreadySame}\n";
echo "  Skipped (history)  : {$skippedHistory}\n";
echo '</pre>';


<?php
// Show all errors except warnings and notices like undefined variables
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
?>

<?php
session_start();
function display_msg() {
    if (isset($_SESSION['msg'])) {
        $msg = $_SESSION['msg'];
        echo '<div class="alert alert-' . htmlspecialchars($msg['type']) . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($msg['text']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['msg']);
    }
}
date_default_timezone_set('Asia/Karachi');

// --- Database Setup (db.php) ---
define('DB_FILE', 'madrasa.sqlite');
define('UPLOAD_DIR', 'uploads/');
define('BACKUP_DIR', 'backups/');

if (!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
if (!file_exists(BACKUP_DIR)) mkdir(BACKUP_DIR, 0777, true);

function get_db() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new PDO('sqlite:' . __DIR__ . '/madrasa.sqlite');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception $e) {
            exit('Database connection failed');
        }
    }
    return $db;
}

function init_db() {
    $db = get_db();
    $db->exec("CREATE TABLE IF NOT EXISTS cfg ( k TEXT PRIMARY KEY, v TEXT )");
    $db->exec("CREATE TABLE IF NOT EXISTS cl ( id INTEGER PRIMARY KEY AUTOINCREMENT, n TEXT NOT NULL UNIQUE, tch_id INTEGER, FOREIGN KEY (tch_id) REFERENCES tch(id) ON DELETE SET NULL )");
    $db->exec("CREATE TABLE IF NOT EXISTS tch ( id INTEGER PRIMARY KEY AUTOINCREMENT, n TEXT NOT NULL, s TEXT, c TEXT, adr TEXT, jd DATE, sal REAL, cf1 TEXT, cf2 TEXT )");
    $db->exec("CREATE TABLE IF NOT EXISTS st ( id INTEGER PRIMARY KEY AUTOINCREMENT, n TEXT NOT NULL, fn TEXT, a INTEGER, cl_id INTEGER, ad DATE, c TEXT, adr TEXT, cf1 TEXT, cf2 TEXT, FOREIGN KEY (cl_id) REFERENCES cl(id) ON DELETE SET NULL )");
    $db->exec("CREATE TABLE IF NOT EXISTS att ( id INTEGER PRIMARY KEY AUTOINCREMENT, st_id INTEGER NOT NULL, dt DATE NOT NULL, st TEXT NOT NULL, UNIQUE (st_id, dt), FOREIGN KEY (st_id) REFERENCES st(id) ON DELETE CASCADE )"); // H=Hazir, G=Ghair Hazir, R=Rukhsat
    $db->exec("CREATE TABLE IF NOT EXISTS fp ( id INTEGER PRIMARY KEY AUTOINCREMENT, st_id INTEGER NOT NULL, amt REAL NOT NULL, dt DATE NOT NULL, m INTEGER NOT NULL, y INTEGER NOT NULL, st TEXT NOT NULL, FOREIGN KEY (st_id) REFERENCES st(id) ON DELETE CASCADE )"); // 'Ada Shuda', 'Baqaya'
    $db->exec("CREATE TABLE IF NOT EXISTS sp ( id INTEGER PRIMARY KEY AUTOINCREMENT, tch_id INTEGER NOT NULL, amt REAL NOT NULL, dt DATE NOT NULL, m INTEGER NOT NULL, y INTEGER NOT NULL, FOREIGN KEY (tch_id) REFERENCES tch(id) ON DELETE CASCADE )");
    $db->exec("CREATE TABLE IF NOT EXISTS inv ( id INTEGER PRIMARY KEY AUTOINCREMENT, itm TEXT NOT NULL, qty INTEGER NOT NULL, dt DATE NOT NULL )");
    $db->exec("CREATE TABLE IF NOT EXISTS inc ( id INTEGER PRIMARY KEY AUTOINCREMENT, src TEXT NOT NULL, amt REAL NOT NULL, dt DATE NOT NULL )");
    $db->exec("CREATE TABLE IF NOT EXISTS exp ( id INTEGER PRIMARY KEY AUTOINCREMENT, dsc TEXT NOT NULL, amt REAL NOT NULL, dt DATE NOT NULL )");

    // Default settings
    $defaults = [
        'madrasa_name' => 'میرا مدرسہ', 'madrasa_address' => 'میرا پتہ', 'madrasa_phone' => '000-0000000',
        'madrasa_logo' => '', 'st_cf1_lbl' => 'اضافی فیلڈ 1', 'st_cf2_lbl' => 'اضافی فیلڈ 2',
        'tch_cf1_lbl' => 'اضافی فیلڈ 1', 'tch_cf2_lbl' => 'اضافی فیلڈ 2'
    ];
    $stmt = $db->prepare("INSERT OR IGNORE INTO cfg (k, v) VALUES (?, ?)");
    foreach ($defaults as $k => $v) {
        $stmt->execute([$k, $v]);
    }
}

init_db();

// --- Helper Functions ---
function get_cfg($k) {
    $db = get_db();
    $stmt = $db->prepare("SELECT v FROM cfg WHERE k = ?");
    $stmt->execute([$k]);
    $r = $stmt->fetchColumn();
    return $r !== false ? $r : null;
}
function upd_cfg($k, $v) {
    $db = get_db();
    $stmt = $db->prepare("UPDATE cfg SET v = ? WHERE k = ?");
    if (!$stmt->execute([$v, $k])) {
        error_log("Failed to update config: k=$k");
        return false;
    }
     // Check if update actually changed rows, if not, try inserting
    if ($stmt->rowCount() === 0) {
        $stmt_insert = $db->prepare("INSERT OR IGNORE INTO cfg (k, v) VALUES (?, ?)");
        return $stmt_insert->execute([$k, $v]);
    }
    return true;
}


function esc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function get_cls() { return get_db()->query("SELECT id, n FROM cl ORDER BY n")->fetchAll(); }
function get_cln($id) { $s = get_db()->prepare("SELECT n FROM cl WHERE id = ?"); $s->execute([$id]); return $s->fetchColumn() ?: 'کوئی نہیں'; }
function get_tchs() { return get_db()->query("SELECT id, n FROM tch ORDER BY n")->fetchAll(); }
function get_tchn($id) { $s = get_db()->prepare("SELECT n FROM tch WHERE id = ?"); $s->execute([$id]); return $s->fetchColumn() ?: 'کوئی نہیں'; }
function get_sts() { return get_db()->query("SELECT id, n FROM st ORDER BY n")->fetchAll(); }
function get_stn($id) { $s = get_db()->prepare("SELECT n FROM st WHERE id = ?"); $s->execute([$id]); return $s->fetchColumn() ?: 'نامعلوم'; }
function h_msg($text, $type = 'success') {
    $_SESSION['msg'] = ['text' => $text, 'type' => $type];
}
function s_msg() {
    if (isset($_SESSION['msg'])) {
        echo '<div class="alert alert-' . esc($_SESSION['msg']['type']) . ' alert-dismissible fade show" role="alert">' . esc($_SESSION['msg']['txt']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="بند کریں"></button></div>';
        unset($_SESSION['msg']);
    }
}
function f_date($d) { return $d ? date('Y-m-d', strtotime($d)) : ''; }
function u_date($d) { return $d ? date('d-m-Y', strtotime($d)) : ''; }

// --- CRUD Operations ---
// Students (st)
function add_st($d) {
    $db = get_db();
    $sql = "INSERT INTO st (n, fn, a, cl_id, ad, c, adr, cf1, cf2) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$d['n'], $d['fn'], $d['a'], $d['cl_id'] ?: null, $d['ad'] ?: null, $d['c'], $d['adr'], $d['cf1'], $d['cf2']]);
}
function upd_st($id, $d) {
    $db = get_db();
    $sql = "UPDATE st SET n=?, fn=?, a=?, cl_id=?, ad=?, c=?, adr=?, cf1=?, cf2=? WHERE id=?";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$d['n'], $d['fn'], $d['a'], $d['cl_id'] ?: null, $d['ad'] ?: null, $d['c'], $d['adr'], $d['cf1'], $d['cf2'], $id]);
}
function get_st($id) { $s = get_db()->prepare("SELECT * FROM st WHERE id = ?"); $s->execute([$id]); return $s->fetch(); }
function get_all_st($cl_id = null) {
    $db = get_db();
    $sql = "SELECT st.*, cl.n as cln FROM st LEFT JOIN cl ON st.cl_id = cl.id";
    $params = [];
    if ($cl_id) { $sql .= " WHERE st.cl_id = ?"; $params[] = $cl_id; }
    $sql .= " ORDER BY st.n";
    $s = $db->prepare($sql); $s->execute($params); return $s->fetchAll();
}
function del_st($id) { $s = get_db()->prepare("DELETE FROM st WHERE id = ?"); return $s->execute([$id]); }

// Teachers (tch)
function add_tch($d) {
    $db = get_db();
    $sql = "INSERT INTO tch (n, s, c, adr, jd, sal, cf1, cf2) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$d['n'], $d['s'], $d['c'], $d['adr'], $d['jd'] ?: null, $d['sal'] ?: null, $d['cf1'], $d['cf2']]);
}
function upd_tch($id, $d) {
    $db = get_db();
    $sql = "UPDATE tch SET n=?, s=?, c=?, adr=?, jd=?, sal=?, cf1=?, cf2=? WHERE id=?";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$d['n'], $d['s'], $d['c'], $d['adr'], $d['jd'] ?: null, $d['sal'] ?: null, $d['cf1'], $d['cf2'], $id]);
}
function get_tch($id) { $s = get_db()->prepare("SELECT * FROM tch WHERE id = ?"); $s->execute([$id]); return $s->fetch(); }
function get_all_tch() { $s = get_db()->prepare("SELECT * FROM tch ORDER BY n"); $s->execute(); return $s->fetchAll(); }
function del_tch($id) { $s = get_db()->prepare("DELETE FROM tch WHERE id = ?"); return $s->execute([$id]); }

// Classes (cl)
function add_cl($d) { $s = get_db()->prepare("INSERT INTO cl (n, tch_id) VALUES (?, ?)"); return $s->execute([$d['n'], $d['tch_id'] ?: null]); }
function upd_cl($id, $d) { $s = get_db()->prepare("UPDATE cl SET n=?, tch_id=? WHERE id=?"); return $s->execute([$d['n'], $d['tch_id'] ?: null, $id]); }
function get_cl($id) { $s = get_db()->prepare("SELECT cl.*, tch.n as tchn FROM cl LEFT JOIN tch ON cl.tch_id = tch.id WHERE cl.id = ?"); $s->execute([$id]); return $s->fetch(); }
function get_all_cl() { $s = get_db()->prepare("SELECT cl.*, tch.n as tchn FROM cl LEFT JOIN tch ON cl.tch_id = tch.id ORDER BY cl.n"); $s->execute(); return $s->fetchAll(); }
function del_cl($id) { $s = get_db()->prepare("DELETE FROM cl WHERE id = ?"); return $s->execute([$id]); }

// Attendance (att)
function mark_att($dt, $atts) {
    $db = get_db();
    $sql = "INSERT OR REPLACE INTO att (st_id, dt, st) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);
    $check_st = $db->prepare("SELECT id FROM st WHERE id = ?");
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dt) || !strtotime($dt)) {
        return false;
    }
    
    if (empty($atts) || !is_array($atts)) {
        return false;
    }
    
    try {
        $valid_records = false;
        foreach ($atts as $st_id => $st) {
            if (!is_numeric($st_id) || !in_array($st, ['H', 'G', 'R'], true)) {
                continue;
            }
            
            $check_st->execute([$st_id]);
            if (!$check_st->fetch()) {
                continue;
            }
            
            $result = $stmt->execute([$st_id, $dt, $st]);
            if ($result) {
                $valid_records = true;
            }
        }
        return $valid_records;
    } catch (Exception $e) {
        return false;
    }
}
function get_att_for_dt($dt, $cl_id = null) {
    $log_file = __DIR__ . '/attendance_debug.log';
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] get_att_for_dt called: dt=$dt, cl_id=" . ($cl_id ?? 'null') . "\n", FILE_APPEND);
    
    $db = get_db();
    $st_sql = "SELECT id, n FROM st";
    $st_params = [];
    if ($cl_id) {
        $st_sql .= " WHERE cl_id = ?";
        $st_params[] = $cl_id;
    }
    $st_sql .= " ORDER BY n";
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Student query: $st_sql, params: " . json_encode($st_params) . "\n", FILE_APPEND);
    
    try {
        $st_stmt = $db->prepare($st_sql);
        $st_stmt->execute($st_params);
        $students = $st_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Students fetched: " . json_encode($students) . "\n", FILE_APPEND);
        
        if (empty($students)) {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] No students found for dt=$dt, cl_id=" . ($cl_id ?? 'null') . "\n", FILE_APPEND);
            return [];
        }
        
        $att_sql = "SELECT st_id, st FROM att WHERE dt = ? AND st_id IN (" . implode(',', array_fill(0, count($students), '?')) . ")";
        $att_params = array_merge([$dt], array_keys($students));
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Attendance query: $att_sql, params: " . json_encode($att_params) . "\n", FILE_APPEND);
        
        $att_stmt = $db->prepare($att_sql);
        $att_stmt->execute($att_params);
        $attendance_data = $att_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Attendance data: " . json_encode($attendance_data) . "\n", FILE_APPEND);
        
        $result = [];
        foreach ($students as $st_id => $st_name) {
            $result[$st_id] = [
                'n' => $st_name,
                'st' => $attendance_data[$st_id] ?? ''
            ];
        }
        
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Final attendance data for dt=$dt: " . json_encode($result) . "\n", FILE_APPEND);
        return $result;
    } catch (Exception $e) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] get_att_for_dt failed: " . $e->getMessage() . "\n", FILE_APPEND);
        return [];
    }
}
function get_att_rpt($st_id = null, $cl_id = null, $start_dt = null, $end_dt = null) {
    $db = get_db();
    $sql = "SELECT att.dt, st.n as stn, cl.n as cln, att.st 
            FROM att 
            JOIN st ON att.st_id = st.id 
            LEFT JOIN cl ON st.cl_id = cl.id 
            WHERE 1=1";
    $params = [];
    
    if ($st_id) {
        $sql .= " AND att.st_id = ?";
        $params[] = $st_id;
    }
    if ($cl_id) {
        $sql .= " AND st.cl_id = ?";
        $params[] = $cl_id;
    }
    if ($start_dt) {
        $sql .= " AND att.dt >= ?";
        $params[] = $start_dt;
    }
    if ($end_dt) {
        $sql .= " AND att.dt <= ?";
        $params[] = $end_dt;
Trailer:     }
    $sql .= " ORDER BY att.dt DESC, st.n ASC";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        error_log("Attendance report query: " . $sql . ", params: " . json_encode($params) . ", rows: " . count($results));
        return $results;
    } catch (Exception $e) {
        error_log("Attendance report failed: " . $e->getMessage());
        return [];
    }
}


// Fee Payments (fp)
function add_fp($d) {
    $db = get_db();
    $sql = "INSERT INTO fp (st_id, amt, dt, m, y, st) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$d['st_id'], $d['amt'], $d['dt'], $d['m'], $d['y'], $d['st']]);
}
function upd_fp($id, $d) {
    $db = get_db();
    $sql = "UPDATE fp SET st_id=?, amt=?, dt=?, m=?, y=?, st=? WHERE id=?";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$d['st_id'], $d['amt'], $d['dt'], $d['m'], $d['y'], $d['st'], $id]);
}
function get_fp($id) { $s = get_db()->prepare("SELECT * FROM fp WHERE id = ?"); $s->execute([$id]); return $s->fetch(); }
function get_all_fp($st_id = null, $cl_id = null, $m = null, $y = null, $status = null) {
    $db = get_db();
    $sql = "SELECT fp.*, st.n as stn, st.fn as stfn, cl.n as cln
            FROM fp
            JOIN st ON fp.st_id = st.id
            LEFT JOIN cl ON st.cl_id = cl.id WHERE 1=1";
    $params = [];
    if ($st_id) { $sql .= " AND fp.st_id = ?"; $params[] = $st_id; }
    if ($cl_id) { $sql .= " AND st.cl_id = ?"; $params[] = $cl_id; }
    if ($m) { $sql .= " AND fp.m = ?"; $params[] = $m; }
    if ($y) { $sql .= " AND fp.y = ?"; $params[] = $y; }
    if ($status) { $sql .= " AND fp.st = ?"; $params[] = $status; }
    $sql .= " ORDER BY fp.y DESC, fp.m DESC, st.n ASC";
    $s = $db->prepare($sql); $s->execute($params); return $s->fetchAll();
}
function del_fp($id) { $s = get_db()->prepare("DELETE FROM fp WHERE id = ?"); return $s->execute([$id]); }

// Salary Payments (sp)
function add_sp($d) {
    $db = get_db();
    $sql = "INSERT INTO sp (tch_id, amt, dt, m, y) VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$d['tch_id'], $d['amt'], $d['dt'], $d['m'], $d['y']]);
}
function upd_sp($id, $d) {
    $db = get_db();
    $sql = "UPDATE sp SET tch_id=?, amt=?, dt=?, m=?, y=? WHERE id=?";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$d['tch_id'], $d['amt'], $d['dt'], $d['m'], $d['y'], $id]);
}
function get_sp($id) { $s = get_db()->prepare("SELECT * FROM sp WHERE id = ?"); $s->execute([$id]); return $s->fetch(); }
function get_all_sp($tch_id = null, $m = null, $y = null) {
    $db = get_db();
    $sql = "SELECT sp.*, tch.n as tchn
            FROM sp
            JOIN tch ON sp.tch_id = tch.id WHERE 1=1";
    $params = [];
    if ($tch_id) { $sql .= " AND sp.tch_id = ?"; $params[] = $tch_id; }
    if ($m) { $sql .= " AND sp.m = ?"; $params[] = $m; }
    if ($y) { $sql .= " AND sp.y = ?"; $params[] = $y; }
    $sql .= " ORDER BY sp.y DESC, sp.m DESC, tch.n ASC";
    $s = $db->prepare($sql); $s->execute($params); return $s->fetchAll();
}
function del_sp($id) { $s = get_db()->prepare("DELETE FROM sp WHERE id = ?"); return $s->execute([$id]); }

// Inventory (inv)
function add_inv($d) { $s = get_db()->prepare("INSERT INTO inv (itm, qty, dt) VALUES (?, ?, ?)"); return $s->execute([$d['itm'], $d['qty'], $d['dt']]); }
function upd_inv($id, $d) { $s = get_db()->prepare("UPDATE inv SET itm=?, qty=?, dt=? WHERE id=?"); return $s->execute([$d['itm'], $d['qty'], $d['dt'], $id]); }
function get_inv($id) { $s = get_db()->prepare("SELECT * FROM inv WHERE id = ?"); $s->execute([$id]); return $s->fetch(); }
function get_all_inv() { $s = get_db()->prepare("SELECT * FROM inv ORDER BY dt DESC"); $s->execute(); return $s->fetchAll(); }
function del_inv($id) { $s = get_db()->prepare("DELETE FROM inv WHERE id = ?"); return $s->execute([$id]); }

// Income (inc)
function add_inc($d) { $s = get_db()->prepare("INSERT INTO inc (src, amt, dt) VALUES (?, ?, ?)"); return $s->execute([$d['src'], $d['amt'], $d['dt']]); }
function upd_inc($id, $d) { $s = get_db()->prepare("UPDATE inc SET src=?, amt=?, dt=? WHERE id=?"); return $s->execute([$d['src'], $d['amt'], $d['dt'], $id]); }
function get_inc($id) { $s = get_db()->prepare("SELECT * FROM inc WHERE id = ?"); $s->execute([$id]); return $s->fetch(); }
function get_all_inc() { $s = get_db()->prepare("SELECT * FROM inc ORDER BY dt DESC"); $s->execute(); return $s->fetchAll(); }
function del_inc($id) { $s = get_db()->prepare("DELETE FROM inc WHERE id = ?"); return $s->execute([$id]); }

// Expenses (exp)
function add_exp($d) { $s = get_db()->prepare("INSERT INTO exp (dsc, amt, dt) VALUES (?, ?, ?)"); return $s->execute([$d['dsc'], $d['amt'], $d['dt']]); }
function upd_exp($id, $d) { $s = get_db()->prepare("UPDATE exp SET dsc=?, amt=?, dt=? WHERE id=?"); return $s->execute([$d['dsc'], $d['amt'], $d['dt'], $id]); }
function get_exp($id) { $s = get_db()->prepare("SELECT * FROM exp WHERE id = ?"); $s->execute([$id]); return $s->fetch(); }
function get_all_exp() { $s = get_db()->prepare("SELECT * FROM exp ORDER BY dt DESC"); $s->execute(); return $s->fetchAll(); }
function del_exp($id) { $s = get_db()->prepare("DELETE FROM exp WHERE id = ?"); return $s->execute([$id]); }

// --- Actions ---
$act = $_POST['act'] ?? null;
$pg = $_GET['pg'] ?? 'dash';
$id = $_GET['id'] ?? null;
$dt = $_GET['dt'] ?? date('Y-m-d');
$cl_id_filter = $_GET['cl_id'] ?? null;
$m_filter = $_GET['m'] ?? null;
$y_filter = $_GET['y'] ?? date('Y');
$st_filter = $_GET['st'] ?? null; // For fee status
$rep = $_GET['rep'] ?? null; // For reports section
$export = $_GET['export'] ?? null; // For CSV export


// Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act) {
$log_file = __DIR__ . '/attendance_debug.log';
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Main POST processing started\n", FILE_APPEND);
    
    $db = get_db();
    $data = $_POST;
    $action = $data['act'] ?? '';
    unset($data['act']);
    
    try {
        $db->beginTransaction();
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Transaction started for action=$action\n", FILE_APPEND);
        
        switch ($action) {
				case 'mark_att':
                $att_date = $data['dt'] ?? date('Y-m-d');
                $cl_id = $data['cl_id_filter'] ?? '';
                unset($data['dt'], $data['cl_id_filter']);
                
                $valid_atts = array_filter($data, function($st) {
                    return in_array($st, ['H', 'G', 'R'], true);
                });
                
                if (empty($valid_atts)) {
                    $_SESSION['msg'] = ['text' => 'کوئی درست حاضری ڈیٹا موصول نہیں ہوا۔', 'type' => 'danger'];
                } elseif (mark_att($att_date, $valid_atts)) {
                    $_SESSION['msg'] = ['text' => $att_date . ' کی حاضری محفوظ ہو گئی۔', 'type' => 'success'];
                } else {
                    $_SESSION['msg'] = ['text' => 'حاضری محفوظ کرنے میں ناکامی۔', 'type' => 'danger'];
                }
                header('Location: ?pg=att&dt=' . $att_date . '&cl_id=' . $cl_id);
                break;
            // Students
            case 'add_st': if(add_st($data)) h_msg('طالب علم شامل کر دیا گیا۔'); else h_msg('طالب علم شامل کرنے میں ناکامی۔', 'danger'); break;
            case 'upd_st': if(upd_st($id, $data)) h_msg('طالب علم کی معلومات اپ ڈیٹ ہو گئیں۔'); else h_msg('طالب علم کی معلومات اپ ڈیٹ کرنے میں ناکامی۔', 'danger'); break;
            case 'del_st': if(del_st($id)) h_msg('طالب علم حذف کر دیا گیا۔'); else h_msg('طالب علم حذف کرنے میں ناکامی۔', 'danger'); $pg = 'st'; break; // Redirect back to list after delete
            // Teachers
            case 'add_tch': if(add_tch($data)) h_msg('استاد شامل کر دیا گیا۔'); else h_msg('استاد شامل کرنے میں ناکامی۔', 'danger'); break;
            case 'upd_tch': if(upd_tch($id, $data)) h_msg('استاد کی معلومات اپ ڈیٹ ہو گئیں۔'); else h_msg('استاد کی معلومات اپ ڈیٹ کرنے میں ناکامی۔', 'danger'); break;
            case 'del_tch': if(del_tch($id)) h_msg('استاد حذف کر دیا گیا۔'); else h_msg('استاد حذف کرنے میں ناکامی۔', 'danger'); $pg = 'tch'; break;
            // Classes
            case 'add_cl': if(add_cl($data)) h_msg('کلاس شامل کر دی گئی۔'); else h_msg('کلاس شامل کرنے میں ناکامی۔', 'danger'); break;
            case 'upd_cl': if(upd_cl($id, $data)) h_msg('کلاس کی معلومات اپ ڈیٹ ہو گئیں۔'); else h_msg('کلاس کی معلومات اپ ڈیٹ کرنے میں ناکامی۔', 'danger'); break;
            case 'del_cl': if(del_cl($id)) h_msg('کلاس حذف کر دی گئی۔'); else h_msg('کلاس حذف کرنے میں ناکامی۔', 'danger'); $pg = 'cl'; break;
             // Attendance
case 'mark_att':
    $log_file = __DIR__ . '/attendance_debug.log';
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] POST handler for mark_att: raw_data=" . json_encode($_POST) . "\n", FILE_APPEND);
    
    $att_date = $data['dt'] ?? date('Y-m-d');
    unset($data['dt'], $data['cl_id_filter']);
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Processed attendance data for dt=$att_date: " . json_encode($data) . "\n", FILE_APPEND);
    
    $valid_atts = array_filter($data, function($st) {
        return in_array($st, ['H', 'G', 'R'], true);
    });
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Valid attendance data for dt=$att_date: " . json_encode($valid_atts) . "\n", FILE_APPEND);
    
    if (empty($valid_atts)) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] No valid attendance data for dt=$att_date\n", FILE_APPEND);
        h_msg('کوئی درست حاضری ڈیٹا موصول نہیں ہوا۔', 'danger');
    } elseif (mark_att($att_date, $valid_atts)) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Attendance marked successfully for dt=$att_date\n", FILE_APPEND);
        $db = get_db();
        $records = $db->query("SELECT * FROM att WHERE dt = '$att_date'")->fetchAll(PDO::FETCH_ASSOC);
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Post-mark check for dt=$att_date: " . json_encode($records) . "\n", FILE_APPEND);
        h_msg($att_date . ' کی حاضری محفوظ ہو گئی۔');
    } else {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Attendance marking failed for dt=$att_date\n", FILE_APPEND);
        h_msg('حاضری محفوظ کرنے میں ناکامی۔', 'danger');
    }
    header('Location: ?pg=att&dt=' . $att_date . '&cl_id=' . ($_POST['cl_id_filter'] ?? ''));
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Redirecting to ?pg=att&dt=$att_date&cl_id=" . ($_POST['cl_id_filter'] ?? '') . "\n", FILE_APPEND);
    exit;
    break;
            // Fee Payments
            case 'add_fp': if(add_fp($data)) h_msg('فیس ادائیگی شامل کر دی گئی۔'); else h_msg('فیس ادائیگی شامل کرنے میں ناکامی۔', 'danger'); break;
            case 'upd_fp': if(upd_fp($id, $data)) h_msg('فیس ادائیگی اپ ڈیٹ ہو گئی۔'); else h_msg('فیس ادائیگی اپ ڈیٹ کرنے میں ناکامی۔', 'danger'); break;
            case 'del_fp': if(del_fp($id)) h_msg('فیس ادائیگی حذف کر دی گئی۔'); else h_msg('فیس ادائیگی حذف کرنے میں ناکامی۔', 'danger'); $pg = 'fee'; break;
            // Salary Payments
            case 'add_sp': if(add_sp($data)) h_msg('تنخواہ ادائیگی شامل کر دی گئی۔'); else h_msg('تنخواہ ادائیگی شامل کرنے میں ناکامی۔', 'danger'); break;
            case 'upd_sp': if(upd_sp($id, $data)) h_msg('تنخواہ ادائیگی اپ ڈیٹ ہو گئی۔'); else h_msg('تنخواہ ادائیگی اپ ڈیٹ کرنے میں ناکامی۔', 'danger'); break;
            case 'del_sp': if(del_sp($id)) h_msg('تنخواہ ادائیگی حذف کر دی گئی۔'); else h_msg('تنخواہ ادائیگی حذف کرنے میں ناکامی۔', 'danger'); $pg = 'sal'; break;
            // Inventory
            case 'add_inv': if(add_inv($data)) h_msg('انوینٹری آئٹم شامل کر دیا گیا۔'); else h_msg('انوینٹری آئٹم شامل کرنے میں ناکامی۔', 'danger'); break;
            case 'upd_inv': if(upd_inv($id, $data)) h_msg('انوینٹری آئٹم اپ ڈیٹ ہو گیا۔'); else h_msg('انوینٹری آئٹم اپ ڈیٹ کرنے میں ناکامی۔', 'danger'); break;
            case 'del_inv': if(del_inv($id)) h_msg('انوینٹری آئٹم حذف کر دیا گیا۔'); else h_msg('انوینٹری آئٹم حذف کرنے میں ناکامی۔', 'danger'); $pg = 'inv'; break;
             // Income
             case 'add_inc': if(add_inc($data)) h_msg('آمدنی شامل کر دی گئی۔'); else h_msg('آمدنی شامل کرنے میں ناکامی۔', 'danger'); break;
             case 'upd_inc': if(upd_inc($id, $data)) h_msg('آمدنی اپ ڈیٹ ہو گئی۔'); else h_msg('آمدنی اپ ڈیٹ کرنے میں ناکامی۔', 'danger'); break;
             case 'del_inc': if(del_inc($id)) h_msg('آمدنی حذف کر دی گئی۔'); else h_msg('آمدنی حذف کرنے میں ناکامی۔', 'danger'); $pg = 'inc_exp'; break;
             // Expenses
             case 'add_exp': if(add_exp($data)) h_msg('خرچہ شامل کر دیا گیا۔'); else h_msg('خرچہ شامل کرنے میں ناکامی۔', 'danger'); break;
             case 'upd_exp': if(upd_exp($id, $data)) h_msg('خرچہ اپ ڈیٹ ہو گیا۔'); else h_msg('خرچہ اپ ڈیٹ کرنے میں ناکامی۔', 'danger'); break;
             case 'del_exp': if(del_exp($id)) h_msg('خرچہ حذف کر دیا گیا۔'); else h_msg('خرچہ حذف کرنے میں ناکامی۔', 'danger'); $pg = 'inc_exp'; break;
            // Settings
            case 'upd_cfg':
                $allowed_keys = ['madrasa_name', 'madrasa_address', 'madrasa_phone', 'st_cf1_lbl', 'st_cf2_lbl', 'tch_cf1_lbl', 'tch_cf2_lbl'];
                $updated = true;
                foreach ($allowed_keys as $k) {
                    if (isset($data[$k])) {
                        if (!upd_cfg($k, $data[$k])) $updated = false;
                    }
                }
                // Handle logo upload
                if (isset($_FILES['madrasa_logo']) && $_FILES['madrasa_logo']['error'] == UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['madrasa_logo']['tmp_name'];
                    $ext = pathinfo($_FILES['madrasa_logo']['name'], PATHINFO_EXTENSION);
                    $new_name = 'logo.' . $ext;
                    $destination = UPLOAD_DIR . $new_name;
                     // Delete old logo if exists
                    $old_logo = get_cfg('madrasa_logo');
                     if ($old_logo && file_exists(UPLOAD_DIR . $old_logo) && strpos($old_logo, 'logo.') === 0) {
                          unlink(UPLOAD_DIR . $old_logo);
                     }
                    if (move_uploaded_file($tmp_name, $destination)) {
                        if (!upd_cfg('madrasa_logo', $new_name)) $updated = false;
                    } else {
                        h_msg('لوگو اپ لوڈ کرنے میں ناکامی۔', 'danger');
                        $updated = false;
                    }
                }
                if ($updated) h_msg('ترتیبات محفوظ ہو گئیں۔'); else h_msg('کچھ ترتیبات محفوظ کرنے میں ناکامی ہوئی۔', 'warning');
                break;
             // Backup/Restore
            case 'restore_db':
                if (isset($_FILES['db_file']) && $_FILES['db_file']['error'] == UPLOAD_ERR_OK) {
                    $allowed_ext = 'sqlite';
                    $ext = pathinfo($_FILES['db_file']['name'], PATHINFO_EXTENSION);
                    if (strtolower($ext) === $allowed_ext) {
                        $tmp_name = $_FILES['db_file']['tmp_name'];
                        // Close the current connection if open
                        $db = null; // Force closing PDO connection
                        // Attempt to move the uploaded file
                        if (rename(DB_FILE, DB_FILE . '.bak-' . time())) { // Backup current DB first
                             if (move_uploaded_file($tmp_name, DB_FILE)) {
                                h_msg('ڈیٹا بیس کامیابی سے بحال ہو گیا۔ براہ کرم صفحہ ریفریش کریں۔');
                                // Force re-init after restore might be needed if schema changed
                                try { init_db(); } catch (Exception $e) { /* Ignore errors during re-init */ }
                             } else {
                                h_msg('ڈیٹا بیس فائل کو منتقل کرنے میں ناکامی۔ بحالی منسوخ کر دی گئی۔', 'danger');
                                rename(DB_FILE . '.bak-' . time(), DB_FILE); // Restore backup
                             }
                        } else {
                             h_msg('موجودہ ڈیٹا بیس کا بیک اپ بنانے میں ناکامی۔ بحالی منسوخ کر دی گئی۔', 'danger');
                        }

                    } else {
                        h_msg('غلط فائل کی قسم۔ صرف .sqlite فائلیں قابل قبول ہیں۔', 'danger');
                    }
                } else {
                    h_msg('فائل اپ لوڈ میں خرابی: ' . ($_FILES['db_file']['error'] ?? 'Unknown error'), 'danger');
                }
                $pg = 'backup'; // Stay on backup page
                break;
        }
$db->commit();
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Transaction committed for action=$action\n", FILE_APPEND);
    } catch (Exception $e) {
        $db->rollBack();
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Main POST processing failed: " . $e->getMessage() . "\n", FILE_APPEND);
        h_msg('عملیات میں ناکامی: ' . $e->getMessage(), 'danger');
        header('Location: ?pg=att');
    
    exit;
} catch (Exception $e) {
        $db->rollBack();
        h_msg('ایک خرابی پیش آگئی: ' . $e->getMessage(), 'danger');
        error_log("Database action failed: " . $e->getMessage());
    }
}

// Handle Backup Download Action
if ($pg === 'backup' && isset($_GET['download'])) {
    $db_file = DB_FILE;
    if (file_exists($db_file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($db_file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($db_file));
        readfile($db_file);
        exit;
    } else {
        h_msg('ڈیٹا بیس فائل نہیں ملی۔', 'danger');
    }
}

// Handle CSV Export
if ($export) {
    $filename = $export . "_report_" . date('Ymd') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF"); // Add BOM for UTF-8 Excel compatibility

    $start_dt = $_GET['start_dt'] ?? null;
    $end_dt = $_GET['end_dt'] ?? null;
    $st_id = $_GET['st_id'] ?? null;
    $tch_id = $_GET['tch_id'] ?? null;
    $cl_id = $cl_id_filter;
    $m = $m_filter;
    $y = $y_filter;
    $status = $st_filter;


    switch ($export) {
        case 'st':
            $data = get_all_st($cl_id);
            fputcsv($output, ['ID', 'نام', 'والد کا نام', 'عمر', 'کلاس', 'داخلہ تاریخ', 'رابطہ', 'پتہ', get_cfg('st_cf1_lbl') ?: 'اضافی فیلڈ 1', get_cfg('st_cf2_lbl') ?: 'اضافی فیلڈ 2']);
            foreach ($data as $r) {
                fputcsv($output, [$r['id'], $r['n'], $r['fn'], $r['a'], $r['cln'] ?? get_cln($r['cl_id']), f_date($r['ad']), $r['c'], $r['adr'], $r['cf1'], $r['cf2']]);
            }
            break;
case 'att':
    $dt = $_GET['dt'] ?? date('Y-m-d');
    $cl_id = $_GET['cl_id'] ?? null;
    $att_data = get_att_for_dt($dt, $cl_id);
    error_log("Attendance form rendering: dt=$dt, cl_id=" . ($cl_id ?? 'null') . ", att_data=" . json_encode($att_data));
    ?>
    <div class="container mt-4">
        <h2>حاضری</h2>
        <form method="post" action="phpmadrasa.php">
            <input type="hidden" name="action" value="mark_att">
            <div class="mb-3">
                <label for="dt">تاریخ:</label>
                <input type="date" id="dt" name="dt" value="<?php echo htmlspecialchars($dt); ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="cl_id_filter">کلاس:</label>
                <select id="cl_id_filter" name="cl_id_filter" class="form-control" onchange="this.form.submit()">
                    <option value="">تمام کلاسز</option>
                    <?php foreach (get_cl() as $cl): ?>
                        <option value="<?php echo $cl['id']; ?>" <?php echo $cl_id == $cl['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cl['n']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (empty($att_data)): ?>
                <p class="text-danger">کوئی طالب علم نہیں ملا۔ براہ کرم کلاس منتخب کریں یا طالب علم شامل کریں۔</p>
            <?php else: ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>نام</th>
                            <th>حاضری</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($att_data as $st_id => $st): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($st['n']); ?></td>
                                <td>
                                    <select name="<?php echo $st_id; ?>" class="form-control">
                                        <option value="" <?php echo $st['st'] == '' ? 'selected' : ''; ?>>منتخب کریں</option>
                                        <option value="H" <?php echo $st['st'] == 'H' ? 'selected' : ''; ?>>حاضر</option>
                                        <option value="G" <?php echo $st['st'] == 'G' ? 'selected' : ''; ?>>غیر حاضر</option>
                                        <option value="R" <?php echo $st['st'] == 'R' ? 'selected' : ''; ?>>رخصت</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="btn btn-primary">حاضری محفوظ کریں</button>
            <?php endif; ?>
        </form>
    </div>
    <?php
    break;
        case 'fee':
            $data = get_all_fp($st_id, $cl_id, $m, $y, $status);
            fputcsv($output, ['ID', 'طالب علم', 'والد کا نام', 'کلاس', 'رقم', 'ادا شدہ تاریخ', 'مہینہ', 'سال', 'کیفیت']);
            foreach ($data as $r) {
                fputcsv($output, [$r['id'], $r['stn'], $r['stfn'], $r['cln'], $r['amt'], u_date($r['dt']), $r['m'], $r['y'], $r['st']]);
            }
            break;
        case 'sal':
             $data = get_all_sp($tch_id, $m, $y);
            fputcsv($output, ['ID', 'استاد', 'رقم', 'ادا شدہ تاریخ', 'مہینہ', 'سال']);
            foreach ($data as $r) {
                fputcsv($output, [$r['id'], $r['tchn'], $r['amt'], u_date($r['dt']), $r['m'], $r['y']]);
            }
            break;
    }
    fclose($output);
    exit;
}


// --- HTML Rendering ---
?>
<!DOCTYPE html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc(get_cfg('madrasa_name')); ?> - مدرسہ مینجمنٹ سسٹم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap');
        body { font-family: 'Noto Nastaliq Urdu', serif; background-color: #f8f9fa; }
        .navbar { background-color: #2c3e50; }
        .navbar-brand, .nav-link { color: #ecf0f1 !important; }
        .nav-link.active { font-weight: bold; color: #ffffff !important; border-bottom: 2px solid #18bc9c; }
        .btn-primary { background-color: #18bc9c; border-color: #18bc9c; }
        .btn-primary:hover { background-color: #15a589; border-color: #15a589; }
        .btn-danger { background-color: #e74c3c; border-color: #e74c3c; }
        .btn-danger:hover { background-color: #c0392b; border-color: #c0392b; }
        .table { direction: rtl; }
        th, td { text-align: right !important; vertical-align: middle; }
        .form-label { font-weight: bold; }
        .card-header { background-color: #34495e; color: white; font-weight: bold;}
        .container-fluid { max-width: 1600px; }
        .madrasa-logo { max-height: 40px; margin-left: 10px; }
        .printable { background-color: white; padding: 20px; border: 1px solid #dee2e6; }
        @media print {
            body * { visibility: hidden; }
            .printable, .printable * { visibility: visible; }
            .printable { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 10px; border: none;}
            .no-print { display: none !important; }
            .table { font-size: 10pt; } /* Adjust font size for print */
            th, td { padding: 4px !important; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4 no-print">
        <div class="container-fluid">
		<?php display_msg(); ?>
            <a class="navbar-brand" href="?pg=dash">
                <?php $logo = get_cfg('madrasa_logo'); if ($logo && file_exists(UPLOAD_DIR . $logo)): ?>
                    <img src="<?php echo UPLOAD_DIR . esc($logo); ?>" alt="Madrasa Logo" class="madrasa-logo">
                <?php endif; ?>
                <?php echo esc(get_cfg('madrasa_name')); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link <?php echo ($pg=='dash')?'active':'';?>" href="?pg=dash">ڈیش بورڈ</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($pg=='st' || $pg=='st_form')?'active':'';?>" href="?pg=st">طلباء</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($pg=='tch' || $pg=='tch_form')?'active':'';?>" href="?pg=tch">اساتذہ</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($pg=='cl' || $pg=='cl_form')?'active':'';?>" href="?pg=cl">کلاسیں</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($pg=='att')?'active':'';?>" href="?pg=att">حاضری</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($pg=='fee' || $pg=='fp_form')?'active':'';?>" href="?pg=fee">فیس</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($pg=='sal' || $pg=='sp_form')?'active':'';?>" href="?pg=sal">تنخواہیں</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($pg=='inv' || $pg=='inv_form')?'active':'';?>" href="?pg=inv">انوینٹری</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($pg=='inc_exp' || $pg=='inc_form' || $pg=='exp_form')?'active':'';?>" href="?pg=inc_exp">آمدن/اخراجات</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($pg=='rpt')?'active':'';?>" href="?pg=rpt">رپورٹس</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($pg=='cfg')?'active':'';?>" href="?pg=cfg">ترتیبات</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($pg=='backup')?'active':'';?>" href="?pg=backup">بیک اپ/بحالی</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <?php s_msg(); // Display messages ?>

        <?php // --- Page Content Based on $pg ---

        // --- Dashboard ---
        if ($pg == 'dash'):
            $st_count = get_db()->query("SELECT COUNT(*) FROM st")->fetchColumn();
            $tch_count = get_db()->query("SELECT COUNT(*) FROM tch")->fetchColumn();
            $cl_count = get_db()->query("SELECT COUNT(*) FROM cl")->fetchColumn();
            $today_att_q = get_db()->prepare("SELECT COUNT(DISTINCT st_id) FROM att WHERE dt = ? AND st = 'H'");
            $today_att_q->execute([date('Y-m-d')]);
            $today_att = $today_att_q->fetchColumn();
            $today_fee_q = get_db()->prepare("SELECT SUM(amt) FROM fp WHERE dt = ? AND st = 'Ada Shuda'");
            $today_fee_q->execute([date('Y-m-d')]);
            $today_fee = $today_fee_q->fetchColumn() ?: 0;
            $month_inc_q = get_db()->prepare("SELECT SUM(amt) FROM inc WHERE strftime('%Y-%m', dt) = ?");
            $month_inc_q->execute([date('Y-m')]);
            $month_inc = $month_inc_q->fetchColumn() ?: 0;
            $month_exp_q = get_db()->prepare("SELECT SUM(amt) FROM exp WHERE strftime('%Y-%m', dt) = ?");
            $month_exp_q->execute([date('Y-m')]);
            $month_exp = $month_exp_q->fetchColumn() ?: 0;

        ?>
            <h2 class="mb-4">ڈیش بورڈ</h2>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">کل طلباء</h5>
                            <p class="fs-3"><?php echo $st_count; ?></p>
                            <a href="?pg=st" class="btn btn-sm btn-outline-primary">تفصیلات</a>
                        </div>
                    </div>
                </div>
                 <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">کل اساتذہ</h5>
                            <p class="fs-3"><?php echo $tch_count; ?></p>
                            <a href="?pg=tch" class="btn btn-sm btn-outline-primary">تفصیلات</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">کل کلاسیں</h5>
                            <p class="fs-3"><?php echo $cl_count; ?></p>
                            <a href="?pg=cl" class="btn btn-sm btn-outline-primary">تفصیلات</a>
                        </div>
                    </div>
                </div>
                 <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">آج حاضر طلباء</h5>
                            <p class="fs-3"><?php echo $today_att; ?></p>
                            <a href="?pg=att&dt=<?php echo date('Y-m-d'); ?>" class="btn btn-sm btn-outline-primary">حاضری لگائیں</a>
                        </div>
                    </div>
                </div>
                 <div class="col-md-3">
                    <div class="card text-center text-success">
                        <div class="card-body">
                            <h5 class="card-title">آج وصول شدہ فیس</h5>
                            <p class="fs-3"><?php echo number_format($today_fee, 2); ?></p>
                             <a href="?pg=fee" class="btn btn-sm btn-outline-success">فیس تفصیلات</a>
                        </div>
                    </div>
                </div>
                 <div class="col-md-3">
                    <div class="card text-center text-info">
                        <div class="card-body">
                            <h5 class="card-title">رواں ماہ آمدن</h5>
                            <p class="fs-3"><?php echo number_format($month_inc, 2); ?></p>
                            <a href="?pg=inc_exp" class="btn btn-sm btn-outline-info">تفصیلات</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center text-danger">
                        <div class="card-body">
                            <h5 class="card-title">رواں ماہ اخراجات</h5>
                            <p class="fs-3"><?php echo number_format($month_exp, 2); ?></p>
                             <a href="?pg=inc_exp" class="btn btn-sm btn-outline-danger">تفصیلات</a>
                        </div>
                    </div>
                </div>
            </div>

        <?php // --- Students ---
        elseif ($pg == 'st'):
            $st_list = get_all_st($cl_id_filter);
            $classes = get_cls();
        ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>طلباء کا انتظام</h2>
                <a href="?pg=st_form" class="btn btn-primary"><i class="bi bi-plus-circle"></i> نیا طالب علم شامل کریں</a>
            </div>
            <form method="get" class="row g-3 align-items-center bg-light p-3 rounded mb-3 no-print">
                 <input type="hidden" name="pg" value="st">
                 <div class="col-auto">
                     <label for="cl_id_filter" class="form-label">کلاس فلٹر:</label>
                 </div>
                 <div class="col-auto">
                     <select name="cl_id" id="cl_id_filter" class="form-select form-select-sm">
                         <option value="">تمام کلاسیں</option>
                         <?php foreach ($classes as $cl): ?>
                             <option value="<?php echo $cl['id']; ?>" <?php echo ($cl_id_filter == $cl['id']) ? 'selected' : ''; ?>><?php echo esc($cl['n']); ?></option>
                         <?php endforeach; ?>
                     </select>
                 </div>
                 <div class="col-auto">
                    <button type="submit" class="btn btn-secondary btn-sm">فلٹر کریں</button>
                    <a href="?pg=st" class="btn btn-outline-secondary btn-sm">فلٹر صاف کریں</a>
                </div>
                 <div class="col-auto ms-auto">
                     <a href="?pg=st&export=st<?php echo $cl_id_filter ? '&cl_id='.$cl_id_filter : ''; ?>" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</a>
                    <button type="button" onclick="window.print()" class="btn btn-info btn-sm"><i class="bi bi-printer"></i> پرنٹ</button>
                </div>
            </form>
             <div class="printable">
                 <h3 class="text-center d-none d-print-block"><?php echo esc(get_cfg('madrasa_name')); ?> - طلباء کی فہرست <?php if($cl_id_filter) echo ' (' . esc(get_cln($cl_id_filter)) . ')'; ?></h3>
                 <p class="text-center d-none d-print-block">تاریخ: <?php echo u_date(date('Y-m-d')); ?></p>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>نام</th>
                                <th>والد کا نام</th>
                                <th>عمر</th>
                                <th>کلاس</th>
                                <th>داخلہ تاریخ</th>
                                <th>رابطہ</th>
                                <th>پتہ</th>
                                <th><?php echo esc(get_cfg('st_cf1_lbl') ?: 'اضافی فیلڈ 1'); ?></th>
                                <th><?php echo esc(get_cfg('st_cf2_lbl') ?: 'اضافی فیلڈ 2'); ?></th>
                                <th class="no-print">اعمال</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($st_list as $st): ?>
                            <tr>
                                <td><?php echo $st['id']; ?></td>
                                <td><?php echo esc($st['n']); ?></td>
                                <td><?php echo esc($st['fn']); ?></td>
                                <td><?php echo esc($st['a']); ?></td>
                                <td><?php echo esc($st['cln'] ?: '---'); ?></td>
                                <td><?php echo u_date($st['ad']); ?></td>
                                <td><?php echo esc($st['c']); ?></td>
                                <td><?php echo esc($st['adr']); ?></td>
                                <td><?php echo esc($st['cf1']); ?></td>
                                <td><?php echo esc($st['cf2']); ?></td>
                                <td class="no-print">
                                    <a href="?pg=st_form&id=<?php echo $st['id']; ?>" class="btn btn-sm btn-warning" title="ترمیم"><i class="bi bi-pencil-square"></i></a>
                                    <form method="post" action="?pg=st&id=<?php echo $st['id']; ?>" style="display:inline;" onsubmit="return confirm('کیا آپ واقعی اس طالب علم کو حذف کرنا چاہتے ہیں؟');">
                                        <input type="hidden" name="act" value="del_st">
                                        <button type="submit" class="btn btn-sm btn-danger" title="حذف"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                             <?php if (empty($st_list)): ?>
                                <tr><td colspan="11" class="text-center">کوئی طالب علم نہیں ملا۔</td></tr>
                             <?php endif; ?>
                        </tbody>
                    </table>
                </div>
             </div>

        <?php // --- Student Form (Add/Edit) ---
        elseif ($pg == 'st_form'):
            $s_data = $id ? get_st($id) : [];
            $classes = get_cls();
            $form_act = $id ? 'upd_st' : 'add_st';
            $form_title = $id ? 'طالب علم کی معلومات میں ترمیم کریں' : 'نیا طالب علم شامل کریں';
        ?>
            <h2><?php echo $form_title; ?></h2>
            <form method="post" action="?pg=st_form&id=<?php echo $id; ?>">
                <input type="hidden" name="act" value="<?php echo $form_act; ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="n" class="form-label">نام <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="n" name="n" value="<?php echo esc($s_data['n'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="fn" class="form-label">والد کا نام</label>
                        <input type="text" class="form-control" id="fn" name="fn" value="<?php echo esc($s_data['fn'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="a" class="form-label">عمر</label>
                        <input type="number" class="form-control" id="a" name="a" value="<?php echo esc($s_data['a'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="cl_id" class="form-label">کلاس</label>
                        <select class="form-select" id="cl_id" name="cl_id">
                            <option value="">کلاس منتخب کریں</option>
                            <?php foreach ($classes as $cl): ?>
                            <option value="<?php echo $cl['id']; ?>" <?php echo (isset($s_data['cl_id']) && $s_data['cl_id'] == $cl['id']) ? 'selected' : ''; ?>><?php echo esc($cl['n']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                     <div class="col-md-4">
                        <label for="ad" class="form-label">داخلہ تاریخ</label>
                        <input type="date" class="form-control" id="ad" name="ad" value="<?php echo f_date($s_data['ad'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="c" class="form-label">رابطہ نمبر</label>
                        <input type="text" class="form-control" id="c" name="c" value="<?php echo esc($s_data['c'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="adr" class="form-label">پتہ</label>
                        <input type="text" class="form-control" id="adr" name="adr" value="<?php echo esc($s_data['adr'] ?? ''); ?>">
                    </div>
                     <div class="col-md-6">
                        <label for="cf1" class="form-label"><?php echo esc(get_cfg('st_cf1_lbl') ?: 'اضافی فیلڈ 1'); ?></label>
                        <input type="text" class="form-control" id="cf1" name="cf1" value="<?php echo esc($s_data['cf1'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="cf2" class="form-label"><?php echo esc(get_cfg('st_cf2_lbl') ?: 'اضافی فیلڈ 2'); ?></label>
                        <input type="text" class="form-control" id="cf2" name="cf2" value="<?php echo esc($s_data['cf2'] ?? ''); ?>">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">محفوظ کریں</button>
                    <a href="?pg=st" class="btn btn-secondary">منسوخ کریں</a>
                </div>
            </form>

        <?php // --- Teachers ---
        elseif ($pg == 'tch'):
            $tch_list = get_all_tch();
        ?>
             <div class="d-flex justify-content-between align-items-center mb-3">
                 <h2>اساتذہ کا انتظام</h2>
                 <a href="?pg=tch_form" class="btn btn-primary"><i class="bi bi-plus-circle"></i> نیا استاد شامل کریں</a>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>نام</th>
                            <th>مضمون/عہدہ</th>
                            <th>رابطہ</th>
                            <th>پتہ</th>
                            <th>شمولیت تاریخ</th>
                            <th>تنخواہ</th>
                             <th><?php echo esc(get_cfg('tch_cf1_lbl') ?: 'اضافی فیلڈ 1'); ?></th>
                             <th><?php echo esc(get_cfg('tch_cf2_lbl') ?: 'اضافی فیلڈ 2'); ?></th>
                            <th>اعمال</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tch_list as $tch): ?>
                        <tr>
                            <td><?php echo $tch['id']; ?></td>
                            <td><?php echo esc($tch['n']); ?></td>
                            <td><?php echo esc($tch['s']); ?></td>
                            <td><?php echo esc($tch['c']); ?></td>
                            <td><?php echo esc($tch['adr']); ?></td>
                            <td><?php echo u_date($tch['jd']); ?></td>
                            <td><?php echo esc(number_format($tch['sal'] ?? 0, 2)); ?></td>
                            <td><?php echo esc($tch['cf1']); ?></td>
                            <td><?php echo esc($tch['cf2']); ?></td>
                            <td>
                                <a href="?pg=tch_form&id=<?php echo $tch['id']; ?>" class="btn btn-sm btn-warning" title="ترمیم"><i class="bi bi-pencil-square"></i></a>
                                <form method="post" action="?pg=tch&id=<?php echo $tch['id']; ?>" style="display:inline;" onsubmit="return confirm('کیا آپ واقعی اس استاد کو حذف کرنا چاہتے ہیں؟');">
                                    <input type="hidden" name="act" value="del_tch">
                                    <button type="submit" class="btn btn-sm btn-danger" title="حذف"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($tch_list)): ?>
                             <tr><td colspan="10" class="text-center">کوئی استاد نہیں ملا۔</td></tr>
                         <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php // --- Teacher Form (Add/Edit) ---
        elseif ($pg == 'tch_form'):
            $t_data = $id ? get_tch($id) : [];
            $form_act = $id ? 'upd_tch' : 'add_tch';
            $form_title = $id ? 'استاد کی معلومات میں ترمیم کریں' : 'نیا استاد شامل کریں';
        ?>
            <h2><?php echo $form_title; ?></h2>
            <form method="post" action="?pg=tch_form&id=<?php echo $id; ?>">
                <input type="hidden" name="act" value="<?php echo $form_act; ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="n" class="form-label">نام <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="n" name="n" value="<?php echo esc($t_data['n'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="s" class="form-label">مضمون/عہدہ</label>
                        <input type="text" class="form-control" id="s" name="s" value="<?php echo esc($t_data['s'] ?? ''); ?>">
                    </div>
                     <div class="col-md-6">
                        <label for="c" class="form-label">رابطہ نمبر</label>
                        <input type="text" class="form-control" id="c" name="c" value="<?php echo esc($t_data['c'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="adr" class="form-label">پتہ</label>
                        <input type="text" class="form-control" id="adr" name="adr" value="<?php echo esc($t_data['adr'] ?? ''); ?>">
                    </div>
                     <div class="col-md-6">
                        <label for="jd" class="form-label">شمولیت تاریخ</label>
                        <input type="date" class="form-control" id="jd" name="jd" value="<?php echo f_date($t_data['jd'] ?? ''); ?>">
                    </div>
                     <div class="col-md-6">
                        <label for="sal" class="form-label">تنخواہ</label>
                        <input type="number" step="0.01" class="form-control" id="sal" name="sal" value="<?php echo esc($t_data['sal'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="cf1" class="form-label"><?php echo esc(get_cfg('tch_cf1_lbl') ?: 'اضافی فیلڈ 1'); ?></label>
                        <input type="text" class="form-control" id="cf1" name="cf1" value="<?php echo esc($t_data['cf1'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="cf2" class="form-label"><?php echo esc(get_cfg('tch_cf2_lbl') ?: 'اضافی فیلڈ 2'); ?></label>
                        <input type="text" class="form-control" id="cf2" name="cf2" value="<?php echo esc($t_data['cf2'] ?? ''); ?>">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">محفوظ کریں</button>
                    <a href="?pg=tch" class="btn btn-secondary">منسوخ کریں</a>
                </div>
            </form>

        <?php // --- Classes ---
        elseif ($pg == 'cl'):
            $cl_list = get_all_cl();
        ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                 <h2>کلاسوں کا انتظام</h2>
                 <a href="?pg=cl_form" class="btn btn-primary"><i class="bi bi-plus-circle"></i> نئی کلاس شامل کریں</a>
            </div>
             <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>کلاس کا نام</th>
                            <th>استاد</th>
                            <th>طلباء کی تعداد</th>
                            <th>اعمال</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cl_list as $cl):
                            $st_count_q = get_db()->prepare("SELECT COUNT(*) FROM st WHERE cl_id = ?");
                            $st_count_q->execute([$cl['id']]);
                            $st_count = $st_count_q->fetchColumn();
                        ?>
                        <tr>
                            <td><?php echo $cl['id']; ?></td>
                            <td><?php echo esc($cl['n']); ?></td>
                            <td><?php echo esc($cl['tchn'] ?: '---'); ?></td>
                            <td><a href="?pg=st&cl_id=<?php echo $cl['id']; ?>"><?php echo $st_count; ?></a></td>
                            <td>
                                <a href="?pg=cl_form&id=<?php echo $cl['id']; ?>" class="btn btn-sm btn-warning" title="ترمیم"><i class="bi bi-pencil-square"></i></a>
                                <form method="post" action="?pg=cl&id=<?php echo $cl['id']; ?>" style="display:inline;" onsubmit="return confirm('کیا آپ واقعی اس کلاس کو حذف کرنا چاہتے ہیں؟');">
                                    <input type="hidden" name="act" value="del_cl">
                                    <button type="submit" class="btn btn-sm btn-danger" title="حذف"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                         <?php if (empty($cl_list)): ?>
                             <tr><td colspan="5" class="text-center">کوئی کلاس نہیں ملی۔</td></tr>
                         <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php // --- Class Form (Add/Edit) ---
        elseif ($pg == 'cl_form'):
            $c_data = $id ? get_cl($id) : [];
            $teachers = get_tchs();
            $form_act = $id ? 'upd_cl' : 'add_cl';
            $form_title = $id ? 'کلاس کی معلومات میں ترمیم کریں' : 'نئی کلاس شامل کریں';
        ?>
            <h2><?php echo $form_title; ?></h2>
             <form method="post" action="?pg=cl_form&id=<?php echo $id; ?>">
                <input type="hidden" name="act" value="<?php echo $form_act; ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="n" class="form-label">کلاس کا نام <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="n" name="n" value="<?php echo esc($c_data['n'] ?? ''); ?>" required>
                    </div>
                     <div class="col-md-6">
                        <label for="tch_id" class="form-label">استاد تفویض کریں</label>
                         <select class="form-select" id="tch_id" name="tch_id">
                             <option value="">استاد منتخب کریں</option>
                             <?php foreach ($teachers as $tch): ?>
                             <option value="<?php echo $tch['id']; ?>" <?php echo (isset($c_data['tch_id']) && $c_data['tch_id'] == $tch['id']) ? 'selected' : ''; ?>><?php echo esc($tch['n']); ?></option>
                             <?php endforeach; ?>
                         </select>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">محفوظ کریں</button>
                    <a href="?pg=cl" class="btn btn-secondary">منسوخ کریں</a>
                </div>
            </form>

        <?php // --- Attendance ---
        elseif ($pg == 'att'):
            $classes = get_cls();
            // Default to first class if no filter and classes exist
            if (!$cl_id_filter && !empty($classes)) {
                $cl_id_filter = $classes[0]['id'];
            }
            $att_data = get_att_for_dt($dt, $cl_id_filter);
        ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>طلباء کی حاضری</h2>
            </div>
            <form method="get" class="row g-3 align-items-center bg-light p-3 rounded mb-3 no-print">
                 <input type="hidden" name="pg" value="att">
                 <div class="col-md-4">
                     <label for="dt" class="form-label">تاریخ:</label>
                     <input type="date" name="dt" id="dt" class="form-control" value="<?php echo esc($dt); ?>" required>
                 </div>
                 <div class="col-md-4">
                     <label for="cl_id_filter" class="form-label">کلاس:</label>
                      <select name="cl_id" id="cl_id_filter" class="form-select">
                         <option value="">کلاس منتخب کریں</option>
                         <?php foreach ($classes as $cl): ?>
                             <option value="<?php echo $cl['id']; ?>" <?php echo ($cl_id_filter == $cl['id']) ? 'selected' : ''; ?>><?php echo esc($cl['n']); ?></option>
                         <?php endforeach; ?>
                     </select>
                 </div>
                 <div class="col-md-4 align-self-end">
                    <button type="submit" class="btn btn-secondary">ریکارڈ دیکھیں</button>
                    <a href="?pg=rpt&rep=att" class="btn btn-info">حاضری رپورٹ</a>
                </div>
            </form>

             <?php if ($cl_id_filter && !empty($att_data)): ?>
             <form method="post">
                 <input type="hidden" name="act" value="mark_att">
                 <input type="hidden" name="dt" value="<?php echo esc($dt); ?>">
                 <input type="hidden" name="cl_id_filter" value="<?php echo esc($cl_id_filter); ?>">
                 <h4 class="mt-4 mb-3">تاریخ <?php echo u_date($dt); ?>، کلاس <?php echo esc(get_cln($cl_id_filter)); ?></h4>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>طالب علم کا نام</th>
                                <th>حاضر (H)</th>
                                <th>غیر حاضر (G)</th>
                                <th>رخصت (R)</th>
                                <th>کوئی نہیں</th>
                            </tr>
                        </thead>
                        <tbody>
                             <?php foreach ($att_data as $st_id => $data): ?>
                             <tr>
                                 <td><?php echo $st_id; ?></td>
                                 <td><?php echo esc($data['n']); ?></td>
                                 <?php $current_st = $data['st']; ?>
                                 <td><input class="form-check-input" type="radio" name="<?php echo $st_id; ?>" value="H" <?php echo ($current_st == 'H') ? 'checked' : ''; ?>></td>
                                 <td><input class="form-check-input" type="radio" name="<?php echo $st_id; ?>" value="G" <?php echo ($current_st == 'G') ? 'checked' : ''; ?>></td>
                                 <td><input class="form-check-input" type="radio" name="<?php echo $st_id; ?>" value="R" <?php echo ($current_st == 'R') ? 'checked' : ''; ?>></td>
                                  <td><input class="form-check-input" type="radio" name="<?php echo $st_id; ?>" value="" <?php echo empty($current_st) ? 'checked' : ''; ?>></td>
                             </tr>
                             <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 no-print">
                    <button type="submit" class="btn btn-primary">حاضری محفوظ کریں</button>
                </div>
             </form>
             <?php elseif ($cl_id_filter): ?>
                 <div class="alert alert-warning mt-3">اس کلاس میں کوئی طالب علم نہیں ملا یا منتخب کردہ تاریخ کے لیے حاضری کا ریکارڈ موجود نہیں ہے۔</div>
             <?php else: ?>
                 <div class="alert alert-info mt-3">براہ کرم حاضری دیکھنے یا نشان زد کرنے کے لیے ایک کلاس منتخب کریں۔</div>
             <?php endif; ?>


        <?php // --- Fees ---
        elseif ($pg == 'fee'):
            $students = get_sts();
            $classes = get_cls();
            $fee_list = get_all_fp(null, $cl_id_filter, $m_filter, $y_filter, $st_filter);
            $months = ["1"=>"جنوری", "2"=>"فروری", "3"=>"مارچ", "4"=>"اپریل", "5"=>"مئی", "6"=>"جون", "7"=>"جولائی", "8"=>"اگست", "9"=>"ستمبر", "10"=>"اکتوبر", "11"=>"نومبر", "12"=>"دسمبر"];
            $years = range(date('Y') - 5, date('Y') + 1);
        ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>فیس کا انتظام</h2>
                <a href="?pg=fp_form" class="btn btn-primary"><i class="bi bi-plus-circle"></i> نئی فیس ادائیگی شامل کریں</a>
            </div>
            <form method="get" class="row g-3 align-items-center bg-light p-3 rounded mb-3 no-print">
                 <input type="hidden" name="pg" value="fee">
                 <div class="col-md-2">
                    <label for="cl_id_filter" class="form-label">کلاس:</label>
                    <select name="cl_id" id="cl_id_filter" class="form-select form-select-sm">
                         <option value="">تمام</option>
                         <?php foreach ($classes as $cl): ?>
                             <option value="<?php echo $cl['id']; ?>" <?php echo ($cl_id_filter == $cl['id']) ? 'selected' : ''; ?>><?php echo esc($cl['n']); ?></option>
                         <?php endforeach; ?>
                    </select>
                 </div>
                 <div class="col-md-2">
                    <label for="m_filter" class="form-label">مہینہ:</label>
                    <select name="m" id="m_filter" class="form-select form-select-sm">
                         <option value="">تمام</option>
                         <?php foreach ($months as $num => $name): ?>
                             <option value="<?php echo $num; ?>" <?php echo ($m_filter == $num) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                         <?php endforeach; ?>
                    </select>
                 </div>
                 <div class="col-md-2">
                    <label for="y_filter" class="form-label">سال:</label>
                    <select name="y" id="y_filter" class="form-select form-select-sm">
                         <option value="">تمام</option>
                         <?php foreach ($years as $year): ?>
                             <option value="<?php echo $year; ?>" <?php echo ($y_filter == $year) ? 'selected' : ''; ?>><?php echo $year; ?></option>
                         <?php endforeach; ?>
                    </select>
                 </div>
                 <div class="col-md-2">
                     <label for="st_filter" class="form-label">کیفیت:</label>
                     <select name="st" id="st_filter" class="form-select form-select-sm">
                         <option value="">تمام</option>
                         <option value="Ada Shuda" <?php echo ($st_filter == 'Ada Shuda') ? 'selected' : ''; ?>>ادا شدہ</option>
                         <option value="Baqaya" <?php echo ($st_filter == 'Baqaya') ? 'selected' : ''; ?>>بقایا</option>
                     </select>
                 </div>
                 <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-secondary btn-sm">فلٹر کریں</button>
                    <a href="?pg=fee" class="btn btn-outline-secondary btn-sm">صاف کریں</a>
                 </div>
                  <div class="col-md-2 align-self-end text-end">
                     <a href="?pg=fee&export=fee<?php echo $cl_id_filter ? '&cl_id='.$cl_id_filter : ''; ?><?php echo $m_filter ? '&m='.$m_filter : ''; ?><?php echo $y_filter ? '&y='.$y_filter : ''; ?><?php echo $st_filter ? '&st='.$st_filter : ''; ?>" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</a>
                    <button type="button" onclick="window.print()" class="btn btn-info btn-sm"><i class="bi bi-printer"></i> پرنٹ</button>
                </div>
            </form>

             <div class="printable">
                 <h3 class="text-center d-none d-print-block"><?php echo esc(get_cfg('madrasa_name')); ?> - فیس ادائیگیوں کی فہرست</h3>
                 <p class="text-center d-none d-print-block">تاریخ: <?php echo u_date(date('Y-m-d')); ?>
                    <?php
                     if ($cl_id_filter || $m_filter || $y_filter || $st_filter) echo "<br>فلٹرز: ";
                     if ($cl_id_filter) echo "کلاس: " . esc(get_cln($cl_id_filter)). " ";
                     if ($m_filter) echo "مہینہ: " . ($months[$m_filter] ?? $m_filter) . " ";
                     if ($y_filter) echo "سال: " . esc($y_filter). " ";
                     if ($st_filter) echo "کیفیت: " . esc($st_filter). " ";
                    ?>
                </p>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>طالب علم</th>
                                <th>والد کا نام</th>
                                <th>کلاس</th>
                                <th>رقم</th>
                                <th>ادا شدہ تاریخ</th>
                                <th>مہینہ</th>
                                <th>سال</th>
                                <th>کیفیت</th>
                                <th class="no-print">اعمال</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_paid = 0;
                            $total_due = 0;
                             foreach ($fee_list as $fp):
                                if ($fp['st'] == 'Ada Shuda') $total_paid += $fp['amt'];
                                else $total_due += $fp['amt'];
                             ?>
                            <tr class="<?php echo ($fp['st'] == 'Baqaya') ? 'table-danger' : ''; ?>">
                                <td><?php echo $fp['id']; ?></td>
                                <td><?php echo esc($fp['stn']); ?></td>
                                <td><?php echo esc($fp['stfn']); ?></td>
                                <td><?php echo esc($fp['cln']); ?></td>
                                <td><?php echo number_format($fp['amt'], 2); ?></td>
                                <td><?php echo u_date($fp['dt']); ?></td>
                                <td><?php echo $months[$fp['m']] ?? $fp['m']; ?></td>
                                <td><?php echo $fp['y']; ?></td>
                                <td><?php echo esc($fp['st']); ?></td>
                                <td class="no-print">
                                    <a href="?pg=fp_form&id=<?php echo $fp['id']; ?>" class="btn btn-sm btn-warning" title="ترمیم"><i class="bi bi-pencil-square"></i></a>
                                    <form method="post" action="?pg=fee&id=<?php echo $fp['id']; ?>" style="display:inline;" onsubmit="return confirm('کیا آپ واقعی اس ادائیگی کو حذف کرنا چاہتے ہیں؟');">
                                        <input type="hidden" name="act" value="del_fp">
                                        <button type="submit" class="btn btn-sm btn-danger" title="حذف"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($fee_list)): ?>
                                <tr><td colspan="10" class="text-center">کوئی فیس ادائیگی نہیں ملی۔</td></tr>
                            <?php else: ?>
                                <tr class="fw-bold">
                                    <td colspan="4" class="text-end">کل ادا شدہ:</td>
                                    <td><?php echo number_format($total_paid, 2); ?></td>
                                    <td colspan="3" class="text-end">کل بقایا:</td>
                                    <td><?php echo number_format($total_due, 2); ?></td>
                                    <td class="no-print"></td>
                                </tr>
                             <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php // --- Fee Payment Form (Add/Edit) ---
        elseif ($pg == 'fp_form'):
            $fp_data = $id ? get_fp($id) : [];
            $students = get_sts();
            $form_act = $id ? 'upd_fp' : 'add_fp';
            $form_title = $id ? 'فیس ادائیگی میں ترمیم کریں' : 'نئی فیس ادائیگی شامل کریں';
            $months = ["1"=>"جنوری", "2"=>"فروری", "3"=>"مارچ", "4"=>"اپریل", "5"=>"مئی", "6"=>"جون", "7"=>"جولائی", "8"=>"اگست", "9"=>"ستمبر", "10"=>"اکتوبر", "11"=>"نومبر", "12"=>"دسمبر"];
            $years = range(date('Y') - 5, date('Y') + 1);
         ?>
            <h2><?php echo $form_title; ?></h2>
             <form method="post" action="?pg=fp_form&id=<?php echo $id; ?>">
                <input type="hidden" name="act" value="<?php echo $form_act; ?>">
                <div class="row g-3">
                     <div class="col-md-6">
                        <label for="st_id" class="form-label">طالب علم <span class="text-danger">*</span></label>
                         <select class="form-select" id="st_id" name="st_id" required>
                             <option value="">طالب علم منتخب کریں</option>
                             <?php foreach ($students as $st): ?>
                             <option value="<?php echo $st['id']; ?>" <?php echo (isset($fp_data['st_id']) && $fp_data['st_id'] == $st['id']) ? 'selected' : ''; ?>><?php echo esc($st['n']); ?></option>
                             <?php endforeach; ?>
                         </select>
                    </div>
                     <div class="col-md-6">
                        <label for="amt" class="form-label">رقم <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="amt" name="amt" value="<?php echo esc($fp_data['amt'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-4">
                         <label for="dt" class="form-label">ادا شدہ تاریخ <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="dt" name="dt" value="<?php echo f_date($fp_data['dt'] ?? date('Y-m-d')); ?>" required>
                    </div>
                     <div class="col-md-2">
                         <label for="m" class="form-label">مہینہ <span class="text-danger">*</span></label>
                         <select class="form-select" id="m" name="m" required>
                             <?php foreach ($months as $num => $name): ?>
                                 <option value="<?php echo $num; ?>" <?php echo (isset($fp_data['m']) && $fp_data['m'] == $num) ? 'selected' : ( (!isset($fp_data['m'])) && $num == date('n') ? 'selected' : '' ); ?>><?php echo $name; ?></option>
                             <?php endforeach; ?>
                         </select>
                     </div>
                    <div class="col-md-2">
                         <label for="y" class="form-label">سال <span class="text-danger">*</span></label>
                         <select class="form-select" id="y" name="y" required>
                             <?php foreach ($years as $year): ?>
                                 <option value="<?php echo $year; ?>" <?php echo (isset($fp_data['y']) && $fp_data['y'] == $year) ? 'selected' : ( (!isset($fp_data['y'])) && $year == date('Y') ? 'selected' : '' ); ?>><?php echo $year; ?></option>
                             <?php endforeach; ?>
                         </select>
                     </div>
                    <div class="col-md-4">
                        <label for="st" class="form-label">کیفیت <span class="text-danger">*</span></label>
                        <select class="form-select" id="st" name="st" required>
                             <option value="Ada Shuda" <?php echo (isset($fp_data['st']) && $fp_data['st'] == 'Ada Shuda') ? 'selected' : ''; ?>>ادا شدہ</option>
                            <option value="Baqaya" <?php echo (isset($fp_data['st']) && $fp_data['st'] == 'Baqaya') ? 'selected' : ''; ?>>بقایا</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">محفوظ کریں</button>
                    <a href="?pg=fee" class="btn btn-secondary">منسوخ کریں</a>
                </div>
            </form>


        <?php // --- Salaries ---
        elseif ($pg == 'sal'):
            $teachers = get_tchs();
            $salary_list = get_all_sp(null, $m_filter, $y_filter);
            $months = ["1"=>"جنوری", "2"=>"فروری", "3"=>"مارچ", "4"=>"اپریل", "5"=>"مئی", "6"=>"جون", "7"=>"جولائی", "8"=>"اگست", "9"=>"ستمبر", "10"=>"اکتوبر", "11"=>"نومبر", "12"=>"دسمبر"];
            $years = range(date('Y') - 5, date('Y') + 1);
        ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>تنخواہوں کا انتظام</h2>
                <a href="?pg=sp_form" class="btn btn-primary"><i class="bi bi-plus-circle"></i> نئی تنخواہ ادائیگی شامل کریں</a>
            </div>
            <form method="get" class="row g-3 align-items-center bg-light p-3 rounded mb-3 no-print">
                 <input type="hidden" name="pg" value="sal">
                 <div class="col-md-3">
                    <label for="m_filter" class="form-label">مہینہ:</label>
                    <select name="m" id="m_filter" class="form-select form-select-sm">
                         <option value="">تمام</option>
                         <?php foreach ($months as $num => $name): ?>
                             <option value="<?php echo $num; ?>" <?php echo ($m_filter == $num) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                         <?php endforeach; ?>
                    </select>
                 </div>
                 <div class="col-md-3">
                    <label for="y_filter" class="form-label">سال:</label>
                    <select name="y" id="y_filter" class="form-select form-select-sm">
                         <option value="">تمام</option>
                         <?php foreach ($years as $year): ?>
                             <option value="<?php echo $year; ?>" <?php echo ($y_filter == $year) ? 'selected' : ''; ?>><?php echo $year; ?></option>
                         <?php endforeach; ?>
                    </select>
                 </div>
                 <div class="col-md-3 align-self-end">
                    <button type="submit" class="btn btn-secondary btn-sm">فلٹر کریں</button>
                    <a href="?pg=sal" class="btn btn-outline-secondary btn-sm">صاف کریں</a>
                </div>
                 <div class="col-md-3 align-self-end text-end">
                     <a href="?pg=sal&export=sal<?php echo $m_filter ? '&m='.$m_filter : ''; ?><?php echo $y_filter ? '&y='.$y_filter : ''; ?>" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</a>
                    <button type="button" onclick="window.print()" class="btn btn-info btn-sm"><i class="bi bi-printer"></i> پرنٹ</button>
                </div>
            </form>
            <div class="printable">
                 <h3 class="text-center d-none d-print-block"><?php echo esc(get_cfg('madrasa_name')); ?> - تنخواہ ادائیگیوں کی فہرست</h3>
                 <p class="text-center d-none d-print-block">تاریخ: <?php echo u_date(date('Y-m-d')); ?>
                     <?php
                     if ($m_filter || $y_filter) echo "<br>فلٹرز: ";
                     if ($m_filter) echo "مہینہ: " . ($months[$m_filter] ?? $m_filter) . " ";
                     if ($y_filter) echo "سال: " . esc($y_filter). " ";
                    ?>
                 </p>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>استاد</th>
                                <th>رقم</th>
                                <th>ادا شدہ تاریخ</th>
                                <th>مہینہ</th>
                                <th>سال</th>
                                <th class="no-print">اعمال</th>
                            </tr>
                        </thead>
                        <tbody>
                             <?php
                             $total_sal = 0;
                             foreach ($salary_list as $sp):
                                $total_sal += $sp['amt'];
                             ?>
                            <tr>
                                <td><?php echo $sp['id']; ?></td>
                                <td><?php echo esc($sp['tchn']); ?></td>
                                <td><?php echo number_format($sp['amt'], 2); ?></td>
                                <td><?php echo u_date($sp['dt']); ?></td>
                                <td><?php echo $months[$sp['m']] ?? $sp['m']; ?></td>
                                <td><?php echo $sp['y']; ?></td>
                                <td class="no-print">
                                    <a href="?pg=sp_form&id=<?php echo $sp['id']; ?>" class="btn btn-sm btn-warning" title="ترمیم"><i class="bi bi-pencil-square"></i></a>
                                    <form method="post" action="?pg=sal&id=<?php echo $sp['id']; ?>" style="display:inline;" onsubmit="return confirm('کیا آپ واقعی اس ادائیگی کو حذف کرنا چاہتے ہیں؟');">
                                        <input type="hidden" name="act" value="del_sp">
                                        <button type="submit" class="btn btn-sm btn-danger" title="حذف"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($salary_list)): ?>
                                <tr><td colspan="7" class="text-center">کوئی تنخواہ ادائیگی نہیں ملی۔</td></tr>
                             <?php else: ?>
                                <tr class="fw-bold">
                                    <td colspan="2" class="text-end">کل ادا شدہ تنخواہ:</td>
                                    <td><?php echo number_format($total_sal, 2); ?></td>
                                    <td colspan="4"></td>
                                </tr>
                             <?php endif; ?>
                        </tbody>
                    </table>
                </div>
             </div>

        <?php // --- Salary Payment Form (Add/Edit) ---
        elseif ($pg == 'sp_form'):
            $sp_data = $id ? get_sp($id) : [];
            $teachers = get_tchs();
            $form_act = $id ? 'upd_sp' : 'add_sp';
            $form_title = $id ? 'تنخواہ ادائیگی میں ترمیم کریں' : 'نئی تنخواہ ادائیگی شامل کریں';
            $months = ["1"=>"جنوری", "2"=>"فروری", "3"=>"مارچ", "4"=>"اپریل", "5"=>"مئی", "6"=>"جون", "7"=>"جولائی", "8"=>"اگست", "9"=>"ستمبر", "10"=>"اکتوبر", "11"=>"نومبر", "12"=>"دسمبر"];
            $years = range(date('Y') - 5, date('Y') + 1);
        ?>
            <h2><?php echo $form_title; ?></h2>
            <form method="post" action="?pg=sp_form&id=<?php echo $id; ?>">
                <input type="hidden" name="act" value="<?php echo $form_act; ?>">
                <div class="row g-3">
                     <div class="col-md-6">
                        <label for="tch_id" class="form-label">استاد <span class="text-danger">*</span></label>
                         <select class="form-select" id="tch_id" name="tch_id" required>
                             <option value="">استاد منتخب کریں</option>
                             <?php foreach ($teachers as $tch): ?>
                             <option value="<?php echo $tch['id']; ?>" <?php echo (isset($sp_data['tch_id']) && $sp_data['tch_id'] == $tch['id']) ? 'selected' : ''; ?>><?php echo esc($tch['n']); ?></option>
                             <?php endforeach; ?>
                         </select>
                    </div>
                     <div class="col-md-6">
                        <label for="amt" class="form-label">رقم <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="amt" name="amt" value="<?php echo esc($sp_data['amt'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-4">
                         <label for="dt" class="form-label">ادا شدہ تاریخ <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="dt" name="dt" value="<?php echo f_date($sp_data['dt'] ?? date('Y-m-d')); ?>" required>
                    </div>
                    <div class="col-md-4">
                         <label for="m" class="form-label">مہینہ <span class="text-danger">*</span></label>
                         <select class="form-select" id="m" name="m" required>
                             <?php foreach ($months as $num => $name): ?>
                                 <option value="<?php echo $num; ?>" <?php echo (isset($sp_data['m']) && $sp_data['m'] == $num) ? 'selected' : ( (!isset($sp_data['m'])) && $num == date('n') ? 'selected' : '' ); ?>><?php echo $name; ?></option>
                             <?php endforeach; ?>
                         </select>
                     </div>
                    <div class="col-md-4">
                         <label for="y" class="form-label">سال <span class="text-danger">*</span></label>
                         <select class="form-select" id="y" name="y" required>
                             <?php foreach ($years as $year): ?>
                                 <option value="<?php echo $year; ?>" <?php echo (isset($sp_data['y']) && $sp_data['y'] == $year) ? 'selected' : ( (!isset($sp_data['y'])) && $year == date('Y') ? 'selected' : '' ); ?>><?php echo $year; ?></option>
                             <?php endforeach; ?>
                         </select>
                     </div>
                 </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">محفوظ کریں</button>
                    <a href="?pg=sal" class="btn btn-secondary">منسوخ کریں</a>
                </div>
            </form>

        <?php // --- Inventory ---
        elseif ($pg == 'inv'):
            $inv_list = get_all_inv();
        ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>انوینٹری</h2>
                <a href="?pg=inv_form" class="btn btn-primary"><i class="bi bi-plus-circle"></i> نیا آئٹم شامل کریں</a>
            </div>
             <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>آئٹم کا نام</th>
                            <th>مقدار</th>
                            <th>شامل کرنے کی تاریخ</th>
                            <th>اعمال</th>
                        </tr>
                    </thead>
                    <tbody>
                         <?php foreach ($inv_list as $inv): ?>
                        <tr>
                            <td><?php echo $inv['id']; ?></td>
                            <td><?php echo esc($inv['itm']); ?></td>
                            <td><?php echo esc($inv['qty']); ?></td>
                            <td><?php echo u_date($inv['dt']); ?></td>
                            <td>
                                <a href="?pg=inv_form&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-warning" title="ترمیم"><i class="bi bi-pencil-square"></i></a>
                                <form method="post" action="?pg=inv&id=<?php echo $inv['id']; ?>" style="display:inline;" onsubmit="return confirm('کیا آپ واقعی اس آئٹم کو حذف کرنا چاہتے ہیں؟');">
                                    <input type="hidden" name="act" value="del_inv">
                                    <button type="submit" class="btn btn-sm btn-danger" title="حذف"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                         <?php if (empty($inv_list)): ?>
                             <tr><td colspan="5" class="text-center">کوئی انوینٹری آئٹم نہیں ملا۔</td></tr>
                         <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php // --- Inventory Form ---
        elseif ($pg == 'inv_form'):
            $inv_data = $id ? get_inv($id) : [];
            $form_act = $id ? 'upd_inv' : 'add_inv';
            $form_title = $id ? 'انوینٹری آئٹم میں ترمیم کریں' : 'نیا انوینٹری آئٹم شامل کریں';
        ?>
            <h2><?php echo $form_title; ?></h2>
             <form method="post" action="?pg=inv_form&id=<?php echo $id; ?>">
                <input type="hidden" name="act" value="<?php echo $form_act; ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="itm" class="form-label">آئٹم کا نام <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="itm" name="itm" value="<?php echo esc($inv_data['itm'] ?? ''); ?>" required>
                    </div>
                     <div class="col-md-3">
                        <label for="qty" class="form-label">مقدار <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="qty" name="qty" value="<?php echo esc($inv_data['qty'] ?? ''); ?>" required>
                    </div>
                     <div class="col-md-3">
                         <label for="dt" class="form-label">تاریخ <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="dt" name="dt" value="<?php echo f_date($inv_data['dt'] ?? date('Y-m-d')); ?>" required>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">محفوظ کریں</button>
                    <a href="?pg=inv" class="btn btn-secondary">منسوخ کریں</a>
                </div>
            </form>

        <?php // --- Income/Expenses ---
        elseif ($pg == 'inc_exp'):
            $inc_list = get_all_inc();
            $exp_list = get_all_exp();
            $total_inc = array_sum(array_column($inc_list, 'amt'));
            $total_exp = array_sum(array_column($exp_list, 'amt'));
        ?>
             <h2>آمدن اور اخراجات</h2>
             <div class="row">
                <div class="col-md-6">
                     <div class="card">
                         <div class="card-header d-flex justify-content-between align-items-center">
                            <span>آمدن</span>
                            <a href="?pg=inc_form" class="btn btn-success btn-sm"><i class="bi bi-plus-circle"></i> نئی آمدن شامل کریں</a>
                        </div>
                        <div class="card-body">
                             <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-striped">
                                    <thead><tr><th>ID</th><th>ذریعہ</th><th>رقم</th><th>تاریخ</th><th>اعمال</th></tr></thead>
                                    <tbody>
                                        <?php foreach($inc_list as $inc): ?>
                                        <tr>
                                            <td><?php echo $inc['id']; ?></td>
                                            <td><?php echo esc($inc['src']); ?></td>
                                            <td><?php echo number_format($inc['amt'], 2); ?></td>
                                            <td><?php echo u_date($inc['dt']); ?></td>
                                             <td>
                                                <a href="?pg=inc_form&id=<?php echo $inc['id']; ?>" class="btn btn-sm btn-warning py-0 px-1" title="ترمیم"><i class="bi bi-pencil-square"></i></a>
                                                <form method="post" action="?pg=inc_exp&id=<?php echo $inc['id']; ?>" style="display:inline;" onsubmit="return confirm('کیا آپ واقعی اس آمدنی کو حذف کرنا چاہتے ہیں؟');">
                                                    <input type="hidden" name="act" value="del_inc">
                                                    <button type="submit" class="btn btn-sm btn-danger py-0 px-1" title="حذف"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($inc_list)): ?>
                                             <tr><td colspan="5" class="text-center">کوئی آمدنی درج نہیں۔</td></tr>
                                         <?php endif; ?>
                                    </tbody>
                                    <tfoot><tr class="fw-bold"><td></td><td>کل</td><td><?php echo number_format($total_inc, 2); ?></td><td></td><td></td></tr></tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                 </div>
                 <div class="col-md-6">
                     <div class="card">
                         <div class="card-header d-flex justify-content-between align-items-center">
                            <span>اخراجات</span>
                             <a href="?pg=exp_form" class="btn btn-danger btn-sm"><i class="bi bi-plus-circle"></i> نیا خرچہ شامل کریں</a>
                        </div>
                        <div class="card-body">
                             <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-striped">
                                     <thead><tr><th>ID</th><th>تفصیل</th><th>رقم</th><th>تاریخ</th><th>اعمال</th></tr></thead>
                                    <tbody>
                                         <?php foreach($exp_list as $exp): ?>
                                        <tr>
                                            <td><?php echo $exp['id']; ?></td>
                                            <td><?php echo esc($exp['dsc']); ?></td>
                                            <td><?php echo number_format($exp['amt'], 2); ?></td>
                                            <td><?php echo u_date($exp['dt']); ?></td>
                                            <td>
                                                <a href="?pg=exp_form&id=<?php echo $exp['id']; ?>" class="btn btn-sm btn-warning py-0 px-1" title="ترمیم"><i class="bi bi-pencil-square"></i></a>
                                                <form method="post" action="?pg=inc_exp&id=<?php echo $exp['id']; ?>" style="display:inline;" onsubmit="return confirm('کیا آپ واقعی اس خرچے کو حذف کرنا چاہتے ہیں؟');">
                                                    <input type="hidden" name="act" value="del_exp">
                                                    <button type="submit" class="btn btn-sm btn-danger py-0 px-1" title="حذف"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                         <?php if (empty($exp_list)): ?>
                                             <tr><td colspan="5" class="text-center">کوئی خرچہ درج نہیں۔</td></tr>
                                         <?php endif; ?>
                                    </tbody>
                                    <tfoot><tr class="fw-bold"><td></td><td>کل</td><td><?php echo number_format($total_exp, 2); ?></td><td></td><td></td></tr></tfoot>
                                </table>
                            </div>
                        </div>
                     </div>
                 </div>
            </div>
             <div class="alert alert-info mt-4 text-center">
                 <strong>خالص آمدن: <?php echo number_format($total_inc - $total_exp, 2); ?></strong>
             </div>

        <?php // --- Income Form ---
        elseif ($pg == 'inc_form'):
            $inc_data = $id ? get_inc($id) : [];
            $form_act = $id ? 'upd_inc' : 'add_inc';
            $form_title = $id ? 'آمدن میں ترمیم کریں' : 'نئی آمدن شامل کریں';
        ?>
            <h2><?php echo $form_title; ?></h2>
             <form method="post" action="?pg=inc_form&id=<?php echo $id; ?>">
                <input type="hidden" name="act" value="<?php echo $form_act; ?>">
                <div class="row g-3">
                     <div class="col-md-5">
                        <label for="src" class="form-label">ذریعہ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="src" name="src" value="<?php echo esc($inc_data['src'] ?? ''); ?>" required>
                    </div>
                     <div class="col-md-4">
                        <label for="amt" class="form-label">رقم <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="amt" name="amt" value="<?php echo esc($inc_data['amt'] ?? ''); ?>" required>
                    </div>
                     <div class="col-md-3">
                         <label for="dt" class="form-label">تاریخ <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="dt" name="dt" value="<?php echo f_date($inc_data['dt'] ?? date('Y-m-d')); ?>" required>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">محفوظ کریں</button>
                    <a href="?pg=inc_exp" class="btn btn-secondary">منسوخ کریں</a>
                </div>
            </form>

        <?php // --- Expense Form ---
        elseif ($pg == 'exp_form'):
            $exp_data = $id ? get_exp($id) : [];
            $form_act = $id ? 'upd_exp' : 'add_exp';
            $form_title = $id ? 'خرچے میں ترمیم کریں' : 'نیا خرچہ شامل کریں';
        ?>
             <h2><?php echo $form_title; ?></h2>
             <form method="post" action="?pg=exp_form&id=<?php echo $id; ?>">
                <input type="hidden" name="act" value="<?php echo $form_act; ?>">
                <div class="row g-3">
                     <div class="col-md-5">
                        <label for="dsc" class="form-label">تفصیل <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="dsc" name="dsc" value="<?php echo esc($exp_data['dsc'] ?? ''); ?>" required>
                    </div>
                     <div class="col-md-4">
                        <label for="amt" class="form-label">رقم <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="amt" name="amt" value="<?php echo esc($exp_data['amt'] ?? ''); ?>" required>
                    </div>
                     <div class="col-md-3">
                         <label for="dt" class="form-label">تاریخ <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="dt" name="dt" value="<?php echo f_date($exp_data['dt'] ?? date('Y-m-d')); ?>" required>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">محفوظ کریں</button>
                    <a href="?pg=inc_exp" class="btn btn-secondary">منسوخ کریں</a>
                </div>
            </form>


         <?php // --- Reports ---
        elseif ($pg == 'rpt'):
            $students = get_sts();
            $teachers = get_tchs();
            $classes = get_cls();
            $months = ["1"=>"جنوری", "2"=>"فروری", "3"=>"مارچ", "4"=>"اپریل", "5"=>"مئی", "6"=>"جون", "7"=>"جولائی", "8"=>"اگست", "9"=>"ستمبر", "10"=>"اکتوبر", "11"=>"نومبر", "12"=>"دسمبر"];
            $years = range(date('Y') - 5, date('Y') + 1);

            // Get filter values from GET parameters
            $rep_type = $_GET['rep'] ?? 'st'; // Default to student report
            $st_id = $_GET['st_id'] ?? null;
            $tch_id = $_GET['tch_id'] ?? null;
            $cl_id = $_GET['cl_id'] ?? null;
            $start_dt = $_GET['start_dt'] ?? null;
            $end_dt = $_GET['end_dt'] ?? null;
            $m = $_GET['m'] ?? null;
            $y = $_GET['y'] ?? null;
            $status = $_GET['st'] ?? null; // Fee status

        ?>
            <h2>رپورٹس</h2>
            <form method="get" class="row g-3 align-items-center bg-light p-3 rounded mb-4 no-print">
                 <input type="hidden" name="pg" value="rpt">
                 <div class="col-md-3">
                     <label for="rep_type" class="form-label">رپورٹ کی قسم:</label>
                     <select name="rep" id="rep_type" class="form-select" onchange="this.form.submit()">
                         <option value="st" <?php echo ($rep_type == 'st') ? 'selected' : ''; ?>>طلباء کی فہرست</option>
                         <option value="att" <?php echo ($rep_type == 'att') ? 'selected' : ''; ?>>حاضری رپورٹ</option>
                         <option value="fee" <?php echo ($rep_type == 'fee') ? 'selected' : ''; ?>>فیس رپورٹ</option>
                         <option value="sal" <?php echo ($rep_type == 'sal') ? 'selected' : ''; ?>>تنخواہ رپورٹ</option>
                     </select>
                 </div>

                 <?php // Filters based on report type ?>
                 <?php if ($rep_type == 'st' || $rep_type == 'att' || $rep_type == 'fee'): ?>
                     <div class="col-md-3">
                         <label for="cl_id_filter" class="form-label">کلاس:</label>
                         <select name="cl_id" id="cl_id_filter" class="form-select">
                             <option value="">تمام کلاسیں</option>
                             <?php foreach ($classes as $cl): ?>
                                 <option value="<?php echo $cl['id']; ?>" <?php echo ($cl_id == $cl['id']) ? 'selected' : ''; ?>><?php echo esc($cl['n']); ?></option>
                             <?php endforeach; ?>
                         </select>
                     </div>
                 <?php endif; ?>

                 <?php if ($rep_type == 'att' || $rep_type == 'fee'): ?>
                      <div class="col-md-3">
                         <label for="st_id_filter" class="form-label">طالب علم:</label>
                         <select name="st_id" id="st_id_filter" class="form-select">
                             <option value="">تمام طلباء</option>
                             <?php foreach ($students as $st): ?>
                                 <option value="<?php echo $st['id']; ?>" <?php echo ($st_id == $st['id']) ? 'selected' : ''; ?>><?php echo esc($st['n']); ?></option>
                             <?php endforeach; ?>
                         </select>
                     </div>
                 <?php endif; ?>

                 <?php if ($rep_type == 'sal'): ?>
                     <div class="col-md-3">
                         <label for="tch_id_filter" class="form-label">استاد:</label>
                         <select name="tch_id" id="tch_id_filter" class="form-select">
                             <option value="">تمام اساتذہ</option>
                             <?php foreach ($teachers as $tch): ?>
                                 <option value="<?php echo $tch['id']; ?>" <?php echo ($tch_id == $tch['id']) ? 'selected' : ''; ?>><?php echo esc($tch['n']); ?></option>
                             <?php endforeach; ?>
                         </select>
                     </div>
                 <?php endif; ?>

                 <?php if ($rep_type == 'att'): ?>
                      <div class="col-md-3">
                         <label for="start_dt" class="form-label">شروع تاریخ:</label>
                         <input type="date" name="start_dt" id="start_dt" class="form-control" value="<?php echo esc($start_dt); ?>">
                     </div>
                      <div class="col-md-3">
                         <label for="end_dt" class="form-label">اختتام تاریخ:</label>
                         <input type="date" name="end_dt" id="end_dt" class="form-control" value="<?php echo esc($end_dt); ?>">
                     </div>
                 <?php endif; ?>

                  <?php if ($rep_type == 'fee' || $rep_type == 'sal'): ?>
                       <div class="col-md-2">
                        <label for="m_filter" class="form-label">مہینہ:</label>
                        <select name="m" id="m_filter" class="form-select form-select-sm">
                             <option value="">تمام</option>
                             <?php foreach ($months as $num => $name): ?>
                                 <option value="<?php echo $num; ?>" <?php echo ($m == $num) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                             <?php endforeach; ?>
                        </select>
                     </div>
                     <div class="col-md-2">
                        <label for="y_filter" class="form-label">سال:</label>
                        <select name="y" id="y_filter" class="form-select form-select-sm">
                             <option value="">تمام</option>
                             <?php foreach ($years as $year): ?>
                                 <option value="<?php echo $year; ?>" <?php echo ($y == $year) ? 'selected' : ''; ?>><?php echo $year; ?></option>
                             <?php endforeach; ?>
                        </select>
                     </div>
                 <?php endif; ?>

                  <?php if ($rep_type == 'fee'): ?>
                      <div class="col-md-2">
                         <label for="st_filter" class="form-label">کیفیت:</label>
                         <select name="st" id="st_filter" class="form-select form-select-sm">
                             <option value="">تمام</option>
                             <option value="Ada Shuda" <?php echo ($status == 'Ada Shuda') ? 'selected' : ''; ?>>ادا شدہ</option>
                             <option value="Baqaya" <?php echo ($status == 'Baqaya') ? 'selected' : ''; ?>>بقایا</option>
                         </select>
                     </div>
                 <?php endif; ?>


                 <div class="col-auto align-self-end">
                    <button type="submit" class="btn btn-primary">رپورٹ دیکھیں</button>
                 </div>
                 <div class="col-auto align-self-end ms-auto">
                      <?php // Construct Export URL with current filters
                       $export_params = http_build_query(array_filter(['pg' => 'rpt', 'export' => $rep_type, 'cl_id' => $cl_id, 'st_id' => $st_id, 'tch_id' => $tch_id, 'start_dt' => $start_dt, 'end_dt' => $end_dt, 'm' => $m, 'y' => $y, 'st' => $status]));
                      ?>
                     <a href="?<?php echo $export_params; ?>" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</a>
                     <button type="button" onclick="window.print()" class="btn btn-info btn-sm"><i class="bi bi-printer"></i> پرنٹ</button>
                </div>
            </form>

            <div class="printable">
                 <?php // --- Display Report Data ---
                 $report_title = "";
                 $filter_text = "";
                    // Build filter description for print header
                     $filters = [];
                     if ($cl_id) $filters[] = "کلاس: " . esc(get_cln($cl_id));
                     if ($st_id) $filters[] = "طالب علم: " . esc(get_stn($st_id));
                     if ($tch_id) $filters[] = "استاد: " . esc(get_tchn($tch_id));
                     if ($start_dt) $filters[] = "شروع تاریخ: " . u_date($start_dt);
                     if ($end_dt) $filters[] = "اختتام تاریخ: " . u_date($end_dt);
                     if ($m) $filters[] = "مہینہ: " . ($months[$m] ?? $m);
                     if ($y) $filters[] = "سال: " . esc($y);
                     if ($status) $filters[] = "کیفیت: " . esc($status);
                     if(!empty($filters)) $filter_text = "<br>فلٹرز: " . implode(', ', $filters);


                  if ($rep_type == 'st') {
                     $report_title = "طلباء کی فہرست";
                     $data = get_all_st($cl_id);
                     echo '<h3 class="text-center d-none d-print-block">'.esc(get_cfg('madrasa_name')).' - '.$report_title.'</h3>';
                     echo '<p class="text-center d-none d-print-block">تاریخ: '.u_date(date('Y-m-d')). $filter_text .'</p>';
                     echo '<div class="table-responsive"><table class="table table-striped table-bordered">';
                     echo '<thead><tr><th>ID</th><th>نام</th><th>والد کا نام</th><th>عمر</th><th>کلاس</th><th>داخلہ تاریخ</th><th>رابطہ</th><th>پتہ</th><th>'.esc(get_cfg('st_cf1_lbl')).'</th><th>'.esc(get_cfg('st_cf2_lbl')).'</th></tr></thead><tbody>';
                      if (empty($data)) echo '<tr><td colspan="10" class="text-center">کوئی ریکارڈ نہیں ملا۔</td></tr>';
                     foreach ($data as $r) {
                         echo '<tr><td>'.$r['id'].'</td><td>'.esc($r['n']).'</td><td>'.esc($r['fn']).'</td><td>'.esc($r['a']).'</td><td>'.esc($r['cln'] ?: '---').'</td><td>'.u_date($r['ad']).'</td><td>'.esc($r['c']).'</td><td>'.esc($r['adr']).'</td><td>'.esc($r['cf1']).'</td><td>'.esc($r['cf2']).'</td></tr>';
                     }
                     echo '</tbody></table></div>';
                  } elseif ($rep_type == 'att') {
                      $report_title = "حاضری رپورٹ";
                      $data = get_att_rpt($st_id, $cl_id, $start_dt, $end_dt);
                      $status_map = ['H' => 'حاضر', 'G' => 'غیر حاضر', 'R' => 'رخصت'];
                       echo '<h3 class="text-center d-none d-print-block">'.esc(get_cfg('madrasa_name')).' - '.$report_title.'</h3>';
                       echo '<p class="text-center d-none d-print-block">تاریخ: '.u_date(date('Y-m-d')). $filter_text .'</p>';
                       echo '<div class="table-responsive"><table class="table table-striped table-bordered">';
                       echo '<thead><tr><th>تاریخ</th><th>طالب علم</th><th>کلاس</th><th>کیفیت</th></tr></thead><tbody>';
                       if (empty($data)) echo '<tr><td colspan="4" class="text-center">کوئی ریکارڈ نہیں ملا۔</td></tr>';
                       foreach ($data as $r) {
                           echo '<tr><td>'.u_date($r['dt']).'</td><td>'.esc($r['stn']).'</td><td>'.esc($r['cln']).'</td><td>'.($status_map[$r['st']] ?? esc($r['st'])).'</td></tr>';
                       }
                       echo '</tbody></table></div>';
                  } elseif ($rep_type == 'fee') {
                     $report_title = "فیس رپورٹ";
                     $data = get_all_fp($st_id, $cl_id, $m, $y, $status);
                      $total_paid = 0; $total_due = 0;
                       echo '<h3 class="text-center d-none d-print-block">'.esc(get_cfg('madrasa_name')).' - '.$report_title.'</h3>';
                       echo '<p class="text-center d-none d-print-block">تاریخ: '.u_date(date('Y-m-d')). $filter_text .'</p>';
                       echo '<div class="table-responsive"><table class="table table-striped table-bordered">';
                       echo '<thead><tr><th>ID</th><th>طالب علم</th><th>والد کا نام</th><th>کلاس</th><th>رقم</th><th>ادا شدہ تاریخ</th><th>مہینہ</th><th>سال</th><th>کیفیت</th></tr></thead><tbody>';
                       if (empty($data)) echo '<tr><td colspan="9" class="text-center">کوئی ریکارڈ نہیں ملا۔</td></tr>';
                       foreach ($data as $r) {
                           if ($r['st'] == 'Ada Shuda') $total_paid += $r['amt']; else $total_due += $r['amt'];
                            echo '<tr class="'.($r['st'] == 'Baqaya' ? 'table-danger' : '').'"><td>'.$r['id'].'</td><td>'.esc($r['stn']).'</td><td>'.esc($r['stfn']).'</td><td>'.esc($r['cln']).'</td><td>'.number_format($r['amt'], 2).'</td><td>'.u_date($r['dt']).'</td><td>'.($months[$r['m']] ?? $r['m']).'</td><td>'.$r['y'].'</td><td>'.esc($r['st']).'</td></tr>';
                       }
                        if (!empty($data)) {
                             echo '<tr class="fw-bold"><td colspan="4" class="text-end">کل ادا شدہ:</td><td>'.number_format($total_paid, 2).'</td><td colspan="3" class="text-end">کل بقایا:</td><td>'.number_format($total_due, 2).'</td></tr>';
                        }
                       echo '</tbody></table></div>';
                  } elseif ($rep_type == 'sal') {
                      $report_title = "تنخواہ رپورٹ";
                      $data = get_all_sp($tch_id, $m, $y);
                      $total_sal = 0;
                       echo '<h3 class="text-center d-none d-print-block">'.esc(get_cfg('madrasa_name')).' - '.$report_title.'</h3>';
                       echo '<p class="text-center d-none d-print-block">تاریخ: '.u_date(date('Y-m-d')). $filter_text .'</p>';
                       echo '<div class="table-responsive"><table class="table table-striped table-bordered">';
                       echo '<thead><tr><th>ID</th><th>استاد</th><th>رقم</th><th>ادا شدہ تاریخ</th><th>مہینہ</th><th>سال</th></tr></thead><tbody>';
                       if (empty($data)) echo '<tr><td colspan="6" class="text-center">کوئی ریکارڈ نہیں ملا۔</td></tr>';
                       foreach ($data as $r) {
                           $total_sal += $r['amt'];
                           echo '<tr><td>'.$r['id'].'</td><td>'.esc($r['tchn']).'</td><td>'.number_format($r['amt'], 2).'</td><td>'.u_date($r['dt']).'</td><td>'.($months[$r['m']] ?? $r['m']).'</td><td>'.$r['y'].'</td></tr>';
                       }
                        if (!empty($data)) {
                            echo '<tr class="fw-bold"><td colspan="2" class="text-end">کل ادا شدہ تنخواہ:</td><td>'.number_format($total_sal, 2).'</td><td colspan="3"></td></tr>';
                        }
                       echo '</tbody></table></div>';
                  } else {
                      echo '<div class="alert alert-warning">براہ کرم ایک درست رپورٹ کی قسم منتخب کریں۔</div>';
                  }
                 ?>
            </div>


        <?php // --- Settings ---
        elseif ($pg == 'cfg'): ?>
             <h2>ترتیبات</h2>
            <form method="post" enctype="multipart/form-data">
                 <input type="hidden" name="act" value="upd_cfg">
                 <div class="card mb-3">
                    <div class="card-header">مدرسہ کی معلومات</div>
                    <div class="card-body row g-3">
                        <div class="col-md-6">
                            <label for="madrasa_name" class="form-label">مدرسہ کا نام</label>
                            <input type="text" class="form-control" id="madrasa_name" name="madrasa_name" value="<?php echo esc(get_cfg('madrasa_name')); ?>">
                        </div>
                         <div class="col-md-6">
                             <label for="madrasa_phone" class="form-label">فون نمبر</label>
                             <input type="text" class="form-control" id="madrasa_phone" name="madrasa_phone" value="<?php echo esc(get_cfg('madrasa_phone')); ?>">
                         </div>
                         <div class="col-12">
                             <label for="madrasa_address" class="form-label">پتہ</label>
                            <textarea class="form-control" id="madrasa_address" name="madrasa_address" rows="2"><?php echo esc(get_cfg('madrasa_address')); ?></textarea>
                         </div>
                         <div class="col-md-6">
                             <label for="madrasa_logo" class="form-label">لوگو اپ لوڈ کریں (اختیاری)</label>
                             <input type="file" class="form-control" id="madrasa_logo" name="madrasa_logo" accept="image/png, image/jpeg, image/gif">
                              <?php $logo = get_cfg('madrasa_logo'); if ($logo && file_exists(UPLOAD_DIR . $logo)): ?>
                                 <img src="<?php echo UPLOAD_DIR . esc($logo); ?>" alt="Current Logo" class="img-thumbnail mt-2" style="max-height: 80px;">
                             <?php endif; ?>
                         </div>
                     </div>
                 </div>
                  <div class="card">
                     <div class="card-header">اضافی فیلڈز کے لیبل</div>
                     <div class="card-body row g-3">
                        <div class="col-md-6">
                             <label for="st_cf1_lbl" class="form-label">طالب علم - اضافی فیلڈ 1</label>
                             <input type="text" class="form-control" id="st_cf1_lbl" name="st_cf1_lbl" value="<?php echo esc(get_cfg('st_cf1_lbl') ?: 'اضافی فیلڈ 1'); ?>">
                         </div>
                         <div class="col-md-6">
                             <label for="st_cf2_lbl" class="form-label">طالب علم - اضافی فیلڈ 2</label>
                             <input type="text" class="form-control" id="st_cf2_lbl" name="st_cf2_lbl" value="<?php echo esc(get_cfg('st_cf2_lbl') ?: 'اضافی فیلڈ 2'); ?>">
                         </div>
                        <div class="col-md-6">
                             <label for="tch_cf1_lbl" class="form-label">استاد - اضافی فیلڈ 1</label>
                             <input type="text" class="form-control" id="tch_cf1_lbl" name="tch_cf1_lbl" value="<?php echo esc(get_cfg('tch_cf1_lbl') ?: 'اضافی فیلڈ 1'); ?>">
                         </div>
                         <div class="col-md-6">
                             <label for="tch_cf2_lbl" class="form-label">استاد - اضافی فیلڈ 2</label>
                             <input type="text" class="form-control" id="tch_cf2_lbl" name="tch_cf2_lbl" value="<?php echo esc(get_cfg('tch_cf2_lbl') ?: 'اضافی فیلڈ 2'); ?>">
                         </div>
                     </div>
                 </div>

                 <div class="mt-4">
                    <button type="submit" class="btn btn-primary">ترتیبات محفوظ کریں</button>
                </div>
             </form>


        <?php // --- Backup/Restore ---
        elseif ($pg == 'backup'): ?>
            <h2>ڈیٹا بیس بیک اپ اور بحالی</h2>

            <div class="card mb-4">
                <div class="card-header">ڈیٹا بیس بیک اپ</div>
                <div class="card-body">
                    <p>موجودہ ڈیٹا بیس فائل (تمام طلباء، اساتذہ، فیس، حاضری وغیرہ) کا بیک اپ ڈاؤن لوڈ کرنے کے لیے نیچے دیئے گئے بٹن پر کلک کریں۔ اس فائل کو محفوظ جگہ پر رکھیں۔</p>
                    <a href="?pg=backup&download=1" class="btn btn-success"><i class="bi bi-download"></i> ڈیٹا بیس بیک اپ ڈاؤن لوڈ کریں</a>
                </div>
            </div>

            <div class="card border-danger">
                <div class="card-header bg-danger text-white">ڈیٹا بیس بحال کریں</div>
                <div class="card-body">
                    <p class="text-danger"><strong>انتباہ:</strong> ڈیٹا بیس بحال کرنے سے موجودہ تمام ڈیٹا اوور رائٹ ہو جائے گا۔ یہ عمل ناقابل واپسی ہے۔ صرف اس صورت میں آگے بڑھیں جب آپ کو یقین ہو کہ آپ پچھلا بیک اپ بحال کرنا چاہتے ہیں۔</p>
                    <form method="post" enctype="multipart/form-data" onsubmit="return confirm('کیا آپ واقعی ڈیٹا بیس کو اس فائل سے بحال کرنا چاہتے ہیں؟ موجودہ تمام ڈیٹا ضائع ہو جائے گا!');">
                        <input type="hidden" name="act" value="restore_db">
                        <div class="mb-3">
                            <label for="db_file" class="form-label">بیک اپ فائل منتخب کریں (.sqlite فائل)</label>
                            <input type="file" class="form-control" id="db_file" name="db_file" accept=".sqlite" required>
                        </div>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-upload"></i> ڈیٹا بیس بحال کریں</button>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <div class="alert alert-danger">صفحہ نہیں ملا۔</div>
        <?php endif; ?>

    </div>

    <footer class="mt-5 py-3 bg-light text-center no-print" style="font-size: 0.9em;">
        <p class="mb-0">مدرسہ مینجمنٹ سسٹم &copy; <?php echo date('Y'); ?> </p>
        <p class="text-muted mb-0">ڈیٹا بیس فائل: <?php echo DB_FILE; ?> (<?php echo round(filesize(DB_FILE) / 1024, 2); ?> KB)</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('dt');
    const submitButton = document.querySelector('button.btn.btn-secondary');
    
    if (dateInput && submitButton) {
        dateInput.addEventListener('change', function() {
            submitButton.click();
        });
    }
});
</script>
</body>
</html>
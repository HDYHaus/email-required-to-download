<?php
// Standalone tests: only temporary files and an in-memory database double.
define('ABSPATH', __DIR__);
define('WP_CONTENT_DIR', sys_get_temp_dir() . '/erd-release-' . uniqid());
mkdir(WP_CONTENT_DIR);
mkdir(WP_CONTENT_DIR . '/erd-protected');
function absint($v) { return abs((int)$v); }
function esc_sql($v) { return $v; }
function trailingslashit($v) { return rtrim($v, '/') . '/'; }
function delete_option($v) { $GLOBALS['deleted_options'][] = $v; }
class ERD_Activator { public static function protected_dir() { return WP_CONTENT_DIR . '/erd-protected'; } }
class FixtureDB {
    public $prefix = 'fixture_';
    public $files = array();
    public $queries = array();
    public function prepare($sql, ...$args) { return array($sql, $args); }
    public function get_results($q) { return array_values(array_filter($this->files, function($f) use ($q) { return $f->offer_id === $q[1][0]; })); }
    public function get_var($q) {
        foreach ($this->files as $f) {
            if ($f->protected_path === $q[1][0] && $f->offer_id !== $q[1][1]) { return $f->id; }
        }
        return null;
    }
    public function delete($table, $where) {
        if ($table === $this->prefix . 'erd_offer_files') {
            $this->files = array_filter($this->files, function($f) use ($where) { return $f->offer_id !== $where['offer_id']; });
        }
    }
    public function query($sql) { $this->queries[] = $sql; }
}
$wpdb = new FixtureDB();
require dirname(__DIR__) . '/includes/class-erd-repository.php';
require dirname(__DIR__) . '/includes/class-erd-admin.php';
function check($condition, $name) {
    if (!$condition) { throw new Exception($name); }
    echo "PASS: $name\n";
}
$repo = new ERD_Repository();
$shared = WP_CONTENT_DIR . '/erd-protected/shared.pdf';
file_put_contents($shared, 'fixture');
foreach (array(1, 2) as $id) { $wpdb->files[] = (object) array('id'=>$id,'offer_id'=>$id,'protected_path'=>$shared); }
$repo->delete_offer(1);
check(file_exists($shared), 'Deleting first offer preserves shared protected file');
$repo->delete_offer(2);
check(!file_exists($shared), 'Deleting final referencing offer removes protected copy');
$outside = WP_CONTENT_DIR . '/original.pdf';
file_put_contents($outside, 'media original');
$wpdb->files[] = (object) array('id'=>3,'offer_id'=>3,'protected_path'=>$outside);
$repo->delete_offer(3);
check(file_exists($outside), 'Deleting offer cannot remove a file outside protected storage');
$admin = new ERD_Admin($repo);
$cell = new ReflectionMethod($admin, 'csv_cell');
$cell->setAccessible(true);
foreach (array('=1+1','+SUM(A1:A2)','-1+2','@SUM(A1:A2)',"\t=1+1"," \n=1+1") as $formula) {
    check($cell->invoke($admin, $formula) === "'" . $formula, 'CSV formula escaped: ' . json_encode($formula));
}
foreach (array('Mary Job','Consent as submitted','2026-11-15 10:00:00',0,1,null) as $value) {
    check($cell->invoke($admin, $value) === (string)$value, 'Ordinary CSV data preserved: ' . json_encode($value));
}
file_put_contents(WP_CONTENT_DIR . '/erd-protected/.htaccess', 'Deny from all');
file_put_contents(WP_CONTENT_DIR . '/erd-protected/index.php', '<?php');
file_put_contents(WP_CONTENT_DIR . '/erd-protected/direct-upload.pdf', 'private fixture');
define('WP_UNINSTALL_PLUGIN', 'email-required-to-download/email-required-to-download.php');
require dirname(__DIR__) . '/uninstall.php';
check(count($wpdb->queries) === 3, 'Uninstall drops all three plugin tables');
check($deleted_options === array('erd_db_version','erd_settings'), 'Uninstall removes only plugin options');
check(!is_dir(WP_CONTENT_DIR . '/erd-protected'), 'Uninstall removes protected uploads and hidden access rules');
check(file_exists($outside), 'Uninstall preserves original media outside plugin storage');
unlink($outside);
rmdir(WP_CONTENT_DIR);

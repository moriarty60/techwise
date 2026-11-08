<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
define('BASE','/techwise');
$pdo=new PDO('mysql:host=localhost;dbname=techwise;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
function e($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function user(){global $pdo;static $u=false;if($u===false){$u=null;if(!empty($_SESSION['uid'])){$s=$pdo->prepare('SELECT id,full_name,email,role FROM users WHERE id=?');$s->execute([$_SESSION['uid']]);$u=$s->fetch()?:null;}}return $u;}
function csrf(){if(empty($_SESSION['t']))$_SESSION['t']=bin2hex(random_bytes(16));return '<input type="hidden" name="t" value="'.$_SESSION['t'].'">';}
function check(){if(!hash_equals($_SESSION['t']??'',$_POST['t']??''))die('Token tidak valid');}
function need($roles){$u=user();if(!$u||!in_array($u['role'],$roles,true)){header('Location: '.BASE.'/login.php');exit;}return $u;}
function flash($m=null){if($m!==null){$_SESSION['f']=$m;return '';}$x=$_SESSION['f']??'';unset($_SESSION['f']);return $x;}
function go($p){header('Location: '.BASE.$p);exit;}
function head($t){$u=user();$b=BASE;echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($t).'</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Inter:wght@400;600;800&display=swap"><script>document.documentElement.classList.add("js")</script><link rel="stylesheet" href="'.$b.'/theme.css?v='.@filemtime(__DIR__.'/theme.css').'"></head><body><header class="isl" id="isl"><a class="logo" href="'.$b.'/"><i></i><span>Wisely</span></a><nav id="links"><a href="'.$b.'/">Home</a><a href="'.$b.'/#tips">Tips</a><a href="'.$b.'/#artikel">Artikel</a><a href="'.$b.'/quiz.php">Kuis</a>'.($u&&$u['role']!=='user'?'<a href="'.$b.'/admin/">Admin</a>':'').($u?'<a href="'.$b.'/profile.php">Profil</a><a href="'.$b.'/logout.php">Keluar</a>':'<a href="'.$b.'/login.php">Masuk</a>').'</nav><button class="mb" id="mb" aria-label="Menu" aria-expanded="false">&#9776;</button></header><main class="wrap">';if($f=flash())echo '<p class="toast">'.e($f).'</p>';}
function foot(){global $pdo;$s=$pdo->query('SELECT site_name,contact_email FROM website_settings WHERE id=1')->fetch()?:[];echo '</main><footer class="foot">'.e($s['site_name']??'').' &middot; <a href="'.BASE.'/feedback.php">Kirim masukan</a>'.(!empty($s['contact_email'])?' &middot; '.e($s['contact_email']):'').'</footer><script src="'.BASE.'/app.js"></script></body></html>';}

// ---- Image upload helpers (type checked by file content, random names, size limits) ----
function upload_image(string $field, string $dir, int $max): array {
    $f = $_FILES[$field] ?? null;
    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) { return [null, 'Pilih file gambar dulu.']; }
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return [null, in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 'File terlalu besar.' : 'Upload gagal.'];
    }
    if ($f['size'] > $max) { return [null, 'Ukuran file maksimal ' . round($max / 1048576) . ' MB.']; }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext || !@getimagesize($f['tmp_name'])) { return [null, 'Hanya gambar JPG, PNG, atau WebP.']; }
    $d = __DIR__ . "/uploads/$dir";
    if (!is_dir($d) && !mkdir($d, 0755, true)) { return [null, 'Folder upload tidak bisa dibuat.']; }
    $name = bin2hex(random_bytes(12)) . ".$ext";
    if (!move_uploaded_file($f['tmp_name'], "$d/$name")) { return [null, 'Gagal menyimpan file.']; }
    return ["uploads/$dir/$name", null];
}
function del_file($rel) {
    if ($rel && preg_match('#^uploads/(covers|avatars)/[a-f0-9]+\.(jpg|png|webp)$#', $rel)) { @unlink(__DIR__ . '/' . $rel); }
}
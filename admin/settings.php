<?php
require '../config.php';
need(['admin']);
$err = '';
$cur = $pdo->query('SELECT * FROM website_settings WHERE id = 1')->fetch() ?: [];
$soc = json_decode($cur['social_links'] ?? '{}', true) ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $name = trim($_POST['site_name'] ?? '');
    $desc = trim($_POST['site_description'] ?? '');
    $mail = trim($_POST['contact_email'] ?? '');
    $soc = ['instagram' => trim($_POST['instagram'] ?? ''), 'youtube' => trim($_POST['youtube'] ?? '')];
    if ($name === '' || mb_strlen($name) > 120) {
        $err = 'Nama situs wajib diisi (maksimal 120 karakter).';
    } elseif (mb_strlen($desc) > 255) {
        $err = 'Deskripsi maksimal 255 karakter.';
    } elseif ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        $err = 'Format email tidak valid.';
    } else {
        $pdo->prepare('INSERT INTO website_settings (id, site_name, site_description, contact_email, social_links) VALUES (1, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE site_name = VALUES(site_name), site_description = VALUES(site_description), contact_email = VALUES(contact_email), social_links = VALUES(social_links)')
            ->execute([$name, $desc, $mail, json_encode($soc)]);
        flash('Pengaturan disimpan.');
        go('/admin/settings.php');
    }
    $cur = ['site_name' => $name, 'site_description' => $desc, 'contact_email' => $mail];
}
head('Pengaturan');
?>
<h1>Pengaturan website</h1>
<a class="btn s" href="index.php">Dashboard</a>
<form method="post" class="card" style="margin:24px 0">
    <?php if ($err) { echo '<p class="err">' . e($err) . '</p>'; } ?>
    <?= csrf() ?>
    <label>Nama situs <input name="site_name" maxlength="120" required value="<?= e($cur['site_name'] ?? '') ?>"></label>
    <label>Deskripsi <input name="site_description" maxlength="255" value="<?= e($cur['site_description'] ?? '') ?>"></label>
    <label>Email kontak <input type="email" name="contact_email" value="<?= e($cur['contact_email'] ?? '') ?>"></label>
    <label>Instagram <input name="instagram" value="<?= e($soc['instagram'] ?? '') ?>"></label>
    <label>YouTube <input name="youtube" value="<?= e($soc['youtube'] ?? '') ?>"></label>
    <button class="btn">Simpan pengaturan</button>
</form>
<?php foot();

<?php
require 'config.php';
$u = user();
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $msg = trim($_POST['message'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    if ($name === '' || mb_strlen($name) > 120) {
        $err = 'Nama wajib diisi (maksimal 120 karakter).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        $err = 'Format email tidak valid.';
    } elseif (mb_strlen($msg) < 5 || mb_strlen($msg) > 2000) {
        $err = 'Pesan harus 5 sampai 2000 karakter.';
    } elseif ($rating < 0 || $rating > 5) {
        $err = 'Rating tidak valid.';
    } else {
        $pdo->prepare('INSERT INTO feedback (user_id, name, email, message, rating) VALUES (?, ?, ?, ?, ?)')
            ->execute([$u['id'] ?? null, $name, $email, $msg, $rating ?: null]);
        flash('Terima kasih, masukan Anda sudah terkirim.');
        go('/feedback.php');
    }
}
head('Kirim masukan');
?>
<form method="post" class="card narrow">
    <h2>Kirim masukan</h2>
    <?php if ($err) { echo '<p class="err">' . e($err) . '</p>'; } ?>
    <?= csrf() ?>
    <label>Nama <input name="name" maxlength="120" required value="<?= e($_POST['name'] ?? $u['full_name'] ?? '') ?>"></label>
    <label>Email <input type="email" name="email" maxlength="190" required value="<?= e($_POST['email'] ?? $u['email'] ?? '') ?>"></label>
    <label>Rating
        <select name="rating">
            <option value="0">Tanpa rating</option>
            <?php for ($i = 5; $i >= 1; $i--) { echo '<option value="' . $i . '">' . $i . ' dari 5</option>'; } ?>
        </select>
    </label>
    <label>Pesan <textarea name="message" rows="5" maxlength="2000" required><?= e($_POST['message'] ?? '') ?></textarea></label>
    <button class="btn">Kirim</button>
</form>
<?php foot();

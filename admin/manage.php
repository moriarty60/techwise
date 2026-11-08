<?php
require '../config.php';
$u = need(['admin', 'editor']);
$cats = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);

function slugify($s) { return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($s)), '-'); }

// Whitelist: only these tables/columns can ever be touched. Field = [label, type, required/options]
$T = [
  'tips' => ['label' => 'Tips', 'order' => 'display_order, id', 'list' => ['title', 'display_order', 'is_active'], 'fields' => [
    'title' => ['Judul', 'text', true], 'description' => ['Deskripsi singkat', 'text', true],
    'detailed_content' => ['Isi detail', 'area', false], 'category_id' => ['Kategori', 'cat', false],
    'display_order' => ['Urutan tampil', 'number', false], 'is_active' => ['Aktif', 'check', false]]],
  'categories' => ['label' => 'Kategori', 'order' => 'name', 'list' => ['name', 'slug'], 'fields' => [
    'name' => ['Nama', 'text', true], 'slug' => ['Slug (kosongkan untuk otomatis)', 'slug', false],
    'description' => ['Deskripsi', 'text', false], 'icon' => ['Ikon', 'text', false]]],
  'quizzes' => ['label' => 'Kuis', 'order' => 'id', 'list' => ['question', 'correct_answer', 'difficulty'], 'fields' => [
    'question' => ['Pertanyaan', 'text', true], 'option_a' => ['Pilihan A', 'text', true], 'option_b' => ['Pilihan B', 'text', true],
    'option_c' => ['Pilihan C', 'text', true], 'option_d' => ['Pilihan D', 'text', true],
    'correct_answer' => ['Jawaban benar', 'select', ['a', 'b', 'c', 'd']], 'explanation' => ['Penjelasan', 'text', false],
    'difficulty' => ['Tingkat', 'select', ['easy', 'medium', 'hard']], 'category_id' => ['Kategori', 'cat', false]]],
];
$t = $_GET['t'] ?? 'tips';
if (!isset($T[$t])) { $t = 'tips'; }
$cfg = $T[$t];
$err = '';
$row = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $id = (int)($_POST['id'] ?? 0);
    if (($_POST['a'] ?? '') === 'del') {
        $pdo->prepare("DELETE FROM $t WHERE id = ?")->execute([$id]);
        flash('Data dihapus.');
        go("/admin/manage.php?t=$t");
    }
    if ($t === 'categories' && trim($_POST['slug'] ?? '') === '') {
        $_POST['slug'] = slugify($_POST['name'] ?? '');
    }
    $vals = [];
    foreach ($cfg['fields'] as $f => [$label, $type, $x]) {
        $v = trim($_POST[$f] ?? '');
        if ($type === 'check') {
            $v = isset($_POST[$f]) ? 1 : 0;
        } elseif ($type === 'number') {
            $v = (int)$v;
        } elseif ($type === 'cat') {
            $v = (int)$v;
            if ($v && !isset($cats[$v])) { $err = 'Kategori tidak valid.'; }
            $v = $v ?: null;
        } elseif ($type === 'select') {
            if (!in_array($v, $x, true)) { $err = "$label tidak valid."; }
        } elseif ($type === 'slug') {
            if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $v)) { $err = 'Slug hanya huruf kecil, angka, dan tanda hubung.'; }
        } elseif ($x && $v === '') {
            $err = "$label wajib diisi.";
        }
        $vals[$f] = $v;
    }
    if (!$err) {
        try {
            if ($id) {
                $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($vals)));
                $pdo->prepare("UPDATE $t SET $set WHERE id = ?")->execute([...array_values($vals), $id]);
            } else {
                $cols = implode(', ', array_keys($vals));
                $ph = implode(', ', array_fill(0, count($vals), '?'));
                $pdo->prepare("INSERT INTO $t ($cols) VALUES ($ph)")->execute(array_values($vals));
            }
            flash('Data disimpan.');
            go("/admin/manage.php?t=$t");
        } catch (PDOException $ex) {
            $err = ($ex->getCode() === '23000') ? 'Data duplikat: nama atau slug sudah dipakai.' : 'Gagal menyimpan. Periksa panjang teks.';
        }
    }
    $row = $vals + ['id' => $id];
} elseif (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM $t WHERE id = ?");
    $s->execute([(int)$_GET['edit']]);
    $row = $s->fetch() ?: [];
}
$list = $pdo->query("SELECT * FROM $t ORDER BY " . $cfg['order'])->fetchAll();
head('Kelola ' . $cfg['label']);
?>
<h1>Kelola <?= e($cfg['label']) ?></h1>
<div class="row">
    <a class="btn s" href="index.php">Dashboard</a>
    <?php foreach ($T as $k => $c): ?>
        <a class="btn s" href="?t=<?= $k ?>"><?= e($c['label']) ?></a>
    <?php endforeach; ?>
</div>

<form method="post" action="?t=<?= $t ?>" class="card" style="margin:24px 0">
    <h3><?= !empty($row['id']) ? 'Ubah data' : 'Tambah data' ?></h3>
    <?php if ($err) { echo '<p class="err">' . e($err) . '</p>'; } ?>
    <?= csrf() ?>
    <input type="hidden" name="id" value="<?= (int)($row['id'] ?? 0) ?>">
    <?php foreach ($cfg['fields'] as $f => [$label, $type, $x]):
        $v = $row[$f] ?? ($type === 'check' ? 1 : ''); ?>
        <label><?= e($label) ?>
        <?php if ($type === 'area'): ?>
            <textarea name="<?= $f ?>" rows="4"><?= e($v) ?></textarea>
        <?php elseif ($type === 'select'): ?>
            <select name="<?= $f ?>"><?php foreach ($x as $o) { echo '<option value="' . $o . '"' . ($o === $v ? ' selected' : '') . '>' . e(strtoupper($o)) . '</option>'; } ?></select>
        <?php elseif ($type === 'cat'): ?>
            <select name="<?= $f ?>"><option value="0">Tanpa kategori</option><?php foreach ($cats as $cid => $cn) { echo '<option value="' . $cid . '"' . ($cid == $v ? ' selected' : '') . '>' . e($cn) . '</option>'; } ?></select>
        <?php elseif ($type === 'check'): ?>
            <input type="checkbox" name="<?= $f ?>" style="width:auto" <?= $v ? 'checked' : '' ?>>
        <?php else: ?>
            <input type="<?= $type === 'number' ? 'number' : 'text' ?>" name="<?= $f ?>" value="<?= e($v) ?>">
        <?php endif; ?>
        </label>
    <?php endforeach; ?>
    <button class="btn">Simpan</button>
    <?php if (!empty($row['id'])): ?><a class="btn s" href="?t=<?= $t ?>" style="margin-left:8px">Batal</a><?php endif; ?>
</form>

<table>
    <tr><?php foreach ($cfg['list'] as $c) { echo '<th>' . e($c) . '</th>'; } ?><th></th></tr>
    <?php foreach ($list as $r): ?>
        <tr>
            <?php foreach ($cfg['list'] as $c): ?>
                <td><?= $c === 'is_active' ? ($r[$c] ? 'Ya' : 'Tidak') : e($r[$c]) ?></td>
            <?php endforeach; ?>
            <td class="row">
                <a class="btn s" href="?t=<?= $t ?>&edit=<?= $r['id'] ?>">Edit</a>
                <form method="post" action="?t=<?= $t ?>" onsubmit="return confirm('Hapus data ini? Data yang terkait (misalnya hasil kuis) ikut terhapus.')">
                    <?= csrf() ?><input type="hidden" name="a" value="del"><input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button class="btn s d">Hapus</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php if (!$list) { echo '<p class="muted">Belum ada data.</p>'; } ?>
<?php foot();

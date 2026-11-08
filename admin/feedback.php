<?php
require '../config.php';
need(['admin', 'editor']);
$labels = ['new' => 'Baru', 'read' => 'Dibaca', 'archived' => 'Arsip'];
$s = $_GET['s'] ?? '';
if (!isset($labels[$s])) { $s = ''; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $id = (int)($_POST['id'] ?? 0);
    $a = $_POST['a'] ?? '';
    if ($a === 'del') {
        $pdo->prepare('DELETE FROM feedback WHERE id = ?')->execute([$id]);
        flash('Masukan dihapus.');
    } elseif (isset($labels[$a])) {
        $pdo->prepare('UPDATE feedback SET status = ? WHERE id = ?')->execute([$a, $id]);
        flash('Status diperbarui.');
    }
    go('/admin/feedback.php' . ($s ? "?s=$s" : ''));
}

if ($s) {
    $q = $pdo->prepare('SELECT * FROM feedback WHERE status = ? ORDER BY created_at DESC');
    $q->execute([$s]);
} else {
    $q = $pdo->query('SELECT * FROM feedback ORDER BY created_at DESC');
}
$rows = $q->fetchAll();
head('Feedback');
?>
<h1>Feedback</h1>
<div class="row">
    <a class="btn s" href="index.php">Dashboard</a>
    <a class="btn s" href="?">Semua</a>
    <?php foreach ($labels as $k => $l) { echo '<a class="btn s" href="?s=' . $k . '">' . e($l) . '</a>'; } ?>
</div>
<?php foreach ($rows as $r): ?>
    <div class="card" style="margin:16px 0">
        <b><?= e($r['name']) ?></b> <span class="muted">&lt;<?= e($r['email']) ?>&gt; · <?= e($r['created_at']) ?> · <?= e($labels[$r['status']]) ?></span>
        <?php if ($r['rating']) { echo '<p class="num" style="font-size:24px">' . str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) . '</p>'; } ?>
        <p><?= nl2br(e($r['message'])) ?></p>
        <form method="post" class="row" style="margin-top:10px" onsubmit="return event.submitter.value !== 'del' || confirm('Hapus masukan ini secara permanen?')">
            <?= csrf() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button name="a" value="read" class="btn s">Tandai dibaca</button>
            <button name="a" value="archived" class="btn s">Arsipkan</button>
            <button name="a" value="del" class="btn s d">Hapus</button>
        </form>
    </div>
<?php endforeach; ?>
<?php if (!$rows) { echo '<p class="muted" style="margin-top:20px">Belum ada masukan.</p>'; } ?>
<?php foot();

<?php
require 'config.php';
$u = need(['admin', 'editor', 'user']);

$qs = $pdo->query('SELECT * FROM quizzes ORDER BY id')->fetchAll();
$res = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $ts = date('Y-m-d H:i:s');
    $ok = 0;
    $det = [];
    $ins = $pdo->prepare('INSERT INTO quiz_results (user_id, quiz_id, selected_answer, is_correct, completed_at) VALUES (?, ?, ?, ?, ?)');
    $pdo->beginTransaction();
    foreach ($qs as $q) {
        $a = $_POST['q'][$q['id']] ?? '';
        if (!in_array($a, ['a', 'b', 'c', 'd'], true)) {
            continue;
        }
        $c = ($a === $q['correct_answer']);
        $ok += (int)$c;
        $ins->execute([$u['id'], $q['id'], $a, (int)$c, $ts]);
        $det[] = ['q' => $q, 'a' => $a, 'c' => $c];
    }
    $pdo->commit();
    if (!$det) {
        flash('Pilih minimal satu jawaban.');
    } else {
        $res = ['ok' => $ok, 'n' => count($det), 'det' => $det];
    }
}

$h = $pdo->prepare('SELECT completed_at, COUNT(*) AS n, SUM(is_correct) AS ok FROM quiz_results WHERE user_id = ? GROUP BY completed_at ORDER BY completed_at DESC LIMIT 10');
$h->execute([$u['id']]);
$hist = $h->fetchAll();

head('Kuis');
?>
<h1>Kuis</h1>
<p class="muted">Uji pemahaman Anda tentang penggunaan teknologi yang bijak.</p>

<?php if (!$qs): ?>
    <p class="muted">Belum ada soal kuis.</p>

<?php elseif (!$res): ?>
    <form method="post">
        <?= csrf() ?>
        <?php foreach ($qs as $i => $q): ?>
            <div class="card dlg" style="margin:14px 0">
                <b><?= $i + 1 ?>. <?= e($q['question']) ?></b>
                <?php foreach (['a', 'b', 'c', 'd'] as $o): ?>
                    <label>
                        <input type="radio" name="q[<?= $q['id'] ?>]" value="<?= $o ?>">
                        <?= e($q['option_' . $o]) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <button class="btn">Kirim jawaban</button>
    </form>

<?php else: ?>
    <div class="card">
        <div class="num"><?= $res['ok'] ?>/<?= $res['n'] ?></div>
        <p>Skor Anda: <?= round($res['ok'] / $res['n'] * 100) ?>%</p>
    </div>
    <?php foreach ($res['det'] as $d): ?>
        <div class="card dlg" style="margin:14px 0">
            <b><?= e($d['q']['question']) ?></b>
            <p class="<?= $d['c'] ? '' : 'err' ?>">
                <?= $d['c'] ? 'Benar.' : 'Salah.' ?> Jawaban Anda: <?= e($d['q']['option_' . $d['a']]) ?>
            </p>
            <?php if (!$d['c']): ?>
                <p>Jawaban benar: <?= e($d['q']['option_' . $d['q']['correct_answer']]) ?></p>
            <?php endif; ?>
            <p class="muted"><?= e($d['q']['explanation']) ?></p>
        </div>
    <?php endforeach; ?>
    <a class="btn" href="quiz.php">Ulangi kuis</a>
<?php endif; ?>

<h2 style="margin-top:60px">Riwayat skor</h2>
<?php if ($hist): ?>
    <table>
        <tr><th>Waktu</th><th>Benar</th><th>Skor</th></tr>
        <?php foreach ($hist as $r): ?>
            <tr>
                <td><?= e($r['completed_at']) ?></td>
                <td><?= (int)$r['ok'] ?> dari <?= (int)$r['n'] ?></td>
                <td><?= round($r['ok'] / $r['n'] * 100) ?>%</td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p class="muted">Belum ada riwayat. Kerjakan kuis pertama Anda.</p>
<?php endif; ?>
<?php foot();
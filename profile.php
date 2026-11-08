<?php
require 'config.php';
require 'upload.php';
$u = need(['admin', 'editor', 'user']);
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $a = $_POST['a'] ?? '';
    if ($a === 'name') {
        $n = trim($_POST['full_name'] ?? '');
        if ($n === '' || mb_strlen($n) > 120) {
            $err = 'Nama wajib diisi (maksimal 120 karakter).';
        } else {
            $pdo->prepare('UPDATE users SET full_name = ? WHERE id = ?')->execute([$n, $u['id']]);
            flash('Profil diperbarui.');
            go('/profile.php');
        }
    } elseif ($a === 'pw') {
        $s = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $s->execute([$u['id']]);
        $hash = $s->fetchColumn();
        $new = $_POST['new'] ?? '';
        if (!password_verify($_POST['old'] ?? '', $hash)) {
            $err = 'Kata sandi saat ini salah.';
        } elseif (strlen($new) < 8) {
            $err = 'Kata sandi baru minimal 8 karakter.';
        } elseif ($new !== ($_POST['again'] ?? '')) {
            $err = 'Konfirmasi kata sandi tidak sama.';
        } else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            session_regenerate_id(true);
            flash('Kata sandi diganti.');
            go('/profile.php');
        }
    } elseif ($a === 'avatar' || $a === 'rmavatar') {
        $s = $pdo->prepare('SELECT avatar_url FROM users WHERE id = ?');
        $s->execute([$u['id']]);
        $old = $s->fetchColumn();
        if ($a === 'rmavatar') {
            $pdo->prepare('UPDATE users SET avatar_url = NULL WHERE id = ?')->execute([$u['id']]);
            del_file($old);
            flash('Foto profil dihapus.');
            go('/profile.php');
        }
        [$p, $er] = upload_image('avatar', 'avatars', 2097152);
        if ($er) {
            $err = $er;
        } else {
            $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?')->execute([$p, $u['id']]);
            del_file($old);
            flash('Foto profil diperbarui.');
            go('/profile.php');
        }
    } elseif ($a === 'avatar') {
        [$p, $m] = save_image($_FILES['avatar'] ?? [], 'avatars', 2 * 1048576);
        if ($m) {
            $err = $m;
        } elseif (!$p) {
            $err = 'Pilih file gambar dulu.';
        } else {
            $o = $pdo->prepare('SELECT avatar_url FROM users WHERE id = ?');
            $o->execute([$u['id']]);
            delete_image($o->fetchColumn() ?: null);
            $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?')->execute([$p, $u['id']]);
            flash('Foto profil diperbarui.');
            go('/profile.php');
        }
    } elseif ($a === 'avatar_del') {
        $o = $pdo->prepare('SELECT avatar_url FROM users WHERE id = ?');
        $o->execute([$u['id']]);
        delete_image($o->fetchColumn() ?: null);
        $pdo->prepare('UPDATE users SET avatar_url = NULL WHERE id = ?')->execute([$u['id']]);
        flash('Foto profil dihapus.');
        go('/profile.php');
    } elseif ($a === 'unbm') {
        $pdo->prepare('DELETE FROM bookmarks WHERE id = ? AND user_id = ?')->execute([(int)($_POST['id'] ?? 0), $u['id']]);
        flash('Bookmark dihapus.');
        go('/profile.php');
    }
}

$s = $pdo->prepare('SELECT avatar_url FROM users WHERE id = ?');
$s->execute([$u['id']]);
$avatar = $s->fetchColumn();
$av = $pdo->prepare('SELECT avatar_url FROM users WHERE id = ?');
$av->execute([$u['id']]);
$av = $av->fetchColumn() ?: '';
$b = $pdo->prepare('SELECT b.id, a.title, a.slug, a.status FROM bookmarks b JOIN articles a ON a.id = b.article_id WHERE b.user_id = ? ORDER BY b.created_at DESC');
$b->execute([$u['id']]);
$marks = $b->fetchAll();
head('Profil');
?>
<h1>Profil</h1>
<p class="muted"><?= e($u['email']) ?> · peran: <?= e($u['role']) ?></p>
<?php if ($err) { echo '<p class="err">' . e($err) . '</p>'; } ?>

<div class="grid" style="margin-bottom:40px">
    <div class="card">
        <h3>Foto profil</h3>
        <img id="pv" alt="Pratinjau" src="<?= $av ? e(BASE . '/' . $av) : '' ?>" style="width:96px;height:96px;border-radius:50%;object-fit:cover;<?= $av ? '' : 'display:none' ?>">
        <form method="post" enctype="multipart/form-data">
            <?= csrf() ?><input type="hidden" name="a" value="avatar">
            <label>JPG, PNG, atau WebP, maksimal 2 MB
                <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                       onchange="var f=this.files[0];if(f){var i=document.getElementById('pv');i.src=URL.createObjectURL(f);i.style.display='block'}">
            </label>
            <button class="btn">Unggah</button>
        </form>
        <?php if ($av): ?>
            <form method="post" onsubmit="return confirm('Hapus foto profil?')">
                <?= csrf() ?><input type="hidden" name="a" value="avatar_del">
                <button class="btn s d" style="margin-top:10px">Hapus foto</button>
            </form>
        <?php endif; ?>
    </div>
    <div class="card">
        <h3>Foto profil</h3>
        <?php if ($avatar) { echo '<img class="av" src="' . BASE . '/' . e($avatar) . '" alt="Foto profil">'; } ?>
        <form method="post" enctype="multipart/form-data">
            <?= csrf() ?><input type="hidden" name="a" value="avatar">
            <label>JPG, PNG, atau WebP, maksimal 2 MB <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required></label>
            <button class="btn">Unggah foto</button>
        </form>
        <?php if ($avatar): ?>
            <form method="post"><?= csrf() ?><input type="hidden" name="a" value="rmavatar"><button class="btn s d" style="margin-top:10px">Hapus foto</button></form>
        <?php endif; ?>
    </div>
    <form method="post" class="card">
        <h3>Data diri</h3>
        <?= csrf() ?><input type="hidden" name="a" value="name">
        <label>Nama lengkap <input name="full_name" maxlength="120" required value="<?= e($u['full_name']) ?>"></label>
        <button class="btn">Simpan</button>
    </form>
    <form method="post" class="card">
        <h3>Ganti kata sandi</h3>
        <?= csrf() ?><input type="hidden" name="a" value="pw">
        <label>Kata sandi saat ini <input type="password" name="old" required></label>
        <label>Kata sandi baru <input type="password" name="new" required minlength="8"></label>
        <label>Ulangi kata sandi baru <input type="password" name="again" required minlength="8"></label>
        <button class="btn">Ganti kata sandi</button>
    </form>
</div>

<h2>Bookmark saya</h2>
<?php if ($marks): ?>
    <table>
        <?php foreach ($marks as $m): ?>
            <tr>
                <td>
                    <?php if ($m['status'] === 'published'): ?>
                        <a href="article.php?slug=<?= e($m['slug']) ?>"><?= e($m['title']) ?></a>
                    <?php else: ?>
                        <?= e($m['title']) ?> <span class="muted">(tidak tersedia)</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="post">
                        <?= csrf() ?><input type="hidden" name="a" value="unbm"><input type="hidden" name="id" value="<?= $m['id'] ?>">
                        <button class="btn s d">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p class="muted">Belum ada artikel tersimpan. Buka sebuah artikel lalu klik "Simpan artikel".</p>
<?php endif; ?>
<?php foot();
<?php require 'config.php';
$q=trim($_GET['q']??'');$cat=$_GET['cat']??'';
$cats=$pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$tips=$pdo->query('SELECT * FROM tips WHERE is_active=1 ORDER BY display_order')->fetchAll();
$sql="SELECT a.*,c.name cname FROM articles a LEFT JOIN categories c ON c.id=a.category_id WHERE a.status='published'";$p=[];
if($q!==''){$sql.=' AND (a.title LIKE ? OR a.excerpt LIKE ? OR a.content LIKE ?)';$l="%$q%";$p=[$l,$l,$l];}
if($cat!==''){$sql.=' AND c.slug=?';$p[]=$cat;}
$st=$pdo->prepare($sql.' ORDER BY a.published_at DESC');$st->execute($p);$arts=$st->fetchAll();
$me = user();
if ($me) {
    $rr = $pdo->prepare('SELECT avatar_url, created_at FROM users WHERE id = ?');
    $rr->execute([$me['id']]);
    $pc = $rr->fetch();
    $rr = $pdo->prepare('SELECT COUNT(DISTINCT completed_at) AS tries, COUNT(*) AS n, COALESCE(SUM(is_correct), 0) AS ok FROM quiz_results WHERE user_id = ?');
    $rr->execute([$me['id']]);
    $pq = $rr->fetch();
    $rr = $pdo->prepare('SELECT COUNT(*) FROM bookmarks WHERE user_id = ?');
    $rr->execute([$me['id']]);
    $pb = (int)$rr->fetchColumn();
}
head('Use Technology Wisely');?>
<section class="hero"><div>
<span class="badge">DIGITAL AWARENESS • SMART LIVING</span>
<h1><?php foreach (['Use', 'Technology', 'Wisely,', 'Live', 'Meaningfully.'] as $i => $w) { echo '<span class="w"><b style="--i:' . $i . '">' . $w . '</b></span> '; } ?></h1>
<p class="muted sub" style="max-width:52ch">Teknologi hadir untuk mempermudah kehidupan. Namun, bagaimana kita menggunakannya menentukan apakah teknologi menjadi alat yang bermanfaat atau justru sebuah distraksi.</p>
<div class="cta"><a class="btn" href="#tips">Jelajahi Tips</a><a class="btn g" href="#manfaat">Pelajari Lebih Lanjut</a></div></div>
<div class="vis" aria-hidden="true"><div class="slab wood"></div>
<div class="lap"><div class="scr"><i></i><i></i><i></i><i></i></div><div class="base"></div></div>
<div class="ph"><i></i><i></i><i></i><i></i></div>
<span class="chip" style="top:20px;right:6%">Fokus penuh</span><span class="chip" style="bottom:70px;left:-4px;animation-delay:-3s">Data aman</span></div>
<div class="pcard" tabindex="0" aria-label="Kartu profil. Seret untuk memindahkan, ketuk untuk membalik.">
<div class="pc-in">
<?php if ($me): ?>
<div class="pc-face pc-front">
    <span class="pc-tag">KARTU PROFIL &#9733;</span>
    <div class="pc-av"><?php if (!empty($pc['avatar_url'])) { echo '<img src="' . e(BASE . '/' . $pc['avatar_url']) . '" alt="">'; } else { echo e(mb_strtoupper(mb_substr($me['full_name'], 0, 1))); } ?></div>
    <div class="pc-name"><?= e($me['full_name']) ?></div>
    <span class="pc-role"><?= e($me['role']) ?></span>
    <div class="pc-mail"><?= e($me['email']) ?></div>
    <div class="pc-hint">Seret aku &middot; ketuk untuk membalik</div>
</div>
<div class="pc-face pc-back">
    <h4>Statistikmu</h4>
    <div class="pc-row"><span>Kuis dikerjakan</span><b><?= (int)$pq['tries'] ?></b></div>
    <div class="pc-row"><span>Akurasi jawaban</span><b><?= $pq['n'] ? round($pq['ok'] / $pq['n'] * 100) : 0 ?>%</b></div>
    <div class="pc-row"><span>Bookmark</span><b><?= $pb ?></b></div>
    <div class="pc-row"><span>Bergabung</span><b style="font-size:16px"><?= e(date('d/m/Y', strtotime($pc['created_at']))) ?></b></div>
</div>
<?php else: ?>
<div class="pc-face pc-front">
    <span class="pc-tag">KARTU PROFIL &#9733;</span>
    <div class="pc-av">?</div>
    <div class="pc-name">Tamu</div>
    <span class="pc-role">belum masuk</span>
    <div class="pc-mail">Masuk untuk mengisi kartu ini dengan profilmu.</div>
    <div class="row"><a class="btn s" href="login.php">Masuk</a><a class="btn s" href="register.php">Daftar</a></div>
</div>
<div class="pc-face pc-back"><h4>Statistikmu</h4><p>Masuk untuk melihat jumlah kuis, akurasi, dan bookmark kamu.</p></div>
<?php endif; ?>
</div></div></section>

<div class="tape"><div><?php for($k=0;$k<12;$k++)echo '<span>Use Technology Wisely &#9733; Live Meaningfully &#9733;</span>';?></div></div>
<h2 id="manfaat" style="margin-top:60px">Mengapa harus bijak menggunakan teknologi?</h2>
<div class="grid">
<div class="card rv"><h3>Stay Productive</h3><p>Gunakan teknologi untuk meningkatkan produktivitas dan efisiensi.</p></div>
<div class="card rv"><h3>Protect Your Privacy</h3><p>Lindungi data pribadi dan keamanan digital.</p></div>
<div class="card rv"><h3>Maintain Balance</h3><p>Seimbangkan kehidupan digital dengan kehidupan nyata.</p></div></div>

<h2 id="tips" style="margin-top:80px">Tips bijak berteknologi</h2>
<div class="grid"><?php foreach($tips as $i=>$t):?><div class="card rv tip"><div class="num">0<?=$i+1?></div><h3><?=e($t['title'])?></h3><p><?=e($t['description'])?></p><p style="margin-top:10px;font-size:14px"><?=e($t['detailed_content'])?></p></div><?php endforeach;if(!$tips)echo '<p class="muted">Belum ada tips.</p>';?></div>
<h2 id="artikel">Artikel</h2>
<form class="row" style="margin-top:16px"><input name="q" value="<?=e($q)?>" placeholder="Cari judul atau kata kunci" style="flex:1;min-width:200px"><select name="cat" style="width:auto"><option value="">Semua kategori</option><?php foreach($cats as $c)echo '<option value="'.e($c['slug']).'"'.($cat===$c['slug']?' selected':'').'>'.e($c['name']).'</option>';?></select><button class="btn" style="margin:0">Cari</button></form>
<div class="grid"><?php foreach($arts as $a):?><a class="card rv" href="article.php?slug=<?=e($a['slug'])?>" style="text-decoration:none"><?php if($a['cover_image'])echo '<img src="'.BASE.'/'.e($a['cover_image']).'" alt="" style="width:100%;height:160px;object-fit:cover;border-radius:18px;margin-bottom:14px">';?><?php if($a['cover_image'])echo '<img class="thumb" src="'.BASE.'/'.e($a['cover_image']).'" alt="">';?><span class="muted"><?=e($a['cname']?:'Umum')?></span><h3><?=e($a['title'])?></h3><p><?=e($a['excerpt'])?></p></a><?php endforeach;if(!$arts)echo '<p class="muted">Tidak ada artikel yang cocok.</p>';?></div>
<section id="risiko" class="risk"><h2>Risiko jika teknologi dipakai tanpa kendali</h2>
<p>Dampaknya jarang terasa seketika. Ia menumpuk pelan-pelan lewat kebiasaan harian.</p>
<div class="grid"><div class="it rv"><h3>Kecanduan layar</h3><p>Waktu tersita dan fokus terpecah oleh notifikasi.</p></div>
<div class="it rv"><h3>Kebocoran data</h3><p>Kata sandi lemah dan tautan palsu membuka jalan pencurian identitas.</p></div>
<div class="it rv"><h3>Hoaks dan konflik</h3><p>Informasi yang tidak diverifikasi cepat menyebar.</p></div>
<div class="it rv"><h3>Kesehatan menurun</h3><p>Kurang tidur, mata lelah, dan kurang gerak.</p></div></div></section>
<?php foot();
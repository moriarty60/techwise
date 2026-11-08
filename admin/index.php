<?php require '../config.php';
require '../upload.php';$u=need(['admin','editor']);$isA=$u['role']==='admin';$uid=(int)$u['id'];$w=$isA?'':" AND author_id=$uid";
if($_SERVER['REQUEST_METHOD']==='POST'){check();$a=$_POST['a']??'';$id=(int)($_POST['id']??0);
 if($a==='save'){$t=trim($_POST['title']??'');$st=in_array($_POST['status']??'',['draft','published','archived'],true)?$_POST['status']:'draft';$cat=(int)($_POST['category_id']??0)?:null;
  if(strlen($t)<3)flash('Judul minimal 3 karakter.');
  elseif($id){$pdo->prepare("UPDATE articles SET title=?,excerpt=?,content=?,category_id=?,status=?,published_at=IF(?='published',COALESCE(published_at,NOW()),NULL) WHERE id=?$w")->execute([$t,$_POST['excerpt'],$_POST['content'],$cat,$st,$st,$id]);flash('Artikel diperbarui.');}
  else{$slug=trim(preg_replace('/[^a-z0-9]+/','-',strtolower($t)),'-').'-'.time();$pdo->prepare("INSERT INTO articles(title,slug,excerpt,content,category_id,author_id,status,published_at) VALUES(?,?,?,?,?,?,?,IF(?='published',NOW(),NULL))")->execute([$t,$slug,$_POST['excerpt'],$_POST['content'],$cat,$uid,$st,$st]);flash('Artikel dibuat.');}}
 if($a==='cover'){[$p,$er]=upload_image('cover','covers',5242880);if($er){flash($er);}else{$q=$pdo->prepare("SELECT cover_image FROM articles WHERE id=?$w");$q->execute([$id]);$o=$q->fetch();if($o){$pdo->prepare("UPDATE articles SET cover_image=? WHERE id=?")->execute([$p,$id]);del_file($o['cover_image']);flash('Sampul diperbarui.');}else{del_file($p);flash('Artikel tidak ditemukan.');}}}
 if($a==='rmcover'){$q=$pdo->prepare("SELECT cover_image FROM articles WHERE id=?$w");$q->execute([$id]);$o=$q->fetch();if($o){$pdo->prepare("UPDATE articles SET cover_image=NULL WHERE id=?")->execute([$id]);del_file($o['cover_image']);flash('Sampul dihapus.');}}
 if($a==='save'&&strlen(trim($_POST['title']??''))>=3){$aid=$id?:(int)$pdo->lastInsertId();
  $o=$pdo->prepare("SELECT cover_image FROM articles WHERE id=?$w");$o->execute([$aid]);$old=$o->fetchColumn()?:null;
  [$p,$m]=save_image($_FILES['cover']??[],'covers',5*1048576);
  if($m)flash('Artikel tersimpan, tetapi gambar ditolak: '.$m);
  elseif($p){$pdo->prepare("UPDATE articles SET cover_image=? WHERE id=?$w")->execute([$p,$aid]);delete_image($old);}
  elseif(!empty($_POST['rm_cover'])&&$old){$pdo->prepare("UPDATE articles SET cover_image=NULL WHERE id=?$w")->execute([$aid]);delete_image($old);}}
 if($a==='del'){$o=$pdo->prepare("SELECT cover_image FROM articles WHERE id=?$w");$o->execute([$id]);delete_image($o->fetchColumn()?:null);$q=$pdo->prepare("SELECT cover_image FROM articles WHERE id=?$w");$q->execute([$id]);$o=$q->fetch();$d=$pdo->prepare("DELETE FROM articles WHERE id=?$w");$d->execute([$id]);if($d->rowCount()&&$o)del_file($o['cover_image']);flash('Artikel dihapus.');}
 if($a==='cm'&&in_array($_POST['s']??'',['approved','rejected'],true)){$pdo->prepare('UPDATE comments SET status=? WHERE id=?')->execute([$_POST['s'],$id]);flash('Komentar diperbarui.');}
 go('/admin/');}
$stat=[];foreach(['users'=>'Pengguna','articles'=>'Artikel','tips'=>'Tips','quiz_results'=>'Hasil kuis','comments'=>'Komentar','feedback'=>'Feedback'] as $t=>$l)$stat[$l]=$pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
$stat['Artikel terbit']=$pdo->query("SELECT COUNT(*) FROM articles WHERE status='published'")->fetchColumn();
$cats=$pdo->query('SELECT id,name FROM categories ORDER BY name')->fetchAll();
$arts=$pdo->query("SELECT a.*,c.name cname FROM articles a LEFT JOIN categories c ON c.id=a.category_id WHERE 1=1$w ORDER BY a.created_at DESC")->fetchAll();
$ed=['id'=>0,'title'=>'','excerpt'=>'','content'=>'','category_id'=>0,'status'=>'draft'];
if(isset($_GET['edit']))foreach($arts as $r)if($r['id']==(int)$_GET['edit'])$ed=$r;
$pend=$pdo->query("SELECT c.id,c.content,u.full_name,a.title FROM comments c JOIN users u ON u.id=c.user_id JOIN articles a ON a.id=c.article_id WHERE c.status='pending' ORDER BY c.created_at")->fetchAll();
// ---- Dashboard charts: all numbers come from the database ----
$today = new DateTime($pdo->query('SELECT CURDATE()')->fetchColumn());
function last14(PDO $pdo, string $sql, DateTime $today): array
{
    $days = [];
    for ($i = 13; $i >= 0; $i--) {
        $days[(clone $today)->modify("-$i day")->format('Y-m-d')] = 0;
    }
    foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR) as $d => $n) {
        if (isset($days[$d])) { $days[$d] = (int)$n; }
    }
    return $days;
}
function bars(array $d): string
{
    $max = max($d) ?: 1;
    $o = '<svg viewBox="0 0 420 150" style="width:100%" role="img">';
    $i = 0;
    foreach ($d as $day => $v) {
        $h = (int)round($v / $max * 90);
        $x = $i * 30 + 5;
        $o .= '<rect x="' . $x . '" y="' . (115 - $h) . '" width="20" height="' . max($h, 2) . '" rx="6" fill="' . ($v ? '#e60012' : '#d9d9d9') . '"><title>' . e($day) . ': ' . $v . '</title></rect>';
        if ($v) { $o .= '<text x="' . ($x + 10) . '" y="' . (110 - $h) . '" font-size="10" text-anchor="middle" fill="#000000">' . $v . '</text>'; }
        $o .= '<text x="' . ($x + 10) . '" y="135" font-size="9" text-anchor="middle" fill="#444444">' . date('d', strtotime($day)) . '</text>';
        $i++;
    }
    return $o . '</svg>';
}
$qd = last14($pdo, 'SELECT DATE(completed_at) d, COUNT(DISTINCT user_id, completed_at) n FROM quiz_results WHERE completed_at >= CURDATE() - INTERVAL 13 DAY GROUP BY d', $today);
$ud = last14($pdo, 'SELECT DATE(created_at) d, COUNT(*) n FROM users WHERE created_at >= CURDATE() - INTERVAL 13 DAY GROUP BY d', $today);
$top = $pdo->query("SELECT title, views_count FROM articles WHERE status = 'published' ORDER BY views_count DESC LIMIT 5")->fetchAll();
$topMax = max(array_column($top, 'views_count') ?: [1]) ?: 1;
$accRow = $pdo->query('SELECT COUNT(*) n, COALESCE(SUM(is_correct), 0) ok FROM quiz_results')->fetch();
$acc = $accRow['n'] ? round($accRow['ok'] / $accRow['n'] * 100) : 0;
head('Admin');?>
<h1>Dashboard</h1><nav class="pmenu"><a href="manage.php?t=tips"><b>01</b>Kelola Tips</a><a href="manage.php?t=categories"><b>02</b>Kelola Kategori</a><a href="manage.php?t=quizzes"><b>03</b>Kelola Kuis</a><a href="feedback.php"><b>04</b>Feedback</a><a href="settings.php"><b>05</b>Pengaturan</a></nav><div class="grid"><?php foreach($stat as $l=>$n)echo '<div class="card stat"><div class="num">'.(int)$n.'</div><p>'.e($l).'</p></div>';?></div>
<h2>Aktivitas 14 hari terakhir</h2>
<div class="grid">
<div class="card"><h3>Pengerjaan kuis</h3><?=bars($qd)?></div>
<div class="card"><h3>Pengguna baru</h3><?=bars($ud)?></div>
<div class="card"><h3>Artikel terpopuler</h3>
<?php foreach($top as $ta):?><div style="margin:10px 0"><div style="font-size:14px"><?=e($ta['title'])?> <span class="muted">(<?=(int)$ta['views_count']?>)</span></div><div style="height:8px;border-radius:8px;background:#d9d9d9"><div style="height:8px;border-radius:8px;background:#e60012;width:<?=round($ta['views_count']/$topMax*100)?>%"></div></div></div><?php endforeach;if(!$top)echo '<p class="muted">Belum ada artikel terbit.</p>';?></div>
<div class="card stat"><div class="num"><?=$acc?>%</div><p>Akurasi jawaban kuis (semua pengguna)</p></div>
</div>
<h2>Artikel</h2>
<form method="post" enctype="multipart/form-data" class="card" style="margin:20px 0"><?=csrf()?><input type="hidden" name="a" value="save"><input type="hidden" name="id" value="<?=$ed['id']?>">
<label>Judul<input name="title" value="<?=e($ed['title'])?>" required></label><label>Ringkasan<input name="excerpt" value="<?=e($ed['excerpt'])?>" maxlength="400"></label>
<label>Isi<textarea name="content" rows="6" required><?=e($ed['content'])?></textarea></label>
<div class="row"><select name="category_id" style="width:auto"><option value="0">Tanpa kategori</option><?php foreach($cats as $c)echo '<option value="'.$c['id'].'"'.($c['id']==$ed['category_id']?' selected':'').'>'.e($c['name']).'</option>';?></select>
<select name="status" style="width:auto"><?php foreach(['draft','published','archived'] as $s)echo '<option'.($s===$ed['status']?' selected':'').'>'.$s.'</option>';?></select></div>
<label>Gambar sampul (JPG, PNG, WebP, maks 5 MB)
<input type="file" name="cover" accept="image/jpeg,image/png,image/webp" onchange="var f=this.files[0];if(f){var i=document.getElementById('cpv');i.src=URL.createObjectURL(f);i.style.display='block'}"></label>
<img id="cpv" alt="Pratinjau sampul" src="<?=!empty($ed['cover_image'])?e(BASE.'/'.$ed['cover_image']):''?>" style="max-width:260px;border-radius:16px;margin-top:10px;<?=empty($ed['cover_image'])?'display:none':''?>">
<?php if(!empty($ed['cover_image'])):?><label><input type="checkbox" name="rm_cover" value="1" style="width:auto"> Hapus gambar sampul saat ini</label><?php endif;?>
<button class="btn"><?=$ed['id']?'Simpan perubahan':'Buat artikel'?></button></form>
<table><tr><th>Judul</th><th>Status</th><th>Dibaca</th><th></th></tr><?php foreach($arts as $r):?><tr><td><?php if($r['cover_image'])echo '<img src="'.BASE.'/'.e($r['cover_image']).'" alt="" style="height:40px;border-radius:8px;vertical-align:middle;margin-right:8px">';?><?=e($r['title'])?></td><td><?=e($r['status'])?></td><td><?=$r['views_count']?></td><td class="row"><a class="btn s" href="?edit=<?=$r['id']?>">Edit</a>
<form method="post" enctype="multipart/form-data" class="row"><?=csrf()?><input type="hidden" name="a" value="cover"><input type="hidden" name="id" value="<?=$r['id']?>"><input type="file" name="cover" accept="image/jpeg,image/png,image/webp" required style="width:auto;padding:4px"><button class="btn s">Unggah sampul</button></form>
<?php if($r['cover_image']):?><form method="post"><?=csrf()?><input type="hidden" name="a" value="rmcover"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn s d">Hapus sampul</button></form><?php endif;?>
<form method="post" onsubmit="return confirm('Hapus artikel ini secara permanen?')"><?=csrf()?><input type="hidden" name="a" value="del"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn s d">Hapus</button></form></td></tr><?php endforeach;?></table>
<?php if(!$arts)echo '<p class="muted">Belum ada artikel.</p>';?>
<h2 style="margin-top:50px">Komentar menunggu moderasi</h2>
<?php foreach($pend as $c):?><div class="card" style="margin:12px 0"><b><?=e($c['full_name'])?></b> pada <i><?=e($c['title'])?></i><p><?=e($c['content'])?></p>
<form method="post" class="row" style="margin-top:10px"><?=csrf()?><input type="hidden" name="a" value="cm"><input type="hidden" name="id" value="<?=$c['id']?>"><button name="s" value="approved" class="btn s">Setujui</button><button name="s" value="rejected" class="btn s d">Tolak</button></form></div><?php endforeach;if(!$pend)echo '<p class="muted">Tidak ada komentar yang menunggu.</p>';
foot();
<?php require 'config.php';
$s=$pdo->prepare("SELECT a.*,c.name cname FROM articles a LEFT JOIN categories c ON c.id=a.category_id WHERE a.slug=? AND a.status='published'");$s->execute([$_GET['slug']??'']);$a=$s->fetch();
if(!$a){require '404.php';exit;}
$u=user();
if($_SERVER['REQUEST_METHOD']==='POST'){check();if(!$u){flash('Masuk dulu untuk melakukan ini.');go('/login.php');}
 if(($_POST['a']??'')==='bm'){$d=$pdo->prepare('DELETE FROM bookmarks WHERE user_id=? AND article_id=?');$d->execute([$u['id'],$a['id']]);
  if(!$d->rowCount()){$pdo->prepare('INSERT INTO bookmarks(user_id,article_id) VALUES(?,?)')->execute([$u['id'],$a['id']]);flash('Artikel disimpan.');}else flash('Bookmark dihapus.');}
 if(($_POST['a']??'')==='cm'){$c=trim($_POST['content']??'');
  if(strlen($c)<2||strlen($c)>1000)flash('Komentar 2 sampai 1000 karakter.');
  else{$pdo->prepare("INSERT INTO comments(user_id,article_id,content,status) VALUES(?,?,?,'pending')")->execute([$u['id'],$a['id'],$c]);flash('Komentar terkirim dan menunggu moderasi.');}}
 go('/article.php?slug='.urlencode($a['slug']));}
$pdo->prepare('UPDATE articles SET views_count=views_count+1 WHERE id=?')->execute([$a['id']]);
$bm=false;if($u){$b=$pdo->prepare('SELECT 1 FROM bookmarks WHERE user_id=? AND article_id=?');$b->execute([$u['id'],$a['id']]);$bm=(bool)$b->fetch();}
$cm=$pdo->prepare("SELECT c.content,c.created_at,u.full_name FROM comments c JOIN users u ON u.id=c.user_id WHERE c.article_id=? AND c.status='approved' ORDER BY c.created_at DESC");$cm->execute([$a['id']]);
head($a['title']);?>
<span class="muted"><?=e($a['cname'])?> · <?=$a['views_count']+1?> dibaca</span><h1><?=e($a['title'])?></h1>
<?php if($a['cover_image'])echo '<img src="'.BASE.'/'.e($a['cover_image']).'" alt="" style="width:100%;max-height:420px;object-fit:cover;border-radius:26px;margin:20px 0">';?>
<?php if($a['cover_image'])echo '<img class="cover" src="'.BASE.'/'.e($a['cover_image']).'" alt="">';?>
<form method="post"><?=csrf()?><input type="hidden" name="a" value="bm"><button class="btn s"><?=$bm?'Hapus bookmark':'Simpan artikel'?></button></form>
<div class="callcard"><?=nl2br(e($a['content']))?></div>
<h2 style="margin-top:60px">Komentar</h2>
<?php if($u):?><form method="post" class="narrow" style="margin:0"><?=csrf()?><input type="hidden" name="a" value="cm"><textarea name="content" rows="3" required maxlength="1000"></textarea><button class="btn">Kirim komentar</button></form><?php else:?><p><a href="login.php">Masuk</a> untuk berkomentar.</p><?php endif;
$n=0;foreach($cm as $c){$n++;echo '<div class="card" style="margin-top:14px"><b>'.e($c['full_name']).'</b><p>'.e($c['content']).'</p></div>';}if(!$n)echo '<p class="muted">Belum ada komentar.</p>';
foot();
<?php require 'config.php';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){check();$s=$pdo->prepare('SELECT * FROM users WHERE email=?');$s->execute([trim($_POST['email']??'')]);$r=$s->fetch();
 if($r&&password_verify($_POST['password']??'',$r['password_hash'])){session_regenerate_id(true);$_SESSION['uid']=$r['id'];flash('Selamat datang, '.$r['full_name']);go('/');}
 $err='Email atau kata sandi salah.';}
head('Masuk');?>
<form method="post" class="card narrow"><h2>Masuk</h2><?php if($err)echo '<p class="err">'.e($err).'</p>';echo csrf();?>
<label>Email<input type="email" name="email" required></label><label>Kata sandi<input type="password" name="password" required></label>
<button class="btn">Masuk</button><p>Belum punya akun? <a href="register.php">Daftar</a></p></form><?php foot();

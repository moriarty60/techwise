<?php require 'config.php';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){check();$n=trim($_POST['full_name']??'');$m=trim($_POST['email']??'');$p=$_POST['password']??'';
 if($n===''||strlen($n)>120)$err='Nama wajib diisi (maks 120 karakter).';
 elseif(!filter_var($m,FILTER_VALIDATE_EMAIL))$err='Format email tidak valid.';
 elseif(strlen($p)<8)$err='Kata sandi minimal 8 karakter.';
 else{$s=$pdo->prepare('SELECT 1 FROM users WHERE email=?');$s->execute([$m]);
  if($s->fetch())$err='Email sudah terdaftar.';
  else{$pdo->prepare('INSERT INTO users(full_name,email,password_hash) VALUES(?,?,?)')->execute([$n,$m,password_hash($p,PASSWORD_DEFAULT)]);flash('Pendaftaran berhasil. Silakan masuk.');go('/login.php');}}}
head('Daftar');?>
<form method="post" class="card narrow"><h2>Daftar</h2><?php if($err)echo '<p class="err">'.e($err).'</p>';echo csrf();?>
<label>Nama lengkap<input name="full_name" required maxlength="120"></label><label>Email<input type="email" name="email" required></label><label>Kata sandi<input type="password" name="password" required minlength="8"></label>
<button class="btn">Buat akun</button><p>Sudah punya akun? <a href="login.php">Masuk</a></p></form><?php foot();

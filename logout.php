<?php require 'config.php';$_SESSION=[];session_destroy();session_start();flash('Anda sudah keluar.');go('/');

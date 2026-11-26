<?php
/*
 * DevamTakip - sil.php (Kayıt silme)
 * Sadece POST isteğini kabul eder. Gelen id'ye sahip kaydı DELETE ile veritabanından siler.
 */
require 'oturum.php';
require 'db.php';
require 'functions.php';

$kullaniciId = girisGerekli();

// Sayfa adres çubuğundan (GET ile) açılırsa hiçbir şey silme, ana sayfaya dön
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

csrfKontrol();

$silinecekId = (int)($_POST['id'] ?? 0);

// kayitSil() silinen satır sayısına bakar: 0 ise kayıt yoktur ya da başka kullanıcıya aittir
$silindi = kayitSil(baglanti(), $silinecekId, $kullaniciId);

header('Location: index.php?mesaj=' . ($silindi ? 'silindi' : 'bulunamadi'));
exit;

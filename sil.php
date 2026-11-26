<?php
/*
 * DevamTakip - sil.php (Kayıt silme)
 * Sadece POST isteğini kabul eder. Gelen id'ye sahip kaydı DELETE ile veritabanından siler.
 */
session_start();
require 'db.php';
require 'functions.php';

// Sayfa adres çubuğundan (GET ile) açılırsa hiçbir şey silme, ana sayfaya dön
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$silinecekId = (int)($_POST['id'] ?? 0);

// kayitSil() silinen satır sayısına bakar: 0 ise böyle bir kayıt yoktur
$silindi = kayitSil(baglanti(), $silinecekId);

header('Location: index.php?mesaj=' . ($silindi ? 'silindi' : 'bulunamadi'));
exit;

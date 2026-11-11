<?php
/*
 * DevamTakip - sil.php (Kayıt silme)
 * Sadece POST isteğini kabul eder. Gelen id'ye sahip kaydı $_SESSION'dan çıkarır.
 */
session_start();
require 'functions.php';
oturumuHazirla();

// Sayfa adres çubuğundan (GET ile) açılırsa hiçbir şey silme, ana sayfaya dön
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$silinecekId = (int)($_POST['id'] ?? 0);

foreach ($_SESSION['kayitlar'] as $sira => $kayit) {
    if ($kayit['id'] === $silinecekId) {
        unset($_SESSION['kayitlar'][$sira]);
        break; // Kaydı bulduk, döngüye devam etmeye gerek yok
    }
}

// unset sonrası dizinin sıra numaralarını (0, 1, 2...) yeniden düzenle
$_SESSION['kayitlar'] = array_values($_SESSION['kayitlar']);

header('Location: index.php?mesaj=silindi');
exit;

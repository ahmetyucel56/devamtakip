<?php
/*
 * Tüm sayfalarda ortak olan üst kısım (başlık ve menü).
 * Kullanmadan önce $sayfaBasligi ve $aktifSayfa değişkenleri tanımlanmalı.
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= guvenli($sayfaBasligi) ?> | DevamTakip</title>
    <link rel="stylesheet" href="assets/fonts.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="ust">
    <a href="index.php" class="logo">DevamTakip</a>
    <nav class="menu">
        <a href="index.php" class="<?= $aktifSayfa === 'ana' ? 'aktif' : '' ?>">Haklarım</a>
        <a href="program.php" class="<?= $aktifSayfa === 'program' ? 'aktif' : '' ?>">Ders programım</a>
        <a href="ekle.php" class="menu-buton <?= $aktifSayfa === 'ekle' ? 'aktif' : '' ?>">Devamsızlık ekle</a>
    </nav>
</header>
<main class="sayfa">

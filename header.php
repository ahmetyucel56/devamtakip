<?php
/*
 * Tüm sayfalarda ortak olan üst kısım (başlık ve menü).
 * Kullanmadan önce $sayfaBasligi ve $aktifSayfa değişkenleri tanımlanmalı.
 * Giriş yapılmışsa menü ve "Çıkış" butonu görünür.
 */
$girisli = aktifKullaniciId() !== 0;
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
    <?php if ($girisli): ?>
        <nav class="menu">
            <a href="index.php" class="<?= $aktifSayfa === 'ana' ? 'aktif' : '' ?>">Haklarım</a>
            <a href="program.php" class="<?= $aktifSayfa === 'program' ? 'aktif' : '' ?>">Ders programım</a>
            <a href="ekle.php" class="menu-buton <?= $aktifSayfa === 'ekle' ? 'aktif' : '' ?>">Devamsızlık ekle</a>
            <span class="kullanici-adi"><?= guvenli($_SESSION['ad_soyad'] ?? '') ?></span>
            <!-- Çıkış POST ile yapılır; bir bağlantıya tıklatılarak kullanıcı oturumdan atılamaz -->
            <form method="post" action="cikis.php" class="cikis-form">
                <?= csrfAlani() ?>
                <button type="submit" class="cikis">Çıkış</button>
            </form>
        </nav>
    <?php endif; ?>
</header>
<main class="sayfa">

<?php
/*
 * DevamTakip - cikis.php (Çıkış)
 * Sadece POST ile çalışır (menüdeki "Çıkış" butonu). Oturumu kapatıp giriş sayfasına döner.
 */
require 'oturum.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

csrfKontrol();
oturumKapat();

header('Location: giris.php?mesaj=cikis');
exit;

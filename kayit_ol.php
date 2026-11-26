<?php
/*
 * DevamTakip - kayit_ol.php (Yeni hesap oluşturma)
 * Bilgiler kontrol edilir, parola password_hash() ile özetlenip kullanicilar tablosuna eklenir.
 * Kayıttan sonra kullanıcı otomatik giriş yapar ve ders programı sayfasına gider.
 */
require 'oturum.php';
require 'db.php';
require 'functions.php';

if (aktifKullaniciId() !== 0) {
    header('Location: index.php');
    exit;
}

$veri    = ['ad_soyad' => '', 'kullanici_adi' => ''];
$hatalar = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfKontrol();
    $pdo = baglanti();

    $veri['ad_soyad']      = trim($_POST['ad_soyad'] ?? '');
    $veri['kullanici_adi'] = mb_strtolower(trim($_POST['kullanici_adi'] ?? ''));
    $parola                = $_POST['parola'] ?? '';
    $parolaTekrar          = $_POST['parola_tekrar'] ?? '';

    $adUzunluk = mb_strlen($veri['ad_soyad']);
    if ($adUzunluk < 3 || $adUzunluk > 100) {
        $hatalar['ad_soyad'] = 'Ad soyad 3 ile 100 karakter arasında olmalı.';
    }

    // Sadece küçük harf, rakam ve alt çizgi; 3-30 karakter
    if (!preg_match('/^[a-z0-9_]{3,30}$/', $veri['kullanici_adi'])) {
        $hatalar['kullanici_adi'] = 'Kullanıcı adı 3-30 karakter olmalı; sadece küçük harf, rakam ve _ kullanılabilir.';
    } elseif (kullaniciBul($pdo, $veri['kullanici_adi']) !== null) {
        $hatalar['kullanici_adi'] = 'Bu kullanıcı adı alınmış.';
    }

    if (strlen($parola) < 8) {
        $hatalar['parola'] = 'Parola en az 8 karakter olmalı.';
    } elseif ($parola !== $parolaTekrar) {
        $hatalar['parola_tekrar'] = 'Parolalar aynı değil.';
    }

    if (count($hatalar) === 0) {
        $id = kullaniciEkle($pdo, $veri['ad_soyad'], $veri['kullanici_adi'], $parola);
        oturumAc(['id' => $id, 'ad_soyad' => $veri['ad_soyad']]);
        header('Location: program.php?yeni=1');
        exit;
    }
}

$sayfaBasligi = 'Kayıt ol';
$aktifSayfa = 'giris';
include 'header.php';
?>

<div class="form-sayfa giris-sayfa">
    <h1>Kayıt ol</h1>
    <p class="soluk">Hesabını oluştur, sonra ders programını seçip devamsızlıklarını kaydetmeye başla.</p>

    <?php if (count($hatalar) > 0): ?>
        <p class="bildirim hata">Hesap oluşturulmadı. İşaretli alanları düzeltin.</p>
    <?php endif; ?>

    <form method="post" action="kayit_ol.php" class="panel form" novalidate>
        <?= csrfAlani() ?>
        <div class="alan <?= isset($hatalar['ad_soyad']) ? 'hatali' : '' ?>">
            <label for="ad_soyad">Ad soyad</label>
            <input type="text" id="ad_soyad" name="ad_soyad" value="<?= guvenli($veri['ad_soyad']) ?>" autocomplete="name">
            <?php if (isset($hatalar['ad_soyad'])): ?><span class="hata-yazi"><?= guvenli($hatalar['ad_soyad']) ?></span><?php endif; ?>
        </div>
        <div class="alan <?= isset($hatalar['kullanici_adi']) ? 'hatali' : '' ?>">
            <label for="kullanici_adi">Kullanıcı adı</label>
            <input type="text" id="kullanici_adi" name="kullanici_adi" value="<?= guvenli($veri['kullanici_adi']) ?>" autocomplete="username">
            <?php if (isset($hatalar['kullanici_adi'])): ?><span class="hata-yazi"><?= guvenli($hatalar['kullanici_adi']) ?></span><?php endif; ?>
        </div>
        <div class="alan-grup">
            <div class="alan <?= isset($hatalar['parola']) ? 'hatali' : '' ?>">
                <label for="parola">Parola</label>
                <input type="password" id="parola" name="parola" autocomplete="new-password">
                <?php if (isset($hatalar['parola'])): ?><span class="hata-yazi"><?= guvenli($hatalar['parola']) ?></span><?php endif; ?>
            </div>
            <div class="alan <?= isset($hatalar['parola_tekrar']) ? 'hatali' : '' ?>">
                <label for="parola_tekrar">Parola (tekrar)</label>
                <input type="password" id="parola_tekrar" name="parola_tekrar" autocomplete="new-password">
                <?php if (isset($hatalar['parola_tekrar'])): ?><span class="hata-yazi"><?= guvenli($hatalar['parola_tekrar']) ?></span><?php endif; ?>
            </div>
        </div>
        <div class="butonlar">
            <button type="submit" class="buton">Hesap oluştur</button>
        </div>
    </form>

    <p class="soluk alt-baglanti">Zaten hesabın var mı? <a href="giris.php">Giriş yap</a></p>
</div>

<?php include 'footer.php'; ?>

<?php
/*
 * DevamTakip - giris.php (Kullanıcı girişi)
 * Kullanıcı adı ve parola POST ile gelir. Parola password_verify() ile veritabanındaki özetle karşılaştırılır.
 */
require 'oturum.php';
require 'db.php';
require 'functions.php';

// Zaten giriş yapılmışsa ana sayfaya git
if (aktifKullaniciId() !== 0) {
    header('Location: index.php');
    exit;
}

$kullaniciAdi = '';
$hata = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfKontrol();

    $kullaniciAdi = trim($_POST['kullanici_adi'] ?? '');
    $parola       = $_POST['parola'] ?? '';

    if ($kullaniciAdi === '' || $parola === '') {
        $hata = 'Kullanıcı adı ve parola boş bırakılamaz.';
    } else {
        $kullanici = kullaniciBul(baglanti(), $kullaniciAdi);

        // Kullanıcı yoksa da parola yanlışsa da aynı mesaj: hangisinin yanlış olduğu belli edilmez
        if ($kullanici === null || !password_verify($parola, $kullanici['parola_hash'])) {
            $hata = 'Kullanıcı adı veya parola hatalı.';
        } else {
            oturumAc($kullanici);
            header('Location: index.php');
            exit;
        }
    }
}

$sayfaBasligi = 'Giriş yap';
$aktifSayfa = 'giris';
include 'header.php';
?>

<div class="form-sayfa giris-sayfa">
    <h1>Giriş yap</h1>
    <p class="soluk">Devamsızlık kayıtlarını görmek için hesabına giriş yap.</p>

    <?php if (($_GET['mesaj'] ?? '') === 'cikis'): ?>
        <p class="bildirim basarili">Çıkış yapıldı.</p>
    <?php endif; ?>
    <?php if ($hata !== ''): ?>
        <p class="bildirim hata"><?= guvenli($hata) ?></p>
    <?php endif; ?>

    <form method="post" action="giris.php" class="panel form" novalidate>
        <?= csrfAlani() ?>
        <div class="alan">
            <label for="kullanici_adi">Kullanıcı adı</label>
            <input type="text" id="kullanici_adi" name="kullanici_adi" value="<?= guvenli($kullaniciAdi) ?>" autocomplete="username" autofocus>
        </div>
        <div class="alan">
            <label for="parola">Parola</label>
            <input type="password" id="parola" name="parola" autocomplete="current-password">
        </div>
        <div class="butonlar">
            <button type="submit" class="buton">Giriş yap</button>
        </div>
    </form>

    <p class="soluk alt-baglanti">Hesabın yok mu? <a href="kayit_ol.php">Kayıt ol</a></p>
</div>

<?php include 'footer.php'; ?>

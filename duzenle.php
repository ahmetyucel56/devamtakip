<?php
/*
 * DevamTakip - duzenle.php (Kayıt güncelleme)
 * GET ?id=5  -> kaydı veritabanından okuyup formu doldurur.
 * POST       -> formdaki yeni değerleri kontrol eder ve UPDATE ile kaydeder.
 */
session_start();
require 'db.php';
require 'functions.php';
oturumuHazirla();

$pdo = baglanti();

// Düzenlenecek kaydın id'si: ilk açılışta adres çubuğundan, form gönderilince gizli alandan gelir
$kayitId = (int)($_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['id'] ?? 0) : ($_GET['id'] ?? 0));
$kayit   = kayitGetir($pdo, $kayitId);

// Kayıt yoksa (silinmiş ya da yanlış id) ana sayfaya dön
if ($kayit === null) {
    header('Location: index.php?mesaj=bulunamadi');
    exit;
}

// Ders listesi: programdaki dersler + kaydın kendi dersi
// (Ders sonradan programdan çıkarıldıysa eski kayıt yine de düzenlenebilsin)
$dersSecenekleri = dersAdlari();
if (!in_array($kayit['ders'], $dersSecenekleri, true)) {
    $dersSecenekleri[] = $kayit['ders'];
}

$hatalar = [];
$veri    = $kayit;   // Form ilk açıldığında veritabanındaki değerlerle dolu gelir

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$veri, $hatalar] = kayitDogrula($_POST, $dersSecenekleri);

    // Aynı derse aynı gün başka bir kayıt var mı? (kaydın kendisi hariç)
    if (count($hatalar) === 0 && kayitVarMi($pdo, $veri['ders'], $veri['tarih'], $kayitId)) {
        $hatalar['tarih'] = 'Bu derse bu tarih için başka bir kayıt var.';
    }

    if (count($hatalar) === 0) {
        $veri['saat'] = (int)$veri['saat'];
        kayitGuncelle($pdo, $kayitId, $veri);
        header('Location: index.php?mesaj=guncellendi');
        exit;
    }
}

$formAction  = 'duzenle.php';
$butonYazisi = 'Değişiklikleri kaydet';

$sayfaBasligi = 'Kaydı düzenle';
$aktifSayfa = '';
include 'header.php';
?>

<div class="form-sayfa">
    <h1>Kaydı düzenle</h1>
    <p class="soluk"><?= tarihYaz($kayit['tarih']) ?> tarihli <?= guvenli($kayit['ders']) ?> kaydını değiştiriyorsun.</p>

    <?php if (count($hatalar) > 0): ?>
        <p class="bildirim hata">Kayıt güncellenmedi. İşaretli <?= count($hatalar) ?> alanı düzeltin.</p>
    <?php endif; ?>

    <?php include 'kayit_form.php'; ?>
</div>

<?php include 'footer.php'; ?>

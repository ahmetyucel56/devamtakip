<?php
/*
 * DevamTakip - ekle.php (Yeni kayıt ekleme)
 * Form POST ile bu sayfanın kendisine gönderilir.
 * Ana sayfadaki "Gitmedim" butonları da buraya POST eder.
 * Veriler PHP tarafında kontrol edilir; hata yoksa kayıt PDO ile MySQL'e eklenir.
 */
require 'oturum.php';
require 'db.php';
require 'functions.php';

$kullaniciId = girisGerekli();   // Giriş yoksa giris.php'ye gider

$pdo     = baglanti();
$dersler = dersAdlari(kullaniciProgrami($pdo, $kullaniciId));
$hatalar = [];

// Formun başlangıç değerleri
$veri = ['ders' => '', 'tarih' => date('Y-m-d'), 'saat' => '', 'durum' => 'Devamsız', 'aciklama' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfKontrol();

    // 1) Temizle ve kontrol et (functions.php > kayitDogrula)
    [$veri, $hatalar] = kayitDogrula($_POST, $dersler);

    // 2) Aynı derse aynı gün için ikinci kayıt girilmesin
    if (count($hatalar) === 0 && kayitVarMi($pdo, $kullaniciId, $veri['ders'], $veri['tarih'])) {
        $hatalar['tarih'] = 'Bu derse bu tarih için zaten kayıt var.';
    }

    // 3) Hata yoksa veritabanına ekle ve ana sayfaya dön (Post/Redirect/Get)
    if (count($hatalar) === 0) {
        $veri['saat'] = (int)$veri['saat'];
        kayitEkle($pdo, $kullaniciId, $veri);   // Kayıt giriş yapan kullanıcıya ait olur
        header('Location: index.php?mesaj=eklendi');
        exit;
    }
}

// Form değişkenleri (kayit_form.php bunları kullanır)
$formAction      = 'ekle.php';
$butonYazisi     = 'Kaydı ekle';
$dersSecenekleri = $dersler;

$sayfaBasligi = 'Devamsızlık ekle';
$aktifSayfa = 'ekle';
include 'header.php';
?>

<div class="form-sayfa">
    <h1>Devamsızlık ekle</h1>
    <p class="soluk">Kaçırdığın dersi ve kaç saat olduğunu gir. Bugünkü dersler için ana sayfadaki "Gitmedim" butonu daha hızlı.</p>

    <?php if (count($hatalar) > 0): ?>
        <p class="bildirim hata">Kayıt eklenmedi. İşaretli <?= count($hatalar) ?> alanı düzeltin.</p>
    <?php endif; ?>

    <?php include 'kayit_form.php'; ?>
</div>

<?php include 'footer.php'; ?>

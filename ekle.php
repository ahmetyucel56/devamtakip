<?php
/*
 * DevamTakip - ekle.php (Yeni kayıt ekleme)
 * Form POST ile bu sayfanın kendisine gönderilir.
 * Ana sayfadaki "Gitmedim" butonları da buraya POST eder.
 * Veriler PHP tarafında kontrol edilir; hata yoksa kayıt $_SESSION'a eklenir.
 */
session_start();
require 'functions.php';
oturumuHazirla();

$dersler = dersAdlari();
$hatalar = [];

// Formun önceki değerleri (hata olursa kullanıcı tekrar yazmak zorunda kalmasın diye)
$ders     = '';
$tarih    = date('Y-m-d');
$saat     = '';
$durum    = 'Devamsız';
$aciklama = '';

// Form gönderildiyse (sayfa POST ile açıldıysa) işlemleri yap
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) Verileri al, baştaki/sondaki boşlukları temizle
    $ders     = trim($_POST['ders'] ?? '');
    $tarih    = trim($_POST['tarih'] ?? '');
    $saat     = trim($_POST['saat'] ?? '');
    $durum    = trim($_POST['durum'] ?? '');
    $aciklama = trim($_POST['aciklama'] ?? '');

    // 2) Doğrulama
    if ($ders === '') {
        $hatalar['ders'] = 'Bir ders seçin.';
    } elseif (!in_array($ders, $dersler, true)) {
        $hatalar['ders'] = 'Bu ders programında yok. Önce ders programına ekleyin.';
    }

    if ($tarih === '') {
        $hatalar['tarih'] = 'Tarih boş bırakılamaz.';
    } else {
        $tarihNesnesi = DateTime::createFromFormat('Y-m-d', $tarih);
        if ($tarihNesnesi === false || $tarihNesnesi->format('Y-m-d') !== $tarih) {
            $hatalar['tarih'] = 'Geçerli bir tarih girin.';
        } elseif ($tarih > date('Y-m-d')) {
            $hatalar['tarih'] = 'İleri bir tarihe devamsızlık girilemez.';
        }
    }

    if ($saat === '') {
        $hatalar['saat'] = 'Saat sayısı boş bırakılamaz.';
    } elseif (!ctype_digit($saat) || (int)$saat < 1 || (int)$saat > 8) {
        $hatalar['saat'] = 'Saat 1 ile 8 arasında tam sayı olmalı.';
    }

    if (!in_array($durum, DURUMLAR, true)) {
        $hatalar['durum'] = 'Devamsız veya Raporlu seçin.';
    }

    if (mb_strlen($aciklama) > 150) {
        $hatalar['aciklama'] = 'Not en fazla 150 karakter olabilir.';
    }

    // Aynı derse aynı gün için ikinci kayıt girilmesin
    if (count($hatalar) === 0 && kayitVarMi($_SESSION['kayitlar'], $ders, $tarih)) {
        $hatalar['tarih'] = 'Bu derse bu tarih için zaten kayıt var.';
    }

    // 3) Hata yoksa kaydı ekle
    if (count($hatalar) === 0) {
        $_SESSION['sonId']++;

        $_SESSION['kayitlar'][] = [
            'id'       => $_SESSION['sonId'],
            'ders'     => $ders,
            'tarih'    => $tarih,
            'saat'     => (int)$saat,
            'durum'    => $durum,
            'aciklama' => $aciklama,
        ];

        // Ana sayfaya yönlendir (sayfa yenilenince form tekrar gönderilmesin)
        header('Location: index.php?mesaj=eklendi');
        exit;
    }
}

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

    <!-- novalidate: tarayıcı kontrolünü kapatır, böylece doğrulamayı PHP yapar -->
    <form method="post" action="ekle.php" class="panel form" novalidate>

        <div class="alan <?= isset($hatalar['ders']) ? 'hatali' : '' ?>">
            <label for="ders">Ders</label>
            <select id="ders" name="ders">
                <option value="">Ders seçin</option>
                <?php foreach ($dersler as $secenek): ?>
                    <option value="<?= guvenli($secenek) ?>" <?= $secenek === $ders ? 'selected' : '' ?>><?= guvenli($secenek) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($hatalar['ders'])): ?>
                <span class="hata-yazi"><?= guvenli($hatalar['ders']) ?></span>
            <?php endif; ?>
        </div>

        <div class="alan-grup">
            <div class="alan <?= isset($hatalar['tarih']) ? 'hatali' : '' ?>">
                <label for="tarih">Tarih</label>
                <input type="date" id="tarih" name="tarih" value="<?= guvenli($tarih) ?>">
                <?php if (isset($hatalar['tarih'])): ?>
                    <span class="hata-yazi"><?= guvenli($hatalar['tarih']) ?></span>
                <?php endif; ?>
            </div>

            <div class="alan <?= isset($hatalar['saat']) ? 'hatali' : '' ?>">
                <label for="saat">Saat</label>
                <input type="number" id="saat" name="saat" min="1" max="8" value="<?= guvenli($saat) ?>" placeholder="4">
                <?php if (isset($hatalar['saat'])): ?>
                    <span class="hata-yazi"><?= guvenli($hatalar['saat']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <fieldset class="alan <?= isset($hatalar['durum']) ? 'hatali' : '' ?>">
            <legend>Durum</legend>
            <div class="radyolar">
                <?php foreach (DURUMLAR as $secenek): ?>
                    <label class="radyo">
                        <input type="radio" name="durum" value="<?= guvenli($secenek) ?>" <?= $secenek === $durum ? 'checked' : '' ?>>
                        <?= guvenli($secenek) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if (isset($hatalar['durum'])): ?>
                <span class="hata-yazi"><?= guvenli($hatalar['durum']) ?></span>
            <?php endif; ?>
        </fieldset>

        <div class="alan <?= isset($hatalar['aciklama']) ? 'hatali' : '' ?>">
            <label for="aciklama">Not <span class="soluk">(isteğe bağlı)</span></label>
            <textarea id="aciklama" name="aciklama" rows="3" placeholder="Hastaydım, rapor aldım"><?= guvenli($aciklama) ?></textarea>
            <?php if (isset($hatalar['aciklama'])): ?>
                <span class="hata-yazi"><?= guvenli($hatalar['aciklama']) ?></span>
            <?php endif; ?>
        </div>

        <div class="butonlar">
            <button type="submit" class="buton">Kaydı ekle</button>
            <a href="index.php" class="buton ikincil">Vazgeç</a>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>

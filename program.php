<?php
/*
 * DevamTakip - program.php (Ders programım)
 * Okulun ders programından bölüm seçilir, alınan dersler işaretlenir.
 * Seçim $_SESSION['program'] içinde saklanır; ders listeleri ve "Bugün" paneli buradan gelir.
 */
session_start();
require 'functions.php';
oturumuHazirla();

$programlar = programlariYukle();

// Bütün bölüm adlarını bir diziye al
$bolumAdlari = [];
foreach ($programlar['bolumler'] as $bolum) {
    $bolumAdlari[] = $bolum['ad'];
}

$hata = '';

// --- Kaydet: form POST ile gönderildiyse ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bolumAdi = $_POST['bolum'] ?? '';
    $secilenKodlar = $_POST['dersler'] ?? []; // checkbox'lar dizi olarak gelir: dersler[]

    if (!in_array($bolumAdi, $bolumAdlari, true)) {
        $hata = 'Geçersiz bölüm.';
    } elseif (!is_array($secilenKodlar) || count($secilenKodlar) === 0) {
        $hata = 'En az bir ders işaretleyin.';
    } else {
        // Sadece o bölümde gerçekten olan dersleri al
        $secilenDersler = [];
        foreach (bolumDersleri($programlar, $bolumAdi) as $ders) {
            if (in_array($ders['kod'], $secilenKodlar, true)) {
                $secilenDersler[] = $ders;
            }
        }

        $_SESSION['program'] = ['bolum' => $bolumAdi, 'dersler' => $secilenDersler];
        header('Location: index.php?mesaj=program');
        exit;
    }
}

// --- Gösterilecek bölüm (GET ile seçilir, yoksa kayıtlı bölüm) ---
$gosterilenBolum = $_GET['bolum'] ?? $_SESSION['program']['bolum'];
if (!in_array($gosterilenBolum, $bolumAdlari, true)) {
    $gosterilenBolum = $_SESSION['program']['bolum'];
}
$bolumunDersleri = bolumDersleri($programlar, $gosterilenBolum);

// Hangi kutular işaretli gelsin?
// "?sinif=2" ile gelindiyse o sınıfın hepsi, değilse şu an kayıtlı olan dersler.
$hizliSinif = (int)($_GET['sinif'] ?? 0);
$isaretliKodlar = [];
foreach ($bolumunDersleri as $ders) {
    if ($hizliSinif > 0) {
        if ($ders['sinif'] === $hizliSinif) {
            $isaretliKodlar[] = $ders['kod'];
        }
    } elseif ($gosterilenBolum === $_SESSION['program']['bolum']) {
        foreach ($_SESSION['program']['dersler'] as $kayitli) {
            if ($kayitli['kod'] === $ders['kod']) {
                $isaretliKodlar[] = $ders['kod'];
            }
        }
    }
}

// Haftalık görünüm: kayıtlı dersleri günlere dağıt
$hafta = [];
foreach (['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma'] as $gun) {
    $hafta[$gun] = gununDersleri($gun);
}

$sayfaBasligi = 'Ders programım';
$aktifSayfa = 'program';
include 'header.php';
?>

<div class="program-ust">
    <div>
        <h1>Ders programım</h1>
        <p class="soluk">Okulun <?= guvenli($programlar['donem']) ?> ders programından bölümünü seç, aldığın dersleri işaretle.
           Alttan aldığın dersler varsa diğer sınıftan da işaretleyebilirsin.</p>
    </div>
    <form method="get" action="program.php" class="filtre">
        <select name="bolum" aria-label="Bölüm">
            <?php foreach ($bolumAdlari as $ad): ?>
                <option value="<?= guvenli($ad) ?>" <?= $ad === $gosterilenBolum ? 'selected' : '' ?>><?= guvenli($ad) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="buton ikincil">Göster</button>
    </form>
</div>

<?php if ($hata !== ''): ?>
    <p class="bildirim hata"><?= guvenli($hata) ?></p>
<?php endif; ?>

<form method="post" action="program.php" class="panel">
    <input type="hidden" name="bolum" value="<?= guvenli($gosterilenBolum) ?>">

    <div class="sinif-kolonlari">
        <?php foreach ([1, 2] as $sinif): ?>
            <fieldset class="sinif">
                <legend><?= $sinif ?>. sınıf</legend>
                <a class="hepsini-sec" href="program.php?bolum=<?= urlencode($gosterilenBolum) ?>&amp;sinif=<?= $sinif ?>">Sadece <?= $sinif ?>. sınıfı seç</a>
                <?php foreach ($bolumunDersleri as $ders): ?>
                    <?php if ($ders['sinif'] === $sinif): ?>
                        <label class="ders-secim">
                            <input type="checkbox" name="dersler[]" value="<?= guvenli($ders['kod']) ?>"
                                <?= in_array($ders['kod'], $isaretliKodlar, true) ? 'checked' : '' ?>>
                            <span>
                                <strong><?= guvenli($ders['ad']) ?></strong>
                                <span class="soluk">
                                    <?php foreach ($ders['gunler'] as $gun => $saat): ?>
                                        <?= $gun ?> <?= $saat ?> saat<?= $gun !== array_key_last($ders['gunler']) ? ',' : '' ?>
                                    <?php endforeach; ?>
                                </span>
                            </span>
                        </label>
                    <?php endif; ?>
                <?php endforeach; ?>
            </fieldset>
        <?php endforeach; ?>
    </div>

    <div class="butonlar">
        <button type="submit" class="buton">Programı kaydet</button>
    </div>
</form>

<section class="hafta-bolumu">
    <h2>Haftalık görünüm</h2>
    <p class="soluk"><?= guvenli($_SESSION['program']['bolum']) ?>, <?= count($_SESSION['program']['dersler']) ?> ders</p>
    <div class="tablo-kutu">
        <div class="hafta">
            <?php foreach ($hafta as $gun => $gunDersleri): ?>
                <div class="gun <?= $gun === GUNLER[(int)date('N')] ? 'bugun' : '' ?>">
                    <h3><?= $gun ?></h3>
                    <?php if (count($gunDersleri) === 0): ?>
                        <p class="soluk bos-gun">Ders yok</p>
                    <?php endif; ?>
                    <?php foreach ($gunDersleri as $ders): ?>
                        <div class="blok" style="min-height: <?= $ders['saat'] * 26 + 30 ?>px">
                            <strong><?= guvenli($ders['ad']) ?></strong>
                            <span class="soluk"><?= $ders['saat'] ?> saat, <?= guvenli($ders['derslik']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>

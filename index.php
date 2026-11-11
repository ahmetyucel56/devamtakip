<?php
/*
 * DevamTakip - index.php (Ana sayfa)
 * Ders bazında kalan haklar, bugünkü dersler, özet sayaçlar, filtre ve kayıt listesi.
 */
session_start();
require 'functions.php';
oturumuHazirla();

$kayitlar = $_SESSION['kayitlar'];
$dersler  = dersAdlari();

// --- Filtre değerleri (GET ile adres çubuğundan gelir) ---
$seciliDers  = $_GET['ders']  ?? '';
$seciliDurum = $_GET['durum'] ?? '';

// Listede olmayan bir değer gelirse filtreyi yok say (güvenlik için)
if (!in_array($seciliDers, $dersler, true)) {
    $seciliDers = '';
}
if (!in_array($seciliDurum, DURUMLAR, true)) {
    $seciliDurum = '';
}

$gosterilenler = tariheGoreSirala(kayitlariFiltrele($kayitlar, $seciliDers, $seciliDurum));

// --- Özet sayaçlar ---
$toplamKayit  = count($kayitlar);
$devamsizSaat = durumaGoreSaat($kayitlar, 'Devamsız');
$raporluSaat  = durumaGoreSaat($kayitlar, 'Raporlu');

// --- Her ders için kalan hak ---
$dersOzetleri = [];
$riskliDersSayisi = 0;
foreach ($dersler as $ders) {
    $kullanilan = dersDevamsizligi($kayitlar, $ders);
    $risk = riskSeviyesi($kullanilan);

    if ($risk['sinif'] === 'kritik' || $risk['sinif'] === 'asildi') {
        $riskliDersSayisi++;
    }

    $dersOzetleri[] = [
        'ders'       => $ders,
        'kullanilan' => $kullanilan,
        'kalan'      => max(0, DEVAMSIZLIK_SINIRI - $kullanilan),
        'yuzde'      => min(100, round($kullanilan / DEVAMSIZLIK_SINIRI * 100)),
        'risk'       => $risk,
    ];
}

// En az hakkı kalan ders en üstte olsun
usort($dersOzetleri, function ($a, $b) {
    return $a['kalan'] - $b['kalan'];
});

// --- Bugünün dersleri ---
$bugun      = date('Y-m-d');
$bugunGun   = GUNLER[(int)date('N')];
$bugunkuler = gununDersleri($bugunGun);

// --- İşlem sonrası bilgi mesajı (ekle.php ve sil.php buraya yönlendirir) ---
$mesajlar = [
    'eklendi' => 'Kayıt eklendi.',
    'silindi' => 'Kayıt silindi.',
    'program' => 'Ders programın kaydedildi.',
];
$mesaj = $mesajlar[$_GET['mesaj'] ?? ''] ?? '';

$sayfaBasligi = 'Haklarım';
$aktifSayfa = 'ana';
include 'header.php';
?>

<?php if ($mesaj !== ''): ?>
    <p class="bildirim basarili"><?= guvenli($mesaj) ?></p>
<?php endif; ?>

<div class="iki-kolon">

    <!-- Sol: kalan haklar -->
    <section class="haklar">
        <h1 class="kucuk-baslik">Kalan devamsızlık hakların, en azdan en çoğa</h1>

        <?php if (count($dersOzetleri) === 0): ?>
            <p class="bos">Henüz ders seçmedin. <a href="program.php">Ders programını seç</a>, dersler buraya gelsin.</p>
        <?php endif; ?>

        <?php foreach ($dersOzetleri as $sira => $ozet): ?>
            <div class="hak <?= $sira === 0 ? 'hak-ilk' : '' ?> <?= $ozet['risk']['sinif'] ?>">
                <span class="hak-sayi"><?= $ozet['kalan'] ?></span>
                <div class="hak-detay">
                    <div class="hak-ust">
                        <span class="hak-ders"><?= guvenli($ozet['ders']) ?></span>
                        <span class="hak-etiket"><?= $ozet['risk']['etiket'] ?></span>
                    </div>
                    <p class="hak-aciklama">
                        <?php if ($ozet['kalan'] === 0): ?>
                            Hakkın kalmadı, <?= $ozet['kullanilan'] ?> saat kullandın
                        <?php else: ?>
                            saat hakkın kaldı, <?= $ozet['kullanilan'] ?> saat kullandın
                        <?php endif; ?>
                    </p>
                    <div class="cubuk"><div class="cubuk-dolu" style="width: <?= $ozet['yuzde'] ?>%"></div></div>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- Sağ: bugün ve özet -->
    <aside class="yan">
        <section class="panel">
            <h2><?= $bugunGun ?>, <?= tarihYaz($bugun) ?></h2>

            <?php if (count($bugunkuler) === 0): ?>
                <p class="soluk">Bugün programında ders yok.</p>
            <?php else: ?>
                <p class="soluk">Bugünkü derslerin. Gitmediysen tek tıkla kaydet.</p>
                <ul class="bugun-listesi">
                    <?php foreach ($bugunkuler as $ders): ?>
                        <li>
                            <div>
                                <strong><?= guvenli($ders['ad']) ?></strong>
                                <span class="soluk"><?= $ders['saat'] ?> saat, <?= guvenli($ders['derslik']) ?></span>
                            </div>
                            <?php if (kayitVarMi($kayitlar, $ders['ad'], $bugun)): ?>
                                <span class="kaydedildi">Kaydedildi</span>
                            <?php else: ?>
                                <!-- Hızlı ekleme: ekle.php'deki aynı doğrulamadan geçer -->
                                <form method="post" action="ekle.php">
                                    <input type="hidden" name="ders" value="<?= guvenli($ders['ad']) ?>">
                                    <input type="hidden" name="tarih" value="<?= $bugun ?>">
                                    <input type="hidden" name="saat" value="<?= $ders['saat'] ?>">
                                    <input type="hidden" name="durum" value="Devamsız">
                                    <button type="submit" class="buton-kucuk">Gitmedim</button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <dl class="ozet">
            <div><dt>Devamsız</dt><dd><?= $devamsizSaat ?> saat</dd></div>
            <div><dt>Raporlu</dt><dd><?= $raporluSaat ?> saat</dd></div>
            <div><dt>Kayıt</dt><dd><?= $toplamKayit ?></dd></div>
            <div><dt>Riskli ders</dt><dd class="<?= $riskliDersSayisi > 0 ? 'kirmizi' : '' ?>"><?= $riskliDersSayisi ?></dd></div>
        </dl>
        <p class="soluk kural">Ders başına <?= DEVAMSIZLIK_SINIRI ?> saat hakkın var. Raporlu saatler sınıra sayılmaz.</p>
    </aside>
</div>

<!-- Kayıt listesi ve filtre -->
<section class="kayitlar">
    <div class="bolum-ust">
        <h2>Kayıtlar</h2>
        <!-- Filtre formu GET kullanır: seçim adres çubuğunda görünür -->
        <form method="get" action="index.php" class="filtre">
            <select name="ders" aria-label="Ders">
                <option value="">Tüm dersler</option>
                <?php foreach ($dersler as $ders): ?>
                    <option value="<?= guvenli($ders) ?>" <?= $ders === $seciliDers ? 'selected' : '' ?>><?= guvenli($ders) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="durum" aria-label="Durum">
                <option value="">Tüm durumlar</option>
                <?php foreach (DURUMLAR as $durum): ?>
                    <option value="<?= guvenli($durum) ?>" <?= $durum === $seciliDurum ? 'selected' : '' ?>><?= guvenli($durum) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="buton ikincil">Filtrele</button>
            <?php if ($seciliDers !== '' || $seciliDurum !== ''): ?>
                <a href="index.php" class="temizle">Filtreyi kaldır</a>
            <?php endif; ?>
        </form>
    </div>

    <p class="soluk sonuc-bilgi"><?= count($gosterilenler) ?> / <?= $toplamKayit ?> kayıt</p>

    <?php if (count($gosterilenler) === 0): ?>
        <p class="bos">Bu filtreye uyan kayıt yok.</p>
    <?php else: ?>
        <div class="tablo-kutu">
            <table>
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Ders</th>
                        <th>Saat</th>
                        <th>Durum</th>
                        <th>Not</th>
                        <th><span class="gizli">İşlem</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gosterilenler as $kayit): ?>
                        <tr>
                            <td class="soluk"><?= tarihYaz($kayit['tarih']) ?></td>
                            <td class="kalin"><?= guvenli($kayit['ders']) ?></td>
                            <td><?= $kayit['saat'] ?> saat</td>
                            <td class="<?= $kayit['durum'] === 'Raporlu' ? 'durum-raporlu' : 'durum-devamsiz' ?>"><?= guvenli($kayit['durum']) ?></td>
                            <td class="soluk not"><?= guvenli($kayit['aciklama']) ?></td>
                            <td class="sag">
                                <!-- Silme işlemi POST ile yapılır -->
                                <form method="post" action="sil.php" onsubmit="return confirm('Bu kaydı silmek istediğine emin misin?');">
                                    <input type="hidden" name="id" value="<?= $kayit['id'] ?>">
                                    <button type="submit" class="sil">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php include 'footer.php'; ?>

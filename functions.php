<?php
/*
 * DevamTakip - functions.php
 * Projede birden fazla sayfada kullanılan sabitler ve fonksiyonlar burada.
 * Her sayfa bu dosyayı "require" ile çağırır.
 */

date_default_timezone_set('Europe/Istanbul');

// Bir dersten en fazla kaç saat devamsızlık yapılabileceği.
// Kural değişirse sadece bu sayıyı değiştirmek yeterli.
const DEVAMSIZLIK_SINIRI = 16;

// Kayıt durumları. Sadece "Devamsız" olanlar sınıra sayılır.
const DURUMLAR = ['Devamsız', 'Raporlu'];

// Haftanın günleri (date('N') 1 = Pazartesi ... 7 = Pazar)
const GUNLER = [1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi', 7 => 'Pazar'];

// Başlangıçta seçili gelen program
const VARSAYILAN_BOLUM = 'Bilgisayar Programcılığı';
const VARSAYILAN_SINIF = 2;


/* ------------------------------------------------------------------
 * DERS PROGRAMI
 * ------------------------------------------------------------------ */

/**
 * Okulun ders programını veri/programlar.json dosyasından okur.
 * json_decode(..., true) JSON'u PHP dizisine çevirir.
 */
function programlariYukle(): array
{
    $json = file_get_contents(__DIR__ . '/veri/programlar.json');
    return json_decode($json, true);
}


/**
 * Adı verilen bölümün derslerini döndürür. Bölüm yoksa boş dizi döner.
 */
function bolumDersleri(array $programlar, string $bolumAdi): array
{
    foreach ($programlar['bolumler'] as $bolum) {
        if ($bolum['ad'] === $bolumAdi) {
            return $bolum['dersler'];
        }
    }
    return [];
}


/**
 * Kullanıcının seçtiği derslerin sadece adlarını verir.
 * Form ve filtredeki ders listeleri buradan gelir.
 */
function dersAdlari(): array
{
    $adlar = [];
    foreach ($_SESSION['program']['dersler'] as $ders) {
        $adlar[] = $ders['ad'];
    }
    return $adlar;
}


/**
 * Bugün (veya verilen günde) hangi dersler var, kaç saat?
 * Sonuç: [['ad' => 'Veri Tabanı I', 'saat' => 4, 'derslik' => 'UYGLAB'], ...]
 */
function gununDersleri(string $gun): array
{
    $sonuc = [];
    foreach ($_SESSION['program']['dersler'] as $ders) {
        if (isset($ders['gunler'][$gun])) {
            $sonuc[] = [
                'ad'      => $ders['ad'],
                'saat'    => $ders['gunler'][$gun],
                'derslik' => $ders['derslik'],
            ];
        }
    }
    return $sonuc;
}


/**
 * Bir dersin haftada toplam kaç saat olduğunu hesaplar.
 */
function haftalikSaat(array $ders): int
{
    return array_sum($ders['gunler']);
}


/* ------------------------------------------------------------------
 * OTURUM ($_SESSION)
 * ------------------------------------------------------------------ */

/**
 * Uygulama ilk açıldığında gösterilecek 5 örnek kayıt.
 * Her kayıtta 6 alan var: id, ders, tarih, saat, durum, aciklama.
 */
function ornekKayitlar(): array
{
    return [
        ['id' => 1, 'ders' => 'Veri Tabanı I',             'tarih' => '2026-10-07', 'saat' => 4, 'durum' => 'Devamsız', 'aciklama' => 'Otobüsü kaçırdım'],
        ['id' => 2, 'ders' => 'Oyun Programlama',          'tarih' => '2026-10-07', 'saat' => 3, 'durum' => 'Devamsız', 'aciklama' => ''],
        ['id' => 3, 'ders' => 'Nesne Tabanlı Programlama', 'tarih' => '2026-10-09', 'saat' => 4, 'durum' => 'Raporlu',  'aciklama' => 'Hastane raporu var'],
        ['id' => 4, 'ders' => 'Mesleki Çözümleme I',       'tarih' => '2026-10-12', 'saat' => 4, 'durum' => 'Devamsız', 'aciklama' => 'Sabah dersine yetişemedim'],
        ['id' => 5, 'ders' => 'Veri Tabanı I',             'tarih' => '2026-10-21', 'saat' => 4, 'durum' => 'Devamsız', 'aciklama' => ''],
    ];
}


/**
 * Kayıtları ve ders programını $_SESSION içinde başlatır.
 * Oturumda veri yoksa (ilk açılış) örnek kayıtları ve varsayılan programı yükler.
 * Böylece sayfa yenilense de eklenen/silinen kayıtlar korunur.
 */
function oturumuHazirla(): void
{
    if (!isset($_SESSION['kayitlar'])) {
        $_SESSION['kayitlar'] = ornekKayitlar();
        $_SESSION['sonId'] = 5; // Yeni kayıtlara verilecek id buradan devam eder
    }

    if (!isset($_SESSION['program'])) {
        $programlar = programlariYukle();
        $secilenler = [];
        foreach (bolumDersleri($programlar, VARSAYILAN_BOLUM) as $ders) {
            if ($ders['sinif'] === VARSAYILAN_SINIF) {
                $secilenler[] = $ders;
            }
        }
        $_SESSION['program'] = ['bolum' => VARSAYILAN_BOLUM, 'dersler' => $secilenler];
    }
}


/* ------------------------------------------------------------------
 * HESAPLAMALAR
 * ------------------------------------------------------------------ */

/**
 * Ekrana yazılacak metni güvenli hale getirir.
 * htmlspecialchars() < > " ' & karakterlerini HTML koduna çevirir,
 * böylece kullanıcı <script> yazsa bile çalışmaz, sadece metin olarak görünür.
 */
function guvenli(string $metin): string
{
    return htmlspecialchars($metin, ENT_QUOTES, 'UTF-8');
}


/**
 * Bir dersteki toplam devamsızlık saatini hesaplar.
 * Sadece durumu "Devamsız" olan kayıtlar sayılır, "Raporlu" sayılmaz.
 */
function dersDevamsizligi(array $kayitlar, string $ders): int
{
    $toplam = 0;
    foreach ($kayitlar as $kayit) {
        if ($kayit['ders'] === $ders && $kayit['durum'] === 'Devamsız') {
            $toplam += $kayit['saat'];
        }
    }
    return $toplam;
}


/**
 * Belirli bir durumdaki kayıtların toplam saatini verir (sayaçlar için).
 */
function durumaGoreSaat(array $kayitlar, string $durum): int
{
    $toplam = 0;
    foreach ($kayitlar as $kayit) {
        if ($kayit['durum'] === $durum) {
            $toplam += $kayit['saat'];
        }
    }
    return $toplam;
}


/**
 * Kullanılan devamsızlık yüzdesine göre risk seviyesini döndürür.
 * 'sinif' CSS'te renk için, 'etiket' ekranda yazı olarak kullanılır.
 */
function riskSeviyesi(int $kullanilan): array
{
    $yuzde = $kullanilan / DEVAMSIZLIK_SINIRI * 100;

    if ($yuzde >= 100) {
        return ['sinif' => 'asildi',  'etiket' => 'Sınır doldu'];
    } elseif ($yuzde >= 75) {
        return ['sinif' => 'kritik',  'etiket' => 'Sınıra yakın'];
    } elseif ($yuzde >= 50) {
        return ['sinif' => 'dikkat',  'etiket' => 'Yarısı gitti'];
    } else {
        return ['sinif' => 'guvende', 'etiket' => 'Güvende'];
    }
}


/**
 * Kayıtları derse ve/veya duruma göre filtreler.
 * Boş gelen filtre "hepsi" anlamına gelir.
 */
function kayitlariFiltrele(array $kayitlar, string $ders, string $durum): array
{
    $sonuc = [];
    foreach ($kayitlar as $kayit) {
        if ($ders !== '' && $kayit['ders'] !== $ders) {
            continue; // Ders uymuyorsa bu kaydı atla
        }
        if ($durum !== '' && $kayit['durum'] !== $durum) {
            continue; // Durum uymuyorsa bu kaydı atla
        }
        $sonuc[] = $kayit;
    }
    return $sonuc;
}


/**
 * Kayıtları tarihe göre yeniden eskiye sıralar.
 * Tarihler "YYYY-AA-GG" biçiminde olduğu için metin olarak karşılaştırılabilir.
 */
function tariheGoreSirala(array $kayitlar): array
{
    usort($kayitlar, function ($a, $b) {
        return strcmp($b['tarih'], $a['tarih']);
    });
    return $kayitlar;
}


/**
 * "2026-10-07" biçimindeki tarihi "07.10.2026" biçimine çevirir.
 */
function tarihYaz(string $tarih): string
{
    return date('d.m.Y', strtotime($tarih));
}


/**
 * Aynı derse aynı gün için zaten kayıt var mı?
 */
function kayitVarMi(array $kayitlar, string $ders, string $tarih): bool
{
    foreach ($kayitlar as $kayit) {
        if ($kayit['ders'] === $ders && $kayit['tarih'] === $tarih) {
            return true;
        }
    }
    return false;
}

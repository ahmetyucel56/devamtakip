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
 * OTURUM ($_SESSION) - sadece ders programı
 * ------------------------------------------------------------------ */

/**
 * Ders programını $_SESSION içinde başlatır.
 * Kayıtlar artık MySQL'de tutuluyor; oturumda sadece seçilen ders programı var.
 */
function oturumuHazirla(): void
{
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


/* ------------------------------------------------------------------
 * VERİTABANI İŞLEMLERİ (PDO)
 * Bütün sorgular hazırlanmış ifade (prepare + execute) ile çalışır.
 * Kullanıcıdan gelen değer SQL metnine eklenmez, :ad gibi yer tutuculara bağlanır.
 * Bu, SQL enjeksiyonunu önler.
 * ------------------------------------------------------------------ */

/**
 * Bütün kayıtları tarihe göre yeniden eskiye getirir.
 */
function kayitlariGetir(PDO $pdo): array
{
    $sorgu = $pdo->query('SELECT id, ders, tarih, saat, durum, aciklama FROM kayitlar ORDER BY tarih DESC, id DESC');
    return $sorgu->fetchAll();
}


/**
 * Yeni bir kayıt ekler ve eklenen kaydın id'sini döndürür.
 */
function kayitEkle(PDO $pdo, array $kayit): int
{
    $sorgu = $pdo->prepare(
        'INSERT INTO kayitlar (ders, tarih, saat, durum, aciklama)
         VALUES (:ders, :tarih, :saat, :durum, :aciklama)'
    );
    $sorgu->execute([
        'ders'     => $kayit['ders'],
        'tarih'    => $kayit['tarih'],
        'saat'     => $kayit['saat'],
        'durum'    => $kayit['durum'],
        'aciklama' => $kayit['aciklama'],
    ]);
    return (int)$pdo->lastInsertId();
}


/**
 * Tek bir kaydı id'sine göre getirir. Bulunamazsa null döner.
 */
function kayitGetir(PDO $pdo, int $id): ?array
{
    $sorgu = $pdo->prepare('SELECT id, ders, tarih, saat, durum, aciklama FROM kayitlar WHERE id = :id');
    $sorgu->execute(['id' => $id]);
    $kayit = $sorgu->fetch();
    return $kayit === false ? null : $kayit;
}


/**
 * Var olan bir kaydı günceller. Bir satır değiştiyse true döner.
 */
function kayitGuncelle(PDO $pdo, int $id, array $kayit): bool
{
    $sorgu = $pdo->prepare(
        'UPDATE kayitlar
         SET ders = :ders, tarih = :tarih, saat = :saat, durum = :durum, aciklama = :aciklama
         WHERE id = :id'
    );
    $sorgu->execute([
        'ders'     => $kayit['ders'],
        'tarih'    => $kayit['tarih'],
        'saat'     => $kayit['saat'],
        'durum'    => $kayit['durum'],
        'aciklama' => $kayit['aciklama'],
        'id'       => $id,
    ]);
    return $sorgu->rowCount() > 0;
}


/**
 * Bir kaydı siler. Silinen satır varsa true döner.
 */
function kayitSil(PDO $pdo, int $id): bool
{
    $sorgu = $pdo->prepare('DELETE FROM kayitlar WHERE id = :id');
    $sorgu->execute(['id' => $id]);
    return $sorgu->rowCount() > 0;
}


/**
 * Aynı derse aynı gün için zaten kayıt var mı?
 * Güncellemede kaydın kendisi sayılmasın diye $haricId verilebilir.
 */
function kayitVarMi(PDO $pdo, string $ders, string $tarih, int $haricId = 0): bool
{
    $sorgu = $pdo->prepare('SELECT COUNT(*) FROM kayitlar WHERE ders = :ders AND tarih = :tarih AND id <> :haric');
    $sorgu->execute(['ders' => $ders, 'tarih' => $tarih, 'haric' => $haricId]);
    return (int)$sorgu->fetchColumn() > 0;
}


/* ------------------------------------------------------------------
 * FORM DOĞRULAMA (ekle.php ve duzenle.php ortak kullanır)
 * ------------------------------------------------------------------ */

/**
 * Formdan gelen veriyi temizler ve kontrol eder.
 * Dönüş: [temizlenmiş veri, hata dizisi]. Hata dizisi boşsa veri kaydedilebilir.
 */
function kayitDogrula(array $girdi, array $izinliDersler): array
{
    $veri = [
        'ders'     => trim($girdi['ders'] ?? ''),
        'tarih'    => trim($girdi['tarih'] ?? ''),
        'saat'     => trim($girdi['saat'] ?? ''),
        'durum'    => trim($girdi['durum'] ?? ''),
        'aciklama' => trim($girdi['aciklama'] ?? ''),
    ];
    $hatalar = [];

    if ($veri['ders'] === '') {
        $hatalar['ders'] = 'Bir ders seçin.';
    } elseif (!in_array($veri['ders'], $izinliDersler, true)) {
        $hatalar['ders'] = 'Bu ders programında yok. Önce ders programına ekleyin.';
    }

    if ($veri['tarih'] === '') {
        $hatalar['tarih'] = 'Tarih boş bırakılamaz.';
    } else {
        $tarihNesnesi = DateTime::createFromFormat('Y-m-d', $veri['tarih']);
        if ($tarihNesnesi === false || $tarihNesnesi->format('Y-m-d') !== $veri['tarih']) {
            $hatalar['tarih'] = 'Geçerli bir tarih girin.';
        } elseif ($veri['tarih'] > date('Y-m-d')) {
            $hatalar['tarih'] = 'İleri bir tarihe devamsızlık girilemez.';
        }
    }

    if ($veri['saat'] === '') {
        $hatalar['saat'] = 'Saat sayısı boş bırakılamaz.';
    } elseif (!ctype_digit($veri['saat']) || (int)$veri['saat'] < 1 || (int)$veri['saat'] > 8) {
        $hatalar['saat'] = 'Saat 1 ile 8 arasında tam sayı olmalı.';
    }

    if (!in_array($veri['durum'], DURUMLAR, true)) {
        $hatalar['durum'] = 'Devamsız veya Raporlu seçin.';
    }

    if (mb_strlen($veri['aciklama']) > 150) {
        $hatalar['aciklama'] = 'Not en fazla 150 karakter olabilir.';
    }

    return [$veri, $hatalar];
}

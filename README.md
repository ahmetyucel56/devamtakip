# DevamTakip – Devamsızlık Hak Takip Sistemi

**Ders:** İnternet Programcılığı I (PHP)
**Öğretim elemanı:** Öğr. Gör. Bilal İnan
**Öğrenci:** Ahmet Hilmi Yücel – 254309056
**Sürüm:** 2.0 (vize sonrası beş haftalık görev, MySQL + PDO + kullanıcı girişi)

Vize teslimindeki ilk sürüm (`$_SESSION` ile çalışan) git geçmişinde duruyor: [a646ca0](https://github.com/ahmetyucel56/devamtakip/tree/a646ca0) commit'i. Vize sonrası her hafta ayrı bir commit olarak eklendi.

## Projenin amacı

Öğrenciler çoğu zaman hangi dersten kaç saat devamsızlık yaptıklarını ve ne kadar hakları kaldığını takip edemiyor. DevamTakip, kaçırılan dersleri kaydetmeyi ve her ders için kalan devamsızlık hakkını görmeyi sağlayan bir PHP uygulamasıdır. Okulun ders programı uygulamaya yüklü olduğu için öğrenci dersleri elle girmez; bölümünü seçip aldığı dersleri işaretler.

## Kurulum (XAMPP)

1. XAMPP Control Panel'den **Apache** ve **MySQL**'i başlatın.
2. Proje klasörünü `C:\xampp\htdocs\devamtakip` olarak kopyalayın (GitHub'da **Code → Download ZIP** ya da `git clone`).
3. `http://localhost/phpmyadmin` adresini açın, üstteki **İçe aktar** sekmesinden projedeki `kurulum.sql` dosyasını seçip **İçe aktar**'a basın.
   Bu dosya `devamtakip` veritabanını, üç tabloyu ve örnek verileri oluşturur. Tekrar içe aktarılırsa tablolar silinip baştan kurulur.
   "11 sorgu çalıştırıldı" yazısı çıktıysa kurulum tamamdır; altta görünen `#1046` / `#1007` notları phpMyAdmin'in kendi kontrollerinden gelir.
4. Tarayıcıda `http://localhost/devamtakip/` adresini açın, giriş sayfası gelir.

Veritabanı ayarları `config.php` içindedir (XAMPP varsayılanı: kullanıcı `root`, parola boş).

### Deneme hesapları

| Kullanıcı adı | Parola | İçerik |
|---|---|---|
| `ahmet` | `Ahmet123!` | Bilgisayar Programcılığı 2. sınıf programı, 5 kayıt |
| `deneme` | `Deneme123!` | Bilgisayar Programcılığı 1. sınıftan 4 ders, 2 kayıt |

"Kayıt ol" sayfasından yeni hesap da açılabilir.

## Haftalara göre yapılanlar

| Hafta | Konu | Dosyalar |
|---|---|---|
| 1 | MySQL veritabanı ve `kayitlar` tablosu | `kurulum.sql` |
| 2 | PDO bağlantısı, kayıt ekleme ve listeleme | `config.php`, `db.php`, `ekle.php`, `index.php` |
| 3 | Kayıt güncelleme ve silme | `duzenle.php`, `sil.php`, `kayit_form.php` |
| 4 | Arama, filtreleme, sıralama | `index.php`, `functions.php` (`kayitlariAra`) |
| 5 | Kullanıcı girişi, kayıt sahipliği, son testler | `oturum.php`, `giris.php`, `kayit_ol.php`, `cikis.php`, `TESTLER.md` |

Her hafta ayrı bir commit olarak git geçmişinde görülebilir.

## Veritabanı

```
kullanicilar        id, ad_soyad, kullanici_adi (UNIQUE), parola_hash, bolum, olusturma
kullanici_dersleri  id, kullanici_id → kullanicilar.id, ders_kodu, ders_adi, derslik, gunler
kayitlar            id, kullanici_id → kullanicilar.id, ders, tarih, saat, durum, aciklama,
                    olusturma, guncelleme
```

- `kayitlar.kullanici_id` ve `kullanici_dersleri.kullanici_id` yabancı anahtardır (`ON DELETE CASCADE`): kullanıcı silinirse kayıtları da silinir.
- `UNIQUE (kullanici_id, ders, tarih)`: aynı kullanıcı aynı derse aynı gün için iki kayıt giremez (PHP'de de kontrol edilir).
- `saat` için `CHECK (saat BETWEEN 1 AND 8)`, `durum` için `ENUM('Devamsız','Raporlu')` kullanıldı.
- Tablolar `utf8mb4_turkish_ci` ile oluşturuldu, Türkçe karakterler ve sıralama düzgün çalışır.

## Üç önemli kısmın açıklaması

### 1. PDO bağlantısı (`db.php`)

`baglanti()` fonksiyonu `config.php`'deki bilgilerle bir `PDO` nesnesi oluşturur:

```php
$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
$pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // SQL hatası olursa istisna fırlatılır
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // satırlar ['ders' => ...] dizisi olarak gelir
    PDO::ATTR_EMULATE_PREPARES   => false,                   // prepared statement'ı MySQL çalıştırır
]);
```

- Fonksiyondaki `static $pdo` sayesinde bir sayfada bağlantı bir kez açılır, sonraki çağrılarda aynı nesne döner.
- Bağlantı kurulamazsa (MySQL kapalı, veritabanı yok) kullanıcıya teknik hata yerine "XAMPP'te MySQL'in çalıştığını kontrol edin" mesajı gösterilir.
- Bütün sorgular **hazırlanmış ifade** (prepared statement) ile yazıldı. Kullanıcıdan gelen değer SQL metnine eklenmez, `:ders` gibi yer tutuculara ayrıca gönderilir. Bu yüzden SQL injection yapılamaz:

```php
$sorgu = $pdo->prepare('DELETE FROM kayitlar WHERE id = :id AND kullanici_id = :kullanici_id');
$sorgu->execute(['id' => $id, 'kullanici_id' => $kullaniciId]);
```

- Sıralama (`ORDER BY`) yer tutucuyla yazılamadığı için `SIRALAMALAR` adlı sabit bir listeden seçilir. Listede olmayan değer gelirse varsayılan sıralama kullanılır.
- Aramada `%` ve `_` karakterleri kaçışlanır, böylece kullanıcı "%" yazınca bütün kayıtlar gelmez.

### 2. Kullanıcı oturumu (`oturum.php`, `giris.php`)

- Parolalar veritabanında düz metin olarak değil, `password_hash()` ile oluşturulan özet (hash) olarak saklanır. Girişte `password_verify()` ile karşılaştırılır.
- Kullanıcı adı ya da parola yanlışsa ikisi için de aynı mesaj gösterilir ("Kullanıcı adı veya parola hatalı"). Böylece hangi kullanıcı adlarının kayıtlı olduğu anlaşılmaz.
- Giriş başarılı olunca `oturumAc()` çalışır:
  - `session_regenerate_id(true)` ile oturum kimliği yenilenir (giriş öncesi kimlik ele geçirilmişse işe yaramaz).
  - `$_SESSION['kullanici_id']` ve `$_SESSION['ad_soyad']` yazılır.
- Korunan her sayfanın başında `$kullaniciId = girisGerekli();` satırı vardır. Oturumda `kullanici_id` yoksa kullanıcı `giris.php`'ye yönlendirilir ve sayfanın geri kalanı çalışmaz.
- Oturum çerezi `httponly` (JavaScript okuyamaz) ve `SameSite=Lax` olarak ayarlandı.
- Çıkış POST ile yapılır; `oturumKapat()` oturum verisini ve çerezi siler.
- Tüm POST formlarında gizli bir **CSRF jetonu** vardır. Jeton oturumda saklanır, form gönderilince `hash_equals()` ile karşılaştırılır. Jeton yoksa ya da yanlışsa işlem yapılmaz (400). Bu, başka bir sitenin kullanıcı adına form göndermesini engeller.

### 3. Kayıt sahipliği kontrolü (`functions.php`)

Her kayıt, onu ekleyen kullanıcının id'siyle (`kullanici_id`) saklanır. Kayıtlarla ilgili **bütün** sorgulara `kullanici_id = :kullanici_id` koşulu eklenmiştir ve bu değer formdan değil, oturumdan (`girisGerekli()`) gelir:

| İşlem | Fonksiyon | Koşul |
|---|---|---|
| Listeleme / arama | `kayitlariGetir`, `kayitlariAra` | `WHERE kullanici_id = :kullanici_id ...` |
| Düzenleme için okuma | `kayitGetir` | `WHERE id = :id AND kullanici_id = :kullanici_id` |
| Güncelleme | `kayitGuncelle` | `UPDATE ... WHERE id = :id AND kullanici_id = :kullanici_id` |
| Silme | `kayitSil` | `DELETE ... WHERE id = :id AND kullanici_id = :kullanici_id` |
| Ekleme | `kayitEkle` | `kullanici_id` oturumdaki kullanıcı olarak yazılır |

Örneğin `ahmet` hesabıyla adres çubuğuna `duzenle.php?id=6` yazılırsa (6 numaralı kayıt `deneme`'ye ait), `kayitGetir()` boş döner ve "Kayıt bulunamadı" mesajıyla ana sayfaya dönülür. Silme ve güncellemede de koşul tutmadığı için `rowCount()` 0 olur ve hiçbir satır değişmez. Kaydın başkasına ait olduğu da söylenmez; kullanıcı sadece "bulunamadı" görür.

## Özellikler

- **Giriş / kayıt ol / çıkış:** Kullanıcı adı 3-30 karakter (küçük harf, rakam, alt çizgi), parola en az 8 karakter.
- **Kalan haklar:** Her ders için kalan devamsızlık saati, en az hakkı kalan ders en üstte. Risk durumuna göre renk ve etiket (Güvende, Yarısı gitti, Sınıra yakın, Sınır doldu).
- **Bugünkü dersler:** Ders programına göre o günün dersleri. "Gitmedim" butonu dersi o günün tarihi ve ders saatiyle kaydeder.
- **Ders programı:** Okulun 2026-2027 Güz programındaki 10 bölümden biri seçilir, alınan dersler işaretlenir. Program her kullanıcı için `kullanici_dersleri` tablosunda saklanır.
- **Kayıt ekleme ve düzenleme:** İkisi aynı formu (`kayit_form.php`) ve aynı doğrulama fonksiyonunu (`kayitDogrula`) kullanır.
- **Silme:** Sadece POST ile, onay sorusundan sonra. Adres çubuğundan `sil.php?id=1` açılırsa hiçbir şey silinmez.
- **Arama, filtreleme, sıralama:** Ders adı veya notta arama; derse, duruma ve tarih aralığına göre filtre; en yeni, en eski, saate ve derse göre sıralama. Hepsi SQL sorgusunda yapılır, form GET ile gönderildiği için sonuç adresi paylaşılabilir.
- **Veri doğrulama (PHP tarafında):** Ders programda olmalı; tarih geçerli olmalı ve ileri tarih olamaz; saat 1-8 arası tam sayı; durum "Devamsız" ya da "Raporlu"; not en fazla 150 karakter; aynı derse aynı gün ikinci kayıt girilemez. Hatalı alanların altında mesaj çıkar, girilen değerler kaybolmaz.
- **Güvenli çıktı:** Kullanıcının girdiği tüm metinler `htmlspecialchars()` ile yazılır.
- **Post/Redirect/Get:** Ekleme, güncelleme ve silmeden sonra ana sayfaya yönlendirilir, sayfa yenilenince işlem tekrarlanmaz.

### Kurallar (varsayımlar)

- Ders başına devamsızlık sınırı **16 saat** (`functions.php` içindeki `DEVAMSIZLIK_SINIRI`).
- "Raporlu" kayıtlar listede görünür ama sınıra sayılmaz.

## Klasör yapısı

```
devamtakip/
├── index.php          Ana sayfa: kalan haklar, bugünkü dersler, arama/filtre ve kayıt listesi
├── ekle.php           Yeni kayıt (INSERT)
├── duzenle.php        Kayıt güncelleme (SELECT + UPDATE)
├── sil.php            Kayıt silme (DELETE, sadece POST)
├── kayit_form.php     Ekleme ve düzenlemenin ortak formu
├── program.php        Ders programı seçimi ve haftalık görünüm
├── giris.php          Giriş sayfası
├── kayit_ol.php       Yeni hesap oluşturma
├── cikis.php          Çıkış (sadece POST)
├── oturum.php         Oturum ayarları, giriş kontrolü, CSRF jetonu
├── config.php         Veritabanı bağlantı bilgileri
├── db.php             PDO bağlantısı
├── functions.php      Sabitler ve kullanıcı tanımlı fonksiyonlar
├── header.php         Ortak üst kısım ve menü
├── footer.php         Ortak alt kısım
├── kurulum.sql        Veritabanı, tablolar ve örnek veriler
├── TESTLER.md         Test senaryoları ve sonuçları
├── LICENSE            MIT lisansı
├── veri/
│   └── programlar.json  Okulun ders programı (10 bölüm, 1. ve 2. sınıf)
├── assets/            CSS ve projeye gömülü fontlar
└── docs/              Vize sürümünün tanıtım videosu ve kapak görseli
```

### Kullanıcı tanımlı fonksiyonlar

| Fonksiyon | Dosya | Görevi |
|---|---|---|
| `baglanti()` | db.php | PDO bağlantısını döndürür |
| `aktifKullaniciId()` | oturum.php | Giriş yapan kullanıcının id'si (yoksa 0) |
| `girisGerekli()` | oturum.php | Giriş yoksa giriş sayfasına yönlendirir, varsa id döndürür |
| `oturumAc($kullanici)` / `oturumKapat()` | oturum.php | Giriş ve çıkış işlemleri |
| `csrfJeton()`, `csrfAlani()`, `csrfKontrol()` | oturum.php | Form güvenlik jetonu |
| `kullaniciBul()`, `kullaniciEkle()` | functions.php | Kullanıcı sorgulama ve ekleme (`password_hash`) |
| `kullaniciProgrami()`, `programKaydet()` | functions.php | Kullanıcının ders programını okur / kaydeder (transaction ile) |
| `programlariYukle()`, `bolumDersleri()` | functions.php | `programlar.json`'u okur, bir bölümün derslerini verir |
| `dersAdlari()`, `gununDersleri()`, `haftalikSaat()` | functions.php | Programdan ders adları, günün dersleri, haftalık saat |
| `kayitlariGetir()`, `kayitlariAra()` | functions.php | Kullanıcının kayıtlarını listeler / arar |
| `aramaKriterleri($_GET, $dersler)` | functions.php | Arama formundan gelen değerleri kontrol eder |
| `kayitGetir()`, `kayitEkle()`, `kayitGuncelle()`, `kayitSil()` | functions.php | Tek kayıt işlemleri (hepsinde `kullanici_id` koşulu) |
| `kayitVarMi()` | functions.php | Aynı derse aynı gün kayıt var mı |
| `kayitDogrula()` | functions.php | Form verisini kontrol eder, hataları döndürür |
| `dersDevamsizligi()`, `durumaGoreSaat()`, `riskSeviyesi()` | functions.php | Kalan hak ve sayaç hesapları |
| `guvenli()`, `tarihYaz()` | functions.php | `htmlspecialchars` ve tarih biçimi |

## Testler

Bütün senaryolar XAMPP (PHP 8.2, MariaDB) üzerinde denendi. Ayrıntılar ve sonuçlar [TESTLER.md](TESTLER.md) dosyasında.

## Bilinen eksikler

- Devamsızlık sınırı tüm dersler için aynı (16 saat).
- Resmi tatiller ve sınav haftaları dikkate alınmıyor; "Bugün" paneli sadece haftanın gününe bakıyor.
- "Parolamı unuttum" ve hesap silme özelliği yok.
- Giriş denemesi sayısı sınırlanmıyor.
- `kurulum.sql` proje klasöründe olduğu için sunucuda tarayıcıdan indirilebilir; içinde sadece deneme hesapları var ama gerçek bir sunucuda bu dosya web kök dizini dışında tutulmalı.

## Yapay zekâ kullanımı

**Kullanılan araç:** Claude (Anthropic)

**Kullanım amaçları:**
- Vize sürümünü MySQL'e taşırken tablo yapısını ve haftalık planı çıkarma
- PDO, oturum ve CSRF kodlarının ilk taslağını yazdırma
- Kodun her satırının ne yaptığını açıklatma ve soru-cevaba hazırlanma
- Test senaryolarını listeleme ve testleri tarayıcıda çalıştırma
- README ve TESTLER dosyalarının taslağı

**Örnek istem:**
> "Vize projemdeki $_SESSION ile tutulan kayıtları MySQL'e taşımak istiyorum. PDO kullan, bütün sorgular prepared statement olsun. Sonra kullanıcı girişi ekleyeceğim, her kullanıcı sadece kendi kayıtlarını görebilmeli. Başka kullanıcının kaydını id'sini yazarak açamamalı. Kodu sade yaz, her fonksiyona açıklama ekle."

**Kendi katkım:**
- Vize sürümündeki tasarımı ve ders programı mantığını koruyarak geliştirmeye devam ettim.
- Ders programının da kullanıcıya göre veritabanında tutulmasına karar verdim.
- Uygulamayı XAMPP'te iki farklı hesapla çalıştırıp birinin kaydını diğerinden açmayı, düzenlemeyi ve silmeyi denedim.
- Kodu inceleyip PDO bağlantısının, oturumun ve sahiplik kontrolünün nasıl çalıştığını öğrendim (yukarıdaki açıklamalar).

## Lisans

Proje [MIT Lisansı](LICENSE) ile paylaşılmıştır. `assets/fonts/` içindeki Figtree ve Big Shoulders Display fontları kendi lisanslarıyla (SIL Open Font License) gelir; lisans metinleri aynı klasördedir. `veri/programlar.json` içindeki ders programı bilgisi Niksar MYO'ya aittir.

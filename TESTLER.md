# DevamTakip – Testler

**Ortam:** Windows, XAMPP (PHP 8.2.12, MariaDB 10.4.32), Chrome
**Tarih:** 26.11.2026
**Başlangıç durumu:** `kurulum.sql` phpMyAdmin'den içe aktarıldı (11 sorgu, hatasız).

Deneme hesapları: `ahmet` / `Ahmet123!` (kayıt id 1-5), `deneme` / `Deneme123!` (kayıt id 6-7).

## 1. hafta – Veritabanı

| # | Senaryo | Beklenen | Sonuç |
|---|---|---|---|
| 1.1 | `kurulum.sql` içe aktarılır | `devamtakip` veritabanı, 3 tablo ve örnek veriler oluşur | Geçti |
| 1.2 | Dosya ikinci kez içe aktarılır | Tablolar silinip baştan kurulur, test sırasında eklenen veriler gider | Geçti |
| 1.3 | Aynı kullanıcı, ders ve tarih için ikinci satır | `UNIQUE (kullanici_id, ders, tarih)` engeller | Geçti |

## 2. hafta – PDO bağlantısı, ekleme, listeleme

| # | Senaryo | Beklenen | Sonuç |
|---|---|---|---|
| 2.1 | `ahmet` ile giriş, ana sayfa | 5 kayıt tarihe göre yeniden eskiye listelenir | Geçti |
| 2.2 | Veri Tabanı I, 18.11.2026, 2 saat, Devamsız eklenir | "Kayıt eklendi" mesajı, kayıt listede | Geçti |
| 2.3 | Aynı derse aynı tarihle tekrar eklenir | "Bu derse bu tarih için zaten kayıt var" | Geçti |
| 2.4 | Programda olmayan ders, ileri tarih (2099), saat 12, durum "Hasta" | Kayıt eklenmez, 4 alanın altında hata mesajı | Geçti |
| 2.5 | Not alanına `<script>alert(1)</script>` yazılır | Kod çalışmaz, metin olarak görünür (`&lt;script&gt;`) | Geçti |
| 2.6 | "Gitmedim" butonu | Bugünün tarihi ve dersin saatiyle kayıt eklenir | Geçti |

## 3. hafta – Güncelleme ve silme

| # | Senaryo | Beklenen | Sonuç |
|---|---|---|---|
| 3.1 | 2.2'deki kayıt düzenlenir (saat 3, Raporlu) | "Kayıt güncellendi", liste değişir | Geçti |
| 3.2 | Düzenlemede tarih başka bir kaydın tarihine çekilir | "Bu derse bu tarih için başka bir kayıt var" | Geçti |
| 3.3 | `duzenle.php?id=9999` | "Kayıt bulunamadı" ile ana sayfaya dönüş | Geçti |
| 3.4 | Adres çubuğundan `sil.php?id=…` (GET) | Hiçbir şey silinmez, ana sayfaya dönülür | Geçti |
| 3.5 | Listeden "Sil" (POST) | Onay sorusundan sonra "Kayıt silindi" | Geçti |

## 4. hafta – Arama, filtreleme, sıralama

| # | Senaryo | Beklenen | Sonuç |
|---|---|---|---|
| 4.1 | Arama kutusuna `script` | Sadece notunda bu kelime geçen kayıt gelir | Geçti |
| 4.2 | Durum: Raporlu | Sadece raporlu kayıtlar | Geçti |
| 4.3 | Tarih aralığı 01.11.2026 – 30.11.2026 | Sadece bu aralıktaki kayıtlar | Geçti |
| 4.4 | Arama kutusuna `%` | Bütün kayıtlar gelmez (joker karakter kaçışlanır), sonuç 0 | Geçti |
| 4.5 | Sıralama: saat, ders, en eski | Liste seçilen düzene göre sıralanır | Geçti |
| 4.6 | `index.php?sirala=tarih;DROP TABLE kayitlar` | Hata yok, varsayılan sıralama kullanılır, tablo silinmez | Geçti |
| 4.7 | "Temizle" | Tüm filtreler kalkar | Geçti |

## 5. hafta – Kullanıcı girişi ve kayıt sahipliği

| # | Senaryo | Beklenen | Sonuç |
|---|---|---|---|
| 5.1 | Girişsiz `index.php` ve `duzenle.php?id=1` açılır | Giriş sayfasına yönlendirilir | Geçti |
| 5.2 | `ahmet` + yanlış parola | "Kullanıcı adı veya parola hatalı" | Geçti |
| 5.3 | Olmayan kullanıcı adı | Aynı mesaj (kullanıcı adının var olup olmadığı belli olmaz) | Geçti |
| 5.4 | Giriş formu CSRF jetonu olmadan gönderilir | 400, giriş yapılmaz | Geçti |
| 5.5 | `ahmet` + doğru parola | Ana sayfa, menüde "Ahmet Hilmi Yücel" | Geçti |
| 5.6 | Ekleme formu CSRF jetonu olmadan gönderilir | 400, kayıt eklenmez | Geçti |
| 5.7 | `ahmet` listesinde `deneme`'nin kayıtları | Görünmez (sadece kendi 5 kaydı) | Geçti |
| 5.8 | `ahmet` ile `duzenle.php?id=6` (deneme'nin kaydı) | "Kayıt bulunamadı", form açılmaz | Geçti |
| 5.9 | `ahmet` ile id=6 için düzenleme formu elle POST edilir | "Kayıt bulunamadı", kayıt değişmez | Geçti |
| 5.10 | `ahmet` ile `sil.php`'ye id=7 POST edilir | "Kayıt bulunamadı", kayıt silinmez | Geçti |
| 5.11 | Çıkış | "Çıkış yapıldı", sonra `index.php` giriş sayfasına yönlenir | Geçti |
| 5.12 | `deneme` ile giriş | Sadece kendi 2 kaydı, 5.9'daki değişiklik yok | Geçti |
| 5.13 | `deneme` ile `duzenle.php?id=1` (ahmet'in kaydı) | "Kayıt bulunamadı" | Geçti |
| 5.14 | Kayıt ol: 2 harfli ad, boşluklu kullanıcı adı, kısa ve uyuşmayan parola | "Hesap oluşturulmadı", alanların altında hatalar | Geçti |
| 5.15 | Kayıt ol: kullanıcı adı `ahmet` | "Bu kullanıcı adı alınmış" | Geçti |
| 5.16 | Kayıt ol: geçerli bilgiler | Otomatik giriş, ders programı sayfası ve karşılama mesajı | Geçti |
| 5.17 | Yeni hesabın ana sayfası | Kayıt yok, "Henüz ders seçmedin" mesajı | Geçti |
| 5.18 | Yeni hesapta 2 ders seçilip program kaydedilir | Ekleme formunda sadece bu 2 ders çıkar | Geçti |
| 5.19 | Yeni hesap kayıt ekler, sonra `duzenle.php?id=1` açar | Kayıt eklenir; ahmet'in kaydı açılmaz | Geçti |

## Sonuç

40 senaryonun hepsi beklenen sonucu verdi. Formlar elle doldurulmanın yanında, CSRF ve sahiplik denemeleri için tarayıcı konsolundan doğrudan POST isteği gönderilerek de denendi.

Notlar:
- phpMyAdmin içe aktarmada `#1046 No database selected` ve `#1007 database exists` satırları görünebilir. Bunlar phpMyAdmin'in kendi kontrol sorgularından gelir; "İçe aktarma başarılı olarak tamamlandı, 11 sorgu çalıştırıldı" yazısı çıktıysa kurulum tamamdır.
- MySQL kapalıyken gösterilen bağlantı hatası mesajı bu test turunda denenmedi.

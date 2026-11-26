-- DevamTakip - Veritabanı kurulum dosyası
-- phpMyAdmin > İçe aktar (Import) ile bu dosyayı seçip çalıştırın.
-- Dosya her çalıştırıldığında tablolar silinip örnek verilerle yeniden oluşturulur.

CREATE DATABASE IF NOT EXISTS devamtakip
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_turkish_ci;

USE devamtakip;

-- Yabancı anahtar sırası yüzünden önce bağlı tablolar silinir
DROP TABLE IF EXISTS kayitlar;
DROP TABLE IF EXISTS kullanici_dersleri;
DROP TABLE IF EXISTS kullanicilar;

-- ------------------------------------------------------------
-- Kullanıcılar
-- Parola düz metin olarak değil, password_hash() ile üretilen özet (hash) olarak saklanır.
-- ------------------------------------------------------------
CREATE TABLE kullanicilar (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ad_soyad       VARCHAR(100) NOT NULL,
    kullanici_adi  VARCHAR(30)  NOT NULL,
    parola_hash    VARCHAR(255) NOT NULL,
    bolum          VARCHAR(100) NOT NULL DEFAULT 'Bilgisayar Programcılığı',
    olusturma      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY kullanici_adi_tek (kullanici_adi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ------------------------------------------------------------
-- Kullanıcının seçtiği dersler (ders programı)
-- gunler: hangi gün kaç saat olduğu, JSON metni olarak. Örn: {"Pazartesi": 4, "Salı": 4}
-- ------------------------------------------------------------
CREATE TABLE kullanici_dersleri (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kullanici_id   INT UNSIGNED NOT NULL,
    ders_kodu      VARCHAR(20)  NOT NULL,
    ders_adi       VARCHAR(100) NOT NULL,
    derslik        VARCHAR(50)  NOT NULL DEFAULT '',
    gunler         VARCHAR(255) NOT NULL DEFAULT '{}',
    PRIMARY KEY (id),
    UNIQUE KEY ders_tek (kullanici_id, ders_kodu),
    CONSTRAINT fk_ders_kullanici FOREIGN KEY (kullanici_id)
        REFERENCES kullanicilar (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ------------------------------------------------------------
-- Devamsızlık kayıtları
-- Her kayıt bir kullanıcıya aittir (kullanici_id). Sorgular her zaman bu sütunla süzülür.
-- ------------------------------------------------------------
CREATE TABLE kayitlar (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kullanici_id   INT UNSIGNED NOT NULL,
    ders           VARCHAR(100) NOT NULL,
    tarih          DATE NOT NULL,
    saat           TINYINT UNSIGNED NOT NULL,
    durum          ENUM('Devamsız', 'Raporlu') NOT NULL DEFAULT 'Devamsız',
    aciklama       VARCHAR(150) NOT NULL DEFAULT '',
    olusturma      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    guncelleme     DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY tek_kayit (kullanici_id, ders, tarih),       -- Aynı kullanıcı, aynı derse aynı gün ikinci kayıt giremez
    KEY kullanici_tarih (kullanici_id, tarih),
    CONSTRAINT saat_araligi CHECK (saat BETWEEN 1 AND 8),
    CONSTRAINT fk_kayit_kullanici FOREIGN KEY (kullanici_id)
        REFERENCES kullanicilar (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ------------------------------------------------------------
-- Örnek veriler
-- Deneme hesapları:  ahmet / Ahmet123!   ve   deneme / Deneme123!
-- ------------------------------------------------------------
INSERT INTO kullanicilar (id, ad_soyad, kullanici_adi, parola_hash, bolum) VALUES
(1, 'Ahmet Hilmi Yücel', 'ahmet',  '$2y$10$.Pi8D.0/6FToFcA9WvJQcOLxOf1sPpY/ZMCgnlx35SjD51ZGsqv9K', 'Bilgisayar Programcılığı'),
(2, 'Deneme Öğrenci',    'deneme', '$2y$10$pLZBhuSquKWRgPxb14UPLu9iiUskeBKg7s0wm6WiCMzeKzaZUce0u', 'Bilgisayar Programcılığı');

INSERT INTO kullanici_dersleri (kullanici_id, ders_kodu, ders_adi, derslik, gunler) VALUES
(1, 'BP201', 'İnternet Programcılığı I', 'TLAB', '{"Perşembe": 4}'),
(1, 'BP203', 'Veri Tabanı I', 'UYGLAB', '{"Çarşamba": 4}'),
(1, 'BP205', 'Nesne Tabanlı Programlama', 'UYGLAB', '{"Cuma": 4}'),
(1, 'BP207', 'Mesleki Çözümleme I', 'TLAB', '{"Pazartesi": 4, "Salı": 4}'),
(1, 'BP225', 'Oyun Programlama', 'UYGLAB', '{"Çarşamba": 3}'),
(1, 'BP243', 'Yapay Zekanın Temelleri', 'UYGLAB', '{"Perşembe": 3}'),
(1, 'BP245', 'Veri Madenciliği', 'TLAB', '{"Salı": 3}'),
(2, 'BP101', 'Matematik', 'Z/03', '{"Perşembe": 4}'),
(2, 'BP103', 'Algoritma ve Programlama 1', 'TLAB', '{"Çarşamba": 4}'),
(2, 'BP105', 'Bilgisayar Donanımı', 'UYGLAB', '{"Pazartesi": 2}'),
(2, 'BP107', 'Grafik ve Animasyon', 'UYGLAB', '{"Perşembe": 3}');

INSERT INTO kayitlar (kullanici_id, ders, tarih, saat, durum, aciklama) VALUES
(1, 'Veri Tabanı I',             '2026-10-07', 4, 'Devamsız', 'Otobüsü kaçırdım'),
(1, 'Oyun Programlama',          '2026-10-07', 3, 'Devamsız', ''),
(1, 'Nesne Tabanlı Programlama', '2026-10-09', 4, 'Raporlu',  'Hastane raporu var'),
(1, 'Mesleki Çözümleme I',       '2026-10-12', 4, 'Devamsız', 'Sabah dersine yetişemedim'),
(1, 'Veri Tabanı I',             '2026-10-21', 4, 'Devamsız', ''),
(2, 'Matematik',                 '2026-10-16', 4, 'Devamsız', 'Deneme hesabının kaydı'),
(2, 'Bilgisayar Donanımı',       '2026-10-20', 2, 'Raporlu',  '');

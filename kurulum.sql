-- DevamTakip - Veritabanı kurulum dosyası
-- phpMyAdmin > İçe aktar (Import) ile bu dosyayı seçip çalıştırın.

CREATE DATABASE IF NOT EXISTS devamtakip
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_turkish_ci;

USE devamtakip;

DROP TABLE IF EXISTS kayitlar;

-- Devamsızlık kayıtları
CREATE TABLE kayitlar (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ders        VARCHAR(100) NOT NULL,                      -- Ders adı
    tarih       DATE NOT NULL,                              -- Devamsızlık tarihi
    saat        TINYINT UNSIGNED NOT NULL,                  -- Kaç saat (1-8)
    durum       ENUM('Devamsız', 'Raporlu') NOT NULL DEFAULT 'Devamsız',
    aciklama    VARCHAR(150) NOT NULL DEFAULT '',           -- İsteğe bağlı not
    olusturma   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY tek_kayit (ders, tarih),                     -- Aynı derse aynı gün ikinci kayıt girilemez
    CONSTRAINT saat_araligi CHECK (saat BETWEEN 1 AND 8)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Örnek kayıtlar
INSERT INTO kayitlar (ders, tarih, saat, durum, aciklama) VALUES
('Veri Tabanı I',             '2026-10-07', 4, 'Devamsız', 'Otobüsü kaçırdım'),
('Oyun Programlama',          '2026-10-07', 3, 'Devamsız', ''),
('Nesne Tabanlı Programlama', '2026-10-09', 4, 'Raporlu',  'Hastane raporu var'),
('Mesleki Çözümleme I',       '2026-10-12', 4, 'Devamsız', 'Sabah dersine yetişemedim'),
('Veri Tabanı I',             '2026-10-21', 4, 'Devamsız', '');

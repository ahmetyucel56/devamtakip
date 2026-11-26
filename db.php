<?php
/*
 * DevamTakip - db.php
 * PDO ile MySQL veritabanına bağlanır.
 * Bağlantı bir kez açılır, aynı sayfa içinde tekrar istenirse aynı bağlantı döner.
 */

require_once __DIR__ . '/config.php';

/**
 * Veritabanı bağlantısını (PDO nesnesi) döndürür.
 *
 * - ERRMODE_EXCEPTION: SQL hatasında PDOException fırlatılır, hata sessizce geçmez.
 * - FETCH_ASSOC: Satırlar ['ders' => ..., 'tarih' => ...] şeklinde dizi olarak gelir.
 * - EMULATE_PREPARES false: Hazırlanmış ifadeleri (prepared statement) MySQL'in kendisi çalıştırır.
 */
function baglanti(): PDO
{
    static $pdo = null;   // static: fonksiyon tekrar çağrıldığında değeri korunur

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Kullanıcıya teknik ayrıntı (parola, sunucu adı) gösterilmez
            http_response_code(500);
            exit('Veritabanına bağlanılamadı. XAMPP\'te MySQL\'in çalıştığını ve kurulum.sql dosyasının içe aktarıldığını kontrol edin.');
        }
    }

    return $pdo;
}

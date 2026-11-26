<?php
/*
 * DevamTakip - oturum.php
 * Oturum (session) ayarları, giriş kontrolü ve form güvenliği (CSRF).
 * Her sayfanın en başında require edilir; session_start() burada çağrılır.
 */

// Oturum çerezi: JavaScript okuyamasın (httponly), başka sitelerden gelen isteklerle gönderilmesin (SameSite)
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();


/**
 * Giriş yapmış kullanıcının id'si. Giriş yoksa 0 döner.
 */
function aktifKullaniciId(): int
{
    return (int)($_SESSION['kullanici_id'] ?? 0);
}


/**
 * Sayfayı sadece giriş yapmış kullanıcılara açar.
 * Giriş yoksa giriş sayfasına yönlendirir ve sayfanın geri kalanı çalışmaz.
 */
function girisGerekli(): int
{
    $id = aktifKullaniciId();
    if ($id === 0) {
        header('Location: giris.php');
        exit;
    }
    return $id;
}


/**
 * Başarılı girişten sonra oturumu başlatır.
 * session_regenerate_id(true): eski oturum kimliği geçersiz olur (oturum sabitleme saldırısına karşı).
 */
function oturumAc(array $kullanici): void
{
    session_regenerate_id(true);
    $_SESSION['kullanici_id'] = (int)$kullanici['id'];
    $_SESSION['ad_soyad']     = $kullanici['ad_soyad'];
}


/**
 * Oturumu tamamen kapatır: oturum verisi, çerez ve sunucudaki oturum dosyası silinir.
 */
function oturumKapat(): void
{
    $_SESSION = [];
    $cerez = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $cerez['path'], $cerez['domain'], $cerez['secure'], $cerez['httponly']);
    session_destroy();
}


/**
 * CSRF jetonu: her oturuma özel rastgele bir değer.
 * POST formlarına gizli alan olarak eklenir. Başka bir siteden gönderilen sahte form bu değeri bilemez.
 */
function csrfJeton(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}


/**
 * Formlara eklenecek gizli alanın HTML'i.
 */
function csrfAlani(): string
{
    return '<input type="hidden" name="csrf" value="' . csrfJeton() . '">';
}


/**
 * POST isteğindeki jeton oturumdakiyle aynı değilse işlemi durdurur.
 * hash_equals: iki metni sabit sürede karşılaştırır.
 */
function csrfKontrol(): void
{
    $gelen = $_POST['csrf'] ?? '';
    if (!is_string($gelen) || !hash_equals(csrfJeton(), $gelen)) {
        http_response_code(400);
        exit('Form süresi dolmuş ya da geçersiz. Sayfayı yenileyip tekrar deneyin.');
    }
}

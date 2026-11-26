<?php
/*
 * DevamTakip - kayit_form.php
 * Ekleme (ekle.php) ve düzenleme (duzenle.php) sayfalarının ortak formu.
 * Kullanmadan önce şu değişkenler tanımlı olmalı:
 *   $formAction, $butonYazisi, $veri (ders, tarih, saat, durum, aciklama),
 *   $hatalar, $dersSecenekleri, isteğe bağlı $kayitId
 */
?>
<!-- novalidate: tarayıcı kontrolünü kapatır, böylece doğrulamayı PHP yapar -->
<form method="post" action="<?= guvenli($formAction) ?>" class="panel form" novalidate>
    <?= csrfAlani() ?>
    <?php if (isset($kayitId)): ?>
        <input type="hidden" name="id" value="<?= (int)$kayitId ?>">
    <?php endif; ?>

    <div class="alan <?= isset($hatalar['ders']) ? 'hatali' : '' ?>">
        <label for="ders">Ders</label>
        <select id="ders" name="ders">
            <option value="">Ders seçin</option>
            <?php foreach ($dersSecenekleri as $secenek): ?>
                <option value="<?= guvenli($secenek) ?>" <?= $secenek === $veri['ders'] ? 'selected' : '' ?>><?= guvenli($secenek) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($hatalar['ders'])): ?>
            <span class="hata-yazi"><?= guvenli($hatalar['ders']) ?></span>
        <?php endif; ?>
    </div>

    <div class="alan-grup">
        <div class="alan <?= isset($hatalar['tarih']) ? 'hatali' : '' ?>">
            <label for="tarih">Tarih</label>
            <input type="date" id="tarih" name="tarih" value="<?= guvenli($veri['tarih']) ?>">
            <?php if (isset($hatalar['tarih'])): ?>
                <span class="hata-yazi"><?= guvenli($hatalar['tarih']) ?></span>
            <?php endif; ?>
        </div>

        <div class="alan <?= isset($hatalar['saat']) ? 'hatali' : '' ?>">
            <label for="saat">Saat</label>
            <input type="number" id="saat" name="saat" min="1" max="8" value="<?= guvenli((string)$veri['saat']) ?>" placeholder="4">
            <?php if (isset($hatalar['saat'])): ?>
                <span class="hata-yazi"><?= guvenli($hatalar['saat']) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <fieldset class="alan <?= isset($hatalar['durum']) ? 'hatali' : '' ?>">
        <legend>Durum</legend>
        <div class="radyolar">
            <?php foreach (DURUMLAR as $secenek): ?>
                <label class="radyo">
                    <input type="radio" name="durum" value="<?= guvenli($secenek) ?>" <?= $secenek === $veri['durum'] ? 'checked' : '' ?>>
                    <?= guvenli($secenek) ?>
                </label>
            <?php endforeach; ?>
        </div>
        <?php if (isset($hatalar['durum'])): ?>
            <span class="hata-yazi"><?= guvenli($hatalar['durum']) ?></span>
        <?php endif; ?>
    </fieldset>

    <div class="alan <?= isset($hatalar['aciklama']) ? 'hatali' : '' ?>">
        <label for="aciklama">Not <span class="soluk">(isteğe bağlı)</span></label>
        <textarea id="aciklama" name="aciklama" rows="3" placeholder="Hastaydım, rapor aldım"><?= guvenli($veri['aciklama']) ?></textarea>
        <?php if (isset($hatalar['aciklama'])): ?>
            <span class="hata-yazi"><?= guvenli($hatalar['aciklama']) ?></span>
        <?php endif; ?>
    </div>

    <div class="butonlar">
        <button type="submit" class="buton"><?= guvenli($butonYazisi) ?></button>
        <a href="index.php" class="buton ikincil">Vazgeç</a>
    </div>
</form>

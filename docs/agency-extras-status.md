# Acente ekstraları — 3 Eylül 2026

## Kaynak incelemesi

Kullanıcının açtığı oturum üzerinden https://acente.kirpii.com/Agency listesinin
dört sayfasındaki 35 acentenin her biri açıldı; EXTRAS sekmesindeki görünür
alanlar okundu. Kaynak sitede hiçbir kayıt kaydedilmedi/değiştirilmedi.
İnceleme dökümü: `imports/kirpii-agency-extras-2026-09-03.json`.

Yalnızca AQUAMICE'de iki kayıt bulundu:

| Açıklama | İlk tarih | Son tarih | Alış | Satış |
| --- | --- | --- | --- | --- |
| GOLF BAG | 2018-12-01 | 2019-12-31 | 15 | 15 |
| GOLD | 2018-12-01 | 2019-01-01 | 10 | 10 |

Her iki kayıt: para birimi EU, fiyat tipi PP, zorunlu=false, yaş tablosu
01211, bebek/çocuk alış ve satış tutarları 0. Diğer 34 acentenin tablosu boş;
bu acentelere örnek veya varsayılan fiyat çoğaltılmadı.

## Uygulama

- Acenteler → acente kartı → EKSTRALAR gerçek ayrı sekme oldu.
- Tarihler, açıklama (Kurulum/Ekstra Satışlar alt kalemlerinden), alış/satış,
  para birimi, PP/PROOM/FIX, zorunluluk, yaş tablosu ve dört bebek/çocuk fiyatı.
- Vox Windows mavi tablo başlığı, standart girişler, kırmızı dairesel silme
  simgesi, yeşil Kaydet ve dar pencerelerde yatay kaydırma.
- Yerel MySQL `setup_record_sets.kind=agency-extras`: acente anahtarıyla ayrı
  kayıtlar; sürüm kontrolü, önceki sürüm yedeği, tarih ve ondalık doğrulaması.
- Taslak sekmeler arasında korunur; listeye dönüş/yenileme sırasında
  kaydedilmemiş değişiklik uyarısı vardır. Gizli sekmenin alanları formu engellemez.
- Mevcut acente ana bilgilerinin eski bellek içi saklama yapısı değiştirilmedi.
  Bu çalışma ekstra fiyatlarının kalıcı kaydını kapsar; yeni acente kartlarının
  tamamının kalıcı hale getirilmesi ayrı iştir.

## Aktarım ve test

`node scripts/import-agency-extras.mjs --apply` ile yerel MySQL'e iki kayıt
eklendi. İkinci çalıştırma 0 ekleme/2 mevcut sonucu verdi. Komut yalnızca sabit
127.0.0.1 adresine yazar; mevcut farklı fiyatları sessizce değiştirmez.

Tarayıcıda AQUAMICE kayıtları ve tüm seçimler görüldü, Vox Windows görünümü
ekran görüntüsüyle kontrol edildi. GOLF BAG alış fiyatı aynı tutarın
`15,0000` gösterimiyle kaydedilip `15.0000` olarak MySQL'den doğrulandı; ardından
kaynak gösterimi `15` geri kaydedildi. Son denetim kaynakla aynı iki kaydı
doğruladı. İş tutarları değiştirilmedi; bu test iki sürüm/yedek oluşturdu.
ATLANTIC GOLF kartında 0 kayıt ve boş durum mesajı; ardından yeniden açılan
AQUAMICE kartında 2 kayıt tarayıcıda ayrıca doğrulandı.

- JavaScript: 11 test geçti (ekstralar + API + MySQL kayıt yardımcıları).
- PHP: 24 test / 269 doğrulama geçti (SetupRecords, GolfLogin, SetupUsers).
- `npm run build` ve `git diff --check` başarılı.

## Yayın sınırı

Bu değişiklik yerel çalışma alanında ve yerel MySQL'dedir. Canlı sunucuya yeni
bir dağıtım yapılmadı; 2 Eylül canlı paketi ve yedekleri değiştirilmedi.
# Production update — 2026-09-03

Published to `https://krpsoft.com.tr/golf/desktop`. AQUAMICE's two extras were initialized
in the live MySQL store and verified after reload. The previous nine live record sets
remain byte-for-byte unchanged. See `live-release-status.md` for backups and verification.
Earlier local-only notes below describe the implementation before deployment.

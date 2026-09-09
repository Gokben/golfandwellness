# Ortak Vox uyarı standardı — 3 Eylül 2026

Durum: 3 Eylül 2026'da canlıya yüklendi. https://krpsoft.com.tr/golf/desktop

## Canlı yayın doğrulaması

- `storage/releases/20260903-warnings/golf-warnings-20260903.zip` (182.056 bayt), yalnızca 21 arayüz kaynağı/giriş görünümü ve 4 public build dosyası içerir. DB, kullanıcılar, auth ayarları ve oturumlar değiştirilmedi.
- Sunucuda özel yedek `golf-private-before-warnings-20260903.zip` (35,84 MB), public yedek `golf-public-before-warnings-20260903.zip` (646,64 KB); ikisi de `/home/krpsoftc` altında, public dışında.
- Canlı JS `app-DWnacfLm.js`; iki CSS ve manifest dahil tüm 4 public dosyanın SHA-256 değeri yerel yayın manifestiyle birebir eşleşti (HTTP 200).
- Canlı giriş sayfası HTTP 200 ve yeni JS referansı; oturumsuz golf-courses API erişimi 403. Mevcut admin oturumu korundu.
- Canlı Carya silme onayında native top layer, `shakeX`, 1 saniye ve Vazgeç odağı doğrulandı. Vazgeç ile kapatıldı; silme yapılmadı.
- Bu yayının canlı test başlangıcında listede 15 saha vardı (YAZILIM DENEME GOLF artık yoktu). İptal ve MySQL yenilemesi sonrasında da 15 saha ve Carya korundu; önceki yerel 16 kayıt canlıya aktarılmadı.
- Geri alma gerekirse yalnızca değişen kaynakları ve public build manifestini bu dosya yedeklerinden geri yükleyin; env, oturumlar ve iş verisini geri sarmayın. Eski hashli assetler silinmedi.

## Davranış

- `voxConfirm` ve `voxAlert` ortak, sıralı pencere kuyruğunu kullanır. Yerleşik `window.confirm` / `window.alert` değiştirilmez; çağrılar açıkça asenkron API'ye taşındı.
- `VoxDialogHost` yerel ve Laravel giriş/masaüstü başlangıçlarına bağlandı. Native dialog top layer, karartma, güvenli varsayılan Vazgeç, Escape, odak sınırı ve geri dönüş içerir.
- Vox Windows mavi başlık, standart düğmeler ve referans Vuexy örneğindeki `animate__shakeX` (1 saniye, ±10 px) uygulanır. Azaltılmış hareket tercihinde animasyon oynatılmaz.
- Golf listesi içindeki eski silme onayı kaldırıldı. Acente, kontrat, oyun, otel, pansiyon, kullanıcı ve Kurulum silme/onay akışları ortak pencereye taşındı.
- `useVoxMessages` kayıt/bağlantı/validasyon ve bilgi mesajlarını öne getirir. Satır içi hata bağlamı ve yeniden deneme düğmeleri korunur. Standart HTML form validasyonu ve Laravel giriş hatası da ortak pencereye bağlandı.
- Acente ekstralarından ayrılma çağrıları artık asenkron onayı bekler; iptal yanıtı veri değişikliği başlatmaz.
- Tarayıcının sekme kapatma/yenileme sırasında gösterdiği `beforeunload` güvenlik uyarısı tarayıcıya aittir; özelleştirilemez ve veri kaybını önlemek için korunur. Masaüstündeki bildirim merkezi kayıtları birer modal uyarı değildir, değiştirilmedi.

## Doğrulama

- 42 Node testi geçti (5 yeni ortak uyarı testi dahil).
- 26 PHP testi / 303 assertion geçti (giriş, kullanıcılar, kayıt setleri).
- Laravel üretim derlemesi ve statik önizleme derlemesi geçti.
- Tarayıcıda Golf silme onayının `:modal`, `shakeX`, `1s` değerleri ve varsayılan Vazgeç odağı doğrulandı.
- Tab döngüsü, Escape/Vazgeç ve açan düğmeye odak dönüşü kontrol edildi. YAZILIM DENEME GOLF silinmedi, 16 saha korundu.
- Boş golf formu doğrulaması ortak uyarı açtı. Aynı hata tekrar gösterilecek şekilde validasyon sıfırlaması eklendi.
- Statik giriş formunun yerleşik HTML doğrulama hatası da ortak modalda doğrulandı; giriş gönderilmedi.

## Geliştirme

Yeni işlemlerde `if (!await voxConfirm(metin)) return` kullanın. Promise'i eşzamanlı boolean gibi kullanmayın. Yeni hata/bilgi ref'lerini `useVoxMessages` ile bağlayın; HTML mesajı çalıştırmayın. Uygulama uyarılarını inline-only eklemeyin.

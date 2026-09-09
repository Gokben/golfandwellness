# Yerel Kurulum MySQL verileri

- Sunucu: MySQL 8.4, `127.0.0.1:3307`, veritabanı: `golf_setup`.
- Bağlantı: Laravel `setup_mysql`; parola Git dışında tutulan `.env` dosyasındadır.
- Veri dizini: `storage/mysql-setup` (Git dışında). Bu dizin canlı veridir; silmeyin.
- Uyruk, Market, İptal Nedenleri, Ekstra Satışlar/alt kalemleri, Otel & Golf Ekstraları, Yaş Tabloları ve Parity burada tutulur.
- Kullanıcı kartları ayrı `setup_users` tablosundadır. Görsellerdeki dört kartın şifresi bilinmediğinden başlangıç şifreleri yoktur; tahmini/ortak şifre atanmaz. Girilen şifreler Laravel Hash ile özetlenir ve API yanıtlarına dahil edilmez. Düzenlemede boş şifre mevcut özeti korur.
- `setup_users` henüz giriş doğrulamasına bağlı değildir; mevcut giriş ekranı yalnızca masaüstüne yönlendirir. Aktif seçimi bir kart alanıdır, oturum yetkisi sağlamaz. Canlı kullanım için giriş ve yönetici yetkilendirmesi ayrıca uygulanmalıdır. Bu API de yalnızca yerel ortam ve loopback erişimine açıktır.
- Kullanıcı silme `deleted_at` ile geri alınabilir; kayıt fiziksel olarak silinmez. Kullanıcı adı tekrar kullanımına izin verilmez. Düzenleme/silme işlemleri satır sürümü ile eşzamanlı değişiklikleri korur.
- Kullanıcı kartının `role` alanı Admin, Rezervasyon, Muhasebe veya Operasyon olabilir. Mevcut kullanıcılara otomatik rol atanmaz; seçilmemiş rol `null` olarak kalır. Bu alan şimdilik yalnızca bir tanımdır, hiçbir ekran/işlem yetkisi veya giriş davranışı değiştirmez. Yetki kuralları daha sonra tanımlanacaktır.
- Her grup `setup_record_sets` içinde JSON kayıt kümesi ve sürüm numarasıyla tutulur. Güncelleme öncesindeki sürüm `setup_record_backups` tablosuna aynı transaction içinde yazılır.
- İki pencere aynı sürümü güncellerse ikincisi 409 alır; yeni kayıtlar ezilmez.
- Golf sahası Kontratlar sekmesi `setup_record_sets` içindeki `golf-contracts` grubunu kullanır. Satırlar `courseKey` ile sahaya bağlıdır; yalnızca Carya için görseldeki dört başlangıç kontratı vardır. Ekle/kopyala/sil işlemleri Kaydet'e kadar taslaktır; iptal veritabanını değiştirmez. Kontratlar MySQL'de kalıcıdır; mevcut golf sahası temel kart listesinin bellek içi çalışma biçimi değiştirilmemiştir.
- Golf Sahaları → Oyunlar listesindeki bağlantı saha kartını değil, sahanın oyunlarını açar. `golf-games` grubu oyun adı, kod ve round değerini `courseKey` ile saklar. 02.09.2026 tarihinde `https://acente.kirpii.com/Games` sayfasındaki 17 sahanın 69 oyunu okundu; kaynak kayıtlar `docs/imports/kirpii-games-2026-09-02.json` dosyasındadır. Kaynaktaki deneme kayıtları, noktalama ve round değerleri değiştirilmeden korunur. Ekleme/düzenleme Kaydet ile, silme onaydan sonra kalıcıdır; önceki sürümler yedeklenir. Oyunlar kontratlardan ayrı kayıtlardır; oyun düzenlemek mevcut kontratları değiştirmez.
- Kaynak katalog için eklemeli aktarım: `node scripts/import-course-games.mjs` yalnızca planı gösterir; `--apply` eksik kayıtları yerel MySQL'e ekler. Aynı saha/kod zaten varsa kimliği korunur; ad veya round farklıysa aktarım durur. Önceden var olan başka kayıtlar silinmez. Aynı katalog tekrar çalıştırıldığında kayıt çoğalmaz. Kaynak siteye hiçbir yazma yapılmaz.
- İlk açılışta `vox-setup-<grup>-v1` tarayıcı kayıtları varsa aktarılır; yoksa mevcut başlangıç listesi kullanılır. Boş bir liste de korunur. Tarayıcı anahtarı silinmez veya yeniden yazılmaz.
- MySQL zaten dolu ve tarayıcı yedeği farklıysa otomatik üzerine yazılmaz; uyarı gösterilir.
- Bu API yerel ortam/loopback ile sınırlıdır. Canlıya alınmadan önce oturum/yetkilendirme eklenmelidir.
- Döviz kayıtları `exchange_rates` tablosuna kopyalandı; API ve TCMB yenileme işlemi artık aynı MySQL bağlantısını kullanır. SQLite kaynağı korunur. Tekrar doğrulama: `php artisan setup:import-exchange-rates`.
- Uygulamanın varsayılan SQLite bağlantısı değiştirilmedi. 3306'daki diğer veritabanları kullanılmaz.

## Başlatma

PowerShell: `./scripts/start-local.ps1`

## Yedekleme

`setup_record_backups` yanlış düzenlemeler için sürüm geçmişidir; disk arızasına karşı ayrıca düzenli MySQL dump yedeği alınmalıdır. Çalışan MySQL'in veri dosyalarını doğrudan kopyalamayın. Sunucu admin parolası `.env` içindeki `SETUP_DB_ADMIN_PASSWORD` alanındadır; parola içeren dosyaları paylaşmayın.

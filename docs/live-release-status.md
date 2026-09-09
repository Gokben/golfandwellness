# Canlı yayın hazırlığı — 2 Eylül 2026

## 3 Eylül 2026 — ortak uyarı güncellemesi tamamlandı

Vox Windows ortak modal/Shake uyarıları canlıya alındı. Paket:
`storage/releases/20260903-warnings/golf-warnings-20260903.zip` (182.056 bayt),
SHA-256 `1c0c4c19c5b6de7d850be1318ad382c10a937e4deebb58cb425dbf1fe511561a`.
Yalnızca UI kaynakları, login Blade ve public build değişti. Veritabanına yazma yapılmadı.
Sunucuda güncel özel/public yedekler `golf-private-before-warnings-20260903.zip`
ve `golf-public-before-warnings-20260903.zip` olarak özel ana dizinde tutuluyor.
Canlı JS/CSS/manifest SHA-256 değerleri pakete eşleşti; Carya silme uyarısı
Shake + en önde modal olarak doğrulandı ve Vazgeç ile kapatıldı. Canlıda testten
önce ve yenilemeden sonra 15 saha vardı; Carya korundu. Giriş 200, oturumsuz API 403.
Ayrıntı ve geri alma sınırları: `docs/vox-warning-standard.md`.

## 3 Eylül 2026 güncellemesi — tamamlandı

- `scripts/package-cpanel-update.php` ile 140.310 baytlık kod-only paket hazırlandı:
  `storage/releases/20260903/golf-update-20260903.zip`. Dosya listesi ve SHA-256
  aynı dizindeki manifestte. Yalnızca SetupRecordsController, değişen JS/Vue
  kaynakları ve derlenmiş public assetler/manifest içerir. Env, APP_KEY, kullanıcılar,
  parolalar, vendor, oturumlar ve auth yapılandırması değiştirilmedi.
- cPanel File Manager ile `/home/krpsoftc` altına yüklendi ve aynı dizine çıkarıldı.
  Mevcut özel uygulama `golf_release_20260902`, public hedef `public_html/golf`.
  Önceki hashli assetler silinmedi.
- Yayın öncesi dosya yedekleri: `/home/krpsoftc/golf-private-before-20260903.zip`
  (35,83 MB) ve `/home/krpsoftc/golf-public-before-20260903.zip` (560,67 KB).
  Yedekler public dizin dışında tutuldu.
- DB yedekleri: `krpsoftc_golf.bak_20260903_setup_record_sets` (9 küme) ve
  `bak_20260903_setup_record_backups`. CREATE TABLE LIKE + INSERT SELECT kullanıldı.
- Yeni `golf-courses` kümesi mevcut varsayılan 17 saha ile ilk kez oluşturuldu.
  Canlı UI'dan yalnızca Zeynep Golf silindi: sürüm 2, 16 saha. Önceki 17 kayıt
  `setup_record_backups` içinde golf-courses sürüm 1 olarak doğrulandı.
- AQUAMICE Ekstralar sekmesi açılarak yeni `agency-extras` kümesi başlatıldı.
  GOLF BAG 15/15 (01.12.2018–31.12.2019), GOLD 10/10 (01.12.2018–01.01.2019),
  EU/PP, yaş kodu 01211 ve sıfır bebek/çocuk fiyatları canlıda doğrulandı.
  Yenile düğmesiyle MySQL'den tekrar okununca iki kayıt korundu.
- Acente başlığı `Acenteler — AQUAMICE`; Skype alanı kaldırılmış durumda.
- Canlı tam sayfa yenileme sonrası golf listesi hâlâ 16 kayıt; Zeynep saha satırı yok.
  Carya'nın otel metni ve bağlı oyun/kontrat verileri korunuyor.
- SQL ile önceki 9 kümenin JSON içeriği ve sürümünün yedekle birebir aynı olduğu
  doğrulandı (`unchanged_original_sets=9`); golf-games=69/sürüm2,
  golf-contracts=4/sürüm2 değişmedi.
- Oturumsuz Node HTTPS kontrolü: `/golf/desktop` 302 login yönlendirmesi;
  `/golf/api/setup-records/golf-courses` ve `/agency-extras` 403. Mevcut admin2
  oturumu devam etti, yeni şifre girilmedi veya okunmadı.
- Son canlı görünüm: https://krpsoft.com.tr/golf/desktop, AQUAMICE ekstraları açık.

### Bu güncelleme için geri alma

Gerekirse yalnızca değişen özel kod dosyalarını özel yedekten, public build/manifesti
public yedekten geri yükleyin. Env ve oturum dosyalarını geri sarmayın. Verileri
körlemesine geri yüklemeyin: yayın sonrası yeni kullanıcı değişiklikleri olabilir.
Silinen Zeynep satırı gerekirse golf-courses sürüm1 yedeğinden mevcut listeye
kontrollü biçimde eklenebilir. Eski yedekleri silmedik.

## Yayın tamamlandı

Adres: https://krpsoft.com.tr/golf/

Kullanıcı admin2 ile canlı giriş yaptı. Oturum içinde kullanıcı listesi
`MySQL bağlı` olarak 4 kayıt gösterdi. Marketlerde mevcut Pound Zone kaydı
içeriği değiştirilmeden kaydedildi; `Kayıt kaydedildi` yanıtı alındı. Bu kontrol
yalnızca sürümü/zaman damgasını ve yedek kaydını artırır; iş verisi değişmedi.
31.08.2026 tarihi için GBP/EUR/USD kurları ve tarih sütunu doğrulandı.
Oyunlar ekranında 17 saha ve Carya altında doğru 3 oyun doğrulandı;
toplam 69 oyun ayrıca veritabanından sayıldı. Canlı UI Vox Windows stiliyle
görsel olarak kontrol edildi. Kimlik doğrulamasız erişim testleri aşağıdadır.

## Durum

Yeni sürüm https://krpsoft.com.tr/golf/ adresine yerleştirildi. Kullanıcı, ilk canlı yönetici hesabı olarak yalnızca
`admin2` hesabını onayladı ve şifresini kullanıcı kartında belirledi. Parola
içeriği görülmeden aktif Admin hesabında parola özeti bulunduğu doğrulandı.
Şifre sohbete, günlüklere veya bu dosyaya yazılmamalı.

## 2 Eylül hazır paket ve yedekler

- Sunucu krpsoft.com.tr için PHP 8.4 (ea-php84); MariaDB 10.5.29.
- `/home/krpsoftc/golf-before-20260902.zip` oluşturuldu, 4,47 MB; eski public
  Golf klasörünün sıkıştırma sonucu başarılı. Özel ana dizinde, public değil.
- `krpsoftc_golf` içindeki mevcut 10 tablo `bak_20260902_` önekiyle `CREATE TABLE
  LIKE` + `INSERT SELECT` ile yedeklendi. Eski tablolar değiştirilmedi. Dolu
  tablolar: golf_exchange_rates=6, migrations=3; diğer eski tablolar boş.
- Canlıda henüz setup_users, setup_record_sets, setup_record_backups veya
  exchange_rates yok. Paket yalnızca bu yeni tabloları yaratır; varlarsa
  overwrite yapmaz, hata verir. Eski kur geçmişi yeni exchange_rates'e
  kopyalanır; çakışan tarihlerde canlı kayıt önceliklidir.
- Yerel özel çıktı `storage/releases/20260902/`: golf-release-20260902.zip
  (37.543.319 bayt), golf-setup-import.sql ve manifest.json. Manifest hash'i
  doğrulandı. SQL gizli parola özeti içerir; hiçbirini public içine koyma.
- ZIP iki dizin içerir: golf_release_20260902 (özel app),
  golf_public_20260902 (yalnızca public dosyalar). Ana dizine çıkarılacak.
  `.env` production/debug=false, APP_KEY rastgele, session/cache=file,
  secure/HttpOnly cookie path=/golf, mevcut `/home/krpsoftc/golf-db.php` kullanır.
- Paket kaynak kodu scripts/package-cpanel.php. PHP zip extension gerekiyor:
  php -d extension=zip scripts/package-cpanel.php. Var olan tamamlanmış paketi
  yeniden yazmayı reddeder; şu paket henüz yüklenmedi, değiştirme.
- Kullanıcı belirli veri aktarımını ve /golf yayın değişimini **evet** diyerek
  onayladı. ZIP ve SQL aktarıldı. Büyük ZIP %100 sonrasında sunucuda birkaç
  dakika işlendi; tamamlandıktan sonra dosya listesinde doğrulandı ve açıldı.
- SQL içe aktarma başarılı: 28 sorgu. Doğrulanan toplamlar: kayıt kümesi=9,
  kayıt yedeği=5, kullanıcı=4, kur=6. golf-games=69, golf-contracts=4.
  admin2 aktif Admin, parola özeti var. ozgul kartının Admin rolü yerelde
  kullanıcı tarafından değiştirilmiş; parola boş ve username allow-list
  nedeniyle canlı giriş yetkisi yok. Bu rolü değiştirmedik.
- Eski canlı klasör `/home/krpsoftc/golf_previous_20260902/golf` yoluna taşındı.
  Yeni public klasör önce ana dizinde `golf` adını aldı, sonra
  `/home/krpsoftc/public_html/golf` yoluna taşındı. Eski golf_app değişmedi.
- Büyük yükleme sürerken hazırlanan 760.229 baytlık code-only ZIP de ana
  dizine yüklendi ama KULLANILMADI. Asıl tam paket vendor dahil açıldı.
- Canlı login ekranı açılıyor. Yetkisiz `/golf/desktop.html` girişe gidiyor;
  `/golf/api/setup-users`, `/golf/api/setup-records/golf-games`,
  `/golf/api/exchange-rates` 403; `/golf/exchange-rates.php` Apache 403.
- Kullanıcı kendi admin2 şifresiyle giriş yaptı; oturumlu MySQL ve ekran
  doğrulaması tamamlandı. Şifre okunmadı ve kaydedilmedi.
- PowerShell HTTPS kontrolü yerel TLS bağlantı hatası verdi; doğrulamayı
  güvenlik kontrollerini kapatmadan mevcut tarayıcı üzerinden yaptık.

## Uygulanan geçiş sırası / tekrar yayın için referans

1. cPanel ana dizin `/home/krpsoftc` yükleme sekmesi açık. ZIP'i buraya yükle,
   File Manager'dan ana dizine çıkar. Eski golf_app değişmeyecek.
2. phpMyAdmin'de krpsoftc_golf İçe aktar sekmesi hazır. SQL'i seçip çalıştır.
   Çıktıda parola/hash içeren sorguları görüntüleme; yalnızca sonuç ve sayıları oku.
3. Yeni tabloların sayıları: kayıt kümesi=9, kayıt yedeği=5, kullanıcı=4,
   yerel kur=3 (canlı geçmiş kopyası sonrası 6 veya daha fazla olabilir).
4. Eski `/home/krpsoftc/public_html/golf` dizinini özel ana dizindeki farklı
   bir yedek konuma taşı; yeni `golf_public_20260902` dizinini public_html/golf
   olarak yerleştir. Eski ZIP yedeği ayrıca korunuyor. Hata olursa eski dizini
   geri getir; yeni DB tablolarını silmeye gerek yok.
5. HTTPS login, yetkisiz API/desktop engeli, assetler ve MySQL bağlantısını
   doğrula. Kullanıcı canlı girişini kendi şifresiyle yapmalı; ardından
   oturumlu ekranları ve kayıtları kontrol et. Gerekirse kullanıcıya devret.

Gerçek Laravel oturum açma/kapatma, CSRF, giriş denemesi sınırlaması ve
geçici `admin2` erişim listesi eklendi. Diğer roller yetkilendirilmedi.
Yerel Vite önizlemesi yalnızca local/testing ortamındaki loopback istekleri
için oturumsuz çalışmaya devam eder. Canlı API oturum gerektirir.

## Yayından önce zorunlu kontroller

- Mevcut hedef `https://krpsoft.com.tr/golf/`; cPanel hesabı `krpsoftc`.
- Mevcut açık kök `/home/krpsoftc/public_html/golf` statik HTML dosyaları içeriyor.
  **`dist-pages` güvenli canlı yayın paketi değildir.** Statik giriş şifre doğrulamaz.
- Laravel özel dizini `/home/krpsoftc/golf_app` ve özel bağlantı dosyası
  `/home/krpsoftc/golf-db.php` mevcut. Şifreleri görüntülemeden sunucu tarafında
  mevcut yapılandırmayı kullan. Canlı tablolar incelendi ve yukarıdaki sayılar doğrulandı.
- Canlı dosya ve MySQL yedeği alınmalı. Yerel kayıtlar canlı verilerin üzerine
  körlemesine yazılmamalı. `setup_users`, kayıt kümeleri ve kur geçmişi korunmalı.
- PHP >=8.2, MySQL bağlantısı, vendor bağımlılıkları, uygulama anahtarı,
  `APP_ENV=production`, `APP_DEBUG=false`, güvenli/HttpOnly oturum çerezi,
  dosya tabanlı oturum ve önbellek yapılandırması doğrulanmalı.
- Laravel build kullanılmalı; API tabanı Blade içinden `/golf/api` olarak gelmeli.
  Public giriş dosyası özel uygulama dizinine yönlendirilmeli.
- Apache DirectoryIndex/rewrite Laravel `index.php`'ye gitmeli. Eski
  `desktop.html`, `login.html`, `index.html` dosyaları auth'u atlayamamalı.
  Eski bağımsız `exchange-rates.php`, açık zip yedekleri ve `error_log`
  public erişimden çıkarılmalı/engellenmeli; geri alınabilir özel yedek saklanmalı.
- Mevcut private app izinleri arasında 0777/0666 görülüyor; yeni yayında özel
  kod ve yapılandırma için uygun en dar izinler kullanılmalı.
- Giriş yapmadan masaüstü ve tüm API'ler reddedilmeli; admin2 girişi, kurulum
  kayıtları ve çıkış gerçek HTTPS adresinde test edilmeli.

## Doğrulama

- PHP: GolfLoginTest, SetupUsersTest, SetupRecordsTest, ExampleTest — 26 test,
  248 assertion geçti.
- API/composable JavaScript — 11 test geçti; oyun/kontrat/yaş testleri — 14 geçti.
- Laravel build ve statik yerel önizleme build başarılı.
- Canlı aktarım, yükleme, gerçek admin2 girişi, okuma ve içerik değiştirmeden
  kaydetme testi tamamlandı. Yedekler silinmedi.

# GitHub → cPanel yayın

- Depo: `Gokben/golfandwellness` (public, kullanıcı isteğiyle).
- `main` gönderimleri `Build cPanel release` iş akışını tetikler. Node testleri, Vite build ve PHP sözdizimi kontrolü geçtikten sonra `cpanel-deploy` dalına derlenmiş dosyalar gönderilir.
- cPanel deposu: `/home/krpsoftc/repositories/golfandwellness`.
- Sunucuda her saat başında çalışan cron, yayın dalını fetch/checkout/fast-forward eder; başarısız Git işlemi dağıtımı durdurur. İşlem kilidi eşzamanlı çalışmaları engeller.
- `scripts/cpanel-deploy.php` yalnızca mevcut özel uygulama dizinindeki destek sınıfları, kayıt denetleyicisi ve ön yüz kaynaklarını; açık dizindeki derlenmiş assetleri günceller. Veritabanı, kullanıcılar, `.env`, vendor, giriş noktası ve oturum ayarlarını değiştirmez.
- Aynı kaynak commit ikinci kez dağıtılmaz. Assetler önce, manifest en son atomik dosya değişimiyle yazılır; eski hashli assetler korunur.
- Canlı sürüm: `/golf/build/release.json`. Özel yayın kaydı: `golf_release_20260902/storage/logs/github-deploy.log`.
- Otomasyonu durdurmak için yalnızca golf GitHub yayın cron kaydı kaldırılır; diğer cron işleri korunur.
- Bu akış mevcut kurulum için kod güncellemesidir. Yeni bağımlılıklar, veritabanı değişiklikleri, route/auth veya sunucu yapılandırma değişiklikleri ayrıca dağıtım kapsamına alınmalıdır.

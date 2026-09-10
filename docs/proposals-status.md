# Teklifler — 10 Eylül 2026

- Sol menü Teklifler altında üç alt başlık açılır; orta Teklifler kısayolu yalnızca üç teklif kartını gösterir.
- Orta teklif menüsünün çerçevesi dışına tıklanınca orta alan ana menüye döner.
- Golf, Otel ve Otel + Golf ayrı kayıt kümeleri kullanır. Birleşik teklif paket, ek oda ve ek golf satırlarını aynı teklif içinde saklar.
- Yeni teklif, düzenleme, silme onayı, durum, aktif/iptal/opsiyon filtresi, arama ve CSV dışa aktarma vardır.
- Otel, acente, saha, pansiyon, oda, pazar ve uyruk mevcut kartlardan seçilir. Oda alt tipi otele göre süzülür. Kontrat ve paket seçimleri otel/saha ve tarihle sınırlandırılır.
- Alış/satış tutarları ve ek hizmet bedelleri ayrı tutulur. Kişi, kişi-gece, oda-gece veya sabit satır toplamı seçilebilir. Eksik fiyat sıfır sayılmaz; farklı para birimleri birleştirilmez.
- Tarife bedeli alma seçilen fiyat satırını aktarır; karmaşık indirim, çocuk, ücretsiz kişi/paket koşulları ve kur dönüşümü otomatik hesaplanmaz. Bu sınır formda belirtilir. Teklif onayı rezervasyon oluşturmaz.
- Kaynak GolfProposals listesindeki beş teklif ve HotelGolfPrices listesindeki üç teklif aktarıldı. HotelPrices boştu. Dokuz golf detay satırı ve birleşik tekliflerde bir golf detay satırı görülüp korundu. Geçmiş tablolar salt okunur kaynak dökümü olarak gösterilir; yeni düzenlenebilir satırlar ayrı eklenir. Tarihler, sıfır kur ve boş toplamlar yeniden yorumlanmadı.
- Birleşik kaynak listede durum belirtilmediğinden bu üç kayda yerel başlangıç durumu OPTIONAL verildi; bu değer kaynakta doğrulanmış bir durum değildir.
- Başlangıç aktarımı kayıt kümelerini ilk kez oluşturdu; yedek oluşturulmadı. Mevcut dolu kümeler varsayılan kayıtlarla değiştirilmez.
- Doğrulama: ProposalsTest (45 doğrulama), iki Node hesaplama/kaynak testi, Vite derlemesi; tarayıcıda üç liste, kaynak detay, golf kontrat fiyatı ve otel gece toplamı kontrol edildi. Tarayıcı test taslakları kaydedilmedi.

Canlı dağıtım bekliyor. GitHub iş akışı yalnızca build:pages doğrulaması yapıyor; cPanel dağıtımı yapılandırılmamış.

## Son liste düzenlemeleri

- Altı iptal golf teklifi 17 fiyat satırıyla eklendi; toplam 11 golf teklifi ve 26 kaynak fiyat satırı bulunuyor.
- Aktif liste 5, iptal liste 6, opsiyon tarihi gelen liste 3 kayıt gösterir. Onaylı merve opsiyon listesinde de görünür; durumu kaynakta CONFIRMED olarak doğrulandı.
- Durum dört seçenekle satırdan kaydedilir. Satış Toplamı ve Yenile kaldırıldı; İşlem düğmeleri korundu. Golf detayında oluşturan başlık yanında gösterilir.
- Son doğrulama: 41 PHP testi (502 assertion), iki Node testi ve Vite derlemesi başarılı.

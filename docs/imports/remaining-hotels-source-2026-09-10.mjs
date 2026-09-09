// Transcribed from the authenticated source hotel forms on 10 September 2026.
// Tuple columns are declared beside each group. Empty source values remain empty.
export const informationColumns = ['id','name','code','type','roomType','location1','location2','address','phone','smsPhone','website'];
export const information = [
 [21,'Regnum Carya Golf Hotel & Spa','Regnum Carya','Resort,Spa','STD,VILLA,Suite','Belek','Kadriye','Kadriye Bölgesi Üçkum Tepesi 07500\n\n','02427103434','','www.regnumhotels.com'],
 [23,'Sueno Deluxe Hotel','Sueno Deluxe','City,Resort,Spa','Suite,VILLA,STD','Belek','Kadriye','Belek Mahallesi','02427103000','','www.sueno.com.tr'],
 [25,'Sueno Golf Belek','Sueno Golf','Resort,Golf','Suite,VILLA,STD','Belek','Kadriye','Sueno','','','www.sueno.com.tr'],
 [26,'Cornelia Diamond Hotel','Cornelia Diamond','Golf,Spa,Resort','STD,VILLA,Suite','Belek','Kadriye','İskele mevkii 07506','02427101600','','www.corneliaresort.com'],
 [27,'Cornelia De Luxe Hotel','Cornelia DeLuxe','Golf,Spa,Resort','STD,VILLA,Suite','Belek','Kadriye','İleribaşı Mevkii 07506','0242 710 15 00','','www.corneliaresort.com'],
 [28,'Gloria Serenity Resort','GSR','Resort,Spa,Golf','VILLA,Suite,STD','Belek','Kadriye','Belek Mahallesi','02427102300','','www.gloria.com.tr'],
 [29,'Gloria Verde Resort','GVR','Resort,Spa,Golf','STD,VILLA,Suite','Acısu','Kadriye','İleribaşı Mevkii','0242 7100500','','www.gloria.com.tr'],
 [30,'Kaya Palazzo Hotel','Kaya Palazzo','Golf,Resort','Suite,VILLA,STD','Belek','Kadriye','Çamlık Cad 07500','0242 710 15 00','','www.kayahotels.com'],
 [31,'Kaya Belek Hotel','Kaya Belek','Resort,Golf','VILLA,Suite,STD','Kadriye','Side','Çamlık Cad 07500','02427104000','','www.kayahotels.com'],
 [32,'Kempinski Hotel The Dome','Kempinski','Golf,Spa,Resort','STD,VILLA,Suite','Belek','Kadriye','Kadriye Mahallesi, Yeni Mahalle Uckumtepesi Caddesi No 20-2 Kadriye, 07500','(0242) 710 13 00','','www.kempinski.com'],
 [33,'Maxx Royal Belek Golf Resort','Maxx Royal','Resort,Spa,Golf','STD,VILLA,Suite','Belek','Kadriye','Belek Mahallesi, İskele Mevkii, 07500 Belek / Serik/Serik/Antalya','0242 7102700','','www.maxxroyal.com'],
 [34,'Sirene Golf Hotel','Sirene','Resort,Golf','STD,VILLA,Suite','Üç Kum Tepesi','Kadriye','Yeni Mah., Üçkum tepesi caddesi No:18, 07500 Kadriye, Serik/Serik/Antalya','0242 710 08 00','0242 710 08 00','www.sirene.com.tr'],
 [41,'Voyage Belek Golf & SPA','Voyage Belek','Resort,Spa,Golf','Suite,VILLA,STD','Belek','Kadriye','ff','0242 7102500','','www.voyagehotel.com'],
 [42,'Robinson Club Nobilis','Robinson Nobilis','Golf','STD,VILLA,Suite','Acısu','Kadriye','Belek Mahallesi, Acisu Mevkii, 07500 Serik/Antalya','(0242) 710 03 00','','www.robinson.com'],
 [53,'Titanic Deluxe Belek','Titanic','Resort,Golf','Suite,VILLA,STD','Acısu','Kadriye','Uckumtepesi Besgoz Caddesi 72/1 Kadriye/Belek Antalya','+90 242 710 44 44','','www.titanic.com.tr'],
 [57,'Lykia World Hotel','Lykia','Golf,Spa,Resort','STD,VILLA,Suite','Manavgat','Kadriye','Denizyaka Mah.Kamışlı Göl Küme Evleri No.1   P.K.07550 Manavgat/ Antalya,Turkey','90 242  7441915 ','','www.lykiagroup.com'],
];
export const commonInformation = {category:'5*',catalog:'NNN',status:true,country:'Türkiye',city:'Antalya',fax:'',email:''};
// hotel, source id, first date, last date, description, buy, sell, currency,
// price type, obligatory, age code, infant buy/sell, child buy/sell.
export const extras = [
 [21,20,'2019-01-01','2019-01-01','New Year Gala','90','90','EU','PP',true,'06712','0','0','45','45'],
 [23,18,'2019-01-01','2019-01-01','New Year Gala','50','50','GBP','PP',true,'01211','0','0','0','0'],
 [23,17,'2019-01-01','2019-01-01','New Year Gala','60','60','EU','PP',true,'01211','0','0','0','0'],
 [26,19,'2019-01-01','2019-01-01','New Year Gala','60','60','EU','PP',true,'06712','0','0','30','30'],
 [27,26,'2019-01-01','2019-05-01','Room Supplement','10','15','EU','PP',false,'01211','0','0','0','0'],
 [27,11,'2018-12-31','2019-01-01','New Year Gala','60','60','EU','PP',false,'0237','0','0','30','30'],
 [27,10,'2019-12-31','2019-01-01','New Year Gala','50','50','GBP','PP',false,'0237','0','0','25','25'],
 [28,14,'2019-01-01','2019-01-01','New Year Gala','110','110','EU','PP',true,'06712','0','0','0','0'],
 [28,13,'2019-01-01','2019-01-01','New Year Gala','96','96','GBP','PP',true,'06712','0','0','0','0'],
 [29,16,'2019-01-01','2019-01-01','New Year Gala','100','100','EU','PP',true,'06712','0','0','0','0'],
 [29,15,'2019-01-01','2019-01-01','New Year Gala','87','87','GBP','PP',true,'06712','0','0','0','0'],
 [33,23,'2019-01-01','2019-01-01','New Year Gala','122','122','GBP','PP',true,'01211','0','0','61','61'],
 [33,22,'2019-01-01','2019-01-01','New Year Gala','140','140','EU','PP',true,'01211','0','0','70','70'],
 [53,25,'2019-01-01','2019-01-01','New Year Gala','60','60','GBP','PP',true,'06711','0','0','30','30'],
 [53,24,'2019-01-01','2019-01-01','New Year Gala','65','65','EU','PP',true,'06711','0','0','32,5','32,5'],
 [57,4,'2017-01-01','2017-01-01','New Year Gala','50','245','EU','PP',false,'06712','0','20','0','30'],
];
// All package forms show Standard / Check In Base / ACTIVE. Tuple columns:
// hotel, package id, name, first, last, room name, contract type, nights,
// [bonus id, reduction id, golfer-free id], price rows [id, rounds, full course name, accommodation, price, currency].
export const packages = [
 [57,5,'4oyun COPY','2017-12-22','2017-12-30','Mainbuilding Parkview','MAIN','1',[22,22,22],[[19,'','Faldo Golf Course','DBL','620','GBP'],[18,'','Dunes Course','DBL','20','GBP'],[17,'','Gloria Verde Course','DBL','20','GBP'],[16,'','National Golf Course','DBL','20','GBP']]],
 [27,13,'GBP 7N + 1F','2018-12-22','2019-03-31','Standard Room Partial View','ACTION','7',[14,14,14],[[45,'1','Faldo Golf Course','ADD','595','GBP'],[44,'1','Faldo Golf Course','SNG','721','GBP'],[43,'1','Faldo Golf Course','DBL','595','GBP']]],
 [27,14,'GBP 7N + 2F+2Nob','2018-12-22','2019-03-31','Standard Room Partial View','ACTION','1',[13,13,13],[[67,'2','Nobilis Golf Course','ADD','800','GBP'],[66,'2','Nobilis Golf Course','SNG','950','GBP'],[65,'2','Nobilis Golf Course','DBL','800','GBP'],[48,'2','Faldo Golf Course','ADD','653','GBP'],[47,'2','Faldo Golf Course','SNG','779','GBP'],[46,'2','Faldo Golf Course','DBL','653','GBP']]],
 [27,15,'GBP 7N + 3F','2018-12-22','2019-03-31','Standard Room Partial View','ACTION','1',[12,12,12],[[51,'3','Faldo Golf Course','DBL','712','GBP'],[50,'3','Faldo Golf Course','SNG','838','GBP'],[49,'3','Faldo Golf Course','ADD','712','GBP']]],
 [27,17,'GBP 7N + 4F','2018-12-22','2019-09-27','Standard Room Partial View','MAIN','7',[10,10,10],[[57,'4','Faldo Golf Course','ADD','770','GBP'],[56,'4','Faldo Golf Course','SNG','896','GBP'],[55,'4','Faldo Golf Course','DBL','770','GBP']]],
 [25,18,'€ 7N + 2 SUENO','2019-09-16','2019-10-10','Standard Land View','MAIN','1',[9,9,9],[[59,'2','Dunes Course','SNG','927','EU'],[58,'2','Dunes Course','DBL','717','EU']]],
 [25,19,'€ 7N + UNLIMITED','2019-09-19','2019-10-10','Standard Land View','MAIN','1',[8,8,8],[[61,'2','Dunes Course','DBL','717','EU'],[60,'2','Dunes Course','SNG','927','EU']]],
 [27,21,'GBP 7N + 2F ','2018-12-22','2019-03-31','Standard Room Partial View','ACTION','7',[6,6,6],[[70,'2','Faldo Golf Course','DBL','300','GBP'],[69,'2','Faldo Golf Course','SNG','450','GBP'],[68,'2','Faldo Golf Course','ADD','300','GBP']]],
 [27,22,'GBP 7N + 1F +1Nob','2018-12-22','2019-03-31','Standard Room Partial View','ACTION','7',[5,5,5],[[76,'1','Nobilis Golf Course','ADD','595','GBP'],[75,'1','Nobilis Golf Course','SNG','721','GBP'],[74,'1','Nobilis Golf Course','DBL','610','GBP'],[73,'1','Faldo Golf Course','DBL','610','GBP'],[72,'1','Faldo Golf Course','SNG','721','GBP'],[71,'1','Faldo Golf Course','ADD','595','GBP']]],
 [27,23,'EU 7N + 1F + 1MO + 1T + 1PI','2018-12-22','2019-03-31','Standard Room Partial View','ACTION','7',[4,4,4],[[82,'1','Pines Course','DBL','700','EU'],[81,'1','Titanic Course','DBL','700','EU'],[80,'1','Montgomerie Course','DBL','700','EU'],[79,'1','Faldo Golf Course','DBL','700','EU'],[78,'1','Faldo Golf Course','SNG','900','EU'],[77,'1','Faldo Golf Course','ADD','450','EU']]],
 [27,24,'EU 7N + 4F','2018-12-22','2019-03-31','Standard Room Partial View','ACTION','7',[3,3,3],[[88,'4','Faldo Golf Course','ADD','320','EU'],[87,'4','Faldo Golf Course','SNG','400','EU'],[86,'4','Faldo Golf Course','DBL','380','EU']]],
 [27,25,'EU 7N + 3F + 1 MO','2018-12-22','2019-03-31','Standard Room Partial View','ACTION','7',[2,2,2],[[97,'1','Montgomerie Course','DBL','300','EU'],[96,'1','Montgomerie Course','SNG','500','EU'],[95,'1','Montgomerie Course','ADD','250','EU'],[91,'3','Faldo Golf Course','DBL','300','EU'],[90,'3','Faldo Golf Course','SNG','450','EU'],[89,'3','Faldo Golf Course','ADD','250','EU']]],
 [27,26,'EU 7N + 1F','2018-12-22','2019-03-31','Standard Room Partial View','ACTION','7',[1,1,1],[[100,'1','Faldo Golf Course','DBL','595','EU'],[99,'1','Faldo Golf Course','SNG','721','EU'],[98,'1','Faldo Golf Course','ADD','595','EU']]],
 [27,27,'EURO 7 N + 3 Faldo','2019-04-26','2019-05-31','Standard Room Partial View','MAIN','7',[28,27,27],[[103,'3','Faldo Golf Course','DBL','689','EU']]],
 [27,28,'EURO 7 N + 3 FALDO','2019-04-26','2019-05-31','Standard Room Partial View','MAIN','7',[29,28,28],[[104,'3','Faldo Golf Course','SNG','755','EU']]],
 [27,29,'EURO 7 N + 3 Faldo ','2019-04-26','2019-05-31','Standard Room Partial View','MAIN','7',[30,29,29],[[105,'3','Faldo Golf Course','DBL','689','EU']]],
];
// All 48 package condition rows have zero numeric values, order 1/2/3,
// bonus calculation PP, and dates matching their parent package. Verified individually.
export const packageExtras = {23:{
 hotelExtras:[['1','2018-01-01','2019-01-01','New Year Gala','75','50','PP']],
 golfExtras:[['3','2018-01-01','2019-01-01','Buggy 18 -Faldo','50','30','PP'],['2','2018-01-01','2019-01-01','Trolly -Montgomerie','45','30','PP'],['1','2018-01-01','2019-01-01','Driving Range -Titanic','40','30','PP']]
}};
// No source package has a Rules row. Package extras have no currency field.
// Each of Cornelia's two main contracts has one child detail, with the same source id.
export const contractPriceColumns=['id','accommodationId','accommodation','ageTable','pax','infants','children','parity','price','currency'];
export const contracts=[
 {id:'53',name:'test',firstDate:'2022-11-14',lastDate:'2023-07-14',validityFirstDate:'2022-11-14',validityLastDate:'2023-07-14',roomType:'Standard',roomName:'Standard Room Partial View',allotment:'5',guarantee:'11',contractType:'ACTION',status:'ACTIVE',price:'25',market:'Euro Zone',submarket:'',board:'ALL INCLUSIVE',calculationType:'Accommodation',currency:'EUR',
 prices:[['45','2','2','01211','2','0','0','2','50','EUR'],['272','1','1 + 1 Chd + 1  Inf','01211','1','1','1','2','50','EUR'],['271','2','2 + 1 Inf','01211','2','1','0','1','25','EUR'],['270','1','1 + 1 Inf','01211','1','1','0','1','25','EUR'],['49','2','2 + 1 Chd','01211','2','0','1','2.5','62.5','EUR'],['48','1','1 + 2 Chd','01211','1','0','2','2.5','62.5','EUR'],['47','1','1 + 1 Chd','01211','1','0','1','2','50','EUR'],['46','3','3','01211','3','0','0','2.7','67.5','EUR'],['44','1','1','01211','1','0','0','1.5','37.5','EUR']],conditions:[],rules:[]},
 {id:'45',name:'Summer 2019 Euro',firstDate:'2022-11-14',lastDate:'2025-11-30',validityFirstDate:'2022-11-14',validityLastDate:'2026-07-31',roomType:'Standard',roomName:'Standard Room Partial View',allotment:'15',guarantee:'5',contractType:'MAIN',status:'ACTIVE',price:'100',market:'Euro Zone',submarket:'',board:'ALL INCLUSIVE',calculationType:'Accommodation',currency:'EUR',
 prices:[['45','2','2','01211','2','0','0','2','200','EUR'],['46','3','3','01211','3','0','0','2.7','270','EUR'],['47','1','1 + 1 Chd','01211','1','0','1','2','200','EUR'],['48','1','1 + 2 Chd','01211','1','0','2','2.5','250','EUR'],['49','2','2 + 1 Chd','01211','2','0','1','2.5','250','EUR'],['270','1','1 + 1 Inf','01211','1','1','0','1','100','EUR'],['271','2','2 + 1 Inf','01211','2','1','0','1','100','EUR'],['272','1','1 + 1 Chd + 1  Inf','01211','1','1','1','2','200','EUR'],['44','1','1','01211','1','0','0','1.5','150','EUR']],
 conditions:[
 {id:'49',type:'reduction',firstDate:'2022-11-14',lastDate:'2026-07-31',reduction:'0',payment:'0',order:'1'},
 {id:'56',type:'stayPay',firstDate:'2022-11-14',lastDate:'2025-11-30',stayDays:'0',freeDays:'0',paymentDays:'0',order:'2',calculation:'Check In'},
 {id:'51',type:'longStay',firstDate:'2022-11-14',lastDate:'2025-11-30',minStay:'0',reduction:'0',order:'4'},
 {id:'47',type:'ageReduction',firstDate:'2022-11-14',lastDate:'2026-07-31',age:'0',reduction:'0',order:'5'},
 {id:'9',type:'freePax',firstDate:'2022-11-14',lastDate:'2026-07-31',pax:'0',freePax:'0',roomType:'Standard',roomName:'Standard Room Partial View',reduction:'0',order:'7'}],
 rules:[{id:'2',appliesTo:'LONG STAY',excludes:'AGE REDUCTION'}]}
];
export const notes=[
 'All sixteen remaining local hotel cards and the additional source-only Zeynep card inspected. Zeynep is absent locally and is not restored.',
 'Accounting menu5 content does not exist on any source hotel page. No accounting entries invented.',
 'Only Cornelia De Luxe has hotel contracts: two parents, one child per parent, nine accommodation prices per child. Counts are not separate contracts.',
 'Cornelia package list has thirteen entries across two pages; Lykia one and Sueno Golf two. All four package subtabs loaded before capture.',
 'Cornelia extra 10 contains reversed dates 2019-12-31 / 2019-01-01. Preserve both as sourceDateIssue; leave usable dates blank pending correction rather than guessing.',
 'Lykia package 5 has no selected round count in any of its four price rows. Preserve missing counts and show a warning.',
 'Some package titles say seven nights while the source night field is one; preserve explicit field values, not inferred title values.',
 'Source hotel dates retained; earlier season migration applies to golf course contracts. EU normalized to EUR and decimal comma normalized to dot.'
];

const golfHeader=['Game Date','Hotel','Round','Pax','Free Pax','Agency','Golf Course','Tee Time','C.Type','Golf Game Price','T+H+E','Exchange Rate','Description','Prices'];
const golfRow=(date,hotel,pax,free,course,time,type,price,extras)=>[date,hotel,'1',pax,free,'AQUAMICE',course,time,type,price+' EU',extras+' EU','0','',''];
const source=(id,title,date,option,status,tables,agency='AQUAMICE')=>({id:'kirpii-proposal-'+id,sourceId:String(id),title,createDate:date,optionDate:option,agency,creator:'ADMİN',status,note:'',lines:[],sourceTables:tables});
// Read from the source lists and every detail page on 10 September 2026.
// Source totals, zero exchange rates and empty cells are retained verbatim, not recalculated.
export const sourceProposals={
 golf:[
 source(35,'merve','2018-06-28','2018-11-10','CONFIRMED',[[golfHeader,
 golfRow('1.11.2018','Gloria Golf Resort','7','1','Montgomerie Course','00:00','TO-OHG','910','0'),
 golfRow('1.11.2018','Gloria Golf Resort','7','1','Dunes Course','00:00','TO-OHG','560','40,5'),
 golfRow('19.11.2018','Gloria Golf Resort','7','1','Gloria New Course','00:00','TOHG','315','0')]]),
 source(34,'hhh','2018-06-28','2018-11-10','OPTIONAL',[[golfHeader,golfRow('1.11.2018','Lykia World Hotel','7','1','Lykia Links Course','11:00','TOHG','539','28')]]),
 source(32,'jkk','2018-06-28','2018-11-10','OPTIONAL',[[golfHeader,...[1,2].map(()=>golfRow('1.11.2018','Lykia World Hotel','4','0','Nobilis Golf Course','11:00','TO-OHG','180','0'))]]),
 source(20,'sdfsdf','2018-06-27','','OPTIONAL',[[golfHeader,golfRow('25.11.2018','Gloria Verde Resort','7','0','Titanic Course','00:00','TOHG','350','24,5')]]),
 source(1,'DENEME','2018-06-22','','CHANGED',[[golfHeader,golfRow('15.11.2018','Cornelia De Luxe Hotel','1','0','Lykia Links Course','00:00','TOHG','55','35'),golfRow('15.11.2018','Cornelia De Luxe Hotel','1','0','Lykia Links Course','00:00','TOHG','55','0')]]),
 source(19,'deneme 5','2018-06-26','','CANCELLED',[[golfHeader,golfRow('25.11.2018','Gloria Verde Resort','7','0','Titanic Course','00:00','TO-OHG','350','245')]]),
 source(15,'Deneme 4','2018-06-26','','CANCELLED',[[golfHeader,
 golfRow('19.11.2018','Gloria Golf Resort','7','1','Gloria Old Course','00:00','TOHG','315','16'),
 golfRow('24.11.2018','Gloria Golf Resort','7','1','National Golf Course','00:00','TO-OHG','420','269'),
 golfRow('25.11.2018','Gloria Golf Resort','7','1','PGA Sultan Course','00:00','TO-OHG','455','245'),
 golfRow('15.12.2018','Gloria Golf Resort','7','1','Lykia Links Course','00:00','TO-OHG','273','315')]]),
 source(14,'Deneme 3','2018-06-26','','CANCELLED',[[golfHeader,golfRow('15.12.2018','Gloria Golf Resort','7','1','Lykia Links Course','00:00','TO-OHG','273','371')]]),
 source(10,'cumali','2018-06-25','','CANCELLED',[[golfHeader,
 golfRow('16.11.2018','Gloria Serenity Resort','2','1','Pasha Golf Course','11:00','TO-OHG','70','0'),
 golfRow('17.11.2018','Gloria Serenity Resort','2','1','Faldo Golf Course','11:00','TOHG','170','0'),
 golfRow('19.11.2018','Gloria Serenity Resort','2','1','Gloria New Course','11:00','TOHG','60','7'),
 golfRow('25.11.2018','Gloria Serenity Resort','2','1','PGA Sultan Course','11:00','TO-OHG','130','0')]]),
 source(5,'Mr.Brown-Nov.18','2018-06-25','','CANCELLED',[[golfHeader,
 golfRow('15.11.2018','Gloria Golf Resort','2','0','Gloria Old Course','12:00','TOHG','120','0'),
 golfRow('19.11.2018','Gloria Golf Resort','2','0','Gloria Verde Course','12:00','TOHG','60','0'),
 golfRow('19.11.2018','Gloria Golf Resort','2','0','Gloria New Course','12:00','TOHG','90','7'),
 golfRow('25.11.2018','Gloria Golf Resort','2','0','PGA Sultan Course','12:00','TOHG','100','0'),
 golfRow('28.11.2018','Gloria Golf Resort','2','0','Faldo Golf Course','12:00','TO-OHG','170','7')]]),
 source(3,'DENEME2','2018-06-22','','CANCELLED',[[golfHeader,
 golfRow('15.11.2018','Cornelia De Luxe Hotel','1','0','Lykia Links Course','00:00','TOHG','116','33'),
 golfRow('15.11.2018','Cornelia De Luxe Hotel','1','0','Lykia Links Course','00:00','TOHG','55','33')]]),
 ],hotel:[],
 'hotel-golf':[
 source(10,'TRY PACKAGE','2019-01-09','','OPTIONAL',[[['Room','Pax','Acc.Type','Night','Total','Note','Details $']],[['Room','Pax','Acc.Type','Night','Total','Note','Details $']],[[...golfHeader,'Details $']]],''),
 source(9,'ALL INCLUSIVE PACKAGE','2019-01-09','','OPTIONAL',[[['Room','Pax','Acc.Type','Night','Total','Note','Details $']],[['Room','Pax','Acc.Type','Night','Total','Note','Details $']],[[...golfHeader,'Details $'],[...golfRow('24.12.2018','Cornelia De Luxe Hotel','1','0','Carya Course','12:10','TOHG','40','0'),'']]],''),
 source(8,'FIRST HOTEL+GOLF PACKAGE','2019-01-08','','OPTIONAL',[[['Room','Pax','Acc.Type','Night','Total','Note','Details $']],[['Room','Pax','Acc.Type','Night','Total','Note','Details $']],[[...golfHeader,'Details $']]],''),
 ],
};

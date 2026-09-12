import { useMysqlRecords } from './useMysqlRecords.ts';
import type { HotelDetails } from './hotelDetails.ts';
export type Hotel = {
    details?: HotelDetails;
    id: number;
    name: string;
    code: string;
    category: string;
    type: string;
    catalog: string;
    roomType: string;
    location1: string;
    location2: string;
    status: boolean;
    country: string;
    city: string;
    address: string;
    phone: string;
    smsPhone: string;
    email: string;
    website: string;
    createdBy: string;
    createdAt: string;
    updatedBy: string;
    updatedAt: string;
    active: boolean;
};

export const hotelDefaults: Hotel[] = [
    { id: 20, name: 'Gloria Golf Resort', code: 'GGR', category: '5*', type: '2, 3, 4', catalog: '3', roomType: '41, 85, 58', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'Gloria Hotel', phone: '0242 7100500', smsPhone: '234234', email: '', website: 'www.gloria.com.tr', createdBy: '1', createdAt: '24.07.2017 10:39', updatedBy: '2', updatedAt: '06.05.2019 14:26', active: true },
    { id: 21, name: 'Regnum Carya Golf Hotel & Spa', code: 'Regnum Carya', category: '5*', type: '4, 3', catalog: '5', roomType: '41, 85, 58', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'Kadriye Bölgesi Üçkum Tepesi 07500', phone: '02427103434', smsPhone: '', email: '', website: 'www.regnumhotels.com', createdBy: '1', createdAt: '24.07.2017 10:40', updatedBy: '2', updatedAt: '06.05.2019 14:28', active: true },
    { id: 23, name: 'Sueno Deluxe Hotel', code: 'Sueno Deluxe', category: '5*', type: '5, 4, 3', catalog: '5', roomType: '58, 85, 41', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'Belek Mahallesi', phone: '02427103000', smsPhone: '', email: '', website: 'www.sueno.com.tr', createdBy: '1', createdAt: '24.07.2017 09:42', updatedBy: '2', updatedAt: '06.05.2019 14:30', active: true },
    { id: 25, name: 'Sueno Golf Belek', code: 'Sueno Golf', category: '5*', type: '4, 2', catalog: '5', roomType: '58, 85, 41', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'Sueno', phone: '', smsPhone: '', email: '', website: 'www.sueno.com.tr', createdBy: '1', createdAt: '24.07.2017 10:44', updatedBy: '2', updatedAt: '06.05.2019 14:30', active: true },
    { id: 26, name: 'Cornelia Diamond Hotel', code: 'Cornelia Diamond', category: '5*', type: '2, 3, 4', catalog: '5', roomType: '41, 85, 58', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'İskele mevkii 07506', phone: '02427101600', smsPhone: '', email: '', website: 'www.corneliaresort.com', createdBy: '2', createdAt: '24.07.2017 11:25', updatedBy: '2', updatedAt: '06.05.2019 14:26', active: true },
    { id: 27, name: 'Cornelia De Luxe Hotel', code: 'Cornelia DeLuxe', category: '5*', type: '2, 3, 4', catalog: '5', roomType: '41, 85, 58', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'İleribaşı Mevkii 07506', phone: '0242 710 15 00', smsPhone: '', email: '', website: 'www.corneliaresort.com', createdBy: '2', createdAt: '24.07.2017 11:44', updatedBy: '2', updatedAt: '17.05.2019 13:27', active: true },
    { id: 28, name: 'Gloria Serenity Resort', code: 'GSR', category: '5*', type: '4, 3, 2', catalog: '5', roomType: '85, 58, 41', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'Belek Mahallesi', phone: '02427102300', smsPhone: '', email: '', website: 'www.gloria.com.tr', createdBy: '2', createdAt: '24.07.2017 11:52', updatedBy: '2', updatedAt: '06.05.2019 14:27', active: true },
    { id: 29, name: 'Gloria Verde Resort', code: 'GVR', category: '5*', type: '4, 3, 2', catalog: '5', roomType: '41, 85, 58', location1: '16', location2: '22', status: true, country: '213', city: '6416', address: 'İleribaşı Mevkii', phone: '0242 7100500', smsPhone: '', email: '', website: 'www.gloria.com.tr', createdBy: '2', createdAt: '24.07.2017 11:55', updatedBy: '2', updatedAt: '06.05.2019 14:27', active: true },
    { id: 30, name: 'Kaya Palazzo Hotel', code: 'Kaya Palazzo', category: '5*', type: '2, 4', catalog: '5', roomType: '58, 85, 41', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'Çamlık Cad 07500', phone: '0242 710 15 00', smsPhone: '', email: '', website: 'www.kayahotels.com', createdBy: '2', createdAt: '24.07.2017 12:00', updatedBy: '2', updatedAt: '06.05.2019 14:27', active: true },
    { id: 31, name: 'Kaya Belek Hotel', code: 'Kaya Belek', category: '5*', type: '4, 2', catalog: '5', roomType: '85, 58, 41', location1: '22', location2: '21', status: true, country: '213', city: '6416', address: 'Çamlık Cad 07500', phone: '02427104000', smsPhone: '', email: '', website: 'www.kayahotels.com', createdBy: '2', createdAt: '24.07.2017 12:01', updatedBy: '2', updatedAt: '06.05.2019 14:27', active: true },
    { id: 32, name: 'Kempinski Hotel The Dome', code: 'Kempinski', category: '5*', type: '2, 3, 4', catalog: '5', roomType: '41, 85, 58', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'Kadriye Mahallesi, Yeni Mahalle Uckumtepesi Caddesi No 20-2 Kadriye, 07500', phone: '(0242) 710 13 00', smsPhone: '', email: '', website: 'www.kempinski.com', createdBy: '2', createdAt: '24.07.2017 12:05', updatedBy: '2', updatedAt: '06.05.2019 14:28', active: true },
    { id: 33, name: 'Maxx Royal Belek Golf Resort', code: 'Maxx Royal', category: '5*', type: '4, 3, 2', catalog: '5', roomType: '41, 85, 58', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'Belek Mahallesi, İskele Mevkii, 07500 Belek / Serik / Antalya', phone: '0242 7102700', smsPhone: '', email: '', website: 'www.maxxroyal.com', createdBy: '2', createdAt: '24.07.2017 13:39', updatedBy: '2', updatedAt: '06.05.2019 14:28', active: true },
    { id: 34, name: 'Sirene Golf Hotel', code: 'Sirene', category: '5*', type: '4, 2', catalog: '5', roomType: '41, 85, 58', location1: '15', location2: '22', status: true, country: '213', city: '6416', address: 'Yeni Mah., Üçkum Tepesi Caddesi No:18, 07500 Kadriye, Serik / Antalya', phone: '0242 710 08 00', smsPhone: '0242 710 08 00', email: '', website: 'www.sirene.com.tr', createdBy: '2', createdAt: '26.07.2017 10:42', updatedBy: '2', updatedAt: '06.05.2019 14:29', active: true },
    { id: 41, name: 'Voyage Belek Golf & SPA', code: 'Voyage Belek', category: '5*', type: '4, 3, 2', catalog: '5', roomType: '58, 85, 41', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'Belek', phone: '0242 7102500', smsPhone: '', email: '', website: 'www.voyagehotel.com', createdBy: '2', createdAt: '26.07.2017 11:39', updatedBy: '2', updatedAt: '06.05.2019 14:30', active: true },
    { id: 42, name: 'Robinson Club Nobilis', code: 'Robinson Nobilis', category: '5*', type: '2', catalog: '5', roomType: '41, 85, 58', location1: '16', location2: '22', status: true, country: '213', city: '6416', address: 'Belek Mahallesi, Acısu Mevkii, 07500 Serik / Antalya', phone: '(0242) 710 03 00', smsPhone: '', email: '', website: 'www.robinson.com', createdBy: '2', createdAt: '26.07.2017 11:58', updatedBy: '2', updatedAt: '06.05.2019 14:29', active: true },
    { id: 53, name: 'Titanic Deluxe Belek', code: 'Titanic', category: '5*', type: '4, 2', catalog: '5', roomType: '58, 85, 41', location1: '16', location2: '22', status: true, country: '213', city: '6416', address: 'Üçkumtepesi Beşgöz Caddesi 72/1 Kadriye / Belek / Antalya', phone: '+90 242 710 44 44', smsPhone: '', email: '', website: 'www.titanic.com.tr', createdBy: '2', createdAt: '26.07.2017 13:03', updatedBy: '2', updatedAt: '06.05.2019 14:30', active: true },
    { id: 54, name: 'Zeynep Golf Resort', code: 'Zeynep Golf', category: '5*', type: '2, 4', catalog: '5', roomType: '41, 58, 85', location1: '16', location2: '22', status: true, country: '213', city: '6416', address: 'Taşlıburun Mevki, Belek, Serik, Antalya', phone: '(0242) 725 41 80', smsPhone: '', email: '', website: 'www.zeynepgolfresort.com', createdBy: '2', createdAt: '26.07.2017 13:08', updatedBy: '2', updatedAt: '06.05.2019 14:31', active: true },
    { id: 57, name: 'Lykia World Hotel', code: 'Lykia', category: '5*', type: '2, 3, 4', catalog: '5', roomType: '41, 85, 58', location1: '17', location2: '22', status: true, country: '213', city: '6416', address: 'Denizyaka Mah. Kamışlı Göl Küme Evleri No.1, 07550 Manavgat / Antalya', phone: '90 242 7441915', smsPhone: '', email: '', website: 'www.lykiagroup.com', createdBy: '2', createdAt: '26.07.2017 15:13', updatedBy: '2', updatedAt: '06.05.2019 14:28', active: true },
];
export type Agency = { name: string; code: string; citizen: string; market: string; address: string; extrasKey?: string; subMarket?: string; additionalInfo?: string; contactName?: string; contactEmail?: string; contactPhone?: string; webAddress?: string };
export const agencyDefaults: Agency[] = [
    { name: 'AQUAMICE', code: 'AQUAMICE', citizen: 'RU', market: 'EURO', address: 'Kızıltöprak Mh. 920 Sok. Erşahin Apt. 20/A ANTALYA' },
    { name: 'ATLANTIC GOLF', code: 'ATL', citizen: 'UK', market: 'EURO', address: 'ATLANTIC HOUSE 24 Trewenna Drive Potters Bar Herts EN6 5JL ENGLAND' },
    { name: 'BIRDIE GOLF', code: 'BG', citizen: 'SE', market: 'EURO', address: 'BIRDIE GOLF TOURS/AIREX NIKE TOURS Norrtullsgatan 12A S-113 27 STOCKHOLM, SWEDEN' },
    { name: 'BOUNTY', code: 'BON', citizen: 'SE', market: 'EURO', address: 'Hugo-von-Königsegg-Str. 18 87534 Oberstaufen Amtsgericht Kempten HRB 10150' },
    { name: 'BRYAN SOMERS TRAVEL', code: 'BRYSMR', citizen: 'UK', market: 'EURO', address: '1 Main Str. Saintfield BT24 7AA' },
    { name: 'CLASSIC REISEN', code: 'CLASSIC', citizen: 'CH', market: 'EURO', address: 'CLASSIC Prestige AG | Alte Jonastrasse 74 | 8640 Rapperswil SG' },
    { name: 'DENEME', code: 'DNM', citizen: 'SE', market: 'EURO', address: '' },
    { name: 'DERTOUR', code: 'DTS', citizen: 'SE', market: 'EURO', address: 'ER Touristik Frankfurt GmbH & Co. KG Emil-von-Behring-Straße 6 60424 Frankfurt' },
    { name: 'DIANA', code: 'DIA', citizen: 'DE', market: 'EURO', address: 'Diana Travel Altınova Sinan Mahallesi, No:331, Serik Cd., 07170 Kepez/Antalya,' },
    { name: 'EXCLUSIVE TOURS', code: 'EXCLUSIVE', citizen: 'CZ', market: 'EURO', address: 'Exclusive Tours s.r.o. Olivova 2096/4, 110 00 Praha 1, Czech Republic' },
    { name: 'GLOBAL GOLF TIME', code: 'GGT', citizen: 'DE', market: 'EURO', address: 'Global Golftime GmbH | Goethestraße 1F | 31840 Hessisch Oldendorf | Germany' },
    { name: 'GOLF & WELLNESS', code: 'GW', citizen: 'UK,DE,TR,RU', market: 'EURO', address: 'Kızıltöprak Mh. 920 Sok. Erşahin Apt. 20/A ANTALYA' },
    { name: 'GOLF DELIGHTS', code: 'GDEL', citizen: 'UK,DE', market: 'EURO', address: 'Şirinyalı Mh. 1515.Sok. Hasan dede Apt. K:1 D:5 ANTALYA' },
    { name: 'GOLF ESCAPES', code: 'GER', citizen: 'DE,UK', market: 'EURO', address: "Golf Escapes Ltd, St Andrew's House, Cinder Hill, Horsted Keynes, West Sussex, RH17 7BA, UK" },
    { name: 'GOLF FRIENDS', code: 'GF', citizen: 'DE', market: 'EURO', address: 'Golffriends Travel GmbH - Hafenstrasse 28 - DE-79576 Weil am Rhein' },
    { name: 'GOLF MOTION', code: 'GMOT', citizen: 'DE,SE', market: 'EURO', address: 'Travelmotion AG - Seefeldstrasse 69, CH-8008 Zürich' },
    { name: 'GOLF RESAN TRAVEL', code: 'GFR', citizen: 'DE', market: 'EURO', address: 'Hugo-von-Königsegg-Str. 18 DE-87534 Oberstaufen / Germany' },
    { name: 'GOLF SLOVENIA', code: 'GSLO', citizen: 'SI', market: 'EURO', address: 'Šmartinska cesta 152, 1000 Ljubljana (Hala A – vhod 1)' },
    { name: 'GOLFING HOLIDAYS', code: 'GH', citizen: 'UK', market: 'EURO', address: '' },
    { name: 'GOTP', code: 'GO', citizen: 'DE', market: 'EURO', address: '' },
    { name: 'GREEN FEES DIRECT', code: 'GDIRECT', citizen: 'UK', market: 'EURO', address: '' },
    { name: 'HIO GOLF TRAVEL', code: 'HIO', citizen: 'ESP', market: 'EURO', address: 'Urb.Los Naranjos de Marbella 29660 Puerto Banús - Marbella (Malaga) – España' },
    { name: 'KD TOURISM', code: 'KD', citizen: 'RU,TR', market: 'GBP', address: 'Serdar-ı Ekrem St. Braunstein Apt. No:20/234425 Galata /Beyoğlu/ İstanbul' },
    { name: 'KOMU GROUP', code: 'KOMU', citizen: 'PL', market: 'EURO', address: 'KOMU Group Sp. z o.o. ul. Kiwerska 2 01-682 Warszawa' },
    { name: 'LILY TOURS', code: 'LILY', citizen: 'CR', market: 'EURO', address: 'Pavlinska 3, Varaždin, Hrvatska' },
    { name: 'PARCOUR VOYAGE', code: 'PRCVYG', citizen: 'FR', market: 'EURO', address: "8, Boulevard d'Alsace | 06400 Cannes - France" },
    { name: 'PERSONAL TOUCH', code: 'PTOUCH', citizen: 'UK', market: 'EURO', address: 'Tour Golf G.B. 50, Church Street, Horsley, Derbyshire DE21 5BP' },
    { name: 'REISEBURO HOLLEDAU', code: 'RHOLEDAU', citizen: 'DE', market: 'EURO', address: 'Zeilerbergstr. 33 a Nandlstadt 85405 Deutschland' },
    { name: 'S GUIDE', code: 'SG', citizen: 'CZ', market: 'EURO', address: 'Erbenova 8, CZ-602 00 Brno Czech Republic' },
    { name: 'sdf', code: 'sdf', citizen: 'CR,NL,CH', market: 'EURO,GBP', address: 'sdf' },
    { name: 'SOLOS HOLIDAYS', code: 'SOLOS', citizen: 'UK', market: 'EURO', address: 'Solos Holidays Ltd 54-58 High Street Edgware Middlesex HA8 7EJ' },
    { name: 'STARTIDDEN TRAVEL', code: 'STARTIDDEN', citizen: 'SE', market: 'EURO', address: 'Lugna Gatan 62 211 59 Malmö Sweden' },
    { name: 'SUN GOLF HOLIDAYS', code: 'SUN', citizen: 'UK', market: 'EURO', address: '' },
    { name: 'YAZILIM AGENCY', code: 'YZLM AGENCY', citizen: 'TR', market: 'EURO', address: '' },
    { name: 'YOUR TRAVEL', code: 'YOUR', citizen: 'NL', market: 'EURO', address: 'Binckhorstlaan 287 2516 BC Den Haag' },
];
export function validHotel(value: unknown): value is Hotel { const r = value as Hotel; return !!r && Number.isInteger(r.id) && typeof r.name === 'string' && !!r.name.trim() && typeof r.code === 'string' && !!r.code.trim(); }
export function validAgency(value: unknown): value is Agency { const r = value as Agency; return !!r && typeof r.name === 'string' && !!r.name.trim() && typeof r.code === 'string' && !!r.code.trim(); }
export function useHotels() { return useMysqlRecords('hotels', hotelDefaults, validHotel, 'vox-golf-hotels'); }
export function useAgencies() { return useMysqlRecords('agencies', agencyDefaults, validAgency); }

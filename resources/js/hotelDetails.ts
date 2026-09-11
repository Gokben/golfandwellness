export type HotelExtra = { id: string; firstDate: string; lastDate: string; description: string; buyPrice: string; sellPrice: string; currency: string; priceType: string; obligation: boolean; ageTable: string; buyInfant: string; sellInfant: string; buyChild: string; sellChild: string; sourceDateIssue?: { firstDate: string; lastDate: string } };
export type HotelPackageExtra = { id: string; firstDate: string; lastDate: string; description: string; buyPrice: string; sellPrice: string; priceType: string };
export type HotelRule = { id: string; appliesTo: string; excludes: string };
export type HotelContract = {
    reviewRequired?: boolean; sourceNotes?: string[];
    id: string; name: string; firstDate: string; lastDate: string; validityFirstDate: string; validityLastDate: string;
    roomType: string; roomName: string; allotment: string; guarantee: string; contractType: string; status: string;
    price: string; currency: string; market: string; submarket: string; board: string; calculationType: string;
    prices: { id: string; accommodationId: string; accommodation: string; ageTable: string; pax: string; infants: string; children: string; parity: string; price: string; currency: string; manualPrice?: boolean }[];
    conditions: Record<string,string>[]; rules: HotelRule[];
};
export type HotelPackage = {
    id: string; name: string; firstDate: string; lastDate: string; roomType: string; roomName: string;
    contractType: string; nights: string; calculationType: string; status: string;
    rounds: { id: string; rounds: string; courseKey: string; accommodation: string; price: string; currency: string }[];
    bonus: Record<string, string>[]; reduction: Record<string, string>[]; golferFree: Record<string, string>[];
    hotelExtras: HotelPackageExtra[]; golfExtras: HotelPackageExtra[]; rules: HotelRule[];
};
export type HotelDetails = { contractsStatus: 'available' | 'empty' | 'unavailable'; accountingStatus: 'empty' | 'unavailable'; contracts?: HotelContract[]; extras: HotelExtra[]; packages: HotelPackage[] };

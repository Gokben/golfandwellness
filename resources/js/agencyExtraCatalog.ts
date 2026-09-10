import type { AgencyExtra } from './agencyExtras';

// Read-only source audit: docs/imports/kirpii-agency-extras-2026-09-03.json.
// All 35 agencies were inspected; only AQUAMICE had saved extras.
export const agencyExtraDefaults: AgencyExtra[] = [
    {
        "id": "kirpii-agency-63-extra-1",
        "agencyKey": "AQUAMICE",
        "firstDate": "2018-12-01",
        "lastDate": "2019-12-31",
        "description": "GOLF BAG",
        "buyPrice": "15",
        "sellPrice": "15",
        "currency": "EU",
        "priceType": "PP",
        "obligation": false,
        "ageTable": "01211",
        "buyInfant": "0",
        "sellInfant": "0",
        "buyChild": "0",
        "sellChild": "0"
    },
    {
        "id": "kirpii-agency-63-extra-2",
        "agencyKey": "AQUAMICE",
        "firstDate": "2018-12-01",
        "lastDate": "2019-01-01",
        "description": "GOLD",
        "buyPrice": "10",
        "sellPrice": "10",
        "currency": "EU",
        "priceType": "PP",
        "obligation": false,
        "ageTable": "01211",
        "buyInfant": "0",
        "sellInfant": "0",
        "buyChild": "0",
        "sellChild": "0"
    }
];

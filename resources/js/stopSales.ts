import { useMysqlRecords } from './useMysqlRecords';
export type StopSale = { id:string; firstDate:string; lastDate:string; recordDate:string; hotelId:string; boardId:string; roomId:string; subRoomId:string; marketId:string; subMarket:string };
export function validStopSale(value:unknown):value is StopSale {
    const r=value as StopSale;
    return !!r && ['id','firstDate','lastDate','recordDate','hotelId','boardId','roomId','subRoomId','marketId','subMarket'].every(k=>typeof r[k as keyof StopSale]==='string');
}
export function useStopSales(){return useMysqlRecords<StopSale>('hotel-stop-sales',[],validStopSale);}

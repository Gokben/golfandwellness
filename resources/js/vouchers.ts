import { useMysqlRecords } from './useMysqlRecords';
import { agencyDefaults } from './entities';
import source from './voucherSource.json';
export type Voucher = { id: string; agencyKey: string; name: string; shortCode: string; count: string; lastCount: string };
export const voucherDefaults: Voucher[] = source.map(row => {
    const agency = agencyDefaults.find(a=>a.name===row.name);
    return {...row,id:'kirpii-voucher-'+row.id,agencyKey:agency?.extrasKey??agency?.code??row.name};
});
export function validVoucher(value: unknown): value is Voucher {
    if (!value || typeof value !== 'object') return false;
    const row=value as Voucher;
    return ['id','agencyKey','name','shortCode'].every(key=>typeof row[key as keyof Voucher]==='string' && row[key as keyof Voucher].trim().length>0)
        && typeof row.count==='string' && /^\d{1,12}$/.test(row.count)
        && typeof row.lastCount==='string' && /^\d{1,12}$/.test(row.lastCount) && Number(row.lastCount)>=Number(row.count);
}
export function useVouchers() { return useMysqlRecords('agency-vouchers',voucherDefaults,validVoucher); }

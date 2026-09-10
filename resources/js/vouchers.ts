import { watch, type Ref } from 'vue';
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

// Read the agency's stored voucher; selecting an agency does not advance its counter.
export function useAgencyVoucher(form: Ref<{agency:string;voucher:string}>, editingId: Ref<string>, records: Ref<any[]>) {
    const store=useVouchers();
    watch([()=>form.value.agency, ()=>store.records.value], ([agency], previous) => {
        const saved=records.value.find(row=>row.id===editingId.value);
        if(saved && saved.agency===agency && form.value.voucher===saved.voucher)return;
        const agencyChanged=previous && agency!==previous[0];
        if(!agencyChanged && form.value.voucher)return;
        const row=store.records.value.find(row=>row.agencyKey===agency || row.name===agency);
        form.value.voucher=row ? row.shortCode+'-'+String(Number(row.lastCount)+1).padStart(row.lastCount.length,'0') : '';
    }, {immediate:true});
    return store;
}

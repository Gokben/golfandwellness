<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type { HotelContract } from '../hotelDetails';
import { contractDateDisplay } from '../contractDateDisplay.mjs';
import { calculateReservation } from '../reservationCalculation.mjs';
const props = defineProps<{ reservation: { checkIn:string; checkOut:string; roomCount:string; handling?:string }; contract?: HotelContract; agencyContract?: HotelContract; handling?: {name:string; total:number; currency:string}; saved:boolean }>();
const rowId = ref('');
watch(() => props.contract?.id, () => { rowId.value = ''; });
const matches = (contract?:HotelContract) => (contract?.prices ?? []).filter(item => ['pax','children','infants'].every(key => String((props.reservation as any)[key] ?? '') !== '' && Number((item as any)[key]) === Number((props.reservation as any)[key])));
const row = computed(() => { const rows = matches(props.contract); return rows.length === 1 ? rows[0] : undefined; });
const calculation = computed(() => calculateReservation(props.reservation, props.contract, props.agencyContract, props.handling));
const agencyResult = computed(() => calculation.value.agency);
const profit = computed(() => calculation.value.profit);
const result = computed(() => calculation.value.hotel);
const money = (value: unknown) => Number(value).toLocaleString('tr-TR', { minimumFractionDigits:2, maximumFractionDigits:2 });
</script>
<template>
    <section class="pricing-breakdown">
        <h3>Fiyatlandırma</h3>
        <p v-if="!saved" role="status">Rezervasyon henüz kaydedilmedi.</p>
        <template v-else>
            <p><strong>Kontrat:</strong> {{ contract ? contractDateDisplay(contract.name) : 'Seçilmedi' }}</p>
            <p>{{ contractDateDisplay(reservation.checkIn) }} / {{ contractDateDisplay(reservation.checkOut) }} · {{ result.nights || '—' }} gece · {{ reservation.roomCount }} oda</p>
            <p v-if="contract"><strong>Hesaplama tipi:</strong> {{ contract.calculationType }}</p>

            <ul v-if="result.errors.length" class="pricing-errors" role="status"><li v-for="error in result.errors" :key="error">{{ error }}</li></ul>
            <table><tbody>
                <tr><th>Otel fiyatı</th><td>{{ result.total !== null ? money(result.total) + ' ' + result.currency : 'Hesaplanamadı' }}</td></tr>
                <tr><th>Handling{{ handling ? ' · ' + handling.name : '' }}</th><td>{{ handling ? money(handling.total) + ' ' + handling.currency : reservation.handling ? 'Hesaplanamadı' : 'Seçilmedi' }}</td></tr>
                <tr><th>Acente fiyatı</th><td>{{ agencyResult.total !== null ? money(agencyResult.total) + ' ' + agencyResult.currency : 'Hesaplanamadı' }}</td></tr>
                <tr><th>Net kâr</th><td>{{ profit !== null ? money(profit) + ' ' + result.currency : 'Hesaplanamadı' }}</td></tr>
            </tbody></table>
            <ul class="pricing-errors"><li v-for="error in agencyResult.errors" :key="error">Acente: {{ error }}</li><li v-if="result.total !== null && agencyResult.total !== null && result.currency !== agencyResult.currency">Para birimleri farklı; net kâr hesaplanamaz.</li></ul>
            <p>Net kâr = Acente fiyatı (handling dahil) − Otel fiyatı. Transfer, ekstralar ve diğer giderler dahil değildir.</p>
            <p v-if="agencyResult.total !== null">Acente: {{ money(agencyResult.unit) }} × {{ agencyResult.nights }} gece × {{ agencyResult.rooms }} oda<span v-if="handling"> + {{ money(handling.total) }} {{ handling.currency }} handling</span></p>
            <template v-if="result.total !== null && row">
                <table><tbody>
                    <tr><th>Gecelik oda fiyatı</th><td>{{ row.manualPrice ? 'Kontratta kayıtlı fiyat' : `${money(contract?.price)} × ${row.parity} parite` }}</td><td>{{ money(result.unit) }} {{ result.currency }}</td></tr>
                    <tr><th>Konaklama</th><td>{{ money(result.unit) }} × {{ result.nights }} gece × {{ result.rooms }} oda</td><td>{{ money(result.total) }} {{ result.currency }}</td></tr>
                </tbody></table>
                <p><strong>Kontrat bazlı konaklama tutarı: {{ money(result.total) }} {{ result.currency }}</strong></p>
                <p>Hesaplama rezervasyonun yetişkin, çocuk ve bebek dağılımına göre yapılmıştır.</p>
            </template>
        </template>
    </section>
</template>
<style scoped>
.pricing-breakdown { padding:20px; color:#31526c; }
label { display:flex; flex-direction:column; gap:8px; max-width:640px; }
select { padding:10px; max-width:100%; border:1px solid #8ab1ce; background:white; color:inherit; }
table { width:100%; margin-top:20px; border-collapse:collapse; }
th,td { padding:12px; border-bottom:1px solid #bfd4e5; text-align:left; overflow-wrap:anywhere; }
.pricing-errors { color:#b91c1c; }
</style>

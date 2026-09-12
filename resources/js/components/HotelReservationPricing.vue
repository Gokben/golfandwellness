<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type { HotelContract } from '../hotelDetails';
import { contractDateDisplay } from '../contractDateDisplay.mjs';
import { reservationPricePreview } from '../reservationPricePreview.mjs';
const props = defineProps<{ reservation: { checkIn:string; checkOut:string; roomCount:string }; contract?: HotelContract; saved:boolean }>();
const rowId = ref('');
watch(() => props.contract?.id, () => { rowId.value = ''; });
const row = computed(() => props.contract?.prices.find(item => item.id === rowId.value));
const result = computed(() => reservationPricePreview(props.reservation, props.contract, row.value));
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
            <label>Konaklama / Kişi Dağılımı<select v-model="rowId"><option value="">Seçiniz</option><option v-for="price in contract?.prices ?? []" :key="price.id" :value="price.id">{{ price.accommodation }} · {{ price.pax }} yetişkin / {{ price.children }} çocuk / {{ price.infants }} bebek</option></select></label>
            <ul v-if="result.errors.length" class="pricing-errors" role="status"><li v-for="error in result.errors" :key="error">{{ error }}</li></ul>
            <template v-if="result.total !== null && row">
                <table><tbody>
                    <tr><th>Gecelik oda fiyatı</th><td>{{ row.manualPrice ? 'Kontratta kayıtlı fiyat' : `${money(contract?.price)} × ${row.parity} parite` }}</td><td>{{ money(result.unit) }} {{ result.currency }}</td></tr>
                    <tr><th>Konaklama</th><td>{{ money(result.unit) }} × {{ result.nights }} gece × {{ result.rooms }} oda</td><td>{{ money(result.total) }} {{ result.currency }}</td></tr>
                </tbody></table>
                <p><strong>Kontrat bazlı konaklama tutarı: {{ money(result.total) }} {{ result.currency }}</strong></p>
                <p>Ekstralar, acente satış fiyatı ve komisyon bu tutara dahil değildir. Seçilen kişi dağılımı hesaplama önizlemesidir; rezervasyona kaydedilmez.</p>
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

<script setup lang="ts">
import type { AgencyHotelContract } from '../entities';
import { contractDateDisplay } from '../contractDateDisplay.mjs';
defineProps<{ contracts: AgencyHotelContract[] }>();
</script>
<template>
 <section class="agency-contracts">
  <p v-if="!contracts.length">Henüz otel kontratı bulunmuyor.</p>
  <details v-for="copy in contracts" :key="copy.id">
   <summary>{{ copy.hotelName }} · {{ copy.name }} · {{ copy.currency }}</summary>
   <p>Kâr: {{ copy.markupAmount }} {{ copy.markupMode === 'percent' ? '%' : copy.currency }}</p>
   <details v-for="contract in copy.contracts" :key="contract.id">
    <summary>{{ contract.roomName }} · {{ contractDateDisplay(contract.firstDate) }} / {{ contractDateDisplay(contract.lastDate) }}</summary>
    <p>{{ contract.board }} · {{ contract.market }} · {{ contract.status }}</p>
    <dl><dt>Ana fiyat ({{ copy.currency }})</dt><dd>{{ contract.price || '—' }}</dd><template v-for="row in contract.prices" :key="row.id"><dt>{{ row.accommodation }}</dt><dd>{{ row.price }} {{ row.currency }}</dd></template></dl>
   </details>
  </details>
 </section>
</template>
<style scoped>
.agency-contracts{font:inherit;color:#154c75;overflow-wrap:anywhere}.agency-contracts summary{padding:12px 0;cursor:pointer;border-bottom:1px solid #bfd4e5}.agency-contracts details details{margin-left:16px}.agency-contracts dl{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;max-width:800px}.agency-contracts dd{margin:0}
</style>

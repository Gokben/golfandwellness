<script setup lang="ts">
import { ref } from 'vue';

const description = ref('The Course nurdan\n\nThe course is inspired by the traditional old English style heathland courses in the surrey area outside of London such as Wentworth, Sunningdale, Walton Heath and The Berkshire.\n\nThe construction of Carya Golf Course has involved the planting of close to 1 million heather plants to strengthen the heathland environment that already exists.\n\nCarya Golf Club is open to golf lovers of any age and skill level. The Course is designed to challenge the best players, but at the same time be a throughly enjoyable experience for people at all levels.');
const map = ref('<iframe src="https://www.google.com/maps/embed?pb=..." width="600" height="450"></iframe>');
const active = ref('Aktif');
const mustNumber = ref(1);
const nearby = ref(['Pasha Golf Course', 'National Golf Course']);
</script>

<template>
    <section class="golf-details" aria-label="Golf Sahası Detayları">
        <label>Golf Sahası Açıklaması</label>
        <div class="rich-editor"><div class="toolbar"><button type="button"><b>B</b></button><button type="button"><i>I</i></button><button type="button"><u>U</u></button><button type="button">☷</button><button type="button">⌁</button><button type="button">▧</button><button type="button">▦</button><button type="button">⛶</button></div><textarea v-model="description"></textarea><small>body&nbsp;&nbsp; h4&nbsp;&nbsp; strong</small></div>
        <label>Golf Sahası Google Map Iframe<input v-model="map"></label>
        <div class="fields"><label>Aktif<select v-model="active"><option>Aktif</option><option>Pasif</option></select></label><label>Zorunlu Sayı<input v-model.number="mustNumber" type="number" min="0"></label></div>
        <label>Yakındaki Golf Sahaları<select v-model="nearby" multiple><option>Pasha Golf Course</option><option>National Golf Course</option><option>Carya Course</option><option>Nobilis Golf Course</option></select></label>
        <div class="tags"><span v-for="course in nearby" :key="course">{{ course }} <button type="button" @click="nearby = nearby.filter(item => item !== course)">×</button></span></div>
    </section>
</template>

<style scoped>
.golf-details{height:calc(100% + 40px);margin:-20px;padding:12px 20px;box-sizing:border-box;overflow:auto;background:#f8fbfd;color:#172e41;font:11px Arial}.golf-details>label{display:flex;flex-direction:column;gap:6px;margin:0 0 16px;font:700 12px Tahoma}.golf-details input,.golf-details select{box-sizing:border-box;width:100%;height:30px;padding:4px 8px;border:1px solid #202020;border-radius:2px;background:#fff;color:#536b7c;font:11px Arial}.rich-editor{margin-bottom:22px;border:1px solid #c7cfd4;background:#fff}.toolbar{display:flex;gap:2px;min-height:35px;padding:2px 8px;border-bottom:1px solid #d3d9dd;background:#f5f5f5}.toolbar button{width:29px;height:27px;border:0;background:transparent;color:#303d47;font-size:14px}.toolbar button:hover{background:#e3edf4}.rich-editor textarea{box-sizing:border-box;width:100%;height:195px;padding:14px 20px;border:0;resize:vertical;outline:0;color:#172e41;font:13px/1.55 Arial;white-space:pre-wrap}.rich-editor small{display:block;padding:5px 10px;border-top:1px solid #d3d9dd;background:#f5f5f5;color:#304b5b}.fields{display:grid;grid-template-columns:1fr 1fr;gap:30px}.fields label{display:flex;flex-direction:column;gap:6px;margin-bottom:16px;font:700 12px Tahoma}.golf-details>label:last-of-type select{height:42px}.tags{display:flex;gap:5px;margin-top:-11px}.tags span{padding:4px 7px;border:1px solid #c7ced6;border-radius:3px;background:#f0f2f4;color:#637784}.tags button{padding:0 0 0 4px;border:0;background:transparent;color:#506a80;font-size:15px}
</style>

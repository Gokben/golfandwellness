<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePackageDefinitions, type PackageDefinition, type PackageDefinitionChild } from '../hotelGolfPackageDefinitions';
import { useGolfCourses } from '../golfCourses';
import VoxActionButton from './VoxActionButton.vue';
const store=usePackageDefinitions();
const courses=useGolfCourses();
const parentId=ref<string|null>(null);
const parent=computed(()=>store.records.value.find(p=>p.id===parentId.value));
const query=ref('');
const courseFilter=ref('');
const draft=ref<{id:string;name:string;code:string;courseKey:string}|null>(null);
const courseName=(key:string)=>courses.value.find(c=>(c.contractKey??c.code)===key)?.name??key;
const courseOptions=computed(()=>courses.value.map(c=>({key:c.contractKey??c.code,name:c.name})));
const rows=computed(()=>{
    const search=query.value.trim().toLocaleLowerCase('tr-TR');
    const list: (PackageDefinition|PackageDefinitionChild)[]=parent.value?parent.value.children:store.records.value;
    return list.filter(row=>(!parent.value||!courseFilter.value||('courseKey' in row&&row.courseKey===courseFilter.value))
        &&[row.name,row.code,...('courseKey' in row?[courseName(row.courseKey)]:[])].some(v=>v.toLocaleLowerCase('tr-TR').includes(search)));
});
function openParent(row:PackageDefinition|PackageDefinitionChild){parentId.value=row.id;query.value='';courseFilter.value='';draft.value=null;}
function back(){parentId.value=null;query.value='';courseFilter.value='';draft.value=null;}
function edit(row?:PackageDefinition|PackageDefinitionChild){draft.value={id:row?.id??crypto.randomUUID(),name:row?.name??'',code:row?.code??'',courseKey:row&&'courseKey' in row?row.courseKey:courseFilter.value};}
async function save(){
    if(!draft.value)return;
    const {id,name,code,courseKey}=draft.value;
    if(!name.trim()||!code.trim()||(parent.value&&!courseKey))return;
    let next:PackageDefinition[];
    if(parent.value){
        const child={id,name:name.trim(),code:code.trim(),courseKey};
        next=store.records.value.map(p=>p.id===parentId.value?{...p,children:p.children.some(c=>c.id===id)?p.children.map(c=>c.id===id?child:c):[...p.children,child]}:p);
    }else{
        const existing=store.records.value.find(p=>p.id===id);
        const row={id,name:name.trim(),code:code.trim(),children:existing?.children??[]};
        next=existing?store.records.value.map(p=>p.id===id?row:p):[...store.records.value,row];
    }
    if(await store.commit(next))draft.value=null;
}
</script>
<template>
 <section class="package-definitions" aria-label="Otel ve golf paket tanımları">
  <header><button v-if="parent" type="button" @click="back">← Ana paketler</button><h2>{{ parent?parent.name:'Otel + Golf Paketleri' }}</h2><button type="button" :disabled="!store.ready.value||store.busy.value" @click="edit()">＋ {{ parent?'Alt Paket Ekle':'Paket Ekle' }}</button><button type="button" :disabled="store.busy.value" @click="store.reload()">↻ Yenile</button></header>
  <div class="filters"><label>Ara <input v-model="query" type="search" placeholder="Ad, kod veya golf sahası"></label><label v-if="parent">Golf Sahası <select v-model="courseFilter"><option value="">Tüm sahalar</option><option v-for="course in courseOptions" :key="course.key" :value="course.key">{{ course.name }}</option></select></label></div>
  <p v-if="store.storageError.value" role="alert">{{ store.storageError.value }}</p>
  <form v-if="draft" class="editor" @submit.prevent="save"><label>Ad <input v-model="draft.name" required maxlength="200"></label><label>Kod <input v-model="draft.code" required maxlength="150"></label><label v-if="parent">Golf Sahası <select v-model="draft.courseKey" required><option value="">Seçiniz</option><option v-if="draft.courseKey&&!courseOptions.some(c=>c.key===draft!.courseKey)" :value="draft.courseKey">{{ draft.courseKey }}</option><option v-for="course in courseOptions" :key="course.key" :value="course.key">{{ course.name }}</option></select></label><button :disabled="store.busy.value" type="submit">Kaydet</button><button type="button" @click="draft=null">Vazgeç</button></form>
  <div class="table-scroll"><table><thead><tr><th>Ad</th><th>Kod</th><th>{{ parent?'Golf Sahası':'Alt Kayıt' }}</th><th>İşlem</th></tr></thead><tbody>
   <tr v-for="row in rows" :key="row.id"><td>{{ row.name }}</td><td>{{ row.code }}</td><td>{{ 'courseKey' in row?courseName(row.courseKey):row.children.length }}</td><td><VoxActionButton v-if="!parent" action="link" title="Alt kayıtlar" :aria-label="row.name+' alt kayıtlar'" @click="openParent(row)" /><VoxActionButton action="edit" :aria-label="row.name+' düzenle'" @click="edit(row)" /></td></tr>
   <tr v-if="!rows.length"><td colspan="4">{{ store.busy.value?'Kayıtlar yükleniyor…':'Eşleşen kayıt bulunamadı.' }}</td></tr>
  </tbody></table></div>
  <footer>{{ parent?'Alt kayıt':'Ana paket' }}: {{ parent?parent.children.length:store.records.value.length }} · Gösterilen: {{ rows.length }}</footer>
 </section>
</template>
<style scoped>
.package-definitions {display:flex;flex-direction:column;height:100%;min-height:0;background:#f7fbfe;color:#254f6c;font:12px Tahoma,sans-serif} header,.filters,.editor {display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 16px;border-bottom:1px solid #cadde9} h2 {font-size:16px;margin:0 auto 0 0} label {display:flex;align-items:center;gap:8px} input,select {height:30px;padding:4px 7px;border:1px solid #8cb2cb;background:white;color:#254f6c} input[type=search] {width:230px} button {padding:7px 11px;border:1px solid #8cb2cb;background:#e7f3fb;color:#24577d;cursor:pointer} button:disabled {opacity:.5;cursor:default} .editor {background:#e4f1fa} .table-scroll {flex:1;overflow:auto} table {width:100%;border-collapse:collapse} th {position:sticky;top:0;background:#e4f0f9;text-align:left} th,td {padding:9px 16px;border-bottom:1px solid #d7e4ed} td button+button {margin-left:8px} tbody tr:nth-child(even) {background:#eef6fb} tbody tr:hover {background:#dfedf8} footer {padding:10px 16px;border-top:1px solid #c6d9e7} [role=alert] {color:#a32c20;padding:0 16px}
</style>

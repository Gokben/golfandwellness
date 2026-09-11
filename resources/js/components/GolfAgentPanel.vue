<script setup lang="ts">
import { ref, watch, onBeforeUnmount } from 'vue';
import { apiHeaders, apiUrl } from '../api';
import agentLogo from '../assets/agent-logo.png?inline';
import { refreshMysqlRecords } from '../useMysqlRecords';
type Knowledge = { id:string; owner:string; title:string; content:string; scope:string; status:string; version:number };
const open = ref(false);
const emit = defineEmits<{ active: [value:boolean] }>();
const tab = ref('chat');
const busy = ref(false);
const error = ref('');
const admin = ref(false);
const owner = ref('');
const configured = ref(false);
const knowledge = ref<Knowledge[]>([]);
const hotels = ref<{id:string;name:string}[]>([]);
const hotelId = ref('');
const contractId = ref('');
const bulk = ref(false);
const contractIds = ref<string[]>([]);
const contracts = ref<{id:string;name:string}[]>([]);
const actionText = ref('');
const actionExplanation = ref('');
const actionProposal = ref<{token:string;hotelName:string;contractName:string;changes:{contractName?:string;label:string;before:string;after:string;currency:string}[]}|null>(null);
const actionConfirmed = ref(false);
const actionSaved = ref('');
watch([hotelId,contractId,contractIds,bulk,actionText],()=>{ actionProposal.value=null; actionConfirmed.value=false; actionExplanation.value=''; actionSaved.value=''; });
const question = ref('');
const messages = ref<{question:string;answer:string}[]>([]);
const title = ref('');
const content = ref('');
const scope = ref('personal');
const editing = ref<Knowledge|null>(null);
watch(() => open.value || busy.value || !!question.value || !!content.value || !!title.value || !!actionText.value || !!actionProposal.value, value => emit('active',value));
let controller: AbortController | null = null;
async function request(path:string, body?:unknown) {
    const response = await fetch(apiUrl(path), {method: body ? 'POST' : 'GET', headers:apiHeaders(), body:body ? JSON.stringify(body) : undefined, signal:controller?.signal});
    const data = await response.json();
    if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat().join(' ') || data.message || 'İşlem tamamlanamadı.');
    return data;
}
async function run(action:()=>Promise<void>) {
    if (busy.value) return;
    busy.value=true; error.value=''; controller=new AbortController();
    const timeout=window.setTimeout(()=>controller?.abort(),100000);
    try { await action(); } catch(e) { error.value=e instanceof Error && e.name==='AbortError' ? 'İşlem durduruldu veya zaman aşımına uğradı.' : e instanceof Error ? e.message : 'İşlem tamamlanamadı.'; }
    finally { window.clearTimeout(timeout); busy.value=false; controller=null; }
}
async function load() {
    const data=await request('agent');
    knowledge.value=data.knowledge; admin.value=data.admin; owner.value=data.owner; configured.value=data.configured;
}
function show() { open.value=true; void run(async()=>{ await load(); hotels.value=(await request('agent/hotels')).hotels; }); }
function changeHotel() {
    contractId.value=''; contractIds.value=[]; contracts.value=[];
    if (hotelId.value) void run(async()=>{ contracts.value=(await request('agent/contracts?hotelId='+encodeURIComponent(hotelId.value))).contracts; });
}
function proposeAction() {
    actionProposal.value=null; actionConfirmed.value=false; actionSaved.value='';
    void run(async()=>{ const result=await request('agent/contracts/propose',{hotelId:hotelId.value,...(bulk.value ? {contractIds:contractIds.value} : {contractId:contractId.value}),message:actionText.value}); actionExplanation.value=result.explanation; actionProposal.value=result.proposal; });
}
function approveAction() {
    if (!actionProposal.value || !actionConfirmed.value) return;
    const token=actionProposal.value.token;
    void run(async()=>{ const result=await request('agent/contracts/approve',{token,confirmed:true}); actionProposal.value=null; actionConfirmed.value=false; actionSaved.value=result.message; await refreshMysqlRecords('hotels'); actionSaved.value='Kontrat güncellendi. Açık otel verileri yenilendi.'; });
}
function reset() { editing.value=null; title.value=''; content.value=''; scope.value='personal'; }
function teach(answer:string) { reset(); content.value=answer; tab.value='knowledge'; }
function edit(row:Knowledge) { editing.value=row; title.value=row.title; content.value=row.content; scope.value=row.scope; }
function save() { void run(async()=>{ await request('agent/knowledge',{id:editing.value?.id,version:editing.value?.version,title:title.value,content:content.value,scope:scope.value}); reset(); await load(); }); }
function moderate(row:Knowledge,action:string) { void run(async()=>{ await request(`agent/knowledge/${row.id}`,{action,version:row.version}); await load(); }); }
function send() {
    if (!question.value.trim()) return;
    void run(async()=>{ const q=question.value; const result=await request('agent/chat',{message:q,hotelId:hotelId.value||null,contractId:contractId.value||null}); messages.value.push({question:q,answer:result.answer}); question.value=''; });
}
async function example(event:Event) {
    const input=event.target as HTMLInputElement; const file=input.files?.[0]; if (!file) return;
    await run(async()=>{
        if(file.size>4194304) throw new Error('Dosya en fazla 4 MB olabilir.');
        const document=await new Promise<string>((resolve,reject)=>{const reader=new FileReader();reader.onload=()=>resolve(String(reader.result).split(',')[1]);reader.onerror=()=>reject(new Error('Dosya okunamadı.'));reader.readAsDataURL(file);});
        content.value=(await request('agent/example',{filename:file.name,document})).content;
        if(!title.value) title.value=file.name.slice(0,150);
    }); input.value='';
}
onBeforeUnmount(()=>controller?.abort());
</script>
<template>
    <button class="agent-launch" type="button" @click="open ? open=false : show()" :aria-expanded="open" aria-controls="golf-agent-panel" aria-label="Ajan" title="Ajan">
        <img :src="agentLogo" alt="" width="72" height="72" draggable="false">
    </button>
    <aside v-show="open" id="golf-agent-panel" class="agent-panel" aria-label="Golf ajanı">
        <header><strong>Golf Ajanı</strong><button type="button" title="Kapat" aria-label="Ajanı kapat" @click="open=false">×</button></header>
        <nav aria-label="Ajan görünümleri"><button type="button" :aria-pressed="tab==='chat'" @click="tab='chat'">Sohbet</button><button type="button" :aria-pressed="tab==='knowledge'" @click="tab='knowledge'">Öğrettiklerim</button></nav>
        <button v-if="admin" type="button" :aria-pressed="tab==='actions'" @click="tab='actions'">Kontrat işlemi</button>
        <p v-if="error" role="alert" class="agent-error">{{error}}</p>
        <div v-show="tab==='chat' || tab==='actions'" class="agent-selectors">
            <label>Otel<select v-model="hotelId" :disabled="busy" @change="changeHotel"><option value="">Otel listesi</option><option v-for="hotel in hotels" :key="hotel.id" :value="hotel.id">{{hotel.name}}</option></select></label>
            <label v-if="tab==='actions'" class="action-confirm"><input v-model="bulk" type="checkbox" :disabled="busy">Toplu işlem</label>
            <label v-if="tab!=='actions' || !bulk">Kontrat<select v-model="contractId" :disabled="busy || !hotelId"><option value="">Kontrat seçin</option><option v-for="contract in contracts" :key="contract.id" :value="contract.id">{{contract.name}}</option></select></label>
            <template v-else>
                <label class="action-confirm"><input type="checkbox" :checked="contracts.length>0 && contractIds.length===contracts.length" :disabled="busy || !contracts.length || contracts.length>100" @change="contractIds=($event.target as HTMLInputElement).checked ? contracts.map(c=>c.id) : []">Tümünü seç ({{contracts.length}})</label>
                <div class="bulk-contracts"><label v-for="contract in contracts" :key="contract.id" class="action-confirm"><input v-model="contractIds" type="checkbox" :value="contract.id" :disabled="busy || contractIds.length>=100 && !contractIds.includes(contract.id)">{{contract.name}}</label></div>
                <small>{{contractIds.length}} kontrat seçildi / 100</small>
            </template>
        </div>
        <div class="agent-body" v-show="tab==='chat'">
            <p v-if="!configured && !busy" role="status">OpenAI bağlantısı yapılandırılmalı.</p>
            <div v-for="(message,i) in messages" :key="i" class="agent-message"><strong>{{message.question}}</strong><p>{{message.answer}}</p><button type="button" @click="teach(message.answer)">Düzelt ve öğret</button></div>
            <form @submit.prevent="send"><label>Sorunuz<textarea v-model="question" maxlength="4000" rows="4" :disabled="busy" required /></label><small>Gönderdiğiniz soru, seçili otelin kontratları ve onaylı örnekler OpenAI ile paylaşılır.</small><button type="submit" :disabled="busy || !configured || !question.trim()">Gönder</button><button v-if="busy" type="button" @click="controller?.abort()">Durdur</button></form>
        </div>
        <div v-if="admin" v-show="tab==='actions'" class="agent-body">
            <form @submit.prevent="proposeAction">
                <label>İstenen değişiklik<textarea v-model="actionText" maxlength="4000" rows="3" :disabled="busy" required /></label>
                <small>Seçili kontrat bilgileri ve isteğiniz OpenAI ile paylaşılır.</small>
                <button type="submit" :disabled="busy || !configured || (bulk ? !contractIds.length : !contractId) || !actionText.trim()">Öneri hazırla</button>
            </form>
            <p v-if="actionExplanation">{{actionExplanation}}</p>
            <section v-if="actionProposal" class="action-review">
                <strong>{{actionProposal.hotelName}}</strong><p>{{actionProposal.contractName}}</p>
                <table><thead><tr><th>Alan</th><th>Önce</th><th>Sonra</th></tr></thead><tbody><tr v-for="(change,i) in actionProposal.changes" :key="i"><td><strong v-if="change.contractName">{{change.contractName}}<br></strong>{{change.label}}</td><td>{{change.before || 'Boş'}} {{change.currency}}</td><td>{{change.after}} {{change.currency}}</td></tr></tbody></table>
                <label class="action-confirm"><input v-model="actionConfirmed" type="checkbox" :disabled="busy">Değişiklikleri kontrol ettim ve kaydedilmesini onaylıyorum.</label>
                <button type="button" :disabled="busy || !actionConfirmed" @click="approveAction">Onayla ve kaydet</button>
                <button type="button" :disabled="busy" @click="actionProposal=null;actionConfirmed=false">Vazgeç</button>
            </section>
            <p v-if="actionSaved" role="status">{{actionSaved}}</p>
        </div>
        <div class="agent-body" v-show="tab==='knowledge'">
            <form @submit.prevent="save">
                <label>Başlık<input v-model="title" maxlength="150" required :disabled="busy"></label>
                <label>Doğru bilgi veya örnek<textarea v-model="content" maxlength="6000" rows="6" required :disabled="busy" /></label>
                <label>Örnek belge<input type="file" accept=".docx,.xlsx" :disabled="busy" @change="example"></label>
                <label>Kapsam<select v-model="scope" :disabled="busy"><option value="personal">Kişisel</option><option value="shared">Ortak / onay bekler</option></select></label>
                <button type="submit" :disabled="busy">{{editing ? 'Değişikliği kaydet' : 'Bilgiyi kaydet'}}</button><button v-if="editing" type="button" @click="reset">Vazgeç</button>
            </form>
            <p v-if="!knowledge.length && !busy">Henüz kayıt yok.</p>
            <article v-for="row in knowledge" :key="row.id" class="knowledge-row"><strong>{{row.title}}</strong><small>{{row.scope==='shared'?'Ortak':'Kişisel'}} · {{row.status==='approved'?'Etkin':row.status==='pending'?'Onay bekliyor':'Devre dışı'}}</small><p>{{row.content}}</p><button v-if="row.owner===owner" type="button" :disabled="busy" @click="edit(row)">Düzenle</button><button v-if="admin && row.scope==='shared' && row.status==='pending'" type="button" :disabled="busy" @click="moderate(row,'approve')">Ortak kullanımı onayla</button><button v-if="row.status!=='disabled' && (row.owner===owner || admin && row.scope==='shared')" type="button" :disabled="busy" @click="moderate(row,'disable')">Devre dışı bırak</button></article>
        </div>
        <footer v-if="busy" role="status">İşleniyor…</footer>
    </aside>
</template>
<style scoped>
.bulk-contracts { max-height:140px; overflow:auto; border:1px solid #cbd9df; padding:6px; }
.agent-launch { position:fixed; right:14px; bottom:39px; z-index:90000; display:flex; align-items:center; justify-content:center; width:56px; height:56px; overflow:hidden; border:1px solid #b9cbd4; border-radius:50%; padding:0; background:#fff; color:#154c75; cursor:pointer; box-shadow:0 2px 8px #183d5020; }
.agent-launch img { flex:none; width:63px; height:63px; max-width:none; margin-left:-3.5px; margin-top:1.75px; clip-path:inset(8% 15% 15% 12%); transform-origin:50% 50%; animation:agent-nod 6s ease-in-out infinite; }
.agent-launch:hover { border-color:#648c9c; }
.agent-launch:focus-visible { outline:2px solid #17677f; outline-offset:3px; }
@keyframes agent-nod { 0%,75%,100% { transform:translateY(0) rotate(0); } 82% { transform:translateY(-3px) rotate(-5deg); } 90% { transform:translateY(0) rotate(4deg); } }
@media (prefers-reduced-motion:reduce) { .agent-launch img { animation:none; } }
.agent-panel { position:fixed; right:0; top:0; bottom:32px; width:min(430px,100vw); z-index:90001; display:flex; flex-direction:column; background:#f8fbfd; color:#183d50; border-left:1px solid #a9bfcb; box-shadow:-4px 0 16px #0002; font:13px Tahoma,sans-serif; }
header,nav { display:flex; align-items:center; gap:8px; padding:10px 12px; border-bottom:1px solid #cbd9df; } header { justify-content:space-between; } header strong { font-size:16px; }
.agent-body { overflow:auto; padding:12px; flex:1; min-height:0; } label { display:flex; flex-direction:column; gap:5px; margin-bottom:10px; } input,select,textarea { width:100%; box-sizing:border-box; border:1px solid #9cb5c4; border-radius:3px; background:#fff; color:#183d50; padding:8px; font:inherit; } textarea { resize:vertical; }
.agent-selectors { padding:10px 12px 0; border-bottom:1px solid #cbd9df; }
.action-review { padding:12px 0; } .action-review table { width:100%; table-layout:fixed; border-collapse:collapse; margin:12px 0; } .action-review th,.action-review td { text-align:left; padding:6px; border:1px solid #cbd9df; overflow-wrap:anywhere; } .action-confirm { flex-direction:row; align-items:flex-start; line-height:1.5; } .action-confirm input { width:16px; height:16px; flex:none; }
button { padding:7px 10px; border:1px solid #9cb5c4; border-radius:3px; background:#edf3f7; color:#154c75; cursor:pointer; } button:disabled { opacity:.55; cursor:default; } button[aria-pressed=true] { background:#17677f; color:white; }
form { display:flex; flex-direction:column; gap:8px; } small { display:block; color:#556a75; font-size:11px; line-height:1.5; } .agent-message,.knowledge-row { padding:12px 0; border-bottom:1px solid #cbd9df; margin-bottom:10px; } p,strong { white-space:pre-wrap; overflow-wrap:anywhere; line-height:1.6; } .agent-error { margin:8px 12px; padding:8px; color:#9b2020; background:#fff0f0; } footer { padding:8px 12px; border-top:1px solid #cbd9df; }
</style>

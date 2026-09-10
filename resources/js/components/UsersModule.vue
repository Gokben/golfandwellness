<script setup lang="ts">
import { voxConfirm } from '../voxDialogs';
import { useVoxMessages } from '../useVoxMessages';
import { onBeforeUnmount, reactive, ref } from 'vue';
import VoxActionButton from './VoxActionButton.vue';
import { useSetupUsers, userRoles, type SetupUser, type UserDraft } from '../useSetupUsers';

const { users, busy, ready, error: storageError, reload, save, remove } = useSetupUsers();
const formOpen = ref(false);
const editing = ref<SetupUser | null>(null);
const blankDraft = (): UserDraft => ({ name: '', surname: '', username: '', telephone: '', email: '', password: '', active: true, role: null });
const draft = reactive<UserDraft>(blankDraft());
const error = ref('');
const message = ref('');
const fullName = (user: SetupUser) => `${user.name} ${user.surname}`.trim();

function openForm(user?: SetupUser) {
    if (busy.value || !ready.value) return;
    editing.value = user ? { ...user } : null;
    Object.assign(draft, blankDraft(), user ? {
        name: user.name, surname: user.surname, username: user.username, telephone: user.telephone, email: user.email, active: user.active, role: user.role,
    } : {});
    error.value = ''; storageError.value = ''; message.value = ''; formOpen.value = true;
}
function closeForm() {
    if (busy.value) return;
    Object.assign(draft, blankDraft());
    editing.value = null; formOpen.value = false; error.value = '';
}
async function saveRecord() {
    if (busy.value || !ready.value) return;
    error.value = '';
    const fields: UserDraft = { ...draft, name: draft.name.trim(), surname: draft.surname.trim(), username: draft.username.trim().toLowerCase(), telephone: draft.telephone.trim(), email: draft.email.trim() };
    if (!fields.name || !/^[a-z0-9][a-z0-9._-]{1,79}$/.test(fields.username)) { error.value = 'Ad ve geçerli bir kullanıcı adı girin (en az 2 karakter; a-z, rakam, nokta, tire veya alt çizgi).'; return; }
    if (fields.email && !/^[^\s@]+@[^\s@]+$/.test(fields.email)) { error.value = 'Geçerli bir e-posta adresi girin.'; return; }
    if ((!editing.value || fields.password) && ([...fields.password].length < 8 || new TextEncoder().encode(fields.password).length > 72)) {
        error.value = 'Şifre en az 8 karakter, en fazla 72 bayt olmalıdır.'; return;
    }
    if (users.value.some(user => user.id !== editing.value?.id && user.username.toLowerCase() === fields.username)) {
        error.value = 'Bu kullanıcı adı zaten kullanılıyor.'; return;
    }
    if (await save(fields, editing.value)) {
        closeForm(); message.value = 'Kullanıcı kaydedildi.';
    }
}
async function deleteRecord(user: SetupUser) {
    if (busy.value || !ready.value || !await voxConfirm(`${fullName(user)} kullanıcısını listeden kaldırmak istediğinize emin misiniz?`)) return;
    message.value = '';
    if (await remove(user)) message.value = 'Kullanıcı listeden kaldırıldı.';
}
onBeforeUnmount(() => { draft.password = ''; });
useVoxMessages([storageError, error], [message]);
</script>

<template>
    <section class="users-module" aria-label="Kullanıcılar">
        <header>
            <h2>{{ formOpen ? (editing ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı') : 'Kullanıcı Listesi' }}</h2>
            <small>{{ busy ? 'MySQL işlemi sürüyor…' : ready ? 'MySQL bağlı' : 'MySQL bağlantısı bekleniyor' }}</small>
            <button v-if="formOpen" type="button" class="command" :disabled="busy" @click="closeForm">Listeye dön</button>
            <template v-else><button type="button" class="command" :disabled="busy" @click="reload">↻ Yenile</button><button type="button" class="command" :disabled="busy || !ready" @click="openForm()">＋ Yeni Kayıt</button></template>
        </header>
        <p class="local-note">Canlı giriş kuralı: yalnızca aktif Admin rolündeki admin2. Diğer rollerin yetkileri daha sonra tanımlanacak.</p>
        <p v-if="storageError" class="error" role="alert">{{ storageError }} <button type="button" class="command" :disabled="busy" @click="reload">Yeniden yükle</button></p>
        <p v-if="message" class="message" role="status">{{ message }}</p>
        <form v-if="formOpen" class="user-form" @submit.prevent="saveRecord">
            <fieldset :disabled="busy || !ready">
                <legend class="sr-only">Kullanıcı bilgileri</legend>
                <div class="user-fields">
                    <label>Ad<input v-model="draft.name" required maxlength="100" autocomplete="off"></label>
                    <label>Soyad<input v-model="draft.surname" maxlength="100" autocomplete="off"></label>
                    <label>Kullanıcı Adı<input v-model="draft.username" required minlength="2" maxlength="80" autocomplete="off" autocapitalize="none" spellcheck="false"></label>
                    <label>Telefon<input v-model="draft.telephone" type="tel" maxlength="40" autocomplete="off"></label>
                    <label>E-posta<input v-model="draft.email" type="text" inputmode="email" maxlength="254" autocomplete="off" autocapitalize="none" spellcheck="false"></label>
                    <label>Şifre<input v-model="draft.password" type="password" :required="!editing" minlength="8" maxlength="72" autocomplete="new-password" :placeholder="editing ? 'Değiştirmek için yeni şifre girin' : 'En az 8 karakter'" aria-describedby="user-password-hint"><small id="user-password-hint">{{ editing ? 'Boş bırakırsanız mevcut şifre korunur.' : 'Şifre güvenli özet olarak saklanır.' }}</small></label>
                    <label>Kullanıcı Rolü<select v-model="draft.role" aria-label="Kullanıcı Rolü" aria-describedby="user-role-hint"><option :value="null">Rol seçilmedi</option><option v-for="role in userRoles" :key="role" :value="role">{{ role }}</option></select><small id="user-role-hint">Rol yetkileri daha sonra tanımlanacak.</small></label>
                    <label class="active-field"><input v-model="draft.active" type="checkbox">Aktif</label>
                </div>
            </fieldset>
            <p v-if="error" class="error" role="alert">{{ error }}</p>
            <div class="commands"><button type="button" class="command" :disabled="busy" @click="closeForm">İptal</button><button type="submit" class="command" :disabled="busy || !ready">Kaydet</button></div>
        </form>
        <div v-else class="table-wrap">
            <table>
                <thead><tr><th scope="col">Ad - Soyad</th><th scope="col">Kullanıcı Adı</th><th scope="col">Telefon</th><th scope="col">E-posta</th><th scope="col" class="role-column">Kullanıcı Rolü</th><th scope="col" class="actions">İşlemler</th></tr></thead>
                <tbody>
                    <tr v-for="user in users" :key="user.id" :class="{ inactive: !user.active }"><td><b>{{ fullName(user) }}</b><span v-if="!user.active" class="inactive-label">Pasif</span></td><td>{{ user.username }}</td><td>{{ user.telephone }}</td><td>{{ user.email }}</td><td>{{ user.role || 'Rol seçilmedi' }}</td><td class="actions"><VoxActionButton action="edit" :aria-label="fullName(user) + ' düzenle'" :disabled="busy || !ready" @click="openForm(user)" /><VoxActionButton action="delete" :aria-label="fullName(user) + ' sil'" :disabled="busy || !ready" @click="deleteRecord(user)" /></td></tr>
                    <tr v-if="!users.length"><td colspan="6" class="empty">{{ busy ? 'Kullanıcılar yükleniyor…' : ready ? 'Henüz kullanıcı kaydı yok.' : 'MySQL bağlantısı bekleniyor.' }}</td></tr>
                </tbody>
            </table>
        </div>
        <footer>Toplam kayıt: <b>{{ users.length }}</b></footer>
    </section>
</template>

<style scoped>
.users-module { display: flex; flex-direction: column; height: calc(100% + 40px); margin: -20px; background: #f7fbfe; color: #365268; font: 11px Tahoma, Arial, sans-serif; }
header { display: flex; align-items: center; gap: 8px; min-height: 35px; padding: 3px 8px; border-bottom: 1px solid #86b6d7; background: linear-gradient(#f6fcff, #dceef9); }
h2 { margin: 0 auto 0 0; color: #07508a; font-size: 13px; }
.command { min-height: 27px; padding: 0 12px; border: 1px solid #86b6d7; border-radius: 3px; background: linear-gradient(#fff, #dfedf8); color: #164664; font: inherit; cursor: pointer; }
.command:hover:not(:disabled) { background: linear-gradient(#fff, #cce7fa); border-color: #218dc9; }
button:disabled { opacity: .5; cursor: not-allowed; }
.command:focus-visible, input:focus-visible, select:focus-visible { outline: 2px solid #168fd2; outline-offset: 2px; }
.local-note { margin: 0; padding: 6px 8px; border-bottom: 1px solid #d4e0e8; font-size: 10px; color: #59748a; background: #f1f8fd; }
.table-wrap { flex: 1; min-height: 0; overflow: auto; background: #fff; }
table { width: 100%; min-width: 760px; border-collapse: collapse; table-layout: fixed; }
th { position: sticky; top: 0; height: 28px; padding: 3px 9px; border-right: 1px solid #b5cddd; border-bottom: 1px solid #78aee0; background: linear-gradient(#f7fbff, #cee3f5); color: #164664; font-size: 10px; text-align: left; }
th:nth-child(1) { width: 21%; } th:nth-child(2) { width: 14%; } th:nth-child(3) { width: 14%; }
.role-column { width: 100px; }
td { height: 45px; padding: 4px 9px; border-bottom: 1px solid #d4e0e8; overflow-wrap: anywhere; }
tbody tr:nth-child(even) { background: #f4f9fc; }
tbody tr:hover { background: #eaf5fd; }
.actions { width: 88px; text-align: center; white-space: nowrap; }
.inactive { color: #687b88; } .inactive-label { display: inline-block; margin-left: 8px; font-size: 9px; color: #735426; }
.empty { text-align: center; }
footer { padding: 8px; border-top: 1px solid #87a9bd; background: #edf4f8; font-size: 10px; }
.user-form { flex: 1; min-height: 0; overflow: auto; padding: 20px; }
fieldset { border: 0; padding: 0; margin: 0; min-width: 0; }
.user-fields { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px 18px; }
.user-fields label { display: flex; flex-direction: column; gap: 6px; color: #164664; font-size: 10px; font-weight: bold; }
input:not([type=checkbox]), select { box-sizing: border-box; width: 100%; min-width: 0; height: 29px; padding: 4px 7px; border: 1px solid #78a9d3; border-radius: 0; background: #fff; color: #154c75; font: 11px Tahoma, Arial, sans-serif; }
.user-fields small { color: #59748a; font-weight: normal; }
.user-fields .active-field { flex-direction: row; align-items: center; gap: 8px; }
input[type=checkbox] { width: 17px; height: 17px; margin: 0; accent-color: #168fd2; }
.commands { display: flex; justify-content: flex-end; gap: 7px; margin-top: 32px; }
.error { margin: 0; padding: 8px; background: #fff2f2; color: #a71919; }
.message { margin: 0; padding: 8px; background: #eef8f1; color: #165b32; }
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }
@media (max-width: 700px) { .user-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 480px) { .user-fields { grid-template-columns: 1fr; } header { flex-wrap: wrap; } }
</style>

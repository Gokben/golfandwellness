<script setup lang="ts">
import { useVoxMessages } from '../useVoxMessages';
import { computed, ref, watch } from 'vue';
import { getGolfCoursePage, useGolfCourseStore, golfCourseKey, type GolfCourse } from '../golfCourses';
import VoxActionButton from './VoxActionButton.vue';
import CourseGamesModule from './CourseGamesModule.vue';

const emit = defineEmits<{ 'record-title': [name: string] }>();
const selectedCourse = ref<GolfCourse | null>(null);
watch(() => selectedCourse.value?.name ?? '', name => emit('record-title', name), { immediate: true });
const { records: courses, busy, ready, storageError, reload } = useGolfCourseStore();
watch(courses, rows => {
    if (selectedCourse.value) selectedCourse.value = rows.find(row => golfCourseKey(row) === golfCourseKey(selectedCourse.value!)) ?? null;
});
const requestedPage = ref(1);
const results = computed(() => getGolfCoursePage(courses.value, requestedPage.value));
watch(() => results.value.page, page => { requestedPage.value = page; });
useVoxMessages([storageError]);
</script>

<template>
    <CourseGamesModule v-if="selectedCourse" :course-key="selectedCourse.contractKey ?? selectedCourse.code" :course-name="selectedCourse.name" @back="selectedCourse = null" />
    <section v-else class="games-module" aria-label="Oyunlar">
        <header><h2>Oyunlar</h2><button type="button" :disabled="busy" @click="reload">↻ Yenile</button></header>
        <p v-if="storageError" role="alert">{{ storageError }}</p>
        <div class="games-table-wrap">
            <table aria-label="Oyunlar golf sahaları listesi">
                <thead><tr><th scope="col">Ad</th><th scope="col">Kod</th><th scope="col" class="action-column">Bağlantı</th></tr></thead>
                <tbody>
                    <tr v-for="course in results.rows" :key="course.contractKey ?? course.code">
                        <td><b>{{ course.name }}</b></td><td>{{ course.code }}</td>
                        <td class="action-column"><VoxActionButton action="link" :disabled="busy || !ready" :title="`${course.name} oyunlarını aç`" :aria-label="`${course.name} oyunlarını aç`" @click="selectedCourse = course" /></td>
                    </tr>
                    <tr v-if="!courses.length"><td colspan="3">{{ busy ? 'Golf sahaları yükleniyor…' : ready ? 'Golf sahası kaydı bulunamadı.' : 'Liste yüklenemedi. Yeniden deneyin.' }}</td></tr>
                </tbody>
            </table>
        </div>
        <footer>
            <nav aria-label="Oyunlar sayfaları">
                <button type="button" aria-label="İlk sayfa" :disabled="results.page === 1" @click="requestedPage = 1">«</button>
                <button type="button" aria-label="Önceki sayfa" :disabled="results.page === 1" @click="requestedPage = results.page - 1">‹</button>
                <button v-for="page in results.pageCount" :key="page" type="button" :aria-label="`Sayfa ${page}`" :aria-current="results.page === page ? 'page' : undefined" @click="requestedPage = page">{{ page }}</button>
                <button type="button" aria-label="Sonraki sayfa" :disabled="results.page === results.pageCount" @click="requestedPage = results.page + 1">›</button>
                <button type="button" aria-label="Son sayfa" :disabled="results.page === results.pageCount" @click="requestedPage = results.pageCount">»</button>
            </nav>
            <span aria-live="polite">Sayfa {{ results.page }} / {{ results.pageCount }} · Toplam kayıt: <b>{{ courses.length }}</b></span>
        </footer>
    </section>
</template>

<style scoped>
.games-module { display:flex; flex-direction:column; height:calc(100% + 40px); margin:-20px; background:#f7fbfe; color:#365268; font:11px Tahoma,Arial,sans-serif; }
header { display:flex; align-items:center; min-height:40px; padding:4px 10px; border-bottom:1px solid #86b6d7; background:linear-gradient(#f6fcff,#dceef9); }
h2 { margin:0; color:#07508a; font-size:13px; }
header button { margin-left:auto; height:27px; padding:0 10px; border:1px solid #86b6d7; border-radius:3px; background:linear-gradient(#fff,#dce9f2); color:#174c70; cursor:pointer; }
p[role="alert"] { padding:8px; margin:0; color:#a71919; background:#fff2f2; }
.games-table-wrap { flex:1; min-height:0; overflow:auto; background:#fff; }
table { width:100%; border-collapse:collapse; table-layout:fixed; }
th { position:sticky; top:0; z-index:1; height:30px; padding:4px 10px; border-bottom:1px solid #78aee0; background:linear-gradient(#f7fbff,#cee3f5); color:#164664; text-align:left; font-size:10px; }
th:first-child { width:58%; }
td { height:48px; padding:6px 10px; border-bottom:1px solid #d4e0e8; overflow-wrap:anywhere; }
tbody tr:nth-child(even) { background:#f4f9fc; }
tbody tr:hover { background:#eaf5fd; }
td b { color:#4f6472; }
.action-column { width:84px; text-align:center; }
footer { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px; padding:8px 10px; border-top:1px solid #87a9bd; background:#edf4f8; font-size:10px; }
nav { display:flex; gap:3px; }
nav button { min-width:27px; height:27px; padding:0 7px; border:1px solid #86b6d7; border-radius:2px; background:linear-gradient(#fff,#dce9f2); color:#174c70; font:11px Tahoma,Arial,sans-serif; cursor:pointer; }
nav button[aria-current="page"] { border-color:#087fc2; background:linear-gradient(#29a9e2,#058ccc); color:#fff; }
nav button:hover:not(:disabled):not([aria-current]) { background:#d8eefc; }
nav button:focus-visible { outline:2px solid #168fd2; outline-offset:2px; }
nav button:disabled { opacity:.45; cursor:not-allowed; }
</style>

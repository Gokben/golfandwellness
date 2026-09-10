import DateInput from './components/DateInput.vue';
import './setupDateLimits';
import { createApp } from 'vue';
import VoxDesktop from './components/VoxDesktop.vue';
import './setupVoxDialogs';

const mount = document.querySelector<HTMLElement>('#app');

if (mount) {
    createApp(VoxDesktop, {
        userName: mount.dataset.userName ?? 'Kullanıcı',
        loginUrl: mount.dataset.loginUrl ?? '/login',
    }).component('DateInput', DateInput).mount(mount);
}

import DateInput from './components/DateInput.vue';
import './setupDateLimits';
import '../css/app.css';
import './setupVoxDialogs';
import { createApp } from 'vue';
import VoxDesktop from './components/VoxDesktop.vue';

const mount = document.querySelector<HTMLElement>('#app');

if (mount) {
    createApp(VoxDesktop, {
        userName: mount.dataset.userName ?? 'Kullanıcı',
        loginUrl: mount.dataset.loginUrl ?? './login.html',
    }).component('DateInput', DateInput).mount(mount);
}

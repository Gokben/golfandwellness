import { createApp } from 'vue';
import VoxDialogHost from './components/VoxDialogHost.vue';

const mount = document.createElement('div');
document.body.append(mount);
const app = createApp(VoxDialogHost);
app.mount(mount);

if (import.meta.hot) import.meta.hot.dispose(() => { app.unmount(); mount.remove(); });

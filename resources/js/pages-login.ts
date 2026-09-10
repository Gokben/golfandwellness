import '../css/app.css';
import './setupVoxDialogs';

const form = document.querySelector<HTMLFormElement>('.classic-login-form');
const password = document.querySelector<HTMLInputElement>('#password');
const toggle = document.querySelector<HTMLButtonElement>('.password-toggle');

form?.addEventListener('submit', event => {
    event.preventDefault();
    window.location.assign('./desktop.html');
});

toggle?.addEventListener('click', () => {
    if (!password) return;
    password.type = password.type === 'password' ? 'text' : 'password';
    toggle.classList.toggle('is-visible');
});

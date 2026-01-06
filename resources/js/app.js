import { createInertiaApp } from '@inertiajs/svelte'
import { mount } from 'svelte'
import '../css/app.css';
// import { registerSW } from 'virtual:pwa-register';

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.svelte', { eager: true })
        return pages[`./Pages/${name}.svelte`]
    },
    setup({ el, App, props }) {
        mount(App, { target: el, props })
    },
})

// PWA service worker registration.
// Manual PWA service worker registration to ensure root scope and correct path.
if (import.meta.env.PROD && 'serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js', { scope: '/' })
        .then((reg) => {
            console.log('SW Registered!', reg);

            // Optional: Handle updates
            reg.addEventListener('updatefound', () => {
                const newWorker = reg.installing;
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        // New content available, force refresh or notify user
                        console.log('New SW content available, reloading...');
                        // window.location.reload(); 
                    }
                });
            });
        })
        .catch((err) => console.error('SW Register Error:', err));
}

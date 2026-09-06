import os from 'node:os';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

function lanIPv4() {
    if (process.env.VITE_HMR_HOST) {
        return process.env.VITE_HMR_HOST;
    }

    const skip = /^(lo|docker|br-|veth|virbr|cni|flannel|tun|tap|wg|podman)/;

    for (const [name, addrs] of Object.entries(os.networkInterfaces())) {
        if (skip.test(name)) {
            continue;
        }

        for (const addr of addrs ?? []) {
            const ipv4 = addr.family === 'IPv4' || addr.family === 4;
            if (!ipv4 || addr.internal) {
                continue;
            }

            return addr.address;
        }
    }

    return '127.0.0.1';
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: process.env.DEV_WIFI === '1'
        ? {
            host: '0.0.0.0',
            strictPort: true,
            cors: true,
            allowedHosts: true,
            hmr: {
                host: lanIPv4(),
            },
        }
        : {},
});

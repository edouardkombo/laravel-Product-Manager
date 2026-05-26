import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Patch the plugin to avoid fetching remote fonts
const originalLaravel = laravel;
const patchedLaravel = (options) => {
    const plugin = originalLaravel(options);
    const originalBuildStart = plugin.buildStart;
    plugin.buildStart = async function() {
        if (plugin.laravelPlugin?.fonts) {
            delete plugin.laravelPlugin.fonts;
        }
        if (originalBuildStart) return originalBuildStart.apply(this, arguments);
    };
    return plugin;
};

export default defineConfig({
    plugins: [
        patchedLaravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            external: [],
        },
    },
});

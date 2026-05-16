import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

process.env.LARAVEL_BYPASS_ENV_CHECK = '1';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    test: {
        environment: 'happy-dom',
        globals: true,
        coverage: {
            reporter: ['text', 'json-summary', 'json'],
            thresholds: {
                lines: 49,
                functions: 62,
                branches: 46,
                statements: 48,
            },
        },
    },
});

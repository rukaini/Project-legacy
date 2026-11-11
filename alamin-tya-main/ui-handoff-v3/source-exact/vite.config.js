import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/module-save-coordinator.js',
                'resources/js/assessment-builder.js',
                'resources/js/learner-tracking.js',
                'resources/js/analytics-charts.js',
                'resources/js/admin-analytics-charts.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

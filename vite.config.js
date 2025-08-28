import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/greenhostel.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        alias: {
            vue: "vue/dist/vue.esm-bundler.js",
            '@': resolve(__dirname, 'resources/js') // <-- alias opcional
        },
    },
})

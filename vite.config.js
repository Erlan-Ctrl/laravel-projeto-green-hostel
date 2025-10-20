import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'
import vuetify from 'vite-plugin-vuetify'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/greenhostel.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        vue(),
        vuetify({ styles: 'expose' }),
    ],
    resolve: {
        alias: {
            vue: "vue/dist/vue.esm-bundler.js",
            '@': resolve(__dirname, 'resources/js')
        },
    },
})

// resources/js/app.js
import { createApp } from 'vue'
import App from './App.vue'

// Vuetify
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { aliases, mdi } from 'vuetify/iconsets/mdi-svg'
import '@mdi/font/css/materialdesignicons.css'

// Theme: primary verde e secondary preto
const vuetify = createVuetify({
    components,
    directives,
    icons: {
        defaultSet: 'mdi',
        aliases,
        sets: { mdi },
    },
    theme: {
        defaultTheme: 'greenLight',
        themes: {
            greenLight: {
                dark: false,
                colors: {
                    primary: '#2b7a3a',
                    secondary: '#000000',
                    background: '#ffffff',
                    surface: '#ffffff',
                    'on-primary': '#ffffff',
                    'on-secondary': '#ffffff',
                }
            }
        }
    },
    defaults: {
        VBtn: { rounded: 'lg', elevation: 1 },
        VCard: { elevation: 2, rounded: 'lg' }
    }
})

createApp(App).use(vuetify).mount('#app')

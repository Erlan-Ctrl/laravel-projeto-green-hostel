<template>
    <v-app class="gh-app refined">
        <header class="gh-header">
            <v-parallax
                v-if="heroLoaded"
                :height="parallaxHeight"
                :src="hero"
                class="hero-parallax"
                role="img"
                aria-label="Fundo do site - Green Hostel"
            >
                <v-container class="header-inner" fluid>
                    <div class="brand">
                        <div class="logo-container" aria-hidden="true">
                            <img :src="logo" alt="Green Hostel" class="logo-img" />
                        </div>

                        <div class="brand-text">
                            <div class="title">Green Hostel</div>
                            <div class="subtitle">Belém — COP30 · Hotéis e Reservas</div>
                        </div>
                    </div>

                    <div class="nav-actions">
                        <template v-if="!userLogged">
                            <v-btn text class="mx-2 login-btn" color="white" @click="loginDialogOpen = true">Entrar</v-btn>
                        </template>

                        <template v-else>
                            <v-tooltip text>
                                <template #activator="{ props }">
                                    <v-avatar
                                        v-bind="props"
                                        size="36"
                                        class="me-2 user-avatar"
                                        style="cursor:pointer"
                                        @click="expandedAvatarOpen = true"
                                        :aria-label="`Avatar de ${user.name}`"
                                    >
                                        <v-img :src="user.avatar || avatarPlaceholder" />
                                    </v-avatar>
                                </template>
                                <span>{{ user.name }}</span>
                            </v-tooltip>

                            <v-btn text class="mx-2 logout-btn" @click="logout">Sair</v-btn>
                        </template>
                    </div>
                </v-container>

                <v-container class="hero" fluid>
                    <div class="hero-inner">
                        <div class="hero-copy">
                            <h1>Compare ofertas e reserve o melhor preço</h1>
                            <p class="hero-sub">Encontre hospedagem sustentável em Belém — rápido e confiável.</p>
                        </div>

                        <div class="hero-search" @mouseenter="pauseCarousel" @mouseleave="resumeCarousel">
                            <div class="big-search-wrapper" role="search" aria-label="Busca de hotéis">
                                <div class="big-search-item big-search-item--grow">
                                    <svg class="icon-left" viewBox="0 0 24 24" aria-hidden="true" width="20" height="20">
                                        <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zM9.5 14C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                                    </svg>

                                    <div class="big-search-content">
                                        <div class="label">Ponto de referência</div>
                                        <v-text-field
                                            v-model="q"
                                            placeholder="Belém do Pará"
                                            hide-details
                                            density="comfortable"
                                            class="big-text-field"
                                            clearable
                                            @keydown.enter="runSearchDebouncedImmediate"
                                        />
                                    </div>

                                    <v-icon class="clear-icon" v-if="q" @click="q = ''" aria-hidden="false" title="Limpar">mdi-close</v-icon>
                                </div>

                                <div class="big-search-item">
                                    <svg class="icon-left" viewBox="0 0 24 24" aria-hidden="true" width="20" height="20">
                                        <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14a2 2 0 002 2h14a2 2 0 002-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11zM7 11h5v5H7z"/>
                                    </svg>

                                    <div class="big-search-content" style="cursor:pointer;">
                                        <div class="label">Entrada/saída</div>
                                        <v-text-field
                                            v-model="dates"
                                            placeholder="Selecionar datas"
                                            hide-details
                                            density="comfortable"
                                            class="big-text-field"
                                            readonly
                                            @click="datePickerOpen = true"
                                        />
                                    </div>
                                </div>

                                <div class="big-search-item">
                                    <svg class="icon-left" viewBox="0 0 24 24" aria-hidden="true" width="20" height="20">
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>

                                    <div class="big-search-content" style="cursor:pointer;" @click="openGuestsDialog">
                                        <div class="label">Hóspedes e quartos</div>
                                        <v-text-field
                                            v-model="guests"
                                            placeholder="2 hóspedes, 1 quarto"
                                            hide-details
                                            density="comfortable"
                                            class="big-text-field"
                                            readonly
                                        />
                                    </div>
                                </div>

                                <div class="big-search-item big-search-button">
                                    <v-btn :loading="loading" color="primary" class="search-primary-btn" rounded @click="runSearch">
                                        Pesquisar
                                    </v-btn>
                                </div>
                            </div>

                            <div class="chips-row">
                                <v-chip
                                    v-for="opt in quickPrice"
                                    :key="opt.value"
                                    :variant="priceFilter === opt.value ? 'tonal' : 'flat'"
                                    size="small"
                                    class="mx-1"
                                    @click="selectPrice(opt.value)"
                                >
                                    {{ opt.label }}
                                </v-chip>

                                <v-chip class="mx-1" size="small" variant="outlined" @click="clearAll">Limpar</v-chip>
                            </div>
                        </div>
                    </div>
                </v-container>
            </v-parallax>

            <div v-else class="hero-parallax placeholder">
                <v-container class="header-inner" fluid>
                    <div class="brand">
                        <div class="logo-container" aria-hidden="true">
                            <img :src="logo" alt="Green Hostel" class="logo-img" />
                        </div>
                        <div class="brand-text">
                            <div class="title">Green Hostel</div>
                            <div class="subtitle">Belém — COP30 · Hotéis e Reservas</div>
                        </div>
                    </div>

                    <div class="nav-actions">
                        <template v-if="!userLogged">
                            <v-btn text class="mx-2 login-btn" color="white" @click="loginDialogOpen = true">Entrar</v-btn>
                        </template>

                        <template v-else>
                            <v-btn text class="mx-2 logout-btn" @click="logout">Sair</v-btn>
                        </template>
                    </div>
                </v-container>
            </div>
        </header>

        <v-main>
            <v-container class="container main-content" fluid>
                <section v-if="topHotels.length" class="top-rated refined"></section>

                <div class="results-meta" v-if="!loading">
                    <div>{{ total }} opções encontradas</div>
                    <div>
                        <v-select
                            v-model="sortBy"
                            :items="sortItems"
                            item-title="label"
                            item-value="value"
                            dense
                            hide-details
                            style="max-width:220px"
                        />
                    </div>
                </div>

                <section class="list-layout" aria-live="polite">
                    <div v-if="loading" class="skeleton-grid">
                        <div v-for="n in 6" :key="n" class="grid-item">
                            <v-skeleton-loader type="card" />
                        </div>
                    </div>

                    <div v-else class="cards-grid">
                        <div class="grid-item" v-for="hotel in sortedHotels" :key="hotel.place_id">
                            <v-card class="hotel-card" elevation="1" rounded>
                                <div class="media-wrap">
                                    <v-img :src="hotel.photo" class="media-img" height="180" cover />
                                </div>

                                <v-card-text class="card-body">
                                    <div>
                                        <div class="hotel-title">{{ hotel.name }}</div>
                                        <div class="hotel-sub">{{ hotel.vicinity }}</div>
                                    </div>

                                    <div class="card-bottom">
                                        <div class="left-meta">
                                            <div class="hotel-amenities">{{ (hotel.types || []).slice(0,3).join(' • ') }}</div>
                                        </div>

                                        <div class="right-meta"></div>
                                    </div>

                                    <div class="price-hover">R$ <span class="price-amount">{{ hotel.estimated_price }}</span></div>
                                </v-card-text>

                                <v-card-actions>
                                    <v-btn text small @click="openCardDialog(hotel)">Detalhes</v-btn>
                                </v-card-actions>
                            </v-card>
                        </div>
                    </div>

                    <div v-if="!loading && hotels.length === 0" class="empty">Nenhum hotel encontrado — tente ajustar filtros</div>

                    <div class="load-more" v-if="!loading && (page * perPage) < total">
                        <v-btn outlined @click="loadMore">Carregar mais</v-btn>
                    </div>
                </section>
            </v-container>
        </v-main>

        <v-dialog v-model="dialog.open" width="920" persistent>
            <v-card class="dialog-card">
                <v-btn icon class="dialog-close" @click="dialog.open = false" aria-label="Fechar diálogo">
                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                        <path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                    </svg>
                </v-btn>

                <v-row class="pa-4" no-gutters>
                    <v-col cols="12" md="6" class="image-col">
                        <v-img :src="dialog.hotel?.photo || dialogImagePlaceholder" class="dialog-photo" cover />
                    </v-col>

                    <v-col cols="12" md="6" class="pl-4 d-flex flex-column">
                        <div class="dialog-heading">
                            <h3 class="mb-2">{{ dialog.hotel?.name }}</h3>
                            <div class="dialog-meta">{{ dialog.hotel?.vicinity }}</div>
                            <div class="mt-3">★ {{ dialog.hotel?.rating || '—' }} — {{ dialog.hotel?.user_ratings_total || 0 }} avaliações</div>
                            <p class="dialog-desc mt-4">{{ dialog.hotel?.description || dialog.hotel?.short_description || 'Descrição não disponível.' }}</p>
                        </div>

                        <div class="dialog-actions mt-auto">
                            <v-btn class="confirm-btn" @click="confirmFromDialog">Confirmar reserva</v-btn>
                        </div>
                    </v-col>

                    <v-col cols="12" class="pt-4">
                        <div id="dialog-map" class="dialog-map large"></div>
                    </v-col>
                </v-row>
            </v-card>
        </v-dialog>

        <v-dialog v-model="loginDialogOpen" max-width="420">
            <v-card>
                <v-card-title>Conectar-se</v-card-title>
                <v-card-text>
                    <v-text-field v-model="loginName" label="Nome" />
                    <v-text-field v-model="loginAvatar" label="URL da imagem (avatar)" />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn text @click="loginDialogOpen = false">Cancelar</v-btn>
                    <v-btn color="primary" @click="connect">Conectar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="expandedAvatarOpen" max-width="480">
            <v-card class="pa-6 d-flex flex-column align-center">
                <v-avatar size="200">
                    <v-img :src="user.avatar || avatarPlaceholder" />
                </v-avatar>
                <div class="mt-4 font-weight-bold">{{ user.name }}</div>
                <v-card-actions class="mt-2">
                    <v-btn text @click="expandedAvatarOpen = false">Fechar</v-btn>
                    <v-btn color="primary" @click="goToProfile">Ir para perfil</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="datePickerOpen" max-width="420">
            <v-card>
                <v-card-title>Selecionar datas</v-card-title>
                <v-card-text>
                    <v-date-picker v-model="datesRange" range />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn text @click="datePickerOpen = false">Cancelar</v-btn>
                    <v-btn color="primary" @click="applyDates">OK</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="guestsDialogOpen" max-width="320">
            <v-card>
                <v-card-title>Hóspedes</v-card-title>
                <v-card-text>
                    <v-select :items="guestOptions" v-model="guests" label="Hóspedes" />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn text @click="guestsDialogOpen = false">Fechar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-app>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import axios from 'axios';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import heroImg from '../images/greenhostel.png';
import logoImg from '../images/logogreenhostel.png';
const hero = heroImg || '/images/greenhostel.png';
const logo = logoImg || '/images/logogreenhostel.png';

function debounce(fn, wait = 350) {
    let timer = null;
    function debounced(...args) {
        if (timer) clearTimeout(timer);
        timer = setTimeout(() => { timer = null; fn.apply(this, args); }, wait);
    }
    debounced.cancel = () => { if (timer) { clearTimeout(timer); timer = null; } };
    return debounced;
}

const q = ref('');
const dates = ref('');
const datesRange = ref(null);
const guests = ref('2');
const priceFilter = ref('any');
const hotels = ref([]);
const loading = ref(false);
const totalRef = ref(0);
const page = ref(1);
const perPage = ref(12);
const sortBy = ref('relevance');
const dialog = ref({ open: false, hotel: null });
const mapInstance = ref(null);
const parallaxHeight = ref(520);
const datePickerOpen = ref(false);
const guestsDialogOpen = ref(false);
const loginDialogOpen = ref(false);
const loginName = ref('');
const loginAvatar = ref('');
const userLogged = ref(false);
const user = ref({ name: '', avatar: '' });
const expandedAvatarOpen = ref(false);
const avatarPlaceholder = 'https://cdn.vuetifyjs.com/images/john.jpg';
const dialogImagePlaceholder = 'https://images.unsplash.com/photo-1501117716987-c8e2e0b7f6d3?auto=format&fit=crop&w=1400&q=60';

function connect() {
    if (!loginName.value) { loginName.value = 'Usuário'; }
    user.value.name = loginName.value;
    user.value.avatar = loginAvatar.value || avatarPlaceholder;
    userLogged.value = true;
    loginDialogOpen.value = false;
    loginName.value = '';
    loginAvatar.value = '';
}
function logout() {
    userLogged.value = false;
    user.value = { name: '', avatar: '' };
    expandedAvatarOpen.value = false;
}
function goToProfile() {
    expandedAvatarOpen.value = false;
    alert('Abrir página de perfil (placeholder).');
}

const guestOptions = ['1', '2', '3', '4+'];
const quickPrice = [
    { label: 'Até R$150', value: 'cheap' },
    { label: 'R$150–350', value: 'mid' },
    { label: 'Premium', value: 'premium' }
];
const sortItems = [
    { label: 'Relevância', value: 'relevance' },
    { label: 'Preço: menor', value: 'price_asc' },
    { label: 'Preço: maior', value: 'price_desc' },
    { label: 'Avaliação', value: 'rating' }
];

const total = computed(() => totalRef.value || hotels.value.length);

function estimatePriceFromPriceLevel(level) {
    if (level === undefined || level === null) return Math.floor(120 + Math.random() * 200);
    const base = [60, 120, 220, 420, 800];
    return base[Math.min(Math.max(Math.round(level), 0), 4)];
}
function pickImage(item) {
    const econ = 'https://images.unsplash.com/photo-1560448204-e0ee9a8a3ad9?auto=format&fit=crop&w=1200&q=60';
    const mid = 'https://images.unsplash.com/photo-1542317854-5b41c0b3fded?auto=format&fit=crop&w=1200&q=60';
    const premium = 'https://images.unsplash.com/photo-1501117716987-c8e2e0b7f6d3?auto=format&fit=crop&w=1400&q=60';
    const level = item.price_level;
    if (typeof level === 'number') {
        if (level <= 1) return econ;
        if (level === 2) return mid;
        return premium;
    }
    const price = item.estimated_price || estimatePriceFromPriceLevel(item.price_level);
    if (price < 150) return econ;
    if (price < 350) return mid;
    return premium;
}
function normalizeItems(items) {
    return items.map(i => {
        const est = i.estimated_price ?? estimatePriceFromPriceLevel(i.price_level);
        const photo = i.photo || pickImage({ ...i, estimated_price: est });
        return { ...i, estimated_price: est, photo, types: i.types || [] };
    });
}

async function getPlaces({ page: p = 1 } = {}) {
    loading.value = true;
    try {
        const params = {
            q: q.value || undefined,
            location: undefined,
            price_filter: priceFilter.value !== 'any' ? priceFilter.value : undefined,
            page: p,
            per_page: perPage.value
        };
        const res = await axios.get('/api/places', { params });
        const items = (res.data && res.data.data) ? res.data.data : [];
        const normalized = normalizeItems(items);
        if (p === 1) hotels.value = normalized;
        else hotels.value = hotels.value.concat(normalized);
        totalRef.value = (res.data && res.data.total) ? res.data.total : (hotels.value.length);
        page.value = p;
    } catch (err) {
        console.error('Erro ao buscar places', err);
        hotels.value = [];
        totalRef.value = 0;
    } finally {
        loading.value = false;
    }
}

const runSearchDebounced = debounce(() => getPlaces({ page: 1 }), 350);
function runSearchDebouncedImmediate() { runSearchDebounced.cancel(); getPlaces({ page: 1 }); }
function runSearch() { runSearchDebounced(); }
function loadMore() { if (loading.value) return; getPlaces({ page: page.value + 1 }); }

const sortedHotels = computed(() => {
    const arr = hotels.value.slice();
    if (sortBy.value === 'price_asc') arr.sort((a,b)=> (a.estimated_price||0)-(b.estimated_price||0));
    else if (sortBy.value === 'price_desc') arr.sort((a,b)=> (b.estimated_price||0)-(a.estimated_price||0));
    else if (sortBy.value === 'rating') arr.sort((a,b)=> (b.rating||0)-(a.rating||0));
    return arr;
});
const topHotels = computed(() => hotels.value.slice().sort((a,b)=>(b.rating||0)-(a.rating||0)).slice(0,6));

function clearAll() {
    q.value=''; dates.value=''; guests.value='2'; priceFilter.value='any'; hotels.value=[]; totalRef.value=0;
    runSearchDebouncedImmediate();
}
function onReserve(hotel) { }
async function openCardDialog(hotel) {
    dialog.value.hotel = hotel;
    dialog.value.open = true;
    await nextTick();
    initDialogMap();
}
function confirmFromDialog() {
    if (dialog.value.hotel) { alert(`Reserva confirmada para ${dialog.value.hotel.name}`); dialog.value.open = false; destroyDialogMap(); }
}
function selectPrice(val) { priceFilter.value = val; runSearchDebouncedImmediate(); }

function initDialogMap() {
    const el = document.getElementById('dialog-map');
    if (!el || !dialog.value.hotel) return;
    try { destroyDialogMap(); } catch(e) {}
    if (!L) return;
    el.style.height='360px';
    const lat = dialog.value.hotel.lat ?? -1.4558;
    const lng = dialog.value.hotel.lng ?? -48.5039;
    mapInstance.value = L.map(el, { zoomControl:true, attributionControl:false }).setView([lat,lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapInstance.value);
    const mk = L.marker([lat,lng]).addTo(mapInstance.value);
    mk.bindPopup(`<b>${dialog.value.hotel.name}</b><br>${dialog.value.hotel.vicinity}`).openPopup();
    setTimeout(()=>{ try{ mapInstance.value.invalidateSize() }catch(e){} }, 180);
}
function destroyDialogMap() { if (mapInstance.value){ try{ mapInstance.value.remove() }catch(e){} mapInstance.value=null; } }

const carouselIndex = ref(0);
const paused = ref(false);
let windowTimerId = null;
function startWindowTimer(){
    stopWindowTimer();
    windowTimerId = setInterval(()=>{
        if (paused.value) return;
        const n = Math.max(1, topHotels.value.length);
        carouselIndex.value = (carouselIndex.value + 1) % n;
    }, 4500);
}
function stopWindowTimer(){ if(windowTimerId){ clearInterval(windowTimerId); windowTimerId = null; } }
function pauseCarousel(){ paused.value = true; }
function resumeCarousel(){ paused.value = false; }

onMounted(()=>{
    nextTick(()=>{
        heroLoaded.value = !!hero;
    });
    getPlaces({ page: 1 });
    startWindowTimer();
    window.addEventListener('keydown', onKeyDown);
});
onBeforeUnmount(()=>{
    stopWindowTimer();
    window.removeEventListener('keydown', onKeyDown);
});
function onKeyDown(e){ if(e.key==='Escape'){ q.value=''; dates.value=''; } }
watch(()=> topHotels.value.length, (n)=>{ if(carouselIndex.value>=n) carouselIndex.value=0; });

function applyDates(){
    if(datesRange.value){
        let start = '';
        let end = '';
        if (Array.isArray(datesRange.value)) {
            start = datesRange.value[0] || '';
            end = datesRange.value[1] || '';
        } else if (datesRange.value && typeof datesRange.value === 'object') {
            start = datesRange.value.start || datesRange.value[0] || '';
            end = datesRange.value.end || datesRange.value[1] || '';
        } else {
            start = datesRange.value || '';
        }
        dates.value = `${start} — ${end}`.trim();
    }
    datePickerOpen.value = false;
}
function openGuestsDialog(){ guestsDialogOpen.value = true; }

const heroLoaded = ref(false);
</script>

<style scoped>
:root{ --muted: rgba(11,19,17,0.6); --search-h:64px; box-sizing: border-box; }
*, *::before, *::after { box-sizing: inherit; }

.hero-parallax {
    border-bottom-left-radius: 12px;
    border-bottom-right-radius: 12px;
    overflow: visible;
    background-repeat: no-repeat;
    background-size: cover;
    background-position: center;
    position: relative;
}
.v-parallax__image, .v-img__image { transform: none !important; transition: none !important; }

.header-inner{ display:flex; align-items:center; justify-content:space-between; padding:16px 28px; background:transparent; z-index:10; position:relative; }
.brand{ display:flex; gap:12px; align-items:center; }

.logo-container{ width:56px; height:56px; background:#fff; border-radius:8px; display:flex; align-items:center; justify-content:center; padding:6px; box-shadow: 0 8px 20px rgba(0,0,0,0.08); z-index:20; }
.logo-img{ width:100%; height:100%; border-radius:6px; object-fit:contain; display:block; }

.brand-text .title{ font-weight:800; font-size:18px; color: #fff; }
.brand-text .subtitle{ color: rgba(255,255,255,0.9); font-weight:600; font-size:13px; }

.nav-actions { display:flex; align-items:center; gap:8px; z-index:12; }
.user-avatar { box-shadow: 0 4px 14px rgba(0,0,0,0.25); }
.logout-btn { color: rgba(255,255,255,0.95); }
.login-btn { color: rgba(255,255,255,0.95); }

.hero{ padding: 0 24px 28px 24px; z-index:9; position:relative; }
.hero-inner{ max-width:1180px; margin:0 auto; display:flex; flex-direction:column; gap:14px; padding-top:28px; }
.hero-copy h1{ margin:0; font-size:26px; font-weight:800; color: #fff; text-shadow: 0 6px 20px rgba(0,0,0,0.35); }
.hero-sub{ margin:0; color: rgba(255,255,255,0.85); }

.big-search-wrapper{
    display:flex;
    align-items:center;
    gap:12px;
    background: rgba(255,255,255,0.97);
    border-radius: 40px;
    padding: 10px;
    box-shadow: 0 8px 30px rgba(5,20,10,0.04);
    border: 1px solid rgba(0,0,0,0.06);
    width: 100%;
    max-width: 1180px;
    margin: 18px auto 0 auto;
    box-sizing: border-box;
    position:relative;
    z-index:11;
    align-items: center;
}

.big-search-item{ position:relative; display:flex; align-items:center; gap:12px; padding: 8px 18px; min-height: var(--search-h); background: transparent; border-radius: 28px; flex: 0 0 260px; box-sizing: border-box; border-right: 1px solid rgba(0,0,0,0.04); min-width: 0; }
.big-search-item--grow{ flex: 1 1 480px; min-width: 0; }
.big-search-button{ border-right: none; flex: 0 0 auto; padding-right: 12px; justify-content:flex-end; }
.big-search-content{ display:flex; flex-direction:column; flex:1; min-width:0; }
.label{ font-size: 11px; color: #6b7280; margin-bottom: 4px; }

.big-search-item .icon-left{
    position:absolute;
    left:18px;
    top:50%;
    transform:translateY(-50%);
    pointer-events:none;
    color:#6b7280;
    display:flex;align-items:center;justify-content:center;
}
.big-search-item .icon-left svg{ width:20px;height:20px; display:block; }
.big-search-item .clear-icon{
    position:absolute;
    right:12px;
    top:50%;
    transform:translateY(-50%);
    color:#9ca3af;
    cursor:pointer;
    z-index:12;
    display:flex;align-items:center;justify-content:center;
}

.big-search-wrapper ::v-deep(.v-field__background),
.big-search-wrapper ::v-deep(.v-field__control),
.big-search-wrapper ::v-deep(.v-field__outline) {
    background: transparent !important;
    box-shadow: none !important;
    border: none !important;
}

.big-search-wrapper ::v-deep(.v-field__input),
.big-search-wrapper ::v-deep(input) {
    background: transparent !important;
    height: auto !important;
}

.big-search-item--grow ::v-deep(input),
.big-search-item--grow ::v-deep(.v-field__input),
.big-search-item--grow ::v-deep(.v-input input) {
    padding-left: 56px !important;
}
.big-search-item:not(.big-search-item--grow) ::v-deep(input),
.big-search-item:not(.big-search-item--grow) ::v-deep(.v-field__input),
.big-search-item:not(.big-search-item--grow) ::v-deep(.v-input input) {
    padding-left: 44px !important;
}

.search-primary-btn{
    min-height: calc(var(--search-h) - 12px);
    height: calc(var(--search-h) - 12px);
    padding-left: 22px;
    padding-right: 22px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
}

.chips-row{ margin-top:12px; display:flex; gap:8px; justify-content:center; flex-wrap:wrap; }

.cards-grid{
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    width: 100%;
    align-items: start;
    grid-auto-rows: 1fr;
}
.grid-item { display: flex; align-self: stretch; }
.hotel-card { display: flex; flex-direction: column; width: 100%; height: 100%; overflow: hidden; position:relative; transition: transform .18s ease, box-shadow .18s ease; }
.hotel-card:hover{ transform: translateY(-6px); box-shadow: 0 18px 45px rgba(10,20,30,0.12); }
.card-body{ flex: 1 1 auto; display:flex; flex-direction:column; justify-content:space-between; padding:12px 16px 18px; }

.media-wrap{
    width:100%;
    height:180px;
    overflow:hidden;
    border-bottom-left-radius: 6px;
    border-bottom-right-radius: 6px;
    background:#f3f3f3;
    display:block;
}
.media-wrap .v-img__image{ object-fit:cover !important; width:100% !important; height:100% !important; object-position:center center !important; }
.media-img{ width:100%; height:100%; display:block; }

.hotel-title{ font-weight:800; font-size:15px; margin-bottom:6px; }
.hotel-sub{ color: rgba(11,19,17,0.65); font-size:13px; margin-bottom:8px; line-height:1.2; }
.card-bottom{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:12px; }
.hotel-amenities{ color:rgba(11,19,17,0.55); font-size:13px; }

.price-hover{ position:absolute; right:18px; bottom:58px; background: linear-gradient(90deg, rgba(34,197,94,0.95), rgba(16,185,129,0.95)); color:white; padding:8px 12px; border-radius:6px; font-weight:800; opacity:0; transform: translateY(8px); transition: opacity .18s ease, transform .18s ease; }
.hotel-card:hover .price-hover{ opacity:1; transform: translateY(0); }
.hotel-card .v-card-actions{ justify-content:flex-end; padding:12px 16px; }

.results-meta{ display:flex; justify-content:space-between; align-items:center; margin:8px 0 16px; color:var(--muted); font-weight:700; }
.empty{ text-align:center; padding:18px; border-radius:8px; background:linear-gradient(180deg, rgba(245,250,245,0.6), rgba(255,255,255,0.4)); color:var(--muted); font-weight:700; }
.load-more{ text-align:center; margin:18px 0; }

.big-search-wrapper ::v-deep(.v-field--variant-filled .v-field__control),
.big-search-wrapper ::v-deep(.v-field--variant-filled .v-field__background),
.big-search-wrapper ::v-deep(.v-field--variant-filled .v-field__overlay),
.big-search-wrapper ::v-deep(.v-field__background),
.big-search-wrapper ::v-deep(.v-field__control),
.big-search-wrapper ::v-deep(.v-input__control),
.big-search-wrapper ::v-deep(.v-input__slot),
.big-search-wrapper ::v-deep(.v-field__outline),
.big-search-wrapper ::v-deep(.v-field__control::before),
.big-search-wrapper ::v-deep(.v-field__background::before),
.big-search-wrapper ::v-deep(.v-field__container) {
    background: transparent !important;
    box-shadow: none !important;
    border: none !important;
}

.big-search-wrapper ::v-deep(.v-field__overlay),
.big-search-wrapper ::v-deep(.v-field__outline){
    display: none !important;
    opacity: 0 !important;
    pointer-events: none !important;
}

.big-search-wrapper ::v-deep(input),
.big-search-wrapper ::v-deep(.v-field__input),
.big-search-wrapper ::v-deep(.v-text-field .v-input__slot),
.big-search-wrapper ::v-deep(.v-field__field) {
    background: transparent !important;
    box-shadow: none !important;
    color: inherit !important;
}

.big-search-item--grow ::v-deep(.v-field__input),
.big-search-item--grow ::v-deep(input) {
    padding-left: 56px !important;
    height: 44px !important;
}
.big-search-item:not(.big-search-item--grow) ::v-deep(.v-field__input),
.big-search-item:not(.big-search-item--grow) ::v-deep(input) {
    padding-left: 44px !important;
    height: 44px !important;
}

.big-search-wrapper { align-items: center; }
.big-search-item { align-items: center; display: flex; }
.big-search-item .big-search-content { display:flex; flex-direction:column; justify-content:center; }
.big-search-item .v-field__control, .big-search-item ::v-deep(.v-field__field) { display:flex !important; align-items:center !important; height:100% !important; }
.big-search-button { display:flex; align-items:center; justify-content:center; }

.big-search-item .icon-left, .big-search-item .clear-icon { top: 50%; transform: translateY(-50%); }

.big-search-wrapper ::v-deep(.v-field__input) { display:flex; align-items:center; }

.big-search-wrapper ::v-deep(.v-field__control),
.big-search-wrapper ::v-deep(.v-field__input) {
    display: flex !important;
    align-items: center !important;
    height: 48px !important; /* garante altura uniforme */
    padding-top: 0 !important;
    padding-bottom: 0 !important;
}

.big-search-wrapper ::v-deep(input.v-field__input),
.big-search-wrapper ::v-deep(.v-field__input input) {
    line-height: 1 !important;
    margin-top: 2px !important; /* ajuste fino — pode ser 1 ou 2 px */
}

.big-search-wrapper ::v-deep(.v-field__input::placeholder) {
    line-height: normal;
    transform: translateY(1px);
}

.dialog-card{ position:relative; overflow: visible; }
.dialog-close{ position:absolute; top:8px; right:8px; z-index:10010; background: rgba(255,255,255,0.98); box-shadow: 0 6px 18px rgba(0,0,0,0.08); border-radius: 8px; padding:6px; }
.dialog-close svg{ display:block; }
.dialog-close path{ fill: rgba(11,19,17,0.9); }
.dialog-photo{ width:100%; height:420px; display:block; background:#f3f3f3; border-radius:8px; z-index:1; }
.dialog-photo .v-img__image{ object-fit:cover !important; width:100% !important; height:100% !important; object-position:center center !important; }
.dialog-map.large{ height:360px; border-radius:8px; box-shadow: 0 12px 30px rgba(0,0,0,0.06); overflow:hidden; }
.dialog-desc{ color: rgba(11,19,17,0.85); margin-top:12px; line-height:1.45; }
.confirm-btn{ background: #2e7d32; color:#fff !important; box-shadow: 0 8px 20px rgba(0,0,0,0.08); border: none !important; }
.dialog-actions .v-btn{ min-width:140px; }

.dialog-actions{ width:100%; display:flex; justify-content:flex-end; gap:12px; align-items:center; }

.dialog-map{ width:100%; border-radius:8px; background:#f3f5f6; height:240px; }
.dialog-actions{ display:flex; gap:8px; align-items:center; }

@media (max-width: 1200px) {
    .cards-grid{ grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 700px) {
    .cards-grid{ grid-template-columns: repeat(1, 1fr); }
    .hero-parallax { height: 360px; }
    .big-search-wrapper{ flex-direction:column; padding:12px; border-radius:16px; gap:8px; margin-top: 10px; }
    .big-search-item{ width:100%; border-right:none; padding:8px 12px; min-height:52px; }
    .big-search-item--grow{ min-width:0; }
    .media-wrap{ height:140px; }
    .card-body{ padding:12px; }
    .main-content{ padding-left:12px; padding-right:12px; max-width:100%; margin-top: 8px; }
    .big-search-item .icon-left{ left:12px; }
    .big-search-item .clear-icon{ right:8px; }
    .big-search-wrapper ::v-deep(input),
    .big-search-wrapper ::v-deep(.v-field__input),
    .big-search-wrapper ::v-deep(.v-input input) { padding-left:44px !important; }
    .search-primary-btn { min-height:40px !important; height:40px !important; }
    .dialog-photo{ height:260px; }
}

.hero-parallax.placeholder{ height: 520px; display:flex; align-items:center; background: linear-gradient(180deg, #437a4a 0%, #2b5f38 100%); }
</style>

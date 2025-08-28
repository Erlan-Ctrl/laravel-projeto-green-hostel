<template>
  <div :class="['app-root', dark ? 'theme-dark' : 'theme-light']">
    <header class="site-header">
      <div class="brand">
        <div class="logo">GH</div>
        <div>
          <h1>Green Hostel</h1>
          <small>Belém — COP30 · Hotéis e Reservas</small>
        </div>
      </div>

      <div class="controls-header">
        <label class="dark-toggle" title="Alternar modo escuro">
          <input type="checkbox" v-model="dark" />
          <span>Modo escuro</span>
        </label>
      </div>
    </header>

    <main class="search-bar container">
      <section class="controls">
        <div class="search-input">
          <input class="form-control" v-model="query" placeholder="Buscar por nome ou local..." @keyup.enter="search" />
          <button class="btn btn-success" @click="search" :disabled="loading">Buscar</button>
        </div>

        <div class="search-input small">
          <input class="form-control" v-model="locationQuery" placeholder="Bairro ou endereço (opcional)" />
        </div>

        <div class="search-input small">
          <select v-model="priceFilter" class="form-control">
            <option value="any">Preço</option>
            <option value="cheap">Baixo</option>
            <option value="mid">Médio</option>
            <option value="premium">Premium</option>
          </select>
        </div>

        <div style="margin-left:auto">
          <button class="btn btn-outline-ghost" @click="clearSearch">Limpar</button>
        </div>
      </section>

      <section class="list-layout">
        <div v-if="loading" class="skeleton shiny">Carregando hotéis...</div>

        <div class="cards">
          <article v-for="hotel in hotels" :key="hotel.place_id" class="hotel-card" :class="{ expanded: selected && selected.place_id === hotel.place_id }">
            <div class="card-top" @click="toggleSelect(hotel)" role="button" tabindex="0" @keydown.enter="toggleSelect(hotel)">
              <img :src="hotel.photo || placeholder" alt="" class="thumb" />
              <div class="meta">
                <div class="hotel-title">{{ hotel.name }}</div>
                <div class="hotel-sub">{{ hotel.vicinity }}</div>
                <div class="row-between">
                  <div class="stars">
                    <span v-for="n in Math.round(hotel.rating || 0)" :key="n">★</span>
                    <small>{{ hotel.user_ratings_total || 0 }} avaliações</small>
                  </div>
                  <div class="price">R$ {{ hotel.estimated_price }} <small>/ noite</small></div>
                </div>
              </div>
            </div>

              <div class="card-expanded" v-if="selected && selected.place_id === hotel.place_id">
              <div class="expanded-grid">
                <div class="expanded-photo">
                  <img :src="hotel.photo || placeholder" alt="" />
                </div>
                <div class="expanded-info">
                  <h3>{{ hotel.name }}</h3>
                  <p><strong>Endereço:</strong> {{ hotel.vicinity }}</p>
                  <p><strong>Rating:</strong> {{ hotel.rating || '—' }} ({{ hotel.user_ratings_total || 0 }} avaliações)</p>
                  <p><strong>Preço estimado:</strong> R$ {{ hotel.estimated_price }} / noite</p>
                  <p v-if="hotel.opening_hours"><strong>Horário:</strong> {{ hotel.opening_hours }}</p>
                  <div class="expanded-actions">
                    <button class="btn btn-outline-ghost" @click="deselect">Fechar</button>
                    <button class="btn btn-success" @click="confirmBooking">Reservar</button>
                  </div>
                </div>
              </div>

              <div class="inline-map-wrapper">
                <div :id="`inline-map-${hotel.place_id}`" class="inline-map" aria-label="Mapa do hotel"></div>
              </div>
            </div>
          </article>
        </div>

        <div v-if="!loading && hotels.length === 0" class="empty">Nenhum hotel encontrado — tente ajustar filtros</div>
      </section>
    </main>
  </div>
</template>

<script>
import axios from 'axios';
import { ref, onMounted, watch, nextTick } from 'vue';

export default {
  name: "App",
  setup() {
    const dark = ref(false);
    const query = ref("");
    const locationQuery = ref("");
    const priceFilter = ref("any");
    const hotels = ref([]);
    const loading = ref(false);
    const selected = ref(null);
    const placeholder = "https://via.placeholder.com/800x600?text=Hotel";

    // Leaflet inline maps tracking
    const inlineMaps = {};

    function estimatePriceFromPriceLevel(level) {
      if (level === undefined || level === null) return Math.floor(120 + Math.random()*200);
      const base = [60, 120, 220, 420, 800];
      return base[Math.min(Math.max(Math.round(level),0),4)];
    }

    async function search() {
      loading.value = true;
      try {
        const params = {
          q: query.value || undefined,
          location: locationQuery.value || undefined,
          price_filter: priceFilter.value !== 'any' ? priceFilter.value : undefined
        };
        const res = await axios.get('/api/places', { params });
        const items = res.data.data || [];
        hotels.value = items.map(i => ({ ...i, estimated_price: estimatePriceFromPriceLevel(i.price_level) }));
        await nextTick();
      } catch (err) {
        console.error(err);
        hotels.value = [];
      } finally {
        loading.value = false;
      }
    }

    function toggleSelect(hotel) {
      if (selected.value && selected.value.place_id === hotel.place_id) {
        deselect();
        return;
      }
      selectHotel(hotel);
    }

    async function selectHotel(hotel) {
      selected.value = hotel;
      await nextTick();
      initInlineLeaflet(hotel);
      const el = document.getElementById(`inline-map-${hotel.place_id}`);
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function deselect() {
      if (selected.value) {
        destroyInlineLeaflet(selected.value);
      }
      selected.value = null;
    }

    function initInlineLeaflet(hotel) {
      // requires Leaflet (loaded via Blade)
      if (!window.L) return;
      const id = `inline-map-${hotel.place_id}`;
      const el = document.getElementById(id);
      if (!el) return;
      el.style.height = '240px';
      if (el._leaflet_map) {
        try { el._leaflet_map.remove(); } catch (e) {}
      }
      // create map
      const map = window.L.map(el, { zoomControl: true, attributionControl: false }).setView([hotel.lat || -1.4558, hotel.lng || -48.5039], 16);
      const darkTiles = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
      const lightTiles = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
      const tileUrl = document.body.classList.contains('theme-dark') ? darkTiles : lightTiles;
      window.L.tileLayer(tileUrl, { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
      const mk = window.L.marker([hotel.lat || -1.4558, hotel.lng || -48.5039]).addTo(map);
      mk.bindPopup(`<b>${hotel.name}</b><br>${hotel.vicinity}`).openPopup();
      el._leaflet_map = map;
      inlineMaps[hotel.place_id] = map;
      setTimeout(()=>{ try { map.invalidateSize(); } catch(e){} }, 200);
    }

    function destroyInlineLeaflet(hotel) {
      if (!hotel) return;
      const id = `inline-map-${hotel.place_id}`;
      const el = document.getElementById(id);
      if (!el) return;
      if (el._leaflet_map) {
        try { el._leaflet_map.remove(); } catch(e) {}
        delete el._leaflet_map;
        delete inlineMaps[hotel.place_id];
      }
    }

    function clearSearch() {
      query.value = "";
      locationQuery.value = "";
      priceFilter.value = "any";
      hotels.value = [];
      // deselect any open card and destroy maps
      if (selected.value) {
        destroyInlineLeaflet(selected.value);
        selected.value = null;
      }
    }

    function confirmBooking() {
      if (!selected.value) {
        alert('Selecione um hotel antes de confirmar.');
        return;
      }
      // Demo behavior: inform the user and close the expanded card
      alert(`Reserva demo confirmada para ${selected.value.name}.`);
      destroyInlineLeaflet(selected.value);
      selected.value = null;
    }

    // watch dark theme to update open inline maps tiles
    watch(dark, (val) => {
      document.body.classList.toggle('theme-dark', val);
      // update tiles for all open inline maps
      Object.keys(inlineMaps).forEach(pid => {
        const map = inlineMaps[pid];
        if (!map) return;
        // remove existing tile layers and add new one
        map.eachLayer(layer => {
          if (layer && layer._url) map.removeLayer(layer);
        });
        const tileUrl = val ? 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png' : 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
        window.L.tileLayer(tileUrl, { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
      });
    }, { immediate: true });

    onMounted(() => {
      // if user prefers dark
      if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) dark.value = true;
      // initial search
      search();
    });

    return {
      dark, query, locationQuery, priceFilter, hotels, loading, selected, placeholder,
      toggleSelect, deselect, clearSearch, confirmBooking
    };
  }
};
</script>

<style scoped>
.container { max-width:1100px;margin:0 auto;padding:12px; }
.site-header { display:flex;align-items:center;justify-content:space-between;padding:12px 0;margin-bottom:10px; }
.brand { display:flex;align-items:center;gap:12px; }
.logo { width:46px;height:46px;border-radius:10px;background:var(--accent-600);display:flex;align-items:center;justify-content:center;color:white;font-weight:700; }
.controls-header { display:flex;align-items:center;gap:12px; }
.dark-toggle { display:flex;align-items:center;gap:8px;cursor:pointer; }

.controls { display:flex;gap:12px;flex-wrap:wrap;align-items:center;padding:8px 0;margin-bottom:8px; }
.search-input { display:flex;gap:8px;align-items:center;flex:1;min-width:180px; }
.form-control { padding:8px 10px;border-radius:8px;border:1px solid var(--muted-border);background:var(--input-bg);color:var(--text); }

.list-layout { display:block; }
.cards { display:grid; gap:12px; }

/* hotel card */
.hotel-card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:12px; overflow:hidden; transition: box-shadow .2s, transform .18s; }
.hotel-card .card-top { display:flex; align-items:center; gap:12px; padding:12px; cursor:pointer; }
.thumb { width:120px; height:80px; border-radius:8px; object-fit:cover; flex-shrink:0; }
.meta { flex:1; }
.hotel-title { font-weight:700; font-size:16px; margin-bottom:4px; }
.hotel-sub { color:var(--muted); font-size:13px; margin-bottom:6px; }
.row-between { display:flex; justify-content:space-between; align-items:center; gap:12px; }
.stars { color:var(--accent-600); font-size:14px; }
.price { font-weight:700; }

/* expanded content */
.card-expanded { padding:12px 16px 20px 16px; background: linear-gradient(180deg, rgba(0,0,0,0.02), transparent); }
.expanded-grid { display:grid; grid-template-columns: 320px 1fr; gap:16px; align-items:start; }
.expanded-photo img { width:100%; height:220px; object-fit:cover; border-radius:8px; display:block; }
.expanded-info h3 { margin-top:0; margin-bottom:6px; }
.expanded-actions { margin-top:12px; display:flex; gap:8px; }

/* inline map wrapper */
.inline-map-wrapper { margin-top:12px; }
.inline-map { width:100%; height:240px; border-radius:8px; overflow:hidden; background:var(--map-bg); }

@media (max-width:900px) {
  .expanded-grid { grid-template-columns: 1fr; }
  .thumb { width:96px; height:64px; }
}

:root {
  --bg: #f6fbf6;
  --text: #0b0f10;
  --muted: #5b6b60;
  --muted-border: rgba(0,0,0,0.08);
  --card-bg: #ffffff;
  --card-border: rgba(0,0,0,0.06);
  --accent-600: #2b7a3a;
  --input-bg: #fff;
  --map-bg: #e9f5ea;
}

.theme-dark, .theme-dark * {
  --bg: #071010;
  --text: #e6efe6;
  --muted: #a9b1a8;
  --muted-border: rgba(255,255,255,0.06);
  --card-bg: #0f1a17;
  --card-border: rgba(255,255,255,0.04);
  --accent-600: #3fb867;
  --input-bg: #0b1210;
  --map-bg: #081414;
}

.app-root { background: linear-gradient(180deg, var(--bg), #fff); min-height:100vh; color:var(--text); padding-bottom:40px; }

.btn { cursor:pointer; border:none; padding:8px 12px; border-radius:8px; }
.btn-success { background:var(--accent-600); color:white; }
.btn-outline-ghost { background:transparent; border:1px solid var(--card-border); padding:6px 10px; border-radius:8px; color:var(--text); }

.hotel-card:focus-within { box-shadow: 0 6px 18px rgba(43,122,58,0.06); }
</style>

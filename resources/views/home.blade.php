<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Green Hostel — Home</title>

    <style>
        body { background: #f7fbf6; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Arial; }
        .brand { color: #2b7a3a; font-weight:700; letter-spacing:0.4px }
        .card { border: none; border-radius: 12px; box-shadow: 0 6px 18px rgba(50,95,42,0.06); cursor: pointer; }
        .badge-price { background:#e6f4ea; color:#23532b; font-weight:600; border-radius:6px; padding:4px 8px; display:inline-block; }
        .search-bar { max-width:720px; margin: 0 auto }
        .skeleton { background: linear-gradient(90deg, #eee, #f5f5f5 40%, #eee); height:120px; border-radius:8px }
        footer { padding:30px 0; color:#6b6b6b }
        #map { height: 360px; border-radius:8px; }
        .leaflet-container { border-radius:8px; }
        .hotel-card:hover { transform: translateY(-2px); transition: transform .12s ease; }
    </style>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin="anonymous"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div id="app" class="container py-5">
    <header class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 brand mb-0">Green Hostel</h1>
            <small class="text-muted">Hospedagens sustentáveis, perto de você</small>
        </div>
        <div>
            <button class="btn btn-outline-success btn-sm" @click="refresh">Atualizar</button>
        </div>
    </header>

    <section class="mb-4">
        <div class="search-bar input-group">
            <input v-model="query" @keyup.enter="search" type="search" class="form-control" placeholder="Pesquisar por nome, bairro ou cidade...">
            <button class="btn btn-success" @click="search">Pesquisar</button>
            <button class="btn btn-outline-secondary" @click="useMyLocation">Usar minha localização</button>
        </div>
        <div class="mt-2 text-muted small">Distância aproximada em km. Permita o uso da localização para melhores resultados.</div>
    </section>

    <section>
        <div class="row g-3">
            <div class="col-md-7">
                <div v-if="loading" class="mb-3">
                    <div class="skeleton mb-2"></div>
                    <div class="skeleton" style="height:80px"></div>
                </div>

                <div v-else>
                    <div v-if="hotels.length === 0" class="alert alert-info">Nenhum hostel encontrado. Tente usar sua localização ou outra pesquisa.</div>

                    <div class="row row-cols-1 g-3">
                        <div v-for="hotel in hotels" :key="hotel.id" class="col">
                            <div class="card p-3 d-flex flex-row align-items-center hotel-card" @click="openDetails(hotel)">
                                <img :src="hotel.image_url || 'https://via.placeholder.com/400x300?text=Sem+imagem'" alt="" width="140" height="100" style="object-fit:cover; border-radius:8px">
                                <div class="ms-3 flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-1">@{{ hotel.name }}</h5>
                                            <div class="text-muted small">@{{ hotel.city }} — @{{ hotel.neighborhood || '—' }}</div>
                                        </div>
                                        <div class="text-end">
                                            <div v-if="hotel.price !== null" class="badge-price">R$ @{{ formatPrice(hotel.price) }} / noite</div>
                                            <div v-else-if="hotel.price_text" class="badge-price text-muted">@{{ hotel.price_text }}</div>
                                            <div v-else class="badge-price text-muted">Sob consulta</div>
                                            <div class="small text-muted mt-1">@{{ formatDistance(hotel.distance) }}</div>
                                        </div>
                                    </div>
                                    <p class="mb-0 mt-2 text-muted small">@{{ hotel.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mt-3" v-if="hasMore">
                        <button class="btn btn-outline-secondary" @click="loadMore">Carregar mais</button>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card p-3 mb-3">
                    <h6 class="mb-3">Mapa</h6>
                    <div id="map" class="mb-2"></div>
                    <div class="small text-muted">Marcadores mostram hostels retornados. Clique para mais detalhes.</div>
                </div>

                <div class="card p-3">
                    <h6>Filtros rápidos</h6>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="onlyCheap" v-model="filters.onlyCheap">
                        <label class="form-check-label" for="onlyCheap">Mostrar apenas opções até R$100</label>
                    </div>
                    <div class="mt-3 small text-muted">Filtros aplicados localmente nos resultados.</div>
                </div>
            </div>
        </div>
    </section>

    <footer class="mt-5 text-center">
        <small>&copy; @{{ new Date().getFullYear() }} Green Hostel — Design clean & reativo</small>
    </footer>
</div>

<div class="modal fade" id="hotelDetailsModal" tabindex="-1" aria-labelledby="hotelDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="hotelDetailsLabel">@{{ selectedHotel.name || 'Detalhes' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div v-if="detailsLoading" class="text-center py-4">
                    <div class="spinner-border" role="status"><span class="visually-hidden">Carregando...</span></div>
                    <div class="mt-2 text-muted">Buscando detalhes...</div>
                </div>

                <div v-else>
                    <div class="row">
                        <div class="col-md-5">
                            <img :src="selectedHotel.image_url || 'https://via.placeholder.com/640x480?text=Sem+imagem'" class="img-fluid rounded mb-3" alt="">
                        </div>
                        <div class="col-md-7">
                            <p class="mb-1"><strong>@{{ selectedHotel.name }}</strong></p>
                            <p class="text-muted mb-1">@{{ selectedHotel.address || selectedHotel.formatted_address || selectedHotel.city }}</p>
                            <p class="mb-1" v-if="selectedHotel.phone"><small>Tel: @{{ selectedHotel.phone }}</small></p>
                            <p class="mb-1" v-if="selectedHotel.website"><small><a :href="selectedHotel.website" target="_blank">Site</a></small></p>
                            <p class="mb-1"><strong>Preço:</strong>
                                <span v-if="selectedHotel.price !== null">R$ @{{ formatPrice(selectedHotel.price) }}</span>
                                <span v-else-if="selectedHotel.price_text">@{{ selectedHotel.price_text }}</span>
                                <span v-else>Sob consulta</span>
                            </p>
                            <p class="mb-1" v-if="selectedHotel.rating"><small>Avaliação: @{{ selectedHotel.rating }} (@{{ selectedHotel.user_ratings_total }})</small></p>
                            <p class="mt-2 text-muted small" v-if="selectedHotel.description">@{{ selectedHotel.description }}</p>
                            <div v-if="selectedHotel.opening_hours && selectedHotel.opening_hours.length">
                                <hr/>
                                <h6>Horários</h6>
                                <ul class="small">
                                    <li v-for="line in selectedHotel.opening_hours" :key="line">@{{ line }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a :href="mapLink(selectedHotel.latitude, selectedHotel.longitude)" target="_blank" class="btn btn-outline-secondary">Ver no mapa</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const { createApp } = Vue;

    createApp({
        data() {
            return {
                hotels: [],
                loading: false,
                userLocation: null,
                query: '',
                filters: { onlyCheap: false },
                map: null,
                markersGroup: null,
                userMarker: null,
                hasMore: false,
                selectedHotel: {},
                detailsLoading: false,
                detailsModal: null
            };
        },
        mounted() {
            this.initMap();
            this.detailsModal = new bootstrap.Modal(document.getElementById('hotelDetailsModal'));

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => {
                    this.userLocation = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                    this.setUserMarker();
                    this.fetchHotels();
                }, err => {
                    console.log('Localização negada ou indisponível', err);
                });
            } else {
                console.log('Navegador não suporta geolocalização');
            }
        },
        watch: {
            'filters.onlyCheap'() { this.applyFilters(); }
        },
        methods: {
            initMap() {
                const defaultCenter = [-23.55052, -46.633308];
                this.map = L.map('map', { scrollWheelZoom: true }).setView(defaultCenter, 12);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(this.map);
                this.markersGroup = L.layerGroup().addTo(this.map);
            },
            setUserMarker() {
                if (!this.map || !this.userLocation) return;
                if (this.userMarker) {
                    this.userMarker.setLatLng([this.userLocation.lat, this.userLocation.lng]);
                } else {
                    this.userMarker = L.circleMarker([this.userLocation.lat, this.userLocation.lng], { radius: 7, color: '#2b7a3a', fillColor: '#2b7a3a', fillOpacity: 0.9 })
                        .bindPopup('Você está aqui')
                        .addTo(this.map);
                }
            },
            formatDistance(d) {
                if (d == null) return '—';
                const n = Number(d);
                if (isNaN(n)) return d;
                if (n < 1) return (n * 1000).toFixed(0) + ' m';
                return n.toFixed(2) + ' km';
            },
            formatPrice(p) {
                const n = Number(p || 0);
                if (isNaN(n)) return '0.00';
                return n.toFixed(2);
            },
            refresh() { this.fetchHotels(); },
            search() { this.fetchHotels(); },
            useMyLocation() {
                if (!navigator.geolocation) return alert('Geolocalização não suportada');
                navigator.geolocation.getCurrentPosition(pos => {
                    this.userLocation = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                    this.setUserMarker();
                    this.fetchHotels();
                }, err => alert('Não foi possível obter localização: ' + err.message));
            },
            applyFilters() {
                if (this.filters.onlyCheap) this.hotels = this.hotels.filter(h => Number(h.price) <= 100);
                else this.fetchHotels();
                this.plotMarkers();
            },
            async fetchHotels() {
                if (!this.userLocation) return alert('Permita acesso à sua localização.');
                const { lat, lng } = this.userLocation;
                this.loading = true;
                try {
                    const response = await axios.get('/api/places/nearby', { params: { lat, lng, query: this.query } });
                    this.hotels = response.data.map(h => {
                        h.price_text = h.price_text || null;
                        return h;
                    });
                    if (this.filters.onlyCheap) this.hotels = this.hotels.filter(h => Number(h.price) <= 100);
                    this.plotMarkers();
                } catch (err) {
                    console.error('Erro ao buscar hotéis', err);
                    if (err.response) {
                        alert('Erro ao buscar hotéis: ' + (err.response.data.error || err.response.data.google_status || JSON.stringify(err.response.data)));
                    } else {
                        alert('Erro ao buscar hotéis no servidor.');
                    }
                } finally {
                    this.loading = false;
                }
            },
            plotMarkers() {
                if (!this.map || !this.markersGroup) return;
                this.markersGroup.clearLayers();
                const bounds = [];
                this.hotels.forEach(h => {
                    if (h.latitude == null || h.longitude == null) return;
                    const lat = Number(h.latitude);
                    const lng = Number(h.longitude);
                    if (isNaN(lat) || isNaN(lng)) return;

                    const priceDisplay = h.price !== null ? ('R$ ' + this.formatPrice(h.price)) : (h.price_text || 'Sob consulta');

                    const popupHtml = `<div style="min-width:180px"><strong>${this.escapeHtml(h.name)}</strong><br/><small>${this.escapeHtml(h.city)}</small><br/><div style="margin-top:6px">${this.escapeHtml(priceDisplay)}</div><div class="text-muted" style="font-size:12px">${this.formatDistance(h.distance)}</div><div style="margin-top:6px;font-size:13px">${this.escapeHtml(h.description || '')}</div><div style="margin-top:8px"><a href="#" data-place-id="${this.escapeHtml(h.place_id || h.id)}" class="open-details-link">Mais detalhes</a></div></div>`;

                    const marker = L.marker([lat, lng]).addTo(this.markersGroup);
                    marker.bindPopup(popupHtml);
                    marker.on('popupopen', (e) => {
                        const container = e.popup.getElement();
                        if (!container) return;
                        const link = container.querySelector('.open-details-link');
                        if (link) {
                            link.addEventListener('click', (ev) => {
                                ev.preventDefault();
                                const pid = link.getAttribute('data-place-id');
                                const found = this.hotels.find(x => (x.place_id && x.place_id === pid) || (x.id && x.id === pid));
                                if (found) this.openDetails(found);
                            });
                        }
                    });

                    bounds.push([lat, lng]);
                });

                if (this.userLocation && this.userLocation.lat != null && this.userLocation.lng != null) {
                    bounds.push([this.userLocation.lat, this.userLocation.lng]);
                }

                if (bounds.length > 0) {
                    const b = L.latLngBounds(bounds);
                    this.map.fitBounds(b.pad(0.2));
                }
            },
            escapeHtml(unsafe) {
                if (!unsafe) return '';
                return String(unsafe)
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            },

            async openDetails(hotel) {
                this.selectedHotel = Object.assign({}, hotel);
                this.detailsLoading = true;
                this.detailsModal.show();

                const placeId = hotel.place_id || hotel.id;
                if (!placeId) {
                    console.warn('place_id ausente');
                    this.detailsLoading = false;
                    return;
                }

                try {
                    const resp = await axios.get('/api/place/details', {
                        params: { place_id: placeId },
                        timeout: 12000
                    });

                    if (resp && resp.data) {
                        const d = resp.data;
                        this.selectedHotel = Object.assign({}, this.selectedHotel, {
                            name: d.name || this.selectedHotel.name,
                            address: d.formatted_address || this.selectedHotel.address || this.selectedHotel.city,
                            phone: d.phone || this.selectedHotel.phone || null,
                            website: d.website || this.selectedHotel.website || null,
                            price_text: d.price_text || this.selectedHotel.price_text || null,
                            price: (typeof d.price !== 'undefined') ? d.price : this.selectedHotel.price || null,
                            rating: (typeof d.rating !== 'undefined') ? d.rating : this.selectedHotel.rating || null,
                            user_ratings_total: d.user_ratings_total ?? this.selectedHotel.user_ratings_total ?? 0,
                            opening_hours: Array.isArray(d.opening_hours) ? d.opening_hours : (this.selectedHotel.opening_hours || []),
                            description: this.selectedHotel.description || '',
                            latitude: (d.geometry && d.geometry.location) ? d.geometry.location.lat : this.selectedHotel.latitude,
                            longitude: (d.geometry && d.geometry.location) ? d.geometry.location.lng : this.selectedHotel.longitude,
                        });
                    } else {
                        console.warn('Resposta vazia ao buscar place details', resp);
                        alert('Não foi possível obter detalhes deste local.');
                    }
                } catch (err) {
                    console.error('Erro ao buscar detalhes', err);
                    if (err.response && err.response.data && err.response.data.error) {
                        alert('Erro ao buscar detalhes: ' + err.response.data.error);
                    } else {
                        alert('Erro ao buscar detalhes no servidor.');
                    }
                } finally {
                    this.detailsLoading = false;
                }
            },

            mapLink(lat, lng) {
                if (!lat || !lng) return '#';
                return `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
            },

            loadMore() {
                alert('Carregar mais ainda não implementado.');
            }
        }
    }).mount('#app');
</script>
</body>
</html>

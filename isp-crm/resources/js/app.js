import './bootstrap';
import 'leaflet/dist/leaflet.css';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import L from 'leaflet';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

Alpine.plugin(collapse);

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

window.geoPointPicker = function geoPointPicker(config = {}) {
    return {
        map: null,
        marker: null,
        defaultCenter: [
            Number.parseFloat(config.defaultLat ?? -12.046374),
            Number.parseFloat(config.defaultLng ?? -77.042793),
        ],
        zoom: Number.parseInt(config.zoom ?? 16, 10),
        readonly: Boolean(config.readonly ?? false),

        init() {
            this.$nextTick(() => this.initMap());
        },

        initMap() {
            if (!window.L || !this.$refs.map || this.map) {
                return;
            }

            const lat = this.currentLat() ?? this.defaultCenter[0];
            const lng = this.currentLng() ?? this.defaultCenter[1];

            this.map = window.L.map(this.$refs.map).setView([lat, lng], this.zoom);

            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(this.map);

            if (!this.readonly) {
                this.map.on('click', (event) => {
                    this.setCoordinates(event.latlng.lat, event.latlng.lng);
                });
            }

            if (this.currentLat() !== null && this.currentLng() !== null) {
                this.updateMarker(this.currentLat(), this.currentLng());
            }

            this.$refs.latInput?.addEventListener('input', () => this.syncFromInputs());
            this.$refs.lngInput?.addEventListener('input', () => this.syncFromInputs());

            setTimeout(() => this.map?.invalidateSize(), 100);
        },

        parseCoordinate(value) {
            const parsed = Number.parseFloat(value);
            return Number.isFinite(parsed) ? parsed : null;
        },

        currentLat() {
            return this.parseCoordinate(this.$refs.latInput?.value);
        },

        currentLng() {
            return this.parseCoordinate(this.$refs.lngInput?.value);
        },

        setCoordinates(lat, lng) {
            if (this.readonly) {
                return;
            }

            this.writeInput(this.$refs.latInput, lat.toFixed(7));
            this.writeInput(this.$refs.lngInput, lng.toFixed(7));
            this.updateMarker(lat, lng);
            this.dispatchChange(lat, lng);
        },

        writeInput(input, value) {
            if (!input) {
                return;
            }

            input.value = value;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        },

        updateMarker(lat, lng) {
            if (!this.map) {
                return;
            }

            if (!this.marker) {
                this.marker = window.L.marker([lat, lng], { draggable: !this.readonly }).addTo(this.map);

                if (!this.readonly) {
                    this.marker.on('dragend', (event) => {
                        const position = event.target.getLatLng();
                        this.setCoordinates(position.lat, position.lng);
                    });
                }
            } else {
                this.marker.setLatLng([lat, lng]);
            }

            this.map.setView([lat, lng], Math.max(this.map.getZoom(), this.zoom));
        },

        syncFromInputs() {
            const lat = this.currentLat();
            const lng = this.currentLng();

            if (lat === null || lng === null) {
                return;
            }

            this.updateMarker(lat, lng);
            this.dispatchChange(lat, lng);
        },

        recenter() {
            if (!this.map) {
                return;
            }

            const lat = this.currentLat() ?? this.defaultCenter[0];
            const lng = this.currentLng() ?? this.defaultCenter[1];
            this.map.setView([lat, lng], this.zoom);
        },

        locateMe() {
            if (this.readonly || !navigator.geolocation) {
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.setCoordinates(position.coords.latitude, position.coords.longitude);
                },
                () => {
                    this.$dispatch('geo-point-error', {
                        message: 'No se pudo obtener la ubicacion actual.',
                    });
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        },

        dispatchChange(lat, lng) {
            this.$dispatch('geo-point-changed', { latitude: lat, longitude: lng });
        },
    };
};

window.Alpine = Alpine;
window.L = L;

Alpine.start();

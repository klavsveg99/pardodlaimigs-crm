@php
    $latField = $latField ?? 'lat';
    $lngField = $lngField ?? 'lng';
@endphp

<div
    x-data="{
        map: null,
        marker: null,
        lat: null,
        lng: null,
        initMap() {
            if (!window.google || !window.google.maps) {
                setTimeout(() => this.initMap(), 200);
                return;
            }
            this.lat = $wire.get('data.{{ $latField }}');
            this.lng = $wire.get('data.{{ $lngField }}');
            const hasCoords = this.lat && this.lng;
            const center = hasCoords ? { lat: parseFloat(this.lat), lng: parseFloat(this.lng) } : { lat: 56.9496, lng: 24.1052 };
            this.map = new google.maps.Map($refs.mapContainer, {
                center: center,
                zoom: hasCoords ? 15 : 6,
                mapTypeControl: false,
                streetViewControl: false,
            });
            if (hasCoords) {
                this.placeMarker(new google.maps.LatLng(parseFloat(this.lat), parseFloat(this.lng)));
            }
            this.map.addListener('click', (e) => {
                this.placeMarker(e.latLng);
            });
            const input = $refs.searchBox;
            const autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.bindTo('bounds', this.map);
            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();
                if (!place.geometry || !place.geometry.location) return;
                this.map.setCenter(place.geometry.location);
                this.map.setZoom(17);
                this.placeMarker(place.geometry.location);
            });
        },
        placeMarker(latLng) {
            if (this.marker) this.marker.setMap(null);
            this.lat = Math.round(latLng.lat() * 10000000) / 10000000;
            this.lng = Math.round(latLng.lng() * 10000000) / 10000000;
            this.marker = new google.maps.Marker({
                position: latLng,
                map: this.map,
                draggable: true,
            });
            this.marker.addListener('dragend', (e) => {
                this.lat = Math.round(e.latLng.lat() * 10000000) / 10000000;
                this.lng = Math.round(e.latLng.lng() * 10000000) / 10000000;
                this.sync();
            });
            this.sync();
        },
        sync() {
            this.$wire.set('data.{{ $latField }}', this.lat);
            this.$wire.set('data.{{ $lngField }}', this.lng);
        }
    }"
    x-init="$nextTick(() => initMap())"
    wire:ignore.self
>
    <div style="position: relative; margin-bottom: 0.5rem;">
        <input
            class="pdc-map-search"
            x-ref="searchBox"
            type="text"
            placeholder="Meklēt adresi..."
            style="
                width: 100%;
                padding: 0.5rem 0.75rem;
                border: 1px solid #d1d5db;
                border-radius: 0.375rem;
                font-size: 0.875rem;
                outline: none;
            "
            @focus="this.style.borderColor = 'var(--pdc-primary)'"
            @blur="this.style.borderColor = ''"
        />
    </div>
    <div
        x-ref="mapContainer"
        wire:ignore
        class="pdc-map-container"
        style="
            width: 100%;
            height: 350px;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            overflow: hidden;
        "
    ></div>
    <div class="pdc-map-help" style="display: flex; gap: 1rem; margin-top: 0.5rem; font-size: 0.75rem; color: #6b7280;">
        <span>Adrese un pilsēta nav obligātas. Noklikšķiniet kartē, lai saglabātu tikai atrašanās vietas punktu, vai meklējiet adresi.</span>
        <span x-show="lat && lng" x-text="'Lat: ' + lat + ', Lng: ' + lng"></span>
    </div>
</div>

@if(config('services.google_maps.key'))
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&callback=Function.prototype" async defer></script>
@endif

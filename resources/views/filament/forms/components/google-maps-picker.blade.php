@php
    $latField = $latField ?? 'lat';
    $lngField = $lngField ?? 'lng';
    $cityField = $cityField ?? 'city';
    $addressField = $addressField ?? 'address';
@endphp

<div
    x-data="{
        map: null,
        marker: null,
        geocoder: null,
        lat: null,
        lng: null,
        initMap() {
            if (!window.google || !window.google.maps) {
                setTimeout(() => this.initMap(), 200);
                return;
            }
            this.geocoder = new google.maps.Geocoder();
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
                this.placeMarker(new google.maps.LatLng(parseFloat(this.lat), parseFloat(this.lng)), false);
            }
            this.map.addListener('click', (e) => {
                this.placeMarker(e.latLng, true);
            });
            const input = $refs.searchBox;
            const autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.bindTo('bounds', this.map);
            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();
                if (!place.geometry || !place.geometry.location) return;
                this.map.setCenter(place.geometry.location);
                this.map.setZoom(17);
                // Fill from place directly if available
                if (place.formatted_address) {
                    this.fillFromPlace(place);
                }
                this.placeMarker(place.geometry.location, !place.formatted_address);
            });
        },
        fillFromPlace(place) {
            // Extract city via locality or admin levels
            let city = '';
            let street = '';
            if (place.address_components) {
                for (const comp of place.address_components) {
                    const types = comp.types;
                    if (types.includes('locality')) city = comp.long_name;
                    else if (!city && types.includes('postal_town')) city = comp.long_name;
                    else if (!city && types.includes('administrative_area_level_2')) city = comp.long_name;
                    if (types.includes('route')) street = comp.long_name;
                }
            }
            // Prefer formatted_address for full address, but ensure city is locality not street
            const fullAddress = place.formatted_address || place.name || '';
            if (fullAddress) {
                $wire.set('data.{{ $addressField }}', fullAddress);
                // also update the input field value via Alpine? The TextInput is separate, Livewire will sync
            }
            if (city) {
                // Only overwrite if city is empty or looks like street (contains 'iela' or number)
                const current = $wire.get('data.{{ $cityField }}');
                const looksLikeStreet = current && /iela|ceļš|gatve|prospekts|\d/.test(current);
                if (!current || looksLikeStreet || current !== city) {
                    $wire.set('data.{{ $cityField }}', city);
                }
            } else if (place.vicinity) {
                // fallback
                $wire.set('data.{{ $cityField }}', place.vicinity);
            }
        },
        fillFromGeocode(results) {
            if (!results || !results[0]) return;
            const result = results[0];
            const fullAddress = result.formatted_address || '';
            let city = '';
            for (const comp of result.address_components || []) {
                const types = comp.types;
                if (types.includes('locality')) { city = comp.long_name; break; }
            }
            if (!city) {
                for (const comp of result.address_components || []) {
                    if (comp.types.includes('postal_town')) { city = comp.long_name; break; }
                }
            }
            if (!city) {
                for (const comp of result.address_components || []) {
                    if (comp.types.includes('administrative_area_level_2')) { city = comp.long_name; break; }
                }
            }
            if (!city) {
                for (const comp of result.address_components || []) {
                    if (comp.types.includes('administrative_area_level_1')) { city = comp.long_name; break; }
                }
            }
            if (fullAddress) {
                $wire.set('data.{{ $addressField }}', fullAddress);
            }
            if (city) {
                const current = $wire.get('data.{{ $cityField }}');
                const looksLikeStreet = current && /iela|ceļš|gatve|prospekts/i.test(current);
                // Always set city to locality if we have it, street should be in address
                if (!current || looksLikeStreet || current !== city) {
                    $wire.set('data.{{ $cityField }}', city);
                }
            }
        },
        placeMarker(latLng, doGeocode = true) {
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
                if (this.geocoder) {
                    this.geocoder.geocode({ location: e.latLng }, (results, status) => {
                        if (status === 'OK') this.fillFromGeocode(results);
                    });
                }
            });
            this.sync();
            if (doGeocode && this.geocoder) {
                this.geocoder.geocode({ location: latLng }, (results, status) => {
                    if (status === 'OK') this.fillFromGeocode(results);
                });
            }
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
        <span>Adrese un pilsēta tiek aizpildītas automātiski no kartes. Noklikšķiniet kartē vai meklējiet adresi — pilsēta tiks noteikta kā apdzīvota vieta (nevis iela).</span>
        <span x-show="lat && lng" x-text="'Lat: ' + lat + ', Lng: ' + lng"></span>
    </div>
</div>

@if(config('services.google_maps.key'))
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&callback=Function.prototype" async defer></script>
@endif

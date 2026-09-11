@php
    $latField = $latField ?? 'lat';
    $lngField = $lngField ?? 'lng';
    $cityField = $cityField ?? 'city';
    $addressField = $addressField ?? 'address';
@endphp

<div
    x-data="{
        map: null,
        geocoder: null,
        lat: null,
        lng: null,
        // Marker un Map glabājam slēguma īpašībā caursējumā lai Proxy
        // nesabojā privāto klašu metodes. Vienmēr VIENS pin — klikšķis vai
        // vilkšana pārvieto to, nekad neizveido otru.
        initMap() {
            if (!this.$wire) {
                setTimeout(() => this.initMap(), 200);
                return;
            }
            if (!window.google || !window.google.maps) {
                setTimeout(() => this.initMap(), 200);
                return;
            }
            if (this.map) {
                // Jau uzsākts (arī pēc Livewire morph) — neatkārtojam: pretējā
                // gadījumā dubultos klikšķa listeneri un iespējams pin.
                return;
            }

            this.geocoder = new google.maps.Geocoder();
            this.lat = this.$wire.get('data.{{ $latField }}');
            this.lng = this.$wire.get('data.{{ $lngField }}');
            const hasCoords = this.lat && this.lng;
            const center = hasCoords
                ? { lat: parseFloat(this.lat), lng: parseFloat(this.lng) }
                : { lat: 56.9496, lng: 24.1052 };

            this.map = new google.maps.Map(this.$refs.mapContainer, {
                center: center,
                zoom: hasCoords ? 15 : 6,
                mapTypeControl: false,
                streetViewControl: false,
            });

            // Precīzi VIENS pin: marčējums glabāts ārpus reaktīvā objekta.
            const key = 'pdc-pin-{{ $latField }}-{{ $lngField }}';
            window.__pdcPins = window.__pdcPins || {};
            const self = this;
            let setPin = function setPin(latLng, doGeocode = true) {
                if (!window.__pdcPins[key]) {
                    window.__pdcPins[key] = new google.maps.Marker({
                        position: latLng,
                        map: this.map,
                        draggable: true,
                    });
                    window.__pdcPins[key].addListener('dragend', (e) => {
                        self.lat = Math.round(e.latLng.lat() * 10000000) / 10000000;
                        self.lng = Math.round(e.latLng.lng() * 10000000) / 10000000;
                        self.sync();
                        self.geocoder.geocode({ location: e.latLng }, (results, status) => {
                            if (status === 'OK') self.fillFromGeocode(results);
                        });
                    });
                } else {
                    window.__pdcPins[key].setPosition(latLng);
                    window.__pdcPins[key].setMap(this.map);
                }

                self.lat = Math.round(latLng.lat() * 10000000) / 10000000;
                self.lng = Math.round(latLng.lng() * 10000000) / 10000000;
                self.sync();

                if (doGeocode) {
                    self.geocoder.geocode({ location: latLng }, (results, status) => {
                        if (status === 'OK') self.fillFromGeocode(results);
                    });
                }
            };
            setPin = setPin.bind(this);

            this.setPin = setPin;

            // Jauns īpašums BEZ saglabātām koordinātām — NAV nav sākotnējā
            // pin; lietotājam jāuzliek pats (obligāts pirms saglabāsanās).
            if (hasCoords) {
                setPin(new google.maps.LatLng(parseFloat(self.lat), parseFloat(self.lng)), false);
            }

            // Šo daļu reģistrējam TIKAI vienreiz — līdz ar Map izveidi.
            this.map.addListener('click', (e) => {
                if (!e.latLng) return;
                this.map.panTo(e.latLng);
                setPin(e.latLng, true);
            });

            const input = this.$refs.searchBox;
            const autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.bindTo('bounds', this.map);
            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();
                if (!place.geometry || !place.geometry.location) return;
                this.map.setCenter(place.geometry.location);
                this.map.setZoom(17);
                if (place.formatted_address) {
                    this.fillFromPlace(place);
                }
                setPin(place.geometry.location, !place.formatted_address);
            });
        },
        sync() {
            this.$wire.set('data.{{ $latField }}', this.lat);
            this.$wire.set('data.{{ $lngField }}', this.lng);
        },
        fillFromPlace(place) {
            let city = '';
            let zip = '';
            if (place.address_components) {
                for (const compItem of place.address_components) {
                    if (compItem.types.includes('locality')) { city = compItem.long_name; break; }
                }
            }
            if (place.address_components) {
                for (const compItem of place.address_components) {
                    if (compItem.types.includes('postal_code')) { zip = compItem.long_name; break; }
                }
            }
            if (zip) {
                this.$wire.set('data.{{ $zipField }}', zip);
            }
            const fullAddress = place.formatted_address || place.name || '';
            if (fullAddress) {
                this.$wire.set('data.{{ $addressField }}', fullAddress);
            }
            if (city) {
                this.$wire.set('data.{{ $cityField }}', city);
            } else if (place.vicinity) {
                this.$wire.set('data.{{ $cityField }}', place.vicinity);
            }
        },
        fillFromGeocode(results) {
            if (!results || !results[0]) return;
            const result = results[0];
            const fullAddress = result.formatted_address || '';
            let city = '';
            let zip = '';
            for (const compItem of result.address_components || []) {
                if (compItem.types.includes('postal_code')) { zip = compItem.long_name; break; }
            }
            if (zip) {
                this.$wire.set('data.{{ $zipField }}', zip);
            }
            for (const compItem of result.address_components || []) {
                if (compItem.types.includes('locality')) { city = compItem.long_name; break; }
            }
            if (!city) {
                for (const compItem of result.address_components || []) {
                    if (compItem.types.includes('postal_town')) { city = compItem.long_name; break; }
                }
            }
            if (!city) {
                for (const compItem of result.address_components || []) {
                    if (compItem.types.includes('administrative_area_level_2')) { city = compItem.long_name; break; }
                }
            }
            if (!city) {
                for (const compItem of result.address_components || []) {
                    if (compItem.types.includes('administrative_area_level_1')) { city = compItem.long_name; break; }
                }
            }
            if (fullAddress) {
                this.$wire.set('data.{{ $addressField }}', fullAddress);
            }
            if (city) {
                this.$wire.set('data.{{ $cityField }}', city);
            }
        },
    }"
    x-init="$nextTick(() => initMap())"
    wire:ignore.self
>
    <div class="fi-fo-field-label-ctn">
        <label for="pdc-map-search" class="fi-fo-field-label">
            <span class="fi-fo-field-label-content">Precīza atrašanās vieta kartē</span>
        </label>
    </div>
    <input
        id="pdc-map-search"
        class="fi-input pdc-map-search"
        x-ref="searchBox"
        type="text"
        placeholder="Meklēt adresi..."
        @focus="this.classList.add('map-search-focus')"
        @blur="this.classList.remove('map-search-focus')"
    />
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
        <span x-show="lat && lng" x-text="'Lat: ' + lat + ', Lng: ' + lng"></span>
        <span x-show="!lat || !lng" style="color: #cf2e2e; font-size: 0.8125rem;">
            Iezīmējiet precīzu atrašanās vietu kartē.
        </span>
    </div>
</div>

@if(config('services.google_maps.key'))
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&callback=Function.prototype" async defer></script>
@endif

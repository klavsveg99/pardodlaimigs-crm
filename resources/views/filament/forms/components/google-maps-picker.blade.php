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

            // Kartes meklēšanas lauks: Places API (New) ieteikumi.
            window.pdcPlaceAutocomplete(this.$refs.searchBox, (place) => {
                this.applyPlace(place);
            });

            // Pilnās adreses lauka Google ieteikumi: izvēloties adresi,
            // pārnesam to uz karti (kartes meklēšana + pin + koordinātas).
            const addressInput = document.getElementById('pdc-crm-address-input');
            if (addressInput) {
                window.pdcPlaceAutocomplete(addressInput, (place) => {
                    if (place.formattedAddress) {
                        this.$wire.set('data.{{ $addressField }}', place.formattedAddress);
                        addressInput.value = place.formattedAddress;
                    }
                    this.applyPlace(place);
                });
            }
        },
        applyPlace(place) {
            if (!place || !place.location) return;
            this.fillFromNewPlace(place);
            this.map.setCenter(place.location);
            this.map.setZoom(17);
            this.setPin(place.location, false);
        },
        sync() {
            this.$wire.set('data.{{ $latField }}', this.lat);
            this.$wire.set('data.{{ $lngField }}', this.lng);
        },
        fillFromNewPlace(place) {
            const components = place.addressComponents || [];
            let city = '';
            let zip = '';
            for (const component of components) {
                if (!zip && component.types.includes('postal_code')) zip = component.longText;
                if (!city && component.types.includes('locality')) city = component.longText;
            }
            if (!city) {
                for (const component of components) {
                    if (component.types.includes('postal_town')) { city = component.longText; break; }
                }
            }
            if (!city) {
                for (const component of components) {
                    if (component.types.includes('administrative_area_level_2')) { city = component.longText; break; }
                }
            }
            if (!city) {
                for (const component of components) {
                    if (component.types.includes('administrative_area_level_1')) { city = component.longText; break; }
                }
            }
            if (zip) {
                this.$wire.set('data.{{ $zipField }}', zip);
            }
            const fullAddress = place.formattedAddress || '';
            if (fullAddress) {
                this.$wire.set('data.{{ $addressField }}', fullAddress);
            }
            if (city) {
                this.$wire.set('data.{{ $cityField }}', city);
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
        autocomplete="off"
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

{{-- Places API (New) autocomplete. Google 2025. gada 1. martā pārtrauca
     likt pieejamu veco `google.maps.places.Autocomplete` jauniem projektiem,
     tāpēc ieteikumus ņemam ar AutocompleteSuggestion un renderējam savā
     sarakstā (native ievades lauks paliek Filament stila). --}}
<script>
if (! window.pdcPlaceAutocomplete) {
    window.pdcPlaceAutocomplete = function (input, onPlace) {
        if (! input || input.dataset.pdcPaBound) {
            return;
        }
        input.dataset.pdcPaBound = '1';

        const panel = document.createElement('div');
        panel.className = 'pdc-pa-panel';
        document.body.appendChild(panel);

        let items = [];
        let active = -1;
        let token = null;
        let seq = 0;
        let timer = null;

        const hide = () => {
            panel.style.display = 'none';
            active = -1;
        };

        const position = () => {
            const rect = input.getBoundingClientRect();
            panel.style.left = rect.left + 'px';
            panel.style.top = (rect.bottom + 2) + 'px';
            panel.style.width = rect.width + 'px';
        };

        const render = () => {
            if (! items.length) {
                hide();
                return;
            }
            panel.innerHTML = '';
            items.forEach((item, index) => {
                const prediction = item.placePrediction;
                if (! prediction) {
                    return;
                }
                const row = document.createElement('button');
                row.type = 'button';
                row.className = 'pdc-pa-item' + (index === active ? ' is-active' : '');
                const main = document.createElement('span');
                main.className = 'pdc-pa-main';
                main.textContent = prediction.mainText ? prediction.mainText.text : (prediction.text ? prediction.text.text : '');
                row.appendChild(main);
                const secondary = prediction.secondaryText ? prediction.secondaryText.text : '';
                if (secondary) {
                    const sub = document.createElement('span');
                    sub.className = 'pdc-pa-secondary';
                    sub.textContent = secondary;
                    row.appendChild(sub);
                }
                row.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    choose(index);
                });
                row.addEventListener('mouseenter', () => {
                    active = index;
                });
                panel.appendChild(row);
            });
            position();
            panel.style.display = 'block';
        };

        const choose = (index) => {
            const item = items[index];
            if (! item || ! item.placePrediction) {
                return;
            }
            const prediction = item.placePrediction;
            if (prediction.text) {
                input.value = prediction.text.text;
            }
            hide();
            const place = prediction.toPlace();
            place.fetchFields({ fields: ['addressComponents', 'location', 'formattedAddress', 'displayName'] })
                .then(() => {
                    if (typeof onPlace === 'function') {
                        onPlace(place);
                    }
                    token = null;
                })
                .catch(() => {});
        };

        const fetchSuggestions = async (query) => {
            const mySeq = ++seq;
            try {
                const placesLibrary = await google.maps.importLibrary('places');
                const AutocompleteSuggestion = placesLibrary.AutocompleteSuggestion
                    || (google.maps.places && google.maps.places.AutocompleteSuggestion);
                if (! AutocompleteSuggestion) {
                    return;
                }
                if (! token) {
                    token = new google.maps.places.AutocompleteSessionToken();
                }
                const { suggestions } = await AutocompleteSuggestion.fetchAutocompleteSuggestions({
                    input: query,
                    sessionToken: token,
                });
                if (mySeq !== seq) {
                    return;
                }
                items = suggestions || [];
                active = -1;
                render();
            } catch (error) {
                items = [];
                hide();
            }
        };

        input.addEventListener('input', () => {
            const query = input.value.trim();
            window.clearTimeout(timer);
            if (query.length < 3) {
                items = [];
                hide();
                return;
            }
            timer = window.setTimeout(() => fetchSuggestions(query), 180);
        });
        input.addEventListener('focus', () => {
            if (items.length) {
                render();
            }
        });
        input.addEventListener('blur', () => window.setTimeout(hide, 150));
        input.addEventListener('keydown', (event) => {
            if (panel.style.display === 'none' || ! items.length) {
                return;
            }
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                active = (active + 1) % items.length;
                render();
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                active = (active - 1 + items.length) % items.length;
                render();
            } else if (event.key === 'Enter') {
                if (active >= 0) {
                    event.preventDefault();
                    choose(active);
                }
            } else if (event.key === 'Escape') {
                hide();
            }
        });
        window.addEventListener('scroll', () => {
            if (panel.style.display !== 'none') {
                position();
            }
        }, true);
        window.addEventListener('resize', () => {
            if (panel.style.display !== 'none') {
                position();
            }
        });
    };
}
</script>

@if(config('services.google_maps.key'))
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&callback=Function.prototype" async defer></script>
@endif

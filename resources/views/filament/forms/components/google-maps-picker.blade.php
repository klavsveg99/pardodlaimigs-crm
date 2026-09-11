@php
    $latField = $latField ?? 'lat';
    $lngField = $lngField ?? 'lng';
    $cityField = $cityField ?? 'city';
    $addressField = $addressField ?? 'address';
@endphp

<div
    x-data="(() => {
        // Stāvoklis slēgumā, nevis Alpine objektā: reaktīvais Proxy sabojā
        // kartes instanču metodes (tāpat kā privāto klašu gadījumā ar
        // intl-tel-input). Vienmēr VIENS pin — kartes klikšķis vai vilkšana
        // pārvieto to, nekad neizveido otru.
        let map = null;
        let marker = null;
        let geocoder = null;
        let wire = null;
        let lat = null;
        let lng = null;

        const round7 = (v) => Math.round(v * 10000000) / 10000000;
        const getLat = () => wire ? wire.get('data.{{ $latField }}') : null;
        const getLng = () => wire ? wire.get('data.{{ $lngField }}') : null;
        const setLat = (v) => wire.set('data.{{ $latField }}', v);
        const setLng = (v) => wire.set('data.{{ $lngField }}', v);

        function placeMarker(latLng, doGeocode = true) {
            // Precīzi VIENS pin: ja ir — pārvietojam; ja nav — izveidojam
            // vienreiz un tālāk izmantojam isti šo instanci.
            if (!marker) {
                marker = new google.maps.Marker({
                    position: latLng,
                    map: map,
                    draggable: true,
                });
                marker.addListener('dragend', (e) => {
                    lat = round7(e.latLng.lat());
                    lng = round7(e.latLng.lng());
                    setLat(lat);
                    setLng(lng);
                    geocoder.geocode({ location: e.latLng }, (results, status) => {
                        if (status === 'OK') fillFromGeocode(results);
                    });
                });
            } else {
                marker.setPosition(latLng);
                marker.setMap(map);
            }

            lat = round7(latLng.lat());
            lng = round7(latLng.lng());
            setLat(lat);
            setLng(lng);

            if (doGeocode) {
                geocoder.geocode({ location: latLng }, (results, status) => {
                    if (status === 'OK') fillFromGeocode(results);
                });
            }
        }

        function fillFromPlace(place) {
            let city = '';
            if (place.address_components) {
                for (const compItem of place.address_components) {
                    if (compItem.types.includes('locality')) { city = compItem.long_name; break; }
                }
            }
            const fullAddress = place.formatted_address || place.name || '';
            if (fullAddress) {
                wire.set('data.{{ $addressField }}', fullAddress);
            }
            if (city) {
                wire.set('data.{{ $cityField }}', city);
            } else if (place.vicinity) {
                wire.set('data.{{ $cityField }}', place.vicinity);
            }
        }

        function fillFromGeocode(results) {
            if (!results || !results[0]) return;
            const result = results[0];
            const fullAddress = result.formatted_address || '';
            let city = '';
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
                wire.set('data.{{ $addressField }}', fullAddress);
            }
            if (city) {
                wire.set('data.{{ $cityField }}', city);
            }
        }

        return {
            get latValue() { return lat; },
            get lngValue() { return lng; },
            initMap() {
                // Jau uzsākts (arī pēc Livewire morph) — neatkārtojam, citādi
                // var parādīties otrs pin uz cita Map eksemplāra.
                if (!wire) {
                    wire = this.$wire;
                }
                if (map) return;
                if (!window.google || !window.google.maps) {
                    setTimeout(() => this.initMap(), 200);
                    return;
                }

                geocoder = new google.maps.Geocoder();
                lat = getLat();
                lng = getLng();
                const hasCoords = lat && lng;
                const center = hasCoords
                    ? { lat: parseFloat(lat), lng: parseFloat(lng) }
                    : { lat: 56.9496, lng: 24.1052 };

                map = new google.maps.Map(this.$refs.mapContainer, {
                    center: center,
                    zoom: hasCoords ? 15 : 6,
                    mapTypeControl: false,
                    streetViewControl: false,
                });

                if (hasCoords) {
                    placeMarker(new google.maps.LatLng(parseFloat(lat), parseFloat(lng)), false);
                }

                map.addListener('click', (e) => {
                    if (!e.latLng) return;
                    map.panTo(e.latLng);
                    placeMarker(e.latLng, true);
                });

                const input = this.$refs.searchBox;
                const autocomplete = new google.maps.places.Autocomplete(input);
                autocomplete.bindTo('bounds', map);
                autocomplete.addListener('place_changed', () => {
                    const place = autocomplete.getPlace();
                    if (!place.geometry || !place.geometry.location) return;
                    map.setCenter(place.geometry.location);
                    map.setZoom(17);
                    if (place.formatted_address) {
                        fillFromPlace(place);
                    }
                    placeMarker(place.geometry.location, !place.formatted_address);
                });
            },
        };
    })()"
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
        <span x-show="latValue && lngValue" x-text="'Lat: ' + latValue + ', Lng: ' + lngValue"></span>
    </div>
</div>

@if(config('services.google_maps.key'))
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&callback=Function.prototype" async defer></script>
@endif

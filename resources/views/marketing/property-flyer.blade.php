@php
    // Apraksts redaktoram kā vienkāršs teksts (rindkopas ar tukšām rindām).
    $withBreaks = preg_replace(
        ['/<br\s*\/?>/i', '/<\/(p|div|li|h[1-6])>/i'],
        "\n",
        (string) $property->description
    );
    $descriptionText = trim(preg_replace('/\n{3,}/', "\n\n", html_entity_decode(strip_tags((string) $withBreaks))));

    $addressLine = trim(implode(', ', array_filter([$property->address, $property->city])));
    $priceValue = (float) $property->price_eur > 0 ? number_format((float) $property->price_eur, 0, ',', ' ') : '';
    $areaValue = $property->size_m2 ? (string) (int) $property->size_m2 : '';
    $pricePerSqmValue = $pricePerSqm ? number_format($pricePerSqm, 0, ',', ' ') : '';

    $filename = ($property->slug ?: 'ipasums').'-marketing.pdf';
    $initials = mb_strtoupper(mb_substr((string) $agentName, 0, 1));
    $flyerImages = collect($images)->filter()->values();
@endphp
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $property->title }} · PDF mārketings</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@400;600;700&family=Maven+Pro:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #eef1f0; font-family: 'Source Sans Pro', system-ui, sans-serif; color: #1f2937; }

        .topbar {
            position: sticky; top: 0; z-index: 60;
            display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;
            padding: 0.7rem 1rem; background: #ffffff; border-bottom: 1px solid #e2e8e6;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .topbar .title { font-family: 'Maven Pro', sans-serif; font-weight: 700; font-size: 1rem; margin-right: 0.25rem; }
        .topbar .spacer { flex: 1 1 auto; }

        .btn {
            display: inline-flex; align-items: center; gap: 0.45rem;
            height: 2.35rem; padding: 0 0.95rem; border-radius: 0.5rem;
            font-size: 0.86rem; font-weight: 600; cursor: pointer; white-space: nowrap;
            border: 1px solid #d1d9d7; background: #ffffff; color: #374151; text-decoration: none;
            font-family: inherit;
        }
        .btn:hover { background: #f3f6f5; }
        .btn-primary { background: #285854; border-color: #1e4843; color: #ffffff; }
        .btn-primary:hover { background: #1e4843; }
        .btn:disabled { opacity: 0.65; cursor: default; }
        .btn i { font-size: 0.9rem; }

        .layout { display: grid; grid-template-columns: 400px minmax(0, 1fr); gap: 1.25rem; padding: 1.25rem; align-items: start; }

        .editor { background: #ffffff; border: 1px solid #e2e8e6; border-radius: 0.75rem; overflow: hidden; position: sticky; top: 4.5rem; }
        .tabs { display: flex; border-bottom: 1px solid #e2e8e6; }
        .tabs button {
            flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
            padding: 0.7rem 0.5rem; border: 0; border-bottom: 2px solid transparent; background: transparent;
            font-family: inherit; font-size: 0.85rem; font-weight: 600; color: #6b7280; cursor: pointer;
        }
        .tabs button.active { color: #285854; border-bottom-color: #285854; }

        .panel { display: none; padding: 1rem; max-height: calc(100vh - 12rem); overflow-y: auto; }
        .panel.active { display: block; }

        .field { margin-bottom: 0.8rem; }
        .field label { display: block; font-size: 0.78rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem; }
        .field input[type=text], .field input[type=email], .field textarea {
            width: 100%; border: 1px solid #d7dedc; border-radius: 0.5rem; padding: 0.5rem 0.6rem;
            font-size: 0.875rem; font-family: inherit; color: #111827; background: #fff;
        }
        .field input:focus, .field textarea:focus { outline: 2px solid rgba(40,88,84,0.25); border-color: #285854; }
        .field textarea { resize: vertical; min-height: 7rem; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem; }
        .hint { font-size: 0.72rem; color: #9ca3af; margin-top: 0.2rem; }

        .thumbs-editor { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 0.6rem; }
        .thumb-item { position: relative; border: 1px solid #e2e8e6; border-radius: 0.5rem; overflow: hidden; background: #f3f4f6; }
        .thumb-item img { width: 100%; height: 84px; object-fit: cover; display: block; }
        .thumb-actions { position: absolute; top: 0.3rem; right: 0.3rem; display: flex; gap: 0.25rem; }
        .icon-btn {
            width: 1.7rem; height: 1.7rem; border-radius: 0.4rem; border: 1px solid rgba(255,255,255,0.5);
            background: rgba(0,0,0,0.6); color: #fff; cursor: pointer; display: inline-flex;
            align-items: center; justify-content: center; font-size: 0.75rem;
        }
        .icon-btn:hover { background: rgba(0,0,0,0.8); }
        .icon-btn-danger { background: rgba(185,28,28,0.85); }

        .agent-photo { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.8rem; }
        .agent-photo img, .agent-photo .fallback {
            width: 56px; height: 56px; border-radius: 9999px; object-fit: cover; flex: none;
            background: #285854; color: #fff; display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1.2rem;
        }

        .preview-wrap { display: flex; flex-direction: column; align-items: center; }
        .preview-scaler { transform-origin: top left; }
        .preview-stage { position: relative; }

        /* ── A4 flyer ─────────────────────────────────────────── */
        .flyer {
            width: 794px; height: 1122px; min-height: 1122px; overflow: hidden;
            background: #ffffff; padding: 45px; display: flex; flex-direction: column;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            font-family: 'Source Sans Pro', system-ui, sans-serif;
        }
        .accent { height: 11px; border-radius: 2px; margin-bottom: 40px; background: linear-gradient(90deg, #2d6b66 0%, #285854 100%); }
        .flyer-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #e5e7eb; gap: 10px; }
        .brand { display: flex; align-items: center; min-width: 0; }
        .brand img { width: 40px; height: 40px; object-fit: contain; object-position: left center; flex-shrink: 0; }
        .brand span { margin-left: 8px; font-size: 15px; font-weight: 700; color: #000000; white-space: nowrap; }
        .chip { background: #285854; color: #ffffff; padding: 6px 12px; font-size: 11px; font-weight: 600; white-space: nowrap; flex-shrink: 0; }
        .flyer-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; flex: 1; min-height: 0; }
        .col-left { display: flex; flex-direction: column; min-width: 0; min-height: 0; }
        h1.f-title { font-family: 'Maven Pro', sans-serif; font-size: 32px; font-weight: 700; line-height: 1.2; margin: 0 0 8px; color: #111827; }
        .f-address { font-size: 18px; color: #6b7280; margin: 0 0 20px; }
        .price-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-bottom: 15px; }
        .f-price { font-size: 32px; font-weight: 700; color: #111827; }
        .price-meta { font-size: 14px; color: #6b7280; margin-top: 4px; }
        .desc { flex: 1 1 auto; min-height: 0; margin-bottom: 16px; overflow: hidden; font-size: 14px; line-height: 1.6; color: #4b5563; }
        .desc p { margin: 0 0 12px; word-break: break-word; }
        .f-cta { background: #285854; color: #ffffff; border-radius: 6px; padding: 16px; font-size: 16px; font-weight: 600; text-transform: uppercase; text-align: center; letter-spacing: 0.5px; display: block; text-decoration: none; word-break: break-word; }
        .col-right { display: flex; flex-direction: column; min-width: 0; }
        .map { width: 100%; height: 170px; background: #e5e7eb; border-radius: 4px; overflow: hidden; margin-bottom: 15px; }
        .map img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .map-empty { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 13px; }
        .thumbs { display: flex; flex-wrap: wrap; gap: 6px; }
        .thumbs img { width: calc(50% - 3px); height: 105px; object-fit: cover; border-radius: 4px; background: #e5e7eb; }
        .flyer-footer { border-top: 1px solid #e5e7eb; padding-top: 15px; margin-top: auto; }
        .agent-card { background: #285854; padding: 16px; border-radius: 6px; display: flex; flex-direction: row; align-items: center; gap: 24px; }
        .agent-info { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .agent-card img { width: 50px; height: 50px; border-radius: 9999px; object-fit: cover; flex: none; }
        .agent-avatar-fallback { width: 50px; height: 50px; border-radius: 9999px; background: #ffffff; color: #285854; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700; flex: none; }
        .agent-name { margin: 0; color: #ffffff; font-weight: 600; font-size: 15px; }
        .agent-role { margin: 0; color: #ffffff; font-size: 13px; opacity: 0.9; }
        .agent-contact { flex: 1; text-align: right; }
        .agent-contact p { margin: 0; color: #ffffff; font-size: 14px; font-weight: 500; word-break: break-word; }
        .flyer-meta { display: flex; justify-content: space-between; margin-top: 12px; padding-top: 12px; border-top: 1px solid #f3f4f6; }
        .flyer-meta span { color: #9ca3af; font-size: 11px; }

        /* PDF capture klons — fiksēts A4. */
        .flyer-capture { width: 794px !important; height: 1122px !important; min-height: 1122px !important; overflow: hidden !important; padding: 45px !important; box-shadow: none !important; }

        @page { size: A4; margin: 0; }
        @media print {
            html, body { background: #ffffff; }
            .no-print { display: none !important; }
            .layout { display: block; padding: 0; }
            .flyer { width: 100%; height: 296mm; min-height: 296mm; overflow: hidden; box-shadow: none; padding: 14mm; }
        }
        @media (max-width: 1100px) {
            .layout { grid-template-columns: 1fr; }
            .editor { position: static; }
            .preview-wrap { align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="topbar no-print">
        <span class="title">PDF mārketings</span>
        <button type="button" id="btn-download" class="btn btn-primary" onclick="pdcDownloadPdf()">
            <i class="fa-solid fa-download"></i><span>Lejupielādēt PDF</span>
        </button>
        <button type="button" class="btn" onclick="window.print()"><i class="fa-solid fa-print"></i>Drukāt</button>
        <span class="spacer"></span>
        <span id="status" class="hint"></span>
        <a class="btn" href="{{ route('filament.admin.resources.properties.view', ['record' => $property->slug ?? $property->getKey()]) }}"><i class="fa-solid fa-arrow-left"></i>Atgriezties</a>
    </div>

    <div class="layout">
        <aside class="editor no-print">
            <div class="tabs">
                <button type="button" class="active" data-tab="property"><i class="fa-solid fa-house"></i>Īpašums</button>
                <button type="button" data-tab="images"><i class="fa-solid fa-image"></i>Attēli</button>
                <button type="button" data-tab="agent"><i class="fa-solid fa-user"></i>Aģents</button>
            </div>

            <div class="panel active" data-panel="property">
                <div class="field">
                    <label>Nosaukums</label>
                    <input type="text" data-bind="title" value="{{ $property->title }}">
                </div>
                <div class="field">
                    <label>Adrese</label>
                    <input type="text" data-bind="address" value="{{ $addressLine }}">
                </div>
                <div class="grid-2">
                    <div class="field">
                        <label>Cena</label>
                        <input type="text" data-bind="price" value="{{ $priceValue }}">
                    </div>
                    <div class="field">
                        <label>Cena par m²</label>
                        <input type="text" data-bind="pricePerSqm" value="{{ $pricePerSqmValue }}">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="field">
                        <label>Platība (m²)</label>
                        <input type="text" data-bind="area" value="{{ $areaValue }}">
                    </div>
                    <div class="field">
                        <label>Sludinājuma ID</label>
                        <input type="text" data-bind="listingId" value="{{ $listingId }}">
                    </div>
                </div>
                <div class="field">
                    <label>Datums</label>
                    <input type="text" data-bind="listingDate" value="{{ $listingDate }}">
                </div>
                <div class="field">
                    <label>Apraksts</label>
                    <textarea data-bind="description">{{ $descriptionText }}</textarea>
                </div>
                <div class="field">
                    <label>CTA poga</label>
                    <input type="text" data-bind="cta" value="Sazināties ar aģentu">
                </div>
                <div class="hint">Izmaiņas uzreiz redzamas priekšskatījumā.</div>
            </div>

            <div class="panel" data-panel="images">
                <div class="field">
                    <label>Pievienot attēlus</label>
                    <input type="file" id="image-input" accept="image/*" multiple>
                </div>
                <div class="thumbs-editor" id="thumbs-editor"></div>
                <div class="field">
                    <label style="display:flex;align-items:center;gap:0.4rem;font-weight:600;"><input type="checkbox" id="show-map" @checked($mapUrl)> Rādīt karti</label>
                </div>
                <div class="hint">Tiek izmantoti pirmie 4 attēli. Karte nāk no īpašuma atrašanās vietas.</div>
            </div>

            <div class="panel" data-panel="agent">
                <div class="agent-photo">
                    <img id="agent-photo-preview" src="{{ $agentAvatar ?: '' }}" alt="" style="{{ $agentAvatar ? '' : 'display:none;' }}">
                    <div id="agent-photo-fallback" class="fallback" style="{{ $agentAvatar ? 'display:none;' : '' }}">{{ $initials ?: 'P' }}</div>
                    <div>
                        <label class="btn" style="height:2.1rem;padding:0 0.7rem;font-size:0.8rem;cursor:pointer;"><i class="fa-solid fa-upload"></i>Mainīt foto<input type="file" id="agent-photo-input" accept="image/*" style="display:none;"></label>
                    </div>
                </div>
                <div class="field">
                    <label>Vārds</label>
                    <input type="text" data-bind="agentName" value="{{ $agentName }}">
                </div>
                <div class="field">
                    <label>Amats</label>
                    <input type="text" data-bind="agentRole" value="{{ $agentRole }}">
                </div>
                <div class="grid-2">
                    <div class="field">
                        <label>Tālrunis</label>
                        <input type="text" data-bind="agentPhone" value="{{ $agentPhone }}">
                    </div>
                    <div class="field">
                        <label>E-pasts</label>
                        <input type="email" data-bind="agentEmail" value="{{ $agentEmail }}">
                    </div>
                </div>
            </div>
        </aside>

        <div class="preview-wrap">
            <div class="preview-stage"><div class="preview-scaler" id="preview-scaler">
                <div id="flyer" class="flyer">
                    <div class="accent"></div>

                    <div class="flyer-header">
                        <div class="brand">
                            <img src="{{ asset('images/favicon-180x180.jpg') }}" alt="Pārdod Laimīgs">
                            <span>PardodLaimigs.lv</span>
                        </div>
                        <span class="chip" data-out="listingId">{{ $listingId }}</span>
                    </div>

                    <div class="flyer-grid">
                        <div class="col-left">
                            <h1 class="f-title" data-out="title">{{ $property->title }}</h1>
                            <p class="f-address" data-out="address">{{ $addressLine }}</p>

                            <div class="price-box">
                                <div class="f-price" data-out="price">{{ $priceValue }}</div>
                                <div class="price-meta" id="price-meta">
                                    <span data-out="area">{{ $areaValue }}</span> m²
                                    <span data-show-if="pricePerSqm">• <span data-out="pricePerSqm">{{ $pricePerSqmValue }}</span> €/m²</span>
                                </div>
                            </div>

                            <div class="desc" data-out="description">{!! nl2br(e($descriptionText)) !!}</div>

                            <a class="f-cta" data-out="cta" href="{{ $ctaUrl }}" target="_blank" rel="noopener">Sazināties ar aģentu</a>
                        </div>

                        <div class="col-right">
                            <div class="map" id="map-slot">
                                @if ($mapUrl)
                                    <img src="{{ $mapUrl }}" alt="Karte" data-map-img>
                                @elseif ($flyerImages->isNotEmpty())
                                    <img src="{{ $flyerImages->first() }}" alt="" data-map-img>
                                @else
                                    <div class="map-empty">Karte nav pieejama</div>
                                @endif
                            </div>
                            <div class="thumbs" id="thumbs-slot">
                                @foreach ($flyerImages->slice($mapUrl ? 0 : 1, 4) as $image)
                                    <img src="{{ $image }}" alt="{{ $property->title }}">
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="flyer-footer">
                        <div class="agent-card">
                            <div class="agent-info">
                                <img id="flyer-agent-photo" src="{{ $agentAvatar ?: '' }}" alt="" style="{{ $agentAvatar ? '' : 'display:none;' }}">
                                <div id="flyer-agent-fallback" class="agent-avatar-fallback" style="{{ $agentAvatar ? 'display:none;' : '' }}">{{ $initials ?: 'P' }}</div>
                                <div>
                                    <p class="agent-name" data-out="agentName">{{ $agentName ?: 'Pārdod Laimīgs' }}</p>
                                    <p class="agent-role" data-out="agentRole">{{ $agentRole }}</p>
                                </div>
                            </div>
                            <div class="agent-contact">
                                <p data-out="agentPhone">{{ $agentPhone }}</p>
                                <p data-out="agentEmail">{{ $agentEmail }}</p>
                            </div>
                        </div>
                        <div class="flyer-meta">
                            <span data-out="listingDate">{{ $listingDate }}</span>
                            <span>© pardodlaimigs.lv</span>
                        </div>
                    </div>
                </div>
            </div></div>
        </div>
    </div>

    <script>
        (function () {
            const CDNS = [
                'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js',
                'https://unpkg.com/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js',
                'https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js',
            ];
            const state = {
                images: @json($flyerImages->all()),
                map: @json($mapUrl),
                showMap: @json((bool) $mapUrl),
                agentAvatar: @json($agentAvatar),
            };
            const flyer = document.getElementById('flyer');

            function escapeHtml(value) {
                return String(value).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }

            function renderDescription(text) {
                const paragraphs = String(text).split(/\n{2,}/).filter(function (p) { return p.trim() !== ''; });
                if (paragraphs.length === 0) { return '<p></p>'; }
                return paragraphs.map(function (p) {
                    return '<p>' + escapeHtml(p).replace(/\n/g, '<br>') + '</p>';
                }).join('');
            }

            function update(key, value) {
                document.querySelectorAll('[data-out="' + key + '"]').forEach(function (node) {
                    if (key === 'description') { node.innerHTML = renderDescription(value); return; }
                    if (key === 'cta') { node.textContent = value; return; }
                    node.textContent = value;
                });
                if (key === 'pricePerSqm') {
                    const meta = document.querySelector('[data-show-if="pricePerSqm"]');
                    if (meta) { meta.style.display = String(value).trim() === '' ? 'none' : ''; }
                }
            }

            document.querySelectorAll('[data-bind]').forEach(function (input) {
                const key = input.dataset.bind;
                input.addEventListener('input', function () { update(key, input.value); });
                update(key, input.value);
            });

            // Cilnes
            document.querySelectorAll('.tabs button').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    document.querySelectorAll('.tabs button').forEach(function (t) { t.classList.toggle('active', t === tab); });
                    document.querySelectorAll('.panel').forEach(function (p) { p.classList.toggle('active', p.dataset.panel === tab.dataset.tab); });
                });
            });

            // Attēli
            const thumbsEditor = document.getElementById('thumbs-editor');
            function renderImages() {
                if (thumbsEditor) {
                    thumbsEditor.innerHTML = state.images.map(function (url, i) {
                        return '<div class="thumb-item"><img src="' + url + '" alt="">'
                            + '<div class="thumb-actions">'
                            + '<button type="button" class="icon-btn" data-move="' + i + '" title="Pārvietot uz augšu"><i class="fa-solid fa-arrow-up"></i></button>'
                            + '<button type="button" class="icon-btn icon-btn-danger" data-remove="' + i + '" title="Noņemt"><i class="fa-solid fa-trash"></i></button>'
                            + '</div></div>';
                    }).join('');
                }
                const mapSlot = document.getElementById('map-slot');
                const thumbsSlot = document.getElementById('thumbs-slot');
                if (mapSlot) {
                    if (state.showMap && state.map) {
                        mapSlot.innerHTML = '<img src="' + state.map + '" alt="Karte">';
                    } else if (state.images[0]) {
                        mapSlot.innerHTML = '<img src="' + state.images[0] + '" alt="">';
                    } else {
                        mapSlot.innerHTML = '<div class="map-empty">Karte nav pieejama</div>';
                    }
                }
                if (thumbsSlot) {
                    const list = state.showMap && state.map ? state.images.slice(0, 4) : state.images.slice(1, 5);
                    thumbsSlot.innerHTML = list.map(function (url) {
                        return '<img src="' + url + '" alt="">';
                    }).join('');
                }
            }
            renderImages();

            if (thumbsEditor) {
                thumbsEditor.addEventListener('click', function (e) {
                    const remove = e.target.closest('[data-remove]');
                    if (remove) { state.images.splice(Number(remove.dataset.remove), 1); renderImages(); return; }
                    const move = e.target.closest('[data-move]');
                    if (move) {
                        const i = Number(move.dataset.move);
                        if (i > 0) { const tmp = state.images[i - 1]; state.images[i - 1] = state.images[i]; state.images[i] = tmp; renderImages(); }
                    }
                });
            }

            const imageInput = document.getElementById('image-input');
            if (imageInput) {
                imageInput.addEventListener('change', function () {
                    Array.from(imageInput.files || []).forEach(function (file) {
                        state.images.push(URL.createObjectURL(file));
                    });
                    renderImages();
                    imageInput.value = '';
                });
            }

            const showMap = document.getElementById('show-map');
            if (showMap) {
                showMap.addEventListener('change', function () { state.showMap = showMap.checked; renderImages(); });
            }

            // Aģenta foto
            const agentPhotoInput = document.getElementById('agent-photo-input');
            if (agentPhotoInput) {
                agentPhotoInput.addEventListener('change', function () {
                    const file = (agentPhotoInput.files || [])[0];
                    if (!file) { return; }
                    state.agentAvatar = URL.createObjectURL(file);
                    const p = document.getElementById('agent-photo-preview');
                    const f = document.getElementById('agent-photo-fallback');
                    const fp = document.getElementById('flyer-agent-photo');
                    const ff = document.getElementById('flyer-agent-fallback');
                    p.src = state.agentAvatar; p.style.display = '';
                    fp.src = state.agentAvatar; fp.style.display = '';
                    f.style.display = 'none'; ff.style.display = 'none';
                    agentPhotoInput.value = '';
                });
            }

            // Priekšskatījuma mērogošana, lai A4 ietilptu ekrānā.
            const scaler = document.getElementById('preview-scaler');
            const stage = document.querySelector('.preview-stage');
            function fitPreview() {
                if (!scaler || !stage) { return; }
                const available = stage.parentElement.clientWidth;
                const scale = Math.min(1, available / 794);
                scaler.style.transform = 'scale(' + scale + ')';
                stage.style.height = (1122 * scale) + 'px';
                stage.style.width = (794 * scale) + 'px';
            }
            window.addEventListener('resize', fitPreview);
            fitPreview();

            // PDF lejupielāde
            function loadScript(url) {
                return new Promise(function (resolve, reject) {
                    const s = document.createElement('script');
                    s.src = url; s.async = true;
                    s.onload = resolve; s.onerror = function () { reject(new Error(url)); };
                    document.head.appendChild(s);
                });
            }
            async function ensureHtml2Pdf() {
                if (window.html2pdf) { return; }
                for (const url of CDNS) {
                    try { await loadScript(url); } catch (e) { /* try next */ }
                    if (window.html2pdf) { return; }
                }
                throw new Error('html2pdf nav pieejams');
            }
            function waitForImages(node) {
                return Promise.all(Array.from(node.querySelectorAll('img')).map(function (img) {
                    if (img.complete && img.naturalWidth > 0) { return Promise.resolve(); }
                    return new Promise(function (resolve) { img.onload = img.onerror = resolve; });
                }));
            }
            function buildCaptureNode() {
                const clone = flyer.cloneNode(true);
                clone.id = 'flyer-capture';
                clone.classList.add('flyer-capture');
                clone.style.position = 'fixed';
                clone.style.left = '-10000px';
                clone.style.top = '0';
                document.body.appendChild(clone);
                return clone;
            }

            window.pdcDownloadPdf = async function () {
                const button = document.getElementById('btn-download');
                const status = document.getElementById('status');
                const label = button.querySelector('span');
                const original = label.textContent;
                button.disabled = true;
                label.textContent = 'Ģenerē...';
                if (status) { status.textContent = ''; }
                const capture = buildCaptureNode();
                try {
                    await ensureHtml2Pdf();
                    await waitForImages(capture);
                    await window.html2pdf().set({
                        margin: 0,
                        filename: @json($filename),
                        image: { type: 'jpeg', quality: 0.95 },
                        html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false, scrollX: 0, scrollY: 0 },
                        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                        pagebreak: { mode: [] },
                    }).from(capture).save();
                    if (status) { status.textContent = 'PDF sagatavots.'; }
                } catch (error) {
                    console.warn('PDF ģenerēšana neizdevās:', error);
                    if (status) { status.textContent = 'Neizdevās ģenerēt PDF — atverama drukas forma.'; }
                    window.print();
                } finally {
                    capture.remove();
                    button.disabled = false;
                    label.textContent = original;
                }
            };
        })();
    </script>
</body>
</html>

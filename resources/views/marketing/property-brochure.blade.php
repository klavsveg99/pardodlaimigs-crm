@php
    $addressLine = trim(implode(', ', array_filter([$property->address, $property->city])));
    $priceValue = (float) $property->price_eur > 0
        ? number_format((float) $property->price_eur, 0, ',', ' ').' €'
        : 'Cena pēc pieprasījuma';
    $areaValue = $property->size_m2 ? (string) (int) $property->size_m2 : null;
    $landValue = $property->land_m2 ? (string) (int) $property->land_m2 : null;
    $pricePerSqmValue = $pricePerSqm ? number_format($pricePerSqm, 0, ',', ' ').' €/m²' : null;
    $categoryLabel = \App\Models\CrmProperty::CATEGORIES[$property->category] ?? ($property->category ?: '-');
    $filename = ($property->slug ?: 'ipasums').'-brochure.pdf';
    $allImages = collect($images)->filter()->values();
    $hero = $allImages->first();
    $gallery = $allImages->slice(1, 6)->values();
@endphp
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $property->title }} · Garais PDF</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/favicon-32x32.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@400;600;700&family=Maven+Pro:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --pdc-primary: #285854;
            --pdc-primary-darker: #1e4843;
            --pdc-gray: #414042;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #eef1f0; font-family: 'Source Sans Pro', system-ui, sans-serif; color: #1f2937; }

        .topbar {
            position: sticky; top: 0; z-index: 60;
            display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;
            padding: 0.7rem 1rem; background: #ffffff; border-bottom: 1px solid #e2e8e6;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .topbar .title { font-family: 'Maven Pro', sans-serif; font-weight: 700; font-size: 1rem; }
        .topbar .topbar-logo { height: 2rem; width: auto; object-fit: contain; flex: none; }
        .topbar .spacer { flex: 1 1 auto; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.45rem;
            height: 2.35rem; padding: 0 0.95rem; border-radius: 0.5rem;
            font-size: 0.86rem; font-weight: 600; cursor: pointer; white-space: nowrap;
            border: 1px solid #d1d9d7; background: #ffffff; color: #374151; text-decoration: none;
            font-family: inherit;
        }
        .btn:hover { background: #f3f6f5; }
        .btn-primary { background: var(--pdc-primary); border-color: var(--pdc-primary-darker); color: #ffffff; }
        .btn-primary:hover { background: var(--pdc-primary-darker); }
        .btn:disabled { opacity: 0.65; cursor: default; }
        .hint { font-size: 0.8rem; color: #9ca3af; }

        .preview-wrap { padding: 1.5rem; display: flex; justify-content: center; }
        .brochure { width: 794px; }

        .page {
            width: 794px; height: 1122px; min-height: 1122px;
            background: #ffffff; overflow: hidden; position: relative;
            page-break-after: always; break-after: page;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }
        .page:last-child { page-break-after: auto; break-after: auto; }
        .page + .page { margin-top: 1.5rem; }

        /* ── Cover ───────────────────────────────────────────── */
        .cover { display: flex; flex-direction: column; }
        .cover-band {
            background: var(--pdc-primary); padding: 26px 48px;
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
        }
        .cover-band img { height: 44px; width: auto; }
        .cover-kicker { color: rgba(255,255,255,0.82); font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; }
        .cover-body { padding: 38px 48px 0; flex: 1; display: flex; flex-direction: column; min-height: 0; }
        .cover h1 {
            font-family: 'Maven Pro', sans-serif; font-size: 34px; line-height: 1.12;
            margin: 0 0 10px; color: #111827; overflow-wrap: anywhere;
        }
        .cover-address { font-size: 17px; color: #6b7280; margin: 0 0 20px; }
        .cover-price { display: flex; align-items: baseline; gap: 14px; margin-bottom: 16px; }
        .cover-price .price { font-size: 34px; font-weight: 700; color: var(--pdc-primary); }
        .cover-price .ppsqm { font-size: 15px; color: #6b7280; }
        .cover-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 24px; }
        .cover-tags span { border: 1px solid #d1d9d7; border-radius: 4px; padding: 5px 10px; font-size: 12px; color: #374151; }
        .cover-hero { flex: 1; min-height: 0; border-radius: 8px; background-color: #e5e7eb; background-size: cover; background-position: center; }
        .cover-footer {
            padding: 14px 48px; display: flex; justify-content: space-between;
            color: #6b7280; font-size: 12px; border-top: 1px solid #f3f4f6;
        }

        /* ── Inner pages ─────────────────────────────────────── */
        .page-inner { padding: 44px 48px; height: 100%; display: flex; flex-direction: column; }
        .page-head {
            display: flex; align-items: center; justify-content: space-between;
            padding-bottom: 14px; border-bottom: 1px solid #e5e7eb; margin-bottom: 20px;
        }
        .page-brand { background: var(--pdc-primary); border-radius: 5px; padding: 4px 9px; }
        .page-brand img { height: 24px; width: auto; display: block; }
        .page-kicker { font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--pdc-primary); font-weight: 700; }
        .page h2 { font-family: 'Maven Pro', sans-serif; font-size: 25px; line-height: 1.15; margin: 0 0 14px; color: #111827; }
        .page-foot {
            margin-top: auto; padding-top: 12px; border-top: 1px solid #f3f4f6;
            display: flex; justify-content: space-between; color: #9ca3af; font-size: 11px;
        }

        .desc { font-size: 14px; line-height: 1.62; color: #4b5563; overflow: hidden; }
        .desc p { margin: 0 0 12px; }
        .desc ul, .desc ol { margin: 0 0 12px 1.15rem; padding: 0; }
        .desc li { margin: 0 0 4px; }
        .desc strong, .desc b { font-weight: 700; color: #1f2937; }
        .desc em, .desc i { font-style: italic; }

        .gallery { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-top: 18px; }
        .gallery-item { height: 150px; border-radius: 5px; background-color: #e5e7eb; background-size: cover; background-position: center; }

        .facts { width: 100%; border-collapse: collapse; font-size: 14px; }
        .facts th, .facts td { text-align: left; padding: 9px 0; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .facts th { width: 42%; color: #6b7280; font-weight: 600; }
        .facts td { color: #111827; }
        .map { margin-top: 18px; height: 290px; border-radius: 8px; overflow: hidden; background: #e5e7eb; }
        .map img { width: 100%; height: 100%; object-fit: cover; display: block; }

        /* ── Contact page ────────────────────────────────────── */
        .contact-band { background: var(--pdc-primary); padding: 30px 48px; display: flex; align-items: center; justify-content: space-between; }
        .contact-band img { height: 48px; width: auto; }
        .contact-band span { color: rgba(255,255,255,0.82); font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; }
        .contact-body { padding: 38px 48px; flex: 1; display: flex; flex-direction: column; }
        .steps { font-size: 15px; line-height: 1.7; color: #374151; padding-left: 20px; margin: 0 0 22px; }
        .steps li { margin-bottom: 6px; }
        .agent-block { display: flex; gap: 18px; align-items: center; padding: 20px; background: #f8faf9; border-radius: 8px; margin-bottom: 24px; }
        .agent-photo { width: 72px; height: 72px; border-radius: 9999px; object-fit: cover; flex: none; }
        .agent-name { font-size: 18px; font-weight: 700; margin: 0; color: #111827; }
        .agent-role { font-size: 13px; color: #6b7280; margin: 0 0 6px; }
        .agent-block p { margin: 2px 0; font-size: 14px; color: #374151; }
        .cta-row { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .cta { display: inline-block; background: var(--pdc-primary); color: #ffffff; text-decoration: none; padding: 14px 22px; border-radius: 6px; font-weight: 600; font-size: 15px; }
        .qr { text-align: center; }
        .qr img { width: 108px; height: 108px; display: block; margin: 0 auto 4px; }
        .qr span { font-size: 11px; color: #6b7280; }

        @page { size: A4; margin: 0; }

        @media (max-width: 900px) {
            .preview-wrap { padding: 0.75rem; }
            .brochure { transform-origin: top left; }
        }
        @media print {
            .no-print { display: none !important; }
            html, body { background: #ffffff; }
            .preview-wrap { padding: 0; display: block; }
            .page { box-shadow: none; margin: 0 !important; }
        }
    </style>
</head>
<body>
    <div class="topbar no-print">
        <img class="topbar-logo" src="{{ asset('site-assets/images/brand/logo.svg') }}" alt="Pārdod Laimīgs · CRM">
        <span class="title">Garais PDF</span>
        <button type="button" id="btn-download" class="btn btn-primary" onclick="pdcDownloadBrochure()">
            <i class="fa-solid fa-download"></i><span>Lejupielādēt PDF</span>
        </button>
        <button type="button" class="btn" onclick="window.print()"><i class="fa-solid fa-print"></i>Drukāt</button>
        <span class="spacer"></span>
        <span id="status" class="hint"></span>
        <a class="btn" href="{{ route('filament.admin.resources.properties.view', ['record' => $property->slug ?? $property->getKey()]) }}"><i class="fa-solid fa-arrow-left"></i>Atgriezties</a>
    </div>

    <div class="preview-wrap">
        <div id="brochure" class="brochure">
            {{-- 1. lpp. Vāks --}}
            <section class="page cover">
                <div class="cover-band">
                    <img src="{{ asset('site-assets/images/brand/logo.svg') }}" alt="Pārdod Laimīgs">
                    <span class="cover-kicker">Nekustamā īpašuma piedāvājums</span>
                </div>
                <div class="cover-body">
                    <h1>{{ $property->title }}</h1>
                    <p class="cover-address">{{ $addressLine }}</p>
                    <div class="cover-price">
                        <span class="price">{{ $priceValue }}</span>
                        @if ($pricePerSqmValue)
                            <span class="ppsqm">{{ $pricePerSqmValue }}</span>
                        @endif
                    </div>
                    <div class="cover-tags">
                        <span>{{ $categoryLabel }}</span>
                        @if ($areaValue)<span>{{ $areaValue }} m²</span>@endif
                        @if ($landValue)<span>{{ $landValue }} m² zeme</span>@endif
                        @if ($property->beds)<span>{{ $property->beds }} istabas</span>@endif
                    </div>
                    @if ($hero)
                        <div class="cover-hero" style="background-image:url('{{ $hero }}')"></div>
                    @endif
                </div>
                <div class="cover-footer">
                    <span>{{ $agentName ?: 'Pārdod Laimīgs' }}</span>
                    <span>{{ $agentPhone }}</span>
                </div>
            </section>

            {{-- 2. lpp. Apraksts un foto --}}
            <section class="page">
                <div class="page-inner">
                    <div class="page-head">
                        <span class="page-brand"><img src="{{ asset('site-assets/images/brand/logo.svg') }}" alt="Pārdod Laimīgs"></span>
                        <span class="page-kicker">Īpašuma apraksts</span>
                    </div>
                    <h2>{{ $property->title }}</h2>
                    <div class="desc">{!! $longDescriptionHtml ?? $descriptionHtml !!}</div>
                    @if ($gallery->isNotEmpty())
                        <div class="gallery">
                            @foreach ($gallery as $image)
                                <div class="gallery-item" style="background-image:url('{{ $image }}')"></div>
                            @endforeach
                        </div>
                    @endif
                    <div class="page-foot">
                        <span>{{ $addressLine }}</span>
                        <span>{{ $priceValue }}</span>
                    </div>
                </div>
            </section>

            {{-- 3. lpp. Objekta informācija --}}
            <section class="page">
                <div class="page-inner">
                    <div class="page-head">
                        <span class="page-brand"><img src="{{ asset('site-assets/images/brand/logo.svg') }}" alt="Pārdod Laimīgs"></span>
                        <span class="page-kicker">Objekta informācija</span>
                    </div>
                    <h2>Objekta informācija</h2>
                    <table class="facts">
                        <tr><th>Adrese</th><td>{{ $addressLine ?: '-' }}</td></tr>
                        <tr><th>Kategorija</th><td>{{ $categoryLabel }}</td></tr>
                        <tr><th>Platība</th><td>{{ $areaValue ? $areaValue.' m²' : '-' }}</td></tr>
                        <tr><th>Zemes platība</th><td>{{ $landValue ? $landValue.' m²' : '-' }}</td></tr>
                        <tr><th>Istabas</th><td>{{ $property->beds ?: '-' }}</td></tr>
                        <tr><th>Kadastra numurs</th><td>{{ $property->kadastra_nr ?: '-' }}</td></tr>
                        <tr><th>Cena</th><td>{{ $priceValue }}</td></tr>
                        <tr><th>Cena par m²</th><td>{{ $pricePerSqmValue ?: '-' }}</td></tr>
                    </table>
                    @if ($mapUrl)
                        <div class="map"><img src="{{ $mapUrl }}" alt="Karte"></div>
                    @endif
                    <div class="page-foot">
                        <span>{{ $property->city ?: '' }}</span>
                        <span>{{ $listingDate }}</span>
                    </div>
                </div>
            </section>

            {{-- 4. lpp. Kontakti un nākamie soļi --}}
            <section class="page">
                <div class="contact-band">
                    <img src="{{ asset('site-assets/images/brand/logo.svg') }}" alt="Pārdod Laimīgs">
                    <span>Nākamie soļi</span>
                </div>
                <div class="contact-body">
                    <h2>Nākamie soļi</h2>
                    <ol class="steps">
                        <li>Sazinies ar mums un piesakies apskatei.</li>
                        <li>Vienosimies par tikšanos un izvērtēsim objektu.</li>
                        <li>Nodrošināsim dokumentu pārbaudi un darījuma gaitas atbalstu.</li>
                    </ol>
                    <div class="agent-block">
                        @if ($agentAvatar)
                            <img class="agent-photo" src="{{ $agentAvatar }}" alt="">
                        @endif
                        <div>
                            <p class="agent-name">{{ $agentName ?: 'Pārdod Laimīgs' }}</p>
                            <p class="agent-role">{{ $agentRole }}</p>
                            @if ($agentPhone)<p>{{ $agentPhone }}</p>@endif
                            @if ($agentEmail)<p>{{ $agentEmail }}</p>@endif
                        </div>
                    </div>
                    <div class="cta-row">
                        @if ($ctaUrl)
                            <a class="cta" href="{{ $ctaUrl }}" target="_blank" rel="noopener">Skatīt sludinājumu</a>
                        @endif
                        @if ($qrDataUri)
                            <div class="qr">
                                <img src="{{ $qrDataUri }}" alt="QR kods">
                                <span>Skenē un atver sludinājumu</span>
                            </div>
                        @endif
                    </div>
                    <div class="page-foot">
                        <span>pardodlaimigs.lv</span>
                        <span>{{ $listingId }}</span>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        (function () {
            function ensureHtml2Pdf() {
                return new Promise(function (resolve, reject) {
                    if (window.html2pdf) { resolve(); return; }
                    var sources = [
                        'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js',
                        'https://unpkg.com/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js',
                        'https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js'
                    ];
                    var index = 0;
                    function load() {
                        if (index >= sources.length) { reject(new Error('html2pdf nav pieejams')); return; }
                        var script = document.createElement('script');
                        script.src = sources[index++];
                        script.onload = function () { window.html2pdf ? resolve() : load(); };
                        script.onerror = load;
                        document.head.appendChild(script);
                    }
                    load();
                });
            }

            window.pdcDownloadBrochure = async function () {
                var button = document.getElementById('btn-download');
                var status = document.getElementById('status');
                var label = button.querySelector('span');
                var original = label.textContent;
                button.disabled = true;
                label.textContent = 'Ģenerē...';
                if (status) { status.textContent = ''; }
                try {
                    await ensureHtml2Pdf();
                    await window.html2pdf().set({
                        margin: 0,
                        filename: @json($filename),
                        image: { type: 'jpeg', quality: 0.95 },
                        html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false, scrollX: 0, scrollY: 0 },
                        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                        pagebreak: { mode: ['css', 'legacy'] }
                    }).from(document.getElementById('brochure')).save();
                    if (status) { status.textContent = 'PDF sagatavots.'; }
                } catch (error) {
                    console.warn('PDF ģenerēšana neizdevās:', error);
                    if (status) { status.textContent = 'Neizdevās ģenerēt PDF - atverama drukas forma.'; }
                    window.print();
                } finally {
                    button.disabled = false;
                    label.textContent = original;
                }
            };
        })();
    </script>
</body>
</html>

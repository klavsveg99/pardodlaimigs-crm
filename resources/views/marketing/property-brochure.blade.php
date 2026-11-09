@php
    $addressLine = trim(implode(', ', array_filter([$property->address, $property->city])));
    $priceValue = (float) $property->price_eur > 0
        ? number_format((float) $property->price_eur, 0, ',', ' ').' €'
        : 'Cena pēc pieprasījuma';
    $areaValue = $property->size_m2 ? (string) (int) $property->size_m2 : null;
    $landValue = $property->land_m2 ? (string) (int) $property->land_m2 : null;
    $landDisplay = $property->land_m2
        ? ((int) $property->land_m2 >= 10000
            ? rtrim(rtrim(number_format($property->land_m2 / 10000, 2, ',', ' '), '0'), ',').' ha'
            : number_format($property->land_m2, 0, ',', ' ').' m²')
        : null;
    $pricePerSqmValue = $pricePerSqm ? number_format($pricePerSqm, 0, ',', ' ').' €/m²' : null;
    $categoryLabel = \App\Models\CrmProperty::CATEGORIES[$property->category] ?? ($property->category ?: '-');
    $filename = ($property->slug ?: 'ipasums').'-brochure.pdf';
    $allImages = collect($images)->filter()->values();
    $hero = $allImages->first();
    $gallery = $allImages->slice(1, 6)->values();
    $coverFacts = collect([
        ['value' => $areaValue ? $areaValue.' m²' : null, 'label' => 'Telpu platība'],
        ['value' => $landDisplay, 'label' => 'Zeme'],
        ['value' => $property->beds ? (string) $property->beds : null, 'label' => 'Istabas'],
        ['value' => $pricePerSqmValue, 'label' => 'Cena par m²'],
    ])->filter(fn (array $fact) => filled($fact['value']))->values();
    $propertyFacts = collect([
        ['Platība', $areaValue ? $areaValue.' m²' : null],
        ['Zemes platība', $landDisplay],
        ['Istabas', $property->beds ?: null],
        ['Vannasistabas', $property->baths ?: null],
        ['Kadastra Nr.', $property->kadastra_nr],
        ['Cena', $priceValue],
    ])->filter(fn (array $fact) => filled($fact[1]))->values();
    $locationFacts = collect([
        ['Atrašanās vieta', $addressLine],
        ['Kategorija', $categoryLabel],
        ['Statuss', \App\Models\CrmProperty::STATUSES[$property->status] ?? $property->status],
        ['Sludinājums kopš', $listingDate],
    ])->filter(fn (array $fact) => filled($fact[1]))->values();
    $initials = mb_strtoupper(mb_substr((string) $agentName, 0, 1));
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
            --green: #285854;
            --green-dark: #1e4843;
            --gold: #c99755;
            --paper: #faf8f3;
            --ink: #263b38;
            --muted: #737a75;
            --line: #dedbd2;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #eef1f0; color: var(--ink); font-family: 'Source Sans Pro', system-ui, sans-serif; }
        .topbar {
            position: sticky; top: 0; z-index: 60; display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
            padding: .7rem 1rem; background: #fff; border-bottom: 1px solid #e2e8e6; box-shadow: 0 1px 4px #0000000a;
        }
        .topbar .title { font-family: 'Maven Pro', sans-serif; font-weight: 700; font-size: 1rem; }
        .topbar-logo { height: 2rem; width: auto; }
        .spacer { flex: 1 1 auto; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; height: 2.35rem; padding: 0 .95rem; border: 1px solid #d1d9d7; border-radius: .5rem; background: #fff; color: #374151; font: 600 .86rem 'Source Sans Pro', sans-serif; text-decoration: none; cursor: pointer; white-space: nowrap; }
        .btn-primary { background: var(--green); border-color: var(--green-dark); color: #fff; }
        .btn:disabled { opacity: .65; cursor: default; }
        .hint { color: #737a75; font-size: .8rem; }

        .preview-wrap { padding: 1.5rem; display: flex; justify-content: center; }
        .brochure { width: 794px; }
        .page { position: relative; width: 794px; height: 1122px; overflow: hidden; page-break-after: always; break-after: page; box-shadow: 0 10px 30px #0000001f; }
        .page:last-child { page-break-after: auto; break-after: auto; }
        .page + .page { margin-top: 1.5rem; }
        .eyebrow { color: var(--gold); font-size: 11px; font-weight: 700; letter-spacing: .22em; text-transform: uppercase; }
        .page-title { margin: 6px 0 18px; color: var(--green); font: 700 28px/1.12 'Maven Pro', sans-serif; }
        .page-footer { position: absolute; right: 58px; bottom: 30px; left: 58px; display: flex; align-items: center; gap: 12px; border-top: 1px solid var(--line); padding-top: 11px; color: var(--muted); font-size: 11px; }
        .page-footer img { width: 78px; height: auto; }
        .page-footer .footer-title { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .page-footer .page-number { white-space: nowrap; }

        /* Cover */
        .cover { display: flex; flex-direction: column; background: var(--green); }
        .cover-photo { position: relative; flex: 0 0 53%; background: #d7ddd7; background-position: center; background-size: cover; }
        .cover-logo { position: absolute; top: 42px; left: 58px; padding: 13px 20px; border-radius: 8px; background: var(--green); }
        .cover-logo img { display: block; width: 164px; height: auto; }
        .status-tag { position: absolute; top: 45px; right: 58px; padding: 10px 17px; background: var(--gold); color: var(--green-dark); font-size: 13px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
        .cover-content { flex: 1; display: flex; flex-direction: column; padding: 36px 58px 34px; color: #f7f3e9; }
        .cover-kicker { margin-bottom: 10px; color: var(--gold); font-size: 11px; font-weight: 700; letter-spacing: .24em; text-transform: uppercase; }
        .cover h1 { margin: 0; color: #f8f5ed; font: 700 36px/1.1 'Maven Pro', sans-serif; }
        .cover-address { margin: 5px 0 24px; color: #d4d9d3; font-size: 17px; }
        .cover-price-row { display: flex; align-items: end; justify-content: space-between; gap: 20px; margin-bottom: 24px; }
        .cover-price { color: #f8f5ed; font: 700 40px/1 'Maven Pro', sans-serif; }
        .cover-price-meta { margin-top: 6px; color: #d4d9d3; font-size: 15px; }
        .cover-agent { display: flex; align-items: center; gap: 12px; text-align: right; }
        .cover-agent .agent-initial { display: grid; width: 48px; height: 48px; place-items: center; border-radius: 50%; background: var(--gold); color: var(--green-dark); font-size: 18px; font-weight: 700; }
        .cover-agent strong, .cover-agent span { display: block; }
        .cover-agent strong { font-size: 15px; }
        .cover-agent span { color: #d4d9d3; font-size: 13px; }
        .cover-facts { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: auto; }
        .cover-fact { min-height: 90px; padding: 13px 15px; border: 1px solid #ffffff3b; border-radius: 9px; background: #ffffff12; }
        .cover-fact strong { display: block; margin-bottom: 3px; color: #f8f5ed; font: 700 21px/1.1 'Maven Pro', sans-serif; }
        .cover-fact span { color: #d4d9d3; font-size: 12px; }

        /* Description page */
        .why-page { background: var(--paper); padding: 54px 58px 85px; }
        .why-page .page-title { margin-bottom: 14px; }
        .description { max-height: 235px; overflow: hidden; color: #33413e; font-size: 17px; line-height: 1.5; }
        .description p { margin: 0 0 10px; }
        .description ul, .description ol { margin: 12px 0 14px; padding: 0; list-style: none; }
        .description li { position: relative; margin: 0 0 8px; padding-left: 30px; }
        .description li::before { position: absolute; top: 2px; left: 0; display: grid; width: 19px; height: 19px; place-items: center; border-radius: 50%; background: var(--green); color: #fff; content: '✓'; font-size: 12px; }
        .photo-grid { display: grid; height: 482px; grid-template-columns: 1.35fr 1fr; grid-template-rows: 1fr 1fr; gap: 10px; margin-top: 22px; }
        .photo { min-width: 0; min-height: 0; border-radius: 7px; background: #e4e6e0 center/cover no-repeat; }
        .photo:first-child { grid-row: span 2; }
        .listing-link { margin-top: 9px; color: var(--muted); font-size: 12px; }

        /* Property information page */
        .details-page { background: var(--paper); padding: 54px 58px 85px; }
        .details-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 42px; }
        .details-page h3 { margin: 13px 0 8px; color: var(--green); font: 700 17px 'Maven Pro', sans-serif; }
        .details-list { margin: 0; padding: 0; list-style: none; }
        .details-list li { display: grid; grid-template-columns: 39% 1fr; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--line); font-size: 13px; line-height: 1.35; }
        .details-list strong { color: #35413e; }
        .details-list span { color: #3f4b48; }
        .details-image { width: 100%; height: 205px; margin-top: 18px; border-radius: 8px; background: #e4e6e0 center/cover no-repeat; }
        .map { height: 282px; margin-top: 24px; overflow: hidden; border: 1px solid var(--line); border-radius: 10px; background: #e8ebe5; }
        .map img { display: block; width: 100%; height: 100%; object-fit: cover; }

        /* Contact page */
        .next-page { background: var(--green); color: #f8f5ed; padding: 62px 58px 45px; }
        .next-logo { display: block; width: 205px; height: auto; margin: 0 auto 48px; }
        .next-kicker { color: var(--gold); text-align: center; }
        .next-page h2 { margin: 12px 0 12px; color: #f8f5ed; text-align: center; font: 700 40px/1.05 'Maven Pro', sans-serif; }
        .next-intro { max-width: 540px; margin: 0 auto 36px; color: #d4d9d3; text-align: center; font-size: 17px; line-height: 1.5; }
        .agent-panel { display: flex; align-items: center; gap: 18px; padding: 24px 28px; border-radius: 16px; background: var(--paper); color: var(--ink); }
        .agent-photo { width: 64px; height: 64px; flex: none; border-radius: 50%; object-fit: cover; }
        .agent-fallback { display: grid; width: 64px; height: 64px; flex: none; place-items: center; border-radius: 50%; background: var(--gold); color: var(--green-dark); font-size: 22px; font-weight: 700; }
        .agent-name { margin: 0; font: 700 19px 'Maven Pro', sans-serif; }
        .agent-role { margin: 2px 0 7px; color: var(--muted); font-size: 13px; }
        .agent-contact { margin: 2px 0; font-size: 14px; }
        .qr { margin-left: auto; text-align: center; }
        .qr img { display: block; width: 92px; height: 92px; margin: 0 auto 4px; }
        .qr span { color: var(--muted); font-size: 10px; }
        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 40px; }
        .step { padding-top: 12px; border-top: 2px solid var(--gold); }
        .step strong { display: block; margin-bottom: 5px; font: 700 16px 'Maven Pro', sans-serif; }
        .step span { color: #d4d9d3; font-size: 13px; line-height: 1.4; }
        .disclaimer { position: absolute; right: 58px; bottom: 30px; left: 58px; color: #c4d0ca; text-align: center; font-size: 10px; line-height: 1.45; }

        @page { size: A4; margin: 0; }
        @media (max-width: 900px) { .preview-wrap { padding: .75rem; } }
        @media print {
            .no-print { display: none !important; }
            html, body { background: #fff; }
            .preview-wrap { display: block; padding: 0; }
            .page { box-shadow: none; }
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
            <section class="page cover">
                <div class="cover-photo" @if ($hero) style="background-image:url('{{ $hero }}')" @endif>
                    <div class="cover-logo"><img src="{{ asset('site-assets/images/brand/logo.svg') }}" alt="Pārdod Laimīgs"></div>
                    <span class="status-tag">{{ \App\Models\CrmProperty::STATUSES[$property->status] ?? $property->status }}</span>
                </div>
                <div class="cover-content">
                    <div class="cover-kicker">{{ mb_strtoupper($categoryLabel) }}{{ $addressLine ? ' · '.mb_strtoupper($addressLine) : '' }}</div>
                    <h1>{{ $property->title }}</h1>
                    @if ($addressLine)<p class="cover-address">{{ $addressLine }}</p>@endif
                    <div class="cover-price-row">
                        <div>
                            <div class="cover-price">{{ $priceValue }}</div>
                            @if ($pricePerSqmValue)<div class="cover-price-meta">{{ $pricePerSqmValue }}</div>@endif
                        </div>
                        <div class="cover-agent">
                            <div><strong>{{ $agentName ?: 'Pārdod Laimīgs' }}</strong>@if ($agentPhone)<span>{{ $agentPhone }}</span>@endif</div>
                            <div class="agent-initial">{{ $initials ?: 'P' }}</div>
                        </div>
                    </div>
                    @if ($coverFacts->isNotEmpty())
                        <div class="cover-facts">
                            @foreach ($coverFacts as $fact)
                                <div class="cover-fact"><strong>{{ $fact['value'] }}</strong><span>{{ $fact['label'] }}</span></div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            <section class="page why-page">
                <div class="eyebrow">Kāpēc šis īpašums</div>
                <h2 class="page-title">{{ $property->title }}</h2>
                <div class="description">{!! $longDescriptionHtml ?? $descriptionHtml !!}</div>
                <div class="photo-grid">
                    @foreach ($allImages->take(3) as $image)
                        <div class="photo" style="background-image:url('{{ $image }}')"></div>
                    @endforeach
                </div>
                @if ($ctaUrl)<div class="listing-link">Visas bildes skatāmas sludinājumā: {{ parse_url($ctaUrl, PHP_URL_HOST).parse_url($ctaUrl, PHP_URL_PATH) }}</div>@endif
                <div class="page-footer">
                    <img src="{{ asset('site-assets/images/brand/logo.svg') }}" alt="Pārdod Laimīgs">
                    <span class="footer-title">{{ $property->title }} · {{ $priceValue }}</span>
                    <span class="page-number">2 / 4</span>
                </div>
            </section>

            <section class="page details-page">
                <div class="eyebrow">Telpas, ēkas un tehniskā informācija</div>
                <h2 class="page-title">Viss svarīgākais vienuviet</h2>
                <div class="details-columns">
                    <div>
                        <h3>Īpašuma dati</h3>
                        <ul class="details-list">
                            @foreach ($propertyFacts as [$label, $value])
                                <li><strong>{{ $label }}</strong><span>{{ $value }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                    <div>
                        <h3>Tehniskā informācija</h3>
                        <ul class="details-list">
                            @foreach ($locationFacts as [$label, $value])
                                <li><strong>{{ $label }}</strong><span>{{ $value }}</span></li>
                            @endforeach
                        </ul>
                        @if ($allImages->count() > 3)
                            <div class="details-image" style="background-image:url('{{ $allImages[3] }}')"></div>
                        @endif
                    </div>
                </div>
                @if ($mapUrl)<div class="map"><img src="{{ $mapUrl }}" alt="Īpašuma atrašanās vieta"></div>@endif
                <div class="page-footer">
                    <img src="{{ asset('site-assets/images/brand/logo.svg') }}" alt="Pārdod Laimīgs">
                    <span class="footer-title">{{ $property->title }} · {{ $priceValue }}</span>
                    <span class="page-number">3 / 4</span>
                </div>
            </section>

            <section class="page next-page">
                <img class="next-logo" src="{{ asset('site-assets/images/brand/logo.svg') }}" alt="Pārdod Laimīgs">
                <div class="eyebrow next-kicker">Nākamais solis</div>
                <h2>Piesakies apskatei</h2>
                <p class="next-intro">Sazinies ar mums, lai vienotos par apskati un saņemtu atbildes uz jautājumiem par īpašumu.</p>
                <div class="agent-panel">
                    @if ($agentAvatar)
                        <img class="agent-photo" src="{{ $agentAvatar }}" alt="">
                    @else
                        <div class="agent-fallback">{{ $initials ?: 'P' }}</div>
                    @endif
                    <div>
                        <p class="agent-name">{{ $agentName ?: 'Pārdod Laimīgs' }}</p>
                        <p class="agent-role">{{ $agentRole }} · Pārdod Laimīgs</p>
                        @if ($agentPhone)<p class="agent-contact">{{ $agentPhone }}</p>@endif
                        @if ($agentEmail)<p class="agent-contact">{{ $agentEmail }}</p>@endif
                    </div>
                    @if ($qrDataUri)
                        <div class="qr"><img src="{{ $qrDataUri }}" alt="QR kods"><span>Sludinājums ar visām bildēm</span></div>
                    @endif
                </div>
                <div class="steps">
                    <div class="step"><strong>1. Zvani vai raksti</strong><span>Vienojamies par ērtu apskates laiku.</span></div>
                    <div class="step"><strong>2. Apskate</strong><span>Parādām īpašumu un atbildam uz jautājumiem.</span></div>
                    <div class="step"><strong>3. Darījums</strong><span>Palīdzam ar nākamajiem darījuma soļiem.</span></div>
                </div>
                <div class="disclaimer">Piedāvājums sagatavots {{ now()->format('d.m.Y') }}. Informācija ir aptuvena un var mainīties; tā nav publisks piedāvājums. Pārdod Laimīgs · pardodlaimigs.lv · info@pardodlaimigs.lv</div>
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

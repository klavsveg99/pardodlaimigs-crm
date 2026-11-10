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
    $gallery = $allImages->slice(1, 4)->values();
    $initials = mb_strtoupper(mb_substr((string) $agentName, 0, 1));
    $hasArea = filled($areaValue);
    $hasLand = filled($landDisplay);
    $hasRooms = filled($property->beds);
    $hasBaths = filled($property->baths);
    $hasKadastra = filled($property->kadastra_nr);
    $hasPricePerSqm = filled($pricePerSqmValue);
    $statusLabel = \App\Models\CrmProperty::STATUSES[$property->status] ?? $property->status;

    $coverFacts = collect();
    if ($hasArea) {
        $coverFacts->push(['value' => $areaValue.' m²', 'label' => 'Telpu platība', 'key' => 'area']);
    }
    if ($hasLand) {
        $coverFacts->push(['value' => $landDisplay, 'label' => 'Zemes platība', 'key' => 'land']);
    }
    if ($hasRooms) {
        $coverFacts->push(['value' => (string) $property->beds, 'label' => 'Istabas', 'key' => 'rooms']);
    }
    if ($hasPricePerSqm) {
        $coverFacts->push(['value' => $pricePerSqmValue, 'label' => 'Cena par m²', 'key' => 'pricePerSqm']);
    }
    if ($hasKadastra && $coverFacts->count() < 4) {
        $coverFacts->push(['value' => $property->kadastra_nr, 'label' => 'Kadastra Nr.', 'key' => null]);
    }
    if ($coverFacts->count() < 4) {
        $coverFacts->push(['value' => $categoryLabel, 'label' => 'Kategorija', 'key' => null]);
    }
    if ($coverFacts->count() < 4) {
        $coverFacts->push(['value' => $statusLabel, 'label' => 'Statuss', 'key' => null]);
    }
    $coverFacts = $coverFacts->take(4);

    $propertyFacts = collect();
    if ($hasArea) {
        $propertyFacts->push(['Platība', $areaValue.' m²', 'area']);
    }
    if ($hasLand) {
        $propertyFacts->push(['Zemes platība', $landDisplay, 'land']);
    }
    if ($hasRooms) {
        $propertyFacts->push(['Istabas', (string) $property->beds, 'rooms']);
    }
    if ($hasBaths) {
        $propertyFacts->push(['Vannasistabas', (string) $property->baths, null]);
    }
    if ($hasKadastra) {
        $propertyFacts->push(['Kadastra Nr.', $property->kadastra_nr, null]);
    }
    if ($hasPricePerSqm) {
        $propertyFacts->push(['Cena par m²', $pricePerSqmValue, 'pricePerSqm']);
    }
    $propertyFacts->push(['Cena', $priceValue, 'price']);

    $locationFacts = collect([
        ['Atrašanās vieta', $addressLine, 'address'],
        ['Kategorija', $categoryLabel, null],
        ['Statuss', $statusLabel, null],
        ['Sludinājums kopš', $listingDate, 'listingDate'],
    ])->filter(fn (array $fact) => filled($fact[1]))->values();
@endphp
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $property->title }} · Ģenerēt PDF</title>
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

        /* ── Topbar ─────────────────────────────────────────── */
        .topbar {
            position: sticky; top: 0; z-index: 60; display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
            padding: .7rem 1rem; background: #fff; border-bottom: 1px solid #e2e8e6; box-shadow: 0 1px 4px #0000000a;
        }
        .topbar .title { font-family: 'Maven Pro', sans-serif; font-weight: 700; font-size: 1rem; }
        .topbar-logo { height: 2rem; width: 2rem; }
        .spacer { flex: 1 1 auto; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; height: 2.35rem; padding: 0 .95rem; border: 1px solid #d1d9d7; border-radius: .5rem; background: #fff; color: #374151; font: 600 .86rem 'Source Sans Pro', sans-serif; text-decoration: none; cursor: pointer; white-space: nowrap; }
        .btn-primary { background: var(--green); border-color: var(--green-dark); color: #fff; }
        .btn-primary:hover { background: var(--green-dark); }
        .btn-xs { height: 1.9rem; padding: 0 .6rem; font-size: .76rem; border-radius: .45rem; }
        .btn-block { width: 100%; }
        .btn:disabled { opacity: .65; cursor: default; }
        .hint { color: #737a75; font-size: .8rem; }

        /* ── Editor ─────────────────────────────────────────── */
        .layout { display: grid; grid-template-columns: 400px minmax(0, 1fr); gap: 1.25rem; padding: 1.25rem; align-items: start; max-width: 100%; }
        .editor { background: #fff; border: 1px solid #e2e8e6; border-radius: .75rem; overflow: hidden; position: sticky; top: 4.5rem; min-width: 0; max-width: 100%; }
        .tabs { display: flex; border-bottom: 1px solid #e2e8e6; }
        .tabs button { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; padding: .7rem .5rem; border: 0; border-bottom: 2px solid transparent; background: transparent; font-family: inherit; font-size: .85rem; font-weight: 600; color: #6b7280; cursor: pointer; }
        .tabs button.active { color: var(--green); border-bottom-color: var(--green); }
        .panel { display: none; padding: 1rem; max-height: calc(100vh - 12rem); overflow-y: auto; min-width: 0; }
        .panel.active { display: block; }
        .field { margin-bottom: .8rem; }
        .field-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .25rem; }
        .field label { display: block; font-size: .78rem; font-weight: 600; color: #374151; margin-bottom: .3rem; }
        .field-head label { margin-bottom: 0; }
        .field label.btn { display: inline-flex; font-size: .86rem; margin-bottom: 0; }
        .field input[type=text], .field input[type=email], .field textarea { width: 100%; border: 1px solid #d7dedc; border-radius: .5rem; padding: .5rem .6rem; font-size: .875rem; font-family: inherit; color: #111827; background: #fff; }
        .field input:focus, .field textarea:focus { outline: 2px solid rgba(40,88,84,.25); border-color: var(--green); }
        .field textarea { resize: vertical; min-height: 7rem; }
        .field input[type=file] { width: 100%; max-width: 100%; font-size: .8rem; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; min-width: 0; }
        .editor .hint { font-size: .72rem; color: #9ca3af; margin-top: .2rem; }
        .field-foot { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .counter-over { color: #cf2e2e; font-weight: 600; }
        .wysiwyg { border: 1px solid #d7dedc; border-radius: .5rem; overflow: hidden; background: #fff; min-width: 0; }
        .wysiwyg-toolbar { display: flex; gap: .15rem; padding: .3rem; border-bottom: 1px solid #e2e8e6; background: #f8faf9; flex-wrap: wrap; }
        .wysiwyg-toolbar button { width: 2rem; height: 2rem; border: 1px solid transparent; border-radius: .4rem; background: transparent; color: #374151; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: .8rem; }
        .wysiwyg-toolbar button:hover { background: #e8efed; }
        .wysiwyg-editor { min-height: 9rem; max-height: 22rem; overflow-y: auto; padding: .55rem .65rem; font-size: .875rem; line-height: 1.55; color: #111827; outline: none; overflow-wrap: anywhere; word-break: break-word; }
        .wysiwyg-editor:focus { box-shadow: inset 0 0 0 2px rgba(40,88,84,.18); }
        .wysiwyg-editor p { margin: 0 0 .5rem; }
        .wysiwyg-editor ul, .wysiwyg-editor ol { margin: 0 0 .5rem 1.15rem; padding: 0; }
        .wysiwyg-editor li { margin-bottom: .15rem; }
        .thumbs-editor { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; margin-bottom: .6rem; }
        .thumb-item { position: relative; border: 1px solid #e2e8e6; border-radius: .5rem; overflow: hidden; background: #f3f4f6; }
        .thumb-item img { width: 100%; height: 84px; object-fit: cover; display: block; }
        .thumb-actions { position: absolute; top: .3rem; right: .3rem; display: flex; gap: .25rem; }
        .icon-btn { width: 1.7rem; height: 1.7rem; border-radius: .4rem; border: 1px solid rgba(255,255,255,.5); background: rgba(0,0,0,.6); color: #fff; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: .75rem; }
        .icon-btn:hover { background: rgba(0,0,0,.8); }
        .icon-btn-danger { background: rgba(185,28,28,.85); }
        .main-thumb { border: 1px solid #e2e8e6; border-radius: .5rem; overflow: hidden; background: #f3f4f6; margin-bottom: .4rem; }
        .main-thumb img { width: 100%; height: 130px; object-fit: cover; display: block; }
        .main-empty { display: flex; align-items: center; justify-content: center; height: 130px; border: 1px dashed #d7dedc; border-radius: .5rem; color: #9ca3af; font-size: .8rem; }
        .editor .agent-photo { display: flex; align-items: center; gap: .7rem; margin-bottom: .8rem; }
        .editor .agent-photo img { width: 3.5rem; height: 3.5rem; border-radius: 50%; object-fit: cover; }
        .editor .agent-photo .fallback { display: flex; width: 3.5rem; height: 3.5rem; align-items: center; justify-content: center; border-radius: 50%; background: var(--green); color: #fff; font-size: 1.3rem; font-weight: 700; }

        /* ── Brochure ───────────────────────────────────────── */
        .preview-wrap { padding: 1.5rem; display: flex; justify-content: center; overflow-x: auto; }
        .brochure { width: 794px; flex: none; }
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
        .description { max-height: 300px; overflow: hidden; overflow-wrap: anywhere; color: #33413e; font-size: 16px; line-height: 1.4; }
        .description p { margin: 0 0 10px; }
        .description ul, .description ol { max-width: 100%; margin: 10px 0 12px; padding: 0; list-style: none; }
        .description li { position: relative; display: block; min-width: 0; max-width: 100%; margin: 0 0 5px; padding-left: 30px; overflow-wrap: anywhere; word-break: break-word; }
        .description li > * { max-width: 100%; overflow-wrap: anywhere; word-break: break-word; }
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
        .details-list li { display: grid; min-width: 0; grid-template-columns: minmax(0, 39%) minmax(0, 1fr); gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--line); font-size: 13px; line-height: 1.35; overflow-wrap: anywhere; }
        .details-list li > * { min-width: 0; overflow-wrap: anywhere; }
        .details-list strong { color: #35413e; }
        .details-list span { color: #3f4b48; }
        .details-image { width: 100%; height: 205px; margin-top: 18px; border-radius: 8px; background: #e4e6e0 center/cover no-repeat; }
        .map { height: 282px; margin-top: 24px; overflow: hidden; border: 1px solid var(--line); border-radius: 10px; background: #e8ebe5; }
        .map img { display: block; width: 100%; height: 100%; object-fit: cover; }
        .map-empty { display: flex; align-items: center; justify-content: center; height: 100%; color: #9ca3af; font-size: 13px; }

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
        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; padding: .75rem; }
            .editor { position: static; }
            .preview-wrap { padding: .75rem 0; justify-content: flex-start; }
        }
        @media print {
            .no-print { display: none !important; }
            html, body { background: #fff; }
            .layout { display: block; padding: 0; }
            .preview-wrap { display: block; padding: 0; overflow: visible; }
            .page, .page + .page { margin-top: 0; box-shadow: none; }
            .page:last-child { page-break-after: auto; break-after: auto; }
        }
        body.pdf-exporting .page + .page { margin-top: 0; }
    </style>
</head>
<body>
    <div class="topbar no-print">
        <img class="topbar-logo" src="{{ asset('images/favicon-180x180.jpg') }}" alt="Pārdod Laimīgs · CRM">
        <span class="title">Ģenerēt PDF</span>
        <button type="button" id="btn-download" class="btn btn-primary" onclick="pdcDownloadBrochure()">
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
                        <label>Cena (€)</label>
                        <input type="text" data-bind="price" value="{{ (float) $property->price_eur > 0 ? number_format((float) $property->price_eur, 0, ',', ' ') : '' }}">
                    </div>
                    <div class="field">
                        <label>Cena par m² (€/m²)</label>
                        <input type="text" data-bind="pricePerSqm" value="{{ $pricePerSqm ? number_format($pricePerSqm, 0, ',', ' ') : '' }}">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="field">
                        <label>Platība (m²)</label>
                        <input type="text" data-bind="area" value="{{ $areaValue }}">
                    </div>
                    <div class="field">
                        <label>Zemes platība (m²)</label>
                        <input type="text" data-bind="land" value="{{ $property->land_m2 }}">
                    </div>
                </div>
                <div class="field">
                    <label>Pārdošanas sākuma datums</label>
                    <input type="text" data-bind="listingDate" value="{{ $listingDate }}">
                </div>
                <div class="field">
                    <div class="field-head">
                        <label>Apraksts</label>
                        @if ($aiEnabled && ! $hasPdfDescription)
                            <button type="button" id="btn-ai-desc" class="btn btn-primary btn-xs" data-ai-url="{{ $aiUrl }}">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>AI ģenerēt
                            </button>
                        @endif
                    </div>
                    <div class="wysiwyg">
                        <div class="wysiwyg-toolbar" id="desc-toolbar">
                            <button type="button" data-cmd="bold" title="Treknraksts"><i class="fa-solid fa-bold"></i></button>
                            <button type="button" data-cmd="italic" title="Slīpraksts"><i class="fa-solid fa-italic"></i></button>
                            <button type="button" data-cmd="underline" title="Pasvītrojums"><i class="fa-solid fa-underline"></i></button>
                            <button type="button" data-cmd="insertUnorderedList" title="Aizzīmju saraksts"><i class="fa-solid fa-list-ul"></i></button>
                        </div>
                        <div class="wysiwyg-editor" id="desc-editor" contenteditable="true" data-save-url="{{ $pdfSaveUrl }}">{!! $descriptionHtml !!}</div>
                    </div>
                    <div class="field-foot">
                        <span class="hint" id="desc-count">0 / {{ $flyerMaxChars }}</span>
                        <span style="display:flex;align-items:center;gap:.6rem;">
                            <span class="hint" id="desc-save-status"></span>
                            <span class="hint" id="desc-ai-status"></span>
                        </span>
                    </div>
                </div>
            </div>

            <div class="panel" data-panel="images">
                <div class="field">
                    <label>Galvenais attēls</label>
                    <div id="main-editor"></div>
                    <label class="btn btn-block"><i class="fa-solid fa-upload"></i>Mainīt galveno attēlu<input type="file" id="main-input" accept="image/*" style="display:none;"></label>
                </div>
                <div class="field">
                    <label>Papildu attēli (līdz 4)</label>
                    <div class="thumbs-editor" id="thumbs-editor"></div>
                </div>
                <div class="field">
                    <label>Pievienot attēlus</label>
                    <input type="file" id="image-input" accept="image/*" multiple>
                </div>
                <div class="field">
                    <label style="display:flex;align-items:center;gap:.4rem;font-weight:600;"><input type="checkbox" id="show-map" @checked($mapUrl)> Rādīt karti</label>
                </div>
            </div>

            <div class="panel" data-panel="agent">
                <div class="agent-photo">
                    <img id="agent-photo-preview" src="{{ $agentAvatar ?: '' }}" alt="" style="{{ $agentAvatar ? '' : 'display:none;' }}">
                    <div id="agent-photo-fallback" class="fallback" style="{{ $agentAvatar ? 'display:none;' : '' }}">{{ $initials ?: 'P' }}</div>
                    <div>
                        <label class="btn" style="height:2.1rem;padding:0 .7rem;font-size:.8rem;cursor:pointer;"><i class="fa-solid fa-upload"></i>Mainīt foto<input type="file" id="agent-photo-input" accept="image/*" style="display:none;"></label>
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
            <div id="brochure" class="brochure">
                <section class="page cover">
                    <div class="cover-photo" id="brochure-cover-hero" @if ($hero) style="background-image:url('{{ $hero }}')" @endif>
                        <div class="cover-logo"><img src="{{ asset('site-assets/images/brand/logo.svg') }}" alt="Pārdod Laimīgs"></div>
                        <span class="status-tag">{{ \App\Models\CrmProperty::STATUSES[$property->status] ?? $property->status }}</span>
                    </div>
                    <div class="cover-content">
                        <div class="cover-kicker">{{ mb_strtoupper($categoryLabel) }}{{ $addressLine ? ' · '.mb_strtoupper($addressLine) : '' }}</div>
                        <h1 data-out="title">{{ $property->title }}</h1>
                        <p class="cover-address" data-out="address">{{ $addressLine }}</p>
                        <div class="cover-price-row">
                            <div>
                                <div class="cover-price" data-out="price">{{ $priceValue }}</div>
                                <div class="cover-price-meta" data-out="pricePerSqm">{{ $pricePerSqmValue }}</div>
                            </div>
                            <div class="cover-agent">
                                <div><strong data-out="agentName">{{ $agentName ?: 'Pārdod Laimīgs' }}</strong>@if ($agentPhone)<span data-out="agentPhone">{{ $agentPhone }}</span>@endif</div>
                                <div class="agent-initial" data-out="agentInitial">{{ $initials ?: 'P' }}</div>
                            </div>
                        </div>
                        <div class="cover-facts" style="grid-template-columns: repeat({{ max(1, $coverFacts->count()) }}, 1fr)">
                            @foreach ($coverFacts as $fact)
                                <div class="cover-fact"><strong data-out="{{ $fact['key'] }}">{{ $fact['value'] }}</strong><span>{{ $fact['label'] }}</span></div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="page why-page">
                    <div class="eyebrow">Kāpēc šis īpašums</div>
                    <h2 class="page-title" data-out="title">{{ $property->title }}</h2>
                    <div class="description" data-out="description">{!! $descriptionHtml !!}</div>
                    <div class="photo-grid">
                        @foreach ([0, 1, 2] as $i)
                            <div class="photo" id="brochure-photo-{{ $i + 1 }}" @if ($gallery->get($i)) style="background-image:url('{{ $gallery->get($i) }}')" @else style="display:none" @endif></div>
                        @endforeach
                    </div>
                    @if ($ctaUrl)<div class="listing-link">Visas bildes skatāmas sludinājumā: {{ parse_url($ctaUrl, PHP_URL_HOST).parse_url($ctaUrl, PHP_URL_PATH) }}</div>@endif
                    <div class="page-footer">
                        <img src="{{ asset('site-assets/images/brand/logo.svg') }}" alt="Pārdod Laimīgs">
                        <span class="footer-title">{{ $property->title }} · <span data-out="price">{{ $priceValue }}</span></span>
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
                                @foreach ($propertyFacts as [$label, $value, $key])
                                    <li><strong>{{ $label }}</strong><span @if ($key) data-out="{{ $key }}" @endif>{{ $value }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                        <div>
                            <h3>Tehniskā informācija</h3>
                            <ul class="details-list">
                                @foreach ($locationFacts as [$label, $value, $key])
                                    <li><strong>{{ $label }}</strong><span @if ($key) data-out="{{ $key }}" @endif>{{ $value }}</span></li>
                                @endforeach
                            </ul>
                            <div class="details-image" id="brochure-detail-image" @if ($gallery->get(3)) style="background-image:url('{{ $gallery->get(3) }}')" @else style="display:none" @endif></div>
                        </div>
                    </div>
                    <div class="map" id="brochure-map" @if (! $mapUrl) style="display:none" @endif>
                        @if ($mapUrl)<img src="{{ $mapUrl }}" alt="Īpašuma atrašanās vieta">@endif
                    </div>
                    <div class="page-footer">
                        <img src="{{ asset('site-assets/images/brand/logo.svg') }}" alt="Pārdod Laimīgs">
                        <span class="footer-title">{{ $property->title }} · <span data-out="price">{{ $priceValue }}</span></span>
                        <span class="page-number">3 / 4</span>
                    </div>
                </section>

                <section class="page next-page">
                    <img class="next-logo" src="{{ asset('site-assets/images/brand/logo.svg') }}" alt="Pārdod Laimīgs">
                    <div class="eyebrow next-kicker">Nākamais solis</div>
                    <h2>Piesakies apskatei</h2>
                    <p class="next-intro">Sazinies ar mums, lai vienotos par apskati un saņemtu atbildes uz jautājumiem par īpašumu.</p>
                    <div class="agent-panel">
                        <img class="agent-photo" id="brochure-agent-photo" src="{{ $agentAvatar ?: '' }}" alt="" style="{{ $agentAvatar ? '' : 'display:none;' }}">
                        <div class="agent-fallback" id="brochure-agent-fallback" data-out="agentInitial" style="{{ $agentAvatar ? 'display:none;' : '' }}">{{ $initials ?: 'P' }}</div>
                        <div>
                            <p class="agent-name" data-out="agentName">{{ $agentName ?: 'Pārdod Laimīgs' }}</p>
                            <p class="agent-role"><span data-out="agentRole">{{ $agentRole }}</span> · Pārdod Laimīgs</p>
                            @if ($agentPhone)<p class="agent-contact" data-out="agentPhone">{{ $agentPhone }}</p>@endif
                            @if ($agentEmail)<p class="agent-contact" data-out="agentEmail">{{ $agentEmail }}</p>@endif
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
    </div>

    <script>
        (function () {
            const MAX_CHARS = {{ $flyerMaxChars }};
            const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const state = {
                main: @json($hero),
                photos: @json($gallery->all()),
                map: @json($mapUrl),
                showMap: @json((bool) $mapUrl),
                agentAvatar: @json($agentAvatar),
            };

            // ── Ierobežota HTML sanitizācija ─────────────────────
            const ALLOWED = new Set(['P', 'BR', 'STRONG', 'B', 'EM', 'I', 'U', 'UL', 'OL', 'LI']);
            function sanitizeHtml(html) {
                const tpl = document.createElement('template');
                tpl.innerHTML = html || '';
                const walk = function (node) {
                    Array.from(node.childNodes).forEach(function (child) {
                        if (child.nodeType === 1) {
                            if (child.tagName === 'SCRIPT' || child.tagName === 'STYLE') { child.remove(); return; }
                            if (!ALLOWED.has(child.tagName)) { child.replaceWith.apply(child, Array.from(child.childNodes)); return; }
                            Array.from(child.attributes).forEach(function (attr) { child.removeAttribute(attr.name); });
                            walk(child);
                        } else if (child.nodeType === 8) {
                            child.remove();
                        }
                    });
                };
                walk(tpl.content);
                return tpl.innerHTML;
            }
            function textLength(html) {
                const div = document.createElement('div');
                div.innerHTML = html || '';
                return (div.textContent || '').length;
            }
            function truncateHtml(html, max) {
                const tpl = document.createElement('template');
                tpl.innerHTML = html || '';
                let count = 0;
                let done = false;
                const walk = function (node) {
                    Array.from(node.childNodes).forEach(function (child) {
                        if (done) { child.remove(); return; }
                        if (child.nodeType === 3) {
                            const len = child.textContent.length;
                            if (count + len > max) {
                                child.textContent = child.textContent.slice(0, max - count);
                                count = max;
                                done = true;
                            } else {
                                count += len;
                            }
                        } else if (child.nodeType === 1) {
                            walk(child);
                        }
                    });
                };
                walk(tpl.content);
                return tpl.innerHTML;
            }

            function formatArea(value) {
                const raw = String(value).trim();
                if (raw === '') { return '-'; }
                if (raw.includes('m²') || raw.includes('ha')) { return raw; }
                const num = parseFloat(raw.replace(/[^\d.,]/g, '').replace(',', '.'));
                if (!isNaN(num) && num >= 10000) {
                    return (num / 10000).toFixed(2).replace('.', ',').replace(/,?0+$/, '') + ' ha';
                }
                return raw + ' m²';
            }

            function update(key, value) {
                let display = value;
                if (key === 'price') {
                    display = String(value).trim() === '' ? 'Cena pēc pieprasījuma' : (String(value).includes('€') ? value : value + ' €');
                } else if (key === 'pricePerSqm') {
                    display = String(value).trim() === '' ? '-' : (String(value).includes('€') ? value : value + ' €/m²');
                } else if (key === 'area' || key === 'land') {
                    display = formatArea(value);
                } else if (key === 'rooms') {
                    display = String(value).trim() === '' ? '-' : value;
                }
                document.querySelectorAll('[data-out="' + key + '"]').forEach(function (node) { node.textContent = display; });
                if (key === 'agentName') {
                    const initial = String(value).trim().charAt(0).toUpperCase() || 'P';
                    document.querySelectorAll('[data-out="agentInitial"]').forEach(function (node) { node.textContent = initial; });
                }
            }

            document.querySelectorAll('[data-bind]').forEach(function (input) {
                const key = input.dataset.bind;
                input.addEventListener('input', function () { update(key, input.value); });
                update(key, input.value);
            });

            // ── Apraksts: WYSIWYG + limits + priekšskatījums ─────
            const descEditor = document.getElementById('desc-editor');
            const descCount = document.getElementById('desc-count');
            const descStatus = document.getElementById('desc-ai-status');
            const descSaveStatus = document.getElementById('desc-save-status');
            const descSaveUrl = descEditor ? descEditor.dataset.saveUrl : null;
            let descSaveTimer = null;
            let descSavedHtml = descEditor ? sanitizeHtml(descEditor.innerHTML) : null;

            function applyDescription() {
                if (!descEditor) { return; }
                let html = sanitizeHtml(descEditor.innerHTML);
                if (textLength(html) > MAX_CHARS) {
                    html = truncateHtml(html, MAX_CHARS);
                    descEditor.innerHTML = html;
                }
                document.querySelectorAll('[data-out="description"]').forEach(function (node) { node.innerHTML = sanitizeHtml(html); });
                const len = textLength(html);
                if (descCount) {
                    descCount.textContent = len + ' / ' + MAX_CHARS;
                    descCount.classList.toggle('counter-over', len >= MAX_CHARS);
                }
            }

            function saveDescription() {
                if (!descEditor || !descSaveUrl) { return; }
                const html = sanitizeHtml(descEditor.innerHTML);
                if (html === descSavedHtml) { return; }
                if (descSaveStatus) { descSaveStatus.textContent = 'Saglabā...'; }
                fetch(descSaveUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ html: html }),
                }).then(function (res) {
                    if (!res.ok) { throw new Error(); }
                    descSavedHtml = html;
                    if (descSaveStatus) { descSaveStatus.textContent = 'Saglabāts'; }
                }).catch(function () {
                    if (descSaveStatus) { descSaveStatus.textContent = 'Neizdevās saglabāt'; }
                });
            }
            function scheduleDescriptionSave() {
                if (descSaveTimer) { clearTimeout(descSaveTimer); }
                descSaveTimer = setTimeout(saveDescription, 900);
            }

            if (descEditor) {
                descEditor.innerHTML = sanitizeHtml(descEditor.innerHTML);
                descEditor.addEventListener('input', function () { applyDescription(); scheduleDescriptionSave(); });
                descEditor.addEventListener('blur', saveDescription);
                descEditor.addEventListener('beforeinput', function (e) {
                    if (!e.inputType || e.inputType.indexOf('insert') !== 0) { return; }
                    const sel = window.getSelection();
                    const selected = sel && !sel.isCollapsed ? sel.toString().length : 0;
                    if (textLength(descEditor.innerHTML) - selected >= MAX_CHARS) { e.preventDefault(); }
                });
                descEditor.addEventListener('paste', function (e) {
                    e.preventDefault();
                    const text = (e.clipboardData || window.clipboardData).getData('text/plain');
                    document.execCommand('insertText', false, text);
                });
                try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) { /* ignore */ }
                applyDescription();
            }

            window.addEventListener('pagehide', function () {
                if (!descEditor || !descSaveUrl) { return; }
                const html = sanitizeHtml(descEditor.innerHTML);
                if (html === descSavedHtml) { return; }
                const fd = new FormData();
                fd.append('_token', CSRF);
                fd.append('html', html);
                if (navigator.sendBeacon) { navigator.sendBeacon(descSaveUrl, fd); }
            });

            const toolbar = document.getElementById('desc-toolbar');
            if (toolbar && descEditor) {
                toolbar.addEventListener('mousedown', function (e) { e.preventDefault(); });
                toolbar.addEventListener('click', function (e) {
                    const btn = e.target.closest('[data-cmd]');
                    if (!btn) { return; }
                    descEditor.focus();
                    document.execCommand(btn.dataset.cmd, false, null);
                    applyDescription();
                    scheduleDescriptionSave();
                });
            }

            const btnAi = document.getElementById('btn-ai-desc');
            if (btnAi) {
                btnAi.addEventListener('click', async function () {
                    const original = btnAi.innerHTML;
                    btnAi.disabled = true;
                    btnAi.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>Ģenerē...';
                    if (descStatus) { descStatus.textContent = ''; }
                    try {
                        const res = await fetch(btnAi.dataset.aiUrl, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                        });
                        const data = await res.json().catch(function () { return {}; });
                        if (!res.ok) { throw new Error(data.message || 'Neizdevās ģenerēt aprakstu.'); }
                        descEditor.innerHTML = sanitizeHtml(data.pdf || '');
                        applyDescription();
                        if (descSaveTimer) { clearTimeout(descSaveTimer); }
                        descSavedHtml = sanitizeHtml(descEditor.innerHTML);
                        if (descSaveStatus) { descSaveStatus.textContent = 'Saglabāts'; }
                        btnAi.remove();
                    } catch (error) {
                        if (descStatus) { descStatus.textContent = error.message || 'Neizdevās ģenerēt aprakstu.'; }
                        btnAi.disabled = false;
                        btnAi.innerHTML = original;
                    }
                });
            }

            if (btnAi && @json($shouldAutoGenerate ?? false)) {
                btnAi.click();
            }

            // Cilnes
            document.querySelectorAll('.tabs button').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    document.querySelectorAll('.tabs button').forEach(function (t) { t.classList.toggle('active', t === tab); });
                    document.querySelectorAll('.panel').forEach(function (p) { p.classList.toggle('active', p.dataset.panel === tab.dataset.tab); });
                });
            });

            // ── Attēli ───────────────────────────────────────────
            const mainEditor = document.getElementById('main-editor');
            const thumbsEditor = document.getElementById('thumbs-editor');
            const coverHero = document.getElementById('brochure-cover-hero');
            const photoSlots = [1, 2, 3].map(function (i) { return document.getElementById('brochure-photo-' + i); });
            const detailImage = document.getElementById('brochure-detail-image');
            const mapSlot = document.getElementById('brochure-map');

            function setBackground(el, url) {
                if (!el) { return; }
                if (url) {
                    el.style.backgroundImage = 'url("' + url.replace(/"/g, '\\"') + '")';
                    el.style.display = '';
                } else {
                    el.style.backgroundImage = '';
                    el.style.display = 'none';
                }
            }

            function renderImages() {
                if (mainEditor) {
                    mainEditor.innerHTML = state.main
                        ? '<div class="main-thumb"><img src="' + state.main + '" alt=""></div>'
                        : '<div class="main-empty">Nav galvenā attēla</div>';
                }
                if (thumbsEditor) {
                    thumbsEditor.innerHTML = state.photos.map(function (url, i) {
                        return '<div class="thumb-item"><img src="' + url + '" alt="">'
                            + '<div class="thumb-actions">'
                            + (i > 0 ? '<button type="button" class="icon-btn" data-up="' + i + '" title="Pārvietot uz augšu"><i class="fa-solid fa-arrow-up"></i></button>' : '')
                            + '<button type="button" class="icon-btn" data-main="' + i + '" title="Padarīt par galveno"><i class="fa-solid fa-star"></i></button>'
                            + '<button type="button" class="icon-btn icon-btn-danger" data-remove="' + i + '" title="Noņemt"><i class="fa-solid fa-trash"></i></button>'
                            + '</div></div>';
                    }).join('');
                }

                setBackground(coverHero, state.main);
                photoSlots.forEach(function (el, i) { setBackground(el, state.photos[i] || null); });
                setBackground(detailImage, state.photos[3] || null);

                if (mapSlot) {
                    if (!state.showMap) {
                        mapSlot.style.display = 'none';
                    } else {
                        mapSlot.style.display = '';
                        mapSlot.innerHTML = state.map
                            ? '<img src="' + state.map + '" alt="Karte">'
                            : '<div class="map-empty">Karte nav pieejama</div>';
                    }
                }
            }
            renderImages();

            if (thumbsEditor) {
                thumbsEditor.addEventListener('click', function (e) {
                    const remove = e.target.closest('[data-remove]');
                    if (remove) { state.photos.splice(Number(remove.dataset.remove), 1); renderImages(); return; }
                    const up = e.target.closest('[data-up]');
                    if (up) {
                        const i = Number(up.dataset.up);
                        if (i > 0) { const tmp = state.photos[i - 1]; state.photos[i - 1] = state.photos[i]; state.photos[i] = tmp; renderImages(); }
                        return;
                    }
                    const makeMain = e.target.closest('[data-main]');
                    if (makeMain) {
                        const i = Number(makeMain.dataset.main);
                        const chosen = state.photos[i];
                        const previous = state.main;
                        state.main = chosen;
                        if (previous) { state.photos[i] = previous; } else { state.photos.splice(i, 1); }
                        renderImages();
                    }
                });
            }

            const imageInput = document.getElementById('image-input');
            if (imageInput) {
                imageInput.addEventListener('change', function () {
                    Array.from(imageInput.files || []).forEach(function (file) {
                        const url = URL.createObjectURL(file);
                        if (!state.main) { state.main = url; }
                        else if (state.photos.length < 4) { state.photos.push(url); }
                    });
                    renderImages();
                    imageInput.value = '';
                });
            }

            const mainInput = document.getElementById('main-input');
            if (mainInput) {
                mainInput.addEventListener('change', function () {
                    const file = (mainInput.files || [])[0];
                    if (!file) { return; }
                    state.main = URL.createObjectURL(file);
                    renderImages();
                    mainInput.value = '';
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
                    p.src = state.agentAvatar; p.style.display = '';
                    f.style.display = 'none';
                    const bp = document.getElementById('brochure-agent-photo');
                    const bf = document.getElementById('brochure-agent-fallback');
                    if (bp) { bp.src = state.agentAvatar; bp.style.display = ''; }
                    if (bf) { bf.style.display = 'none'; }
                    agentPhotoInput.value = '';
                });
            }

            // ── PDF lejupielāde ─────────────────────────────────
            function ensureHtml2Pdf() {
                return new Promise(function (resolve, reject) {
                    if (window.html2pdf) { resolve(); return; }
                    const sources = [
                        'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js',
                        'https://unpkg.com/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js',
                        'https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js'
                    ];
                    let index = 0;
                    function load() {
                        if (index >= sources.length) { reject(new Error('html2pdf nav pieejams')); return; }
                        const script = document.createElement('script');
                        script.src = sources[index++];
                        script.onload = function () { window.html2pdf ? resolve() : load(); };
                        script.onerror = load;
                        document.head.appendChild(script);
                    }
                    load();
                });
            }

            window.pdcDownloadBrochure = async function () {
                const button = document.getElementById('btn-download');
                const status = document.getElementById('status');
                const label = button.querySelector('span');
                const original = label.textContent;
                button.disabled = true;
                label.textContent = 'Ģenerē...';
                if (status) { status.textContent = ''; }
                document.body.classList.add('pdf-exporting');
                try {
                    await ensureHtml2Pdf();
                    if (document.fonts?.ready) { await document.fonts.ready; }
                    await window.html2pdf().set({
                        margin: 0,
                        filename: @json($filename),
                        image: { type: 'jpeg', quality: 0.95 },
                        html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false, scrollX: 0, scrollY: 0, windowWidth: 794 },
                        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                        pagebreak: { mode: [] }
                    }).from(document.getElementById('brochure')).save();
                    if (status) { status.textContent = 'PDF sagatavots.'; }
                } catch (error) {
                    console.warn('PDF ģenerēšana neizdevās:', error);
                    if (status) { status.textContent = 'Neizdevās ģenerēt PDF - atverama drukas forma.'; }
                    window.print();
                } finally {
                    document.body.classList.remove('pdf-exporting');
                    button.disabled = false;
                    label.textContent = original;
                }
            };
        })();
    </script>
</body>
</html>

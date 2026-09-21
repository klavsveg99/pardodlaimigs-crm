@php
    $rawDescription = (string) $property->description;
    $rawDescription = preg_replace('/<\s*(script|style|iframe|object|embed)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $rawDescription) ?? $rawDescription;
    $rawDescription = preg_replace('/<\s*(script|style|iframe|object|embed)[^>]*\/?>/i', '', $rawDescription) ?? $rawDescription;
    $descriptionHtml = trim(strip_tags($rawDescription)) === trim($rawDescription)
        ? nl2br(e($rawDescription))
        : $rawDescription;

    $addressLine = trim(implode(', ', array_filter([$property->address, $property->city])));
    $priceText = (float) $property->price_eur > 0
        ? number_format((float) $property->price_eur, 0, ',', ' ').' €'
        : 'Cena nav norādīta';

    $metaParts = [];
    if ($property->size_m2) { $metaParts[] = (int) $property->size_m2.' m²'; }
    if ($pricePerSqm) { $metaParts[] = number_format($pricePerSqm, 0, ',', ' ').' €/m²'; }
    if ($property->beds) { $metaParts[] = (int) $property->beds.' ist.'; }
    if ($property->land_m2) { $metaParts[] = 'zeme '.(int) $property->land_m2.' m²'; }

    $flyerImages = collect($images)->filter()->values();
    $heroImage = $flyerImages->first();
    // Blakus kartei rāda līdz 4 īpašuma attēliem.
    $thumbImages = $flyerImages->take(4)->values();
    $filename = ($property->slug ?: 'ipasums').'-marketing.pdf';
    $initials = mb_strtoupper(mb_substr((string) $agentName, 0, 1));
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
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #eef1f0; font-family: 'Source Sans Pro', system-ui, sans-serif; color: #1f2937; }

        .toolbar {
            position: sticky; top: 0; z-index: 50;
            display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;
            padding: 0.75rem 1rem;
            background: #ffffff; border-bottom: 1px solid #e2e8e6;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .toolbar .spacer { flex: 1 1 auto; }
        .btn {
            display: inline-flex; align-items: center; gap: 0.4rem;
            height: 2.35rem; padding: 0 0.95rem; border-radius: 0.5rem;
            font-size: 0.86rem; font-weight: 600; cursor: pointer; white-space: nowrap;
            border: 1px solid #d1d9d7; background: #ffffff; color: #374151; text-decoration: none;
        }
        .btn:hover { background: #f3f6f5; }
        .btn-primary { background: #285854; border-color: #1e4843; color: #ffffff; }
        .btn-primary:hover { background: #1e4843; }
        .btn:disabled { opacity: 0.65; cursor: default; }
        .toolbar-hint { font-size: 0.78rem; color: #6b7280; }

        .stage { display: flex; justify-content: center; padding: 1.5rem 1rem 3rem; }

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

        h1.title { font-family: 'Maven Pro', sans-serif; font-size: 32px; font-weight: 700; line-height: 1.2; margin: 0 0 8px; color: #111827; outline: none; }
        .address { font-size: 18px; color: #6b7280; margin: 0 0 20px; outline: none; }

        .price-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-bottom: 15px; }
        .price { font-size: 32px; font-weight: 700; color: #111827; }
        .price-meta { font-size: 14px; color: #6b7280; margin-top: 4px; }

        .desc { flex: 1 1 auto; min-height: 0; margin-bottom: 16px; overflow: hidden; font-size: 14px; line-height: 1.6; color: #4b5563; outline: none; }
        .desc p { margin: 0 0 12px; word-break: break-word; }
        .desc ul, .desc ol { margin: 0 0 12px; padding-left: 1.1rem; }

        .cta {
            background: #285854; color: #ffffff; border-radius: 6px; padding: 16px;
            font-size: 16px; font-weight: 600; text-transform: uppercase; text-align: center;
            letter-spacing: 0.5px; display: block; text-decoration: none;
        }

        .col-right { display: flex; flex-direction: column; min-width: 0; }
        .map { width: 100%; height: 170px; background: #e5e7eb; border-radius: 4px; overflow: hidden; margin-bottom: 15px; }
        .map img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .map-empty { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 13px; }
        .thumbs { display: flex; flex-wrap: wrap; gap: 6px; }
        .thumbs img { width: calc(50% - 3px); height: 105px; object-fit: cover; border-radius: 4px; background: #e5e7eb; }
        .thumbs-empty { width: 100%; height: 105px; border-radius: 4px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 13px; }

        .flyer-footer { border-top: 1px solid #e5e7eb; padding-top: 15px; margin-top: auto; }
        .agent-card { background: #285854; padding: 16px; border-radius: 6px; display: flex; flex-direction: row; align-items: center; gap: 24px; }
        .agent-info { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .agent-card img { width: 50px; height: 50px; border-radius: 9999px; object-fit: cover; flex: none; }
        .agent-avatar-fallback { width: 50px; height: 50px; border-radius: 9999px; background: #ffffff; color: #285854; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700; flex: none; }
        .agent-name { margin: 0; color: #ffffff; font-weight: 600; font-size: 15px; }
        .agent-role { margin: 0; color: #ffffff; font-size: 13px; opacity: 0.9; }
        .agent-contact { flex: 1; text-align: right; }
        .agent-contact p { margin: 0; color: #ffffff; font-size: 14px; font-weight: 500; }

        .flyer-meta { display: flex; justify-content: space-between; margin-top: 12px; padding-top: 12px; border-top: 1px solid #f3f4f6; }
        .flyer-meta span { color: #9ca3af; font-size: 11px; }

        @page { size: A4; margin: 0; }
        @media print {
            html, body { background: #ffffff; }
            .no-print { display: none !important; }
            .stage { padding: 0; }
            .flyer { width: 100%; height: 296mm; min-height: 296mm; overflow: hidden; box-shadow: none; padding: 14mm; }
        }
        @media (max-width: 860px) {
            .flyer { width: 100%; height: auto; min-height: auto; overflow: visible; padding: 20px; }
            .flyer-grid { grid-template-columns: 1fr; }
            .desc { overflow: visible; }
            .toolbar-hint { display: none; }
        }

        /* PDF vienmēr tiek veidots no fiksēta A4 klona, tāpēc mobilajā
           skatā izmērs un kolonnas tiek atjaunotas uz laiku. */
        .flyer-capture { width: 794px !important; height: 1122px !important; min-height: 1122px !important; overflow: hidden !important; padding: 45px !important; box-shadow: none !important; }
        .flyer-capture .flyer-grid { grid-template-columns: 1fr 1fr !important; }
        .flyer-capture .desc { overflow: hidden !important; }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" id="btn-download" class="btn btn-primary" onclick="pdcDownloadPdf()">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2m-16-5l8 8 8-8m-16 0V4h16v10"/></svg>
            <span>Lejupielādēt PDF</span>
        </button>
        <button type="button" class="btn" onclick="window.print()">Drukāt</button>
        <a class="btn" href="https://real-estate-pdf-offer-generator.vercel.app/" target="_blank" rel="noopener">Ārējais PDF ģenerators</a>
        <span class="spacer"></span>
        <span class="toolbar-hint">Tekstu var labot tieši lapā pirms lejupielādes.</span>
        <a class="btn" href="{{ route('filament.admin.resources.properties.view', ['record' => $property->slug ?? $property->getKey()]) }}">Atgriezties</a>
    </div>

    <div class="stage">
        <div id="flyer" class="flyer">
            <div class="accent"></div>

            <div class="flyer-header">
                <div class="brand">
                    <img src="{{ asset('images/favicon-180x180.jpg') }}" alt="Pārdod Laimīgs">
                    <span>PardodLaimigs.lv</span>
                </div>
                <span class="chip">{{ $listingId }}</span>
            </div>

            <div class="flyer-grid">
                <div class="col-left">
                    <h1 class="title" contenteditable="true">{{ $property->title }}</h1>
                    <p class="address" contenteditable="true">{{ $addressLine ?: 'Īpašuma adrese' }}</p>

                    <div class="price-box">
                        <div class="price">{{ $priceText }}</div>
                        @if (! empty($metaParts))
                            <div class="price-meta">{{ implode(' • ', $metaParts) }}</div>
                        @endif
                    </div>

                    <div class="desc" contenteditable="true">
                        {!! $descriptionHtml !== '' ? $descriptionHtml : '<p>Īpašuma apraksts.</p>' !!}
                    </div>

                    <a class="cta" href="{{ $ctaUrl }}" target="_blank" rel="noopener">Sazināties ar aģentu</a>
                </div>

                <div class="col-right">
                    <div class="map">
                        @if ($mapUrl)
                            <img src="{{ $mapUrl }}" alt="Karte">
                        @elseif ($heroImage)
                            <img src="{{ $heroImage }}" alt="{{ $property->title }}">
                        @else
                            <div class="map-empty">Karte nav pieejama</div>
                        @endif
                    </div>
                    <div class="thumbs">
                        @forelse ($thumbImages as $image)
                            <img src="{{ $image }}" alt="{{ $property->title }}">
                        @empty
                            @if (! $mapUrl && ! $heroImage)
                                <div class="thumbs-empty">Nav attēlu</div>
                            @endif
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="flyer-footer">
                <div class="agent-card">
                    <div class="agent-info">
                        @if ($agentAvatar)
                            <img src="{{ $agentAvatar }}" alt="{{ $agentName }}">
                        @else
                            <div class="agent-avatar-fallback">{{ $initials ?: 'P' }}</div>
                        @endif
                        <div>
                            <p class="agent-name">{{ $agentName ?: 'Pārdod Laimīgs' }}</p>
                            <p class="agent-role">{{ $agentRole }}</p>
                        </div>
                    </div>
                    <div class="agent-contact">
                        @if ($agentPhone)
                            <p>{{ $agentPhone }}</p>
                        @endif
                        @if ($agentEmail)
                            <p>{{ $agentEmail }}</p>
                        @endif
                    </div>
                </div>
                <div class="flyer-meta">
                    <span>{{ $listingDate }}</span>
                    <span>© pardodlaimigs.lv</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const HTML2PDF_URL = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';

            function loadHtml2Pdf() {
                return new Promise(function (resolve, reject) {
                    if (window.html2pdf) { resolve(); return; }
                    const existing = document.querySelector('script[data-html2pdf]');
                    if (existing) { existing.addEventListener('load', resolve); existing.addEventListener('error', reject); return; }
                    const script = document.createElement('script');
                    script.src = HTML2PDF_URL;
                    script.setAttribute('data-html2pdf', '1');
                    script.onload = function () { window.html2pdf ? resolve() : reject(new Error('html2pdf nav pieejams')); };
                    script.onerror = function () { reject(new Error('neizdevās ielādēt html2pdf')); };
                    document.head.appendChild(script);
                });
            }

            // Fiksēta A4 klona izveide — PDF izskatās vienādi arī mobilajā skatā.
            function buildCaptureNode() {
                const clone = document.getElementById('flyer').cloneNode(true);
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
                const label = button.querySelector('span');
                const original = label.textContent;
                // Noņem rediģēšanas kontūras, lai tās neparādās PDF.
                document.querySelectorAll('[contenteditable]').forEach(function (el) { el.blur(); });
                button.disabled = true;
                label.textContent = 'Ģenerē...';
                const capture = buildCaptureNode();
                try {
                    await loadHtml2Pdf();
                    await window.html2pdf().set({
                        margin: 0,
                        filename: @json($filename),
                        image: { type: 'jpeg', quality: 0.95 },
                        html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', scrollX: 0, scrollY: 0 },
                        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                        // Fiksēta vienas A4 lapas lapa — nelaužam saturu pāri lapām.
                        pagebreak: { mode: [] },
                    }).from(capture).save();
                } catch (error) {
                    console.warn('PDF ģenerēšana neizdevās, izmanto druku:', error);
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

@php
    $formAction = $locale === 'lv'
        ? route('site.contact.store')
        : route('site.contact.store.locale', ['locale' => $locale]);
@endphp
<section class="contact-section" id="kontakti" aria-labelledby="contact-title">
    <div class="container contact-layout">
        <div class="contact-copy">
            <p class="eyebrow">{{ $copy['contactEyebrow'] ?? '' }}</p>
            <h2 id="contact-title">{{ $copy['contactTitle'] ?? '' }}</h2>
            <p>{{ $copy['contactBody'] ?? '' }}</p>
            <div class="contact-direct">
                <a href="tel:+37124922942"><span class="contact-label">Roberts</span><strong>+371 2492 2942</strong></a>
            </div>
        </div>
        <form class="contact-form" method="post" action="{{ $formAction }}">
            @csrf
            @if ($errors->any())
                <p class="form-feedback">{{ $copy['formError'] ?? 'Lūdzu, pārbaudiet ievadītos datus.' }}</p>
            @endif
            <div class="form-row">
                <label>
                    <span>{{ $copy['formType'] ?? '' }}</span>
                    <select name="property_type" required>
                        <option value="" selected disabled>{{ $copy['formChoose'] ?? '' }}</option>
                        <option value="apartment">{{ $copy['formApartment'] ?? '' }}</option>
                        <option value="house">{{ $copy['formHouse'] ?? '' }}</option>
                        <option value="land">{{ $copy['formLand'] ?? '' }}</option>
                        <option value="forest">{{ $copy['formForest'] ?? '' }}</option>
                    </select>
                </label>
                <label><span>{{ $copy['formArea'] ?? '' }}</span><input name="area" inputmode="decimal" required></label>
            </div>
            <label><span>{{ $copy['formLocation'] ?? '' }}</span><input name="location" required></label>
            <div class="form-row">
                <label><span>{{ $copy['formCadastre'] ?? '' }}</span><input name="cadastre" required></label>
                <label><span>{{ $copy['formDesiredPrice'] ?? '' }}</span><input name="desired_price" inputmode="decimal"></label>
            </div>
            <label><span>{{ $copy['formDescription'] ?? '' }}</span><textarea name="description" rows="3" required></textarea></label>
            <label class="form-check"><input name="urgent" type="checkbox"><span>{{ $copy['urgentCheckbox'] ?? '' }}</span></label>
            <div class="form-row">
                <label><span>{{ $copy['formName'] ?? '' }}</span><input name="name" autocomplete="name" required></label>
                <label><span>{{ $copy['formPhone'] ?? '' }}</span><input name="phone" type="tel" autocomplete="tel" required></label>
            </div>
            <label><span>{{ $copy['formEmail'] ?? '' }}</span><input name="email" type="email" autocomplete="email" required></label>
            <label><span>{{ $copy['formComment'] ?? '' }}</span><textarea name="comment" rows="2"></textarea></label>
            <label class="form-check"><input name="consent" type="checkbox" required><span>{{ $copy['formConsent'] ?? '' }}</span></label>
            {{-- Honeypot: bots fill this, people never see it. --}}
            <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
            <div class="form-submit-row">
                <button class="button button-dark" type="submit"><span>{{ $copy['formSubmit'] ?? '' }}</span><span aria-hidden="true">↗</span></button>
            </div>
        </form>
    </div>
</section>

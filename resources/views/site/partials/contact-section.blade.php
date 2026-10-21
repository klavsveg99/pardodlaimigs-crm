@php
    $formAction = $locale === 'lv'
        ? route('site.contact.store')
        : route('site.contact.store.locale', ['locale' => $locale]);

    // The prefix selector and the phone validation read the same list.
    $prefixes = config('site.phone_prefixes');
    $defaultPrefix = old('phone_prefix', config('site.default_phone_prefix', 'LV'));
    $siteKey = config('services.recaptcha.site_key');
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
        <form class="contact-form" method="post" action="{{ $formAction }}"
              data-site-key="{{ $siteKey }}"
              data-email-invalid="{{ $copy['formEmailInvalid'] ?? '' }}"
              data-phone-invalid="{{ $copy['formPhoneInvalid'] ?? '' }}"
              data-captcha-failed="{{ $copy['formCaptchaFailed'] ?? '' }}">
            @csrf
            @if ($errors->has('recaptcha_token'))
                <p class="form-feedback">{{ $copy['formCaptchaFailed'] ?? 'Drošības pārbaude neizdevās.' }}</p>
            @elseif ($errors->any())
                <p class="form-feedback">{{ $copy['formError'] ?? 'Lūdzu, pārbaudiet ievadītos datus.' }}</p>
            @endif
            <div class="form-row">
                <label>
                    <span>{{ $copy['formType'] ?? '' }}</span>
                    <select name="property_type" required>
                        <option value="" selected disabled>{{ $copy['formChoose'] ?? '' }}</option>
                        <option value="apartment" @selected(old('property_type') === 'apartment')>{{ $copy['formApartment'] ?? '' }}</option>
                        <option value="house" @selected(old('property_type') === 'house')>{{ $copy['formHouse'] ?? '' }}</option>
                        <option value="land" @selected(old('property_type') === 'land')>{{ $copy['formLand'] ?? '' }}</option>
                        <option value="forest" @selected(old('property_type') === 'forest')>{{ $copy['formForest'] ?? '' }}</option>
                        <option value="commercial" @selected(old('property_type') === 'commercial')>{{ $copy['formCommercial'] ?? '' }}</option>
                    </select>
                </label>
                <label><span>{{ $copy['formArea'] ?? '' }}</span><input name="area" inputmode="decimal" value="{{ old('area') }}" required></label>
            </div>
            <label><span>{{ $copy['formLocation'] ?? '' }}</span><input name="location" value="{{ old('location') }}" required></label>
            <div class="form-row">
                <label><span>{{ $copy['formCadastre'] ?? '' }}</span><input name="cadastre" value="{{ old('cadastre') }}" required></label>
                <label><span>{{ $copy['formDesiredPrice'] ?? '' }}</span><input name="desired_price" inputmode="decimal" value="{{ old('desired_price') }}"></label>
            </div>
            <label><span>{{ $copy['formDescription'] ?? '' }}</span><textarea name="description" rows="3" required>{{ old('description') }}</textarea></label>
            <label class="form-check"><input name="urgent" type="checkbox" @checked(old('urgent'))><span>{{ $copy['urgentCheckbox'] ?? '' }}</span></label>
            <div class="form-row">
                <label><span>{{ $copy['formName'] ?? '' }}</span><input name="name" autocomplete="name" value="{{ old('name') }}" required></label>
                <label>
                    <span>{{ $copy['formPhone'] ?? '' }}</span>
                    <span class="phone-field">
                        <select name="phone_prefix" aria-label="{{ $copy['formPhoneCode'] ?? '' }}">
                            @foreach ($prefixes as $iso => $rule)
                                <option value="{{ $iso }}" @selected($defaultPrefix === $iso)>{{ $iso }} +{{ $rule['dial'] }}</option>
                            @endforeach
                        </select>
                        <input name="phone" type="tel" inputmode="tel" autocomplete="tel-national" value="{{ old('phone') }}" required>
                    </span>
                    <p class="field-error" id="phone-error" @unless ($errors->has('phone')) hidden @endunless>{{ $errors->has('phone') ? ($copy['formPhoneInvalid'] ?? $errors->first('phone')) : '' }}</p>
                </label>
            </div>
            <label>
                <span>{{ $copy['formEmail'] ?? '' }}</span>
                <input name="email" type="email" autocomplete="email" value="{{ old('email') }}" required>
                <p class="field-error" id="email-error" @unless ($errors->has('email')) hidden @endunless>{{ $errors->has('email') ? ($copy['formEmailInvalid'] ?? $errors->first('email')) : '' }}</p>
            </label>
            <label><span>{{ $copy['formComment'] ?? '' }}</span><textarea name="comment" rows="2">{{ old('comment') }}</textarea></label>
            <label class="form-check"><input name="consent" type="checkbox" @checked(old('consent')) required><span>{{ $copy['formConsent'] ?? '' }}</span></label>
            {{-- Honeypot: bots fill this, people never see it. --}}
            <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
            {{-- reCAPTCHA v3: site.js puts the token here just before it submits. --}}
            <input type="hidden" name="recaptcha_token" value="">
            <div class="form-submit-row">
                <button class="button button-dark" type="submit"><span>{{ $copy['formSubmit'] ?? '' }}</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
            </div>
            <script type="application/json" id="phone-prefixes">@json($prefixes)</script>
            @if ($siteKey && ($copy['recaptchaNote'] ?? null))
                <p class="recaptcha-note">
                    {!! str_replace(
                        ['[privacy]', '[terms]'],
                        [
                            '<a href="https://policies.google.com/privacy" target="_blank" rel="noreferrer">'.e($copy['privacyLabel'] ?? 'Privacy').'</a>',
                            '<a href="https://policies.google.com/terms" target="_blank" rel="noreferrer">'.e($copy['termsLabel'] ?? 'Terms').'</a>',
                        ],
                        e($copy['recaptchaNote'])
                    ) !!}
                </p>
            @endif
        </form>
    </div>
</section>

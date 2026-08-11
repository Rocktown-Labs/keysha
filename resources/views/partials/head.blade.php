<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Keysha') : config('app.name', 'Keysha').' — Open-Source Developer Credential & Environment Vault' }}
</title>

<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="icon" href="/favicon.ico" sizes="any">

<!-- Open Graph / Meta Tags -->
<meta name="title" content="{{ filled($title ?? null) ? $title.' - Keysha' : 'Keysha — Open-Source Developer Credential & Environment Vault' }}">
<meta name="description" content="Keysha is a durable configuration & credential vault for developers built with Libsodium envelope encryption, Livewire 3, and a standalone Bun CLI.">

<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:title" content="{{ filled($title ?? null) ? $title.' - Keysha' : 'Keysha — Open-Source Developer Credential & Environment Vault' }}">
<meta property="og:description" content="Keysha is a durable configuration & credential vault for developers built with Libsodium envelope encryption, Livewire 3, and a standalone Bun CLI.">
<meta property="og:image" content="{{ asset('favicon.svg') }}">

<!-- Twitter Card -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="{{ url()->current() }}">
<meta property="twitter:title" content="{{ filled($title ?? null) ? $title.' - Keysha' : 'Keysha — Open-Source Developer Credential & Environment Vault' }}">
<meta property="twitter:description" content="Keysha is a durable configuration & credential vault for developers built with Libsodium envelope encryption, Livewire 3, and a standalone Bun CLI.">
<meta property="twitter:image" content="{{ asset('favicon.svg') }}">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

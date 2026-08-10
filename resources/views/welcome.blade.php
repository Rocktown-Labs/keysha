<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Keysha — Developer Configuration Vault</title>

        <link rel="icon" href="/favicon.ico" sizes="any">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-black text-white antialiased min-h-screen flex flex-col justify-between p-6 sm:p-12 font-sans">
        <!-- Navigation -->
        <header class="max-w-6xl mx-auto w-full flex items-center justify-between border-b border-zinc-800 pb-6">
            <div class="flex items-center gap-3">
                <div class="size-8 rounded-lg bg-zinc-900 border border-zinc-700 flex items-center justify-center font-bold text-lg text-white">
                    🔑
                </div>
                <span class="text-xl font-bold tracking-tight text-white">KEYSHA</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-zinc-900 border border-zinc-800 text-zinc-400">Vault v1.0</span>
            </div>

            <nav class="flex items-center gap-3 text-sm font-medium">
                @auth
                    <a href="/dashboard" class="px-4 py-2 bg-white text-black hover:bg-zinc-200 rounded-md transition-colors font-semibold">
                        Open Vault Dashboard →
                    </a>
                @else
                    <a href="/login" class="px-4 py-2 text-zinc-300 hover:text-white transition-colors">
                        Log in
                    </a>
                    @if (Route::has('register'))
                        <a href="/register" class="px-4 py-2 bg-white text-black hover:bg-zinc-200 rounded-md transition-colors font-semibold">
                            Get Started
                        </a>
                    @endif
                @endauth
            </nav>
        </header>

        <!-- Hero Section -->
        <main class="max-w-4xl mx-auto w-full text-center py-16 space-y-8">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-zinc-950 border border-zinc-800 text-xs font-mono text-zinc-400">
                <span class="size-2 rounded-full bg-emerald-400"></span>
                <span>Open-Source Envelope Encryption Vault</span>
            </div>

            <h1 class="text-4xl sm:text-6xl font-bold tracking-tight text-white max-w-3xl mx-auto leading-tight">
                Durable configuration & credential vault for developers.
            </h1>

            <p class="text-zinc-400 text-base sm:text-lg max-w-2xl mx-auto leading-relaxed">
                Keysha is the safe source of truth where you can always find your API keys, Stripe price IDs, Resend tokens, and project configs again.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                @auth
                    <a href="/dashboard" class="w-full sm:w-auto px-6 py-3 bg-white text-black hover:bg-zinc-200 font-semibold text-sm rounded-md transition-colors">
                        Open Vault Dashboard
                    </a>
                @else
                    <a href="/register" class="w-full sm:w-auto px-6 py-3 bg-white text-black hover:bg-zinc-200 font-semibold text-sm rounded-md transition-colors">
                        Create Account
                    </a>
                @endauth
            </div>

            <!-- Terminal Hero Preview -->
            <div class="mt-12 bg-zinc-950 border border-zinc-800 rounded-xl text-left p-6 font-mono text-xs text-zinc-300 shadow-2xl space-y-3 max-w-2xl mx-auto">
                <div class="flex items-center justify-between text-zinc-500 border-b border-zinc-800 pb-3 font-sans text-xs">
                    <div class="flex items-center gap-2">
                        <span class="size-3 rounded-full bg-red-500/80"></span>
                        <span class="size-3 rounded-full bg-amber-500/80"></span>
                        <span class="size-3 rounded-full bg-emerald-500/80"></span>
                        <span class="ml-2 font-mono text-zinc-400">keysha-cli ~ bun dev</span>
                    </div>
                    <span>v1.0.0</span>
                </div>

                <div class="space-y-2 pt-2 text-zinc-300">
                    <div><span class="text-emerald-400">$</span> keysha project create mingle</div>
                    <div class="text-zinc-500">✓ Created project 'mingle' with Development, Preview, Production.</div>
                    
                    <div class="pt-2"><span class="text-emerald-400">$</span> keysha set STRIPE_SECRET_KEY</div>
                    <div class="text-zinc-500">Project: Mingle | Environment: Production</div>
                    <div class="text-zinc-500">Provider: Stripe | Classification: Secret</div>
                    <div class="text-zinc-500">✓ Encrypted & bound via Libsodium XChaCha20-Poly1305</div>

                    <div class="pt-2"><span class="text-emerald-400">$</span> keysha copy STRIPE_SECRET_KEY</div>
                    <div class="text-emerald-400">✓ Copied STRIPE_SECRET_KEY to clipboard</div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="max-w-6xl mx-auto w-full text-center text-xs text-zinc-600 border-t border-zinc-800 pt-6">
            Keysha Vault &copy; {{ date('Y') }} — Built with Laravel & Libsodium.
        </footer>
    </body>
</html>

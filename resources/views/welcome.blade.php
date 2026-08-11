<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-black text-white antialiased min-h-screen flex flex-col justify-between p-6 sm:p-12 font-sans">
        <!-- Navigation -->
        <header class="max-w-6xl mx-auto w-full flex items-center justify-between border-b border-zinc-800 pb-6 relative">
            <div class="flex items-center gap-3">
                <div class="flex aspect-square size-10 items-center justify-center rounded-xl bg-zinc-950 border border-emerald-800/80 text-emerald-400 shadow-md">
                    <x-app-logo-icon class="size-7" />
                </div>
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <span class="text-xl font-bold tracking-tight text-white">KEYSHA</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-zinc-900 border border-zinc-800 text-emerald-400 font-semibold">Vault</span>
                    </div>
                </div>
            </div>

            <!-- Centered Navigation Menu -->
            <nav class="hidden md:flex items-center justify-center gap-8 text-sm font-medium absolute left-1/2 -translate-x-1/2">
                <a href="#install" class="text-zinc-400 hover:text-white transition-colors">Install</a>
                <a href="#commands" class="text-zinc-400 hover:text-white transition-colors">Docs</a>
                <a href="#self-host" class="text-zinc-400 hover:text-white transition-colors">Self-Host</a>
            </nav>

            <!-- Right-Aligned Action Buttons -->
            <div class="flex items-center gap-4 text-sm font-medium">
                @auth
                    <a href="/dashboard" class="px-4 py-2 bg-white text-black hover:bg-zinc-200 rounded-md transition-colors font-semibold">
                        Open Vault Dashboard →
                    </a>
                @else
                    <a href="/login" class="px-4 py-2 bg-zinc-900 border border-zinc-800 hover:bg-zinc-800 text-white rounded-md transition-colors font-medium">
                        Log in
                    </a>
                    @if (Route::has('register'))
                        <a href="/register" class="px-4 py-2 bg-white text-black hover:bg-zinc-200 rounded-md transition-colors font-semibold">
                            Get Started
                        </a>
                    @endif
                @endauth
            </div>
        </header>

        <!-- Hero Section -->
        <main class="max-w-5xl mx-auto w-full text-center py-12 sm:py-16 space-y-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-zinc-950 border border-zinc-800 text-xs font-mono text-zinc-400">
                <span class="size-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Open-Source Secret Vault & CLI</span>
            </div>

            <h1 class="text-4xl sm:text-6xl font-bold tracking-tight text-white max-w-4xl mx-auto leading-tight">
                Never lose an API key or environment variable again.
            </h1>

            <p class="text-zinc-400 text-base sm:text-lg max-w-2xl mx-auto leading-relaxed">
                Keysha safely stores your Stripe keys, database credentials, and secret tokens in one encrypted vault. Access them instantly from your terminal or web dashboard.
            </p>

            <!-- Package Manager Installation Component -->
            <div id="install" class="max-w-xl mx-auto w-full text-left bg-zinc-950 border border-zinc-800 rounded-xl p-5 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-900 pb-3">
                    <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider font-mono">Install Keysha CLI</span>
                    
                    <!-- Package Manager Selection Chips -->
                    <div class="flex items-center gap-1 bg-zinc-900 p-1 rounded-md border border-zinc-800 text-xs font-mono" id="pm-tabs">
                        <button onclick="setPm('bun')" id="tab-bun" class="pm-btn px-2 py-1 rounded cursor-pointer transition-colors uppercase text-[11px] bg-zinc-800 text-white font-bold shadow-xs">bun</button>
                        <button onclick="setPm('pnpm')" id="tab-pnpm" class="pm-btn px-2 py-1 rounded cursor-pointer transition-colors uppercase text-[11px] text-zinc-500 hover:text-zinc-300">pnpm</button>
                        <button onclick="setPm('npm')" id="tab-npm" class="pm-btn px-2 py-1 rounded cursor-pointer transition-colors uppercase text-[11px] text-zinc-500 hover:text-zinc-300">npm</button>
                        <button onclick="setPm('yarn')" id="tab-yarn" class="pm-btn px-2 py-1 rounded cursor-pointer transition-colors uppercase text-[11px] text-zinc-500 hover:text-zinc-300">yarn</button>
                        <button onclick="setPm('curl')" id="tab-curl" class="pm-btn px-2 py-1 rounded cursor-pointer transition-colors uppercase text-[11px] text-zinc-500 hover:text-zinc-300">curl</button>
                    </div>
                </div>

                <div class="flex items-center justify-between bg-black border border-zinc-800/80 rounded-lg px-4 py-3 font-mono text-xs">
                    <div class="flex items-center gap-2 truncate">
                        <span class="text-zinc-600 select-none">$</span>
                        <span id="install-cmd" class="select-all font-semibold text-emerald-400">bun add -g keysha-cli</span>
                    </div>
                    <button 
                        type="button"
                        onclick="copyInstallCmd()"
                        id="copy-btn"
                        class="text-zinc-400 hover:text-emerald-400 transition-colors ml-3 cursor-pointer shrink-0 font-sans"
                        title="Copy command"
                    >
                        <span id="copy-btn-text" class="text-xs">📋 Copy</span>
                    </button>
                </div>

                <p class="text-[11px] text-zinc-500 leading-relaxed font-sans">
                    Install globally with your package manager or shell script. No extra runtime setup required.
                </p>

                <div class="flex items-center gap-3 pt-1">
                    <a href="/register" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-400 text-black font-semibold text-xs rounded-md transition-colors">
                        Get Started Free
                    </a>
                    <a href="#commands" class="px-4 py-2 bg-zinc-900 border border-zinc-800 hover:bg-zinc-800 text-zinc-300 font-medium text-xs rounded-md transition-colors">
                        View Commands →
                    </a>
                </div>
            </div>

            <!-- Terminal Hero Preview -->
            <div class="bg-zinc-950 border border-zinc-800 rounded-xl text-left p-6 font-mono text-xs text-zinc-300 shadow-2xl space-y-3 max-w-3xl mx-auto">
                <div class="flex items-center justify-between text-zinc-500 border-b border-zinc-800 pb-3 font-sans text-xs">
                    <div class="flex items-center gap-2">
                        <span class="size-3 rounded-full bg-red-500/80"></span>
                        <span class="size-3 rounded-full bg-amber-500/80"></span>
                        <span class="size-3 rounded-full bg-emerald-500/80"></span>
                        <span class="ml-2 font-mono text-zinc-400">keysha-cli ~ Quickstart Session</span>
                    </div>
                    <span>v1.0.0</span>
                </div>

                <div class="space-y-2.5 pt-2 text-zinc-300">
                    <div>
                        <span class="text-emerald-400">$</span> keysha login
                        <div class="text-zinc-500 pl-3">✓ Successfully authenticated as cg@keysha.sh!</div>
                    </div>

                    <div class="pt-1">
                        <span class="text-emerald-400">$</span> keysha project create mingle
                        <div class="text-zinc-500 pl-3">✓ Created project 'mingle' with Development, Preview, Production.</div>
                    </div>

                    <div class="pt-1">
                        <span class="text-emerald-400">$</span> keysha use mingle
                        <div class="text-zinc-500 pl-3">Active project set to 'mingle'.</div>
                    </div>
                    
                    <div class="pt-1">
                        <span class="text-emerald-400">$</span> keysha set STRIPE_SECRET_KEY dev
                        <div class="text-zinc-500 pl-3">✓ Encrypted & bound via Libsodium XChaCha20-Poly1305</div>
                    </div>

                    <div class="pt-1">
                        <span class="text-emerald-400">$</span> keysha pull dev .env.local
                        <div class="text-emerald-400 pl-3">✓ Exported 44 variables to .env.local</div>
                    </div>
                </div>
            </div>

            <!-- Comprehensive Docs & Commands Reference Section Grouped by Workflow -->
            <div id="commands" class="pt-12 text-left space-y-8 max-w-4xl mx-auto border-t border-zinc-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-white tracking-tight">CLI Command Reference</h2>
                        <p class="text-xs text-zinc-400 mt-1">Step-by-step commands from login to local file export.</p>
                    </div>
                    <span class="text-xs font-mono text-zinc-500">keysha v1.0.0</span>
                </div>

                <!-- Section 1: Authentication & Device Setup -->
                <div class="space-y-3">
                    <div class="flex items-center gap-2 text-xs font-semibold text-zinc-400 uppercase tracking-wider font-mono border-b border-zinc-900 pb-2">
                        <span class="text-emerald-400">01.</span>
                        <span>Authentication & Device Setup</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <code class="text-emerald-400 font-mono text-xs font-bold">keysha login</code>
                                <span class="text-[10px] font-mono text-zinc-400 bg-zinc-900 border border-zinc-800 px-2 py-0.5 rounded">Auth</span>
                            </div>
                            <p class="text-xs text-zinc-300 font-medium">Authorize terminal session</p>
                            <p class="text-[11px] text-zinc-500">First time setting up a dev laptop or terminal. Authorizes your CLI via browser OAuth without typing raw passwords in shell history.</p>
                            <div class="bg-black p-2 rounded text-[11px] font-mono text-zinc-400 border border-zinc-900">$ keysha login</div>
                        </div>

                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <code class="text-emerald-400 font-mono text-xs font-bold">keysha whoami</code>
                                <span class="text-[10px] font-mono text-zinc-400 bg-zinc-900 border border-zinc-800 px-2 py-0.5 rounded">Context</span>
                            </div>
                            <p class="text-xs text-zinc-300 font-medium">Check active user & workspace</p>
                            <p class="text-[11px] text-zinc-500">When switching between Personal and company workspaces to confirm where your commands are executing.</p>
                            <div class="bg-black p-2 rounded text-[11px] font-mono text-zinc-400 border border-zinc-900">$ keysha whoami</div>
                        </div>

                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-4 space-y-2 md:col-span-2">
                            <div class="flex items-center justify-between">
                                <code class="text-emerald-400 font-mono text-xs font-bold">keysha logout</code>
                                <span class="text-[10px] font-mono text-zinc-400 bg-zinc-900 border border-zinc-800 px-2 py-0.5 rounded">Auth</span>
                            </div>
                            <p class="text-xs text-zinc-300 font-medium">Revoke CLI session credentials</p>
                            <p class="text-[11px] text-zinc-500">Leaving a shared computer, rotating credentials, or offboarding a dev machine. Immediately purges local tokens.</p>
                            <div class="bg-black p-2 rounded text-[11px] font-mono text-zinc-400 border border-zinc-900">$ keysha logout</div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Projects & Workspace Scope -->
                <div class="space-y-3 pt-2">
                    <div class="flex items-center gap-2 text-xs font-semibold text-zinc-400 uppercase tracking-wider font-mono border-b border-zinc-900 pb-2">
                        <span class="text-emerald-400">02.</span>
                        <span>Projects & Workspace Context</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <code class="text-emerald-400 font-mono text-xs font-bold">keysha projects</code>
                                <span class="text-[10px] font-mono text-zinc-400 bg-zinc-900 border border-zinc-800 px-2 py-0.5 rounded">Vault</span>
                            </div>
                            <p class="text-xs text-zinc-300 font-medium">List workspace projects</p>
                            <p class="text-[11px] text-zinc-500">At the start of a coding session to see all projects in your vault, their environments, and expected variable counts.</p>
                            <div class="bg-black p-2 rounded text-[11px] font-mono text-zinc-400 border border-zinc-900">$ keysha projects</div>
                        </div>

                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <code class="text-emerald-400 font-mono text-xs font-bold">keysha project create &lt;name&gt;</code>
                                <span class="text-[10px] font-mono text-zinc-400 bg-zinc-900 border border-zinc-800 px-2 py-0.5 rounded">Management</span>
                            </div>
                            <p class="text-xs text-zinc-300 font-medium">Initialize a new project vault</p>
                            <p class="text-[11px] text-zinc-500">Starting a new app or microservice repository. Automatically sets up Development, Preview, and Production envs.</p>
                            <div class="bg-black p-2 rounded text-[11px] font-mono text-zinc-400 border border-zinc-900">$ keysha project create mingle</div>
                        </div>

                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-4 space-y-2 md:col-span-2">
                            <div class="flex items-center justify-between">
                                <code class="text-emerald-400 font-mono text-xs font-bold">keysha use &lt;project&gt;</code>
                                <span class="text-[10px] font-mono text-zinc-400 bg-zinc-900 border border-zinc-800 px-2 py-0.5 rounded">Context</span>
                            </div>
                            <p class="text-xs text-zinc-300 font-medium">Set active target project for session</p>
                            <p class="text-[11px] text-zinc-500">Set target project ONCE per dev session so you don't need to type <code class="bg-zinc-900 px-1 py-0.5 rounded text-emerald-400">--project=mingle</code> on every single command afterwards!</p>
                            <div class="bg-black p-2 rounded text-[11px] font-mono text-zinc-400 border border-zinc-900">$ keysha use mingle</div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Reading & Writing Credentials -->
                <div class="space-y-3 pt-2">
                    <div class="flex items-center gap-2 text-xs font-semibold text-zinc-400 uppercase tracking-wider font-mono border-b border-zinc-900 pb-2">
                        <span class="text-emerald-400">03.</span>
                        <span>Reading & Writing Credentials</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <code class="text-emerald-400 font-mono text-xs font-bold">keysha set &lt;key&gt; [dev|prod]</code>
                                <span class="text-[10px] font-mono text-zinc-400 bg-zinc-900 border border-zinc-800 px-2 py-0.5 rounded">Write</span>
                            </div>
                            <p class="text-xs text-zinc-300 font-medium">Encrypt and set variable credential</p>
                            <p class="text-[11px] text-zinc-500">Adding a new API key or DB connection string. Encrypts value at rest using Libsodium XSalsa20-Poly1305.</p>
                            <div class="bg-black p-2 rounded text-[11px] font-mono text-zinc-400 border border-zinc-900">$ keysha set STRIPE_SECRET_KEY dev</div>
                        </div>

                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <code class="text-emerald-400 font-mono text-xs font-bold">keysha get &lt;key&gt; [dev|prod]</code>
                                <span class="text-[10px] font-mono text-zinc-400 bg-zinc-900 border border-zinc-800 px-2 py-0.5 rounded">Read</span>
                            </div>
                            <p class="text-xs text-zinc-300 font-medium">Retrieve plaintext variable value</p>
                            <p class="text-[11px] text-zinc-500">Piping secret values directly into local shell scripts or Docker run commands without hardcoding keys.</p>
                            <div class="bg-black p-2 rounded text-[11px] font-mono text-zinc-400 border border-zinc-900">$ keysha get STRIPE_SECRET_KEY prod</div>
                        </div>

                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <code class="text-emerald-400 font-mono text-xs font-bold">keysha copy &lt;key&gt; [dev|prod]</code>
                                <span class="text-[10px] font-mono text-zinc-400 bg-zinc-900 border border-zinc-800 px-2 py-0.5 rounded">Clipboard</span>
                            </div>
                            <p class="text-xs text-zinc-300 font-medium">Copy secret directly to OS clipboard</p>
                            <p class="text-[11px] text-zinc-500">Pasting credentials into Postman or AWS console during Zoom screen shares without printing secrets on screen.</p>
                            <div class="bg-black p-2 rounded text-[11px] font-mono text-zinc-400 border border-zinc-900">$ keysha copy STRIPE_SECRET_KEY prod</div>
                        </div>

                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <code class="text-emerald-400 font-mono text-xs font-bold">keysha inspect &lt;key&gt;</code>
                                <span class="text-[10px] font-mono text-zinc-400 bg-zinc-900 border border-zinc-800 px-2 py-0.5 rounded">Metadata</span>
                            </div>
                            <p class="text-xs text-zinc-300 font-medium">Inspect key classification & provider</p>
                            <p class="text-[11px] text-zinc-500">Debugging a variable to check provider metadata (e.g. Stripe, AWS) or whether it is shared in Shared Vault.</p>
                            <div class="bg-black p-2 rounded text-[11px] font-mono text-zinc-400 border border-zinc-900">$ keysha inspect STRIPE_SECRET_KEY</div>
                        </div>

                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-4 space-y-2 md:col-span-2">
                            <div class="flex items-center justify-between">
                                <code class="text-emerald-400 font-mono text-xs font-bold">keysha list [dev|prod]</code>
                                <span class="text-[10px] font-mono text-zinc-400 bg-zinc-900 border border-zinc-800 px-2 py-0.5 rounded">Read</span>
                            </div>
                            <p class="text-xs text-zinc-300 font-medium">Audit project environment variables</p>
                            <p class="text-[11px] text-zinc-500">To verify which keys are set vs missing in Production before deploying a new build.</p>
                            <div class="bg-black p-2 rounded text-[11px] font-mono text-zinc-400 border border-zinc-900">$ keysha list prod</div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Parity & Environment Auditing -->
                <div class="space-y-3 pt-2">
                    <div class="flex items-center gap-2 text-xs font-semibold text-zinc-400 uppercase tracking-wider font-mono border-b border-zinc-900 pb-2">
                        <span class="text-emerald-400">04.</span>
                        <span>Environment Comparison & Auditing</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-4 space-y-2 md:col-span-2">
                            <div class="flex items-center justify-between">
                                <code class="text-emerald-400 font-mono text-xs font-bold">keysha diff [dev|prod]</code>
                                <span class="text-[10px] font-mono text-zinc-400 bg-zinc-900 border border-zinc-800 px-2 py-0.5 rounded">Comparison</span>
                            </div>
                            <p class="text-xs text-zinc-300 font-medium">Compare environment variable parity</p>
                            <p class="text-[11px] text-zinc-500">Pre-release auditing to detect missing keys between Development and Production before deploying.</p>
                            <div class="bg-black p-2 rounded text-[11px] font-mono text-zinc-400 border border-zinc-900">$ keysha diff dev prod</div>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Exporting & Local File Sync -->
                <div class="space-y-3 pt-2">
                    <div class="flex items-center gap-2 text-xs font-semibold text-zinc-400 uppercase tracking-wider font-mono border-b border-zinc-900 pb-2">
                        <span class="text-emerald-400">05.</span>
                        <span>Exporting & Local File Sync</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <code class="text-emerald-400 font-mono text-xs font-bold">keysha template [filepath]</code>
                                <span class="text-[10px] font-mono text-zinc-400 bg-zinc-900 border border-zinc-800 px-2 py-0.5 rounded">Schema</span>
                            </div>
                            <p class="text-xs text-zinc-300 font-medium">Output safe .env.example schema</p>
                            <p class="text-[11px] text-zinc-500">Committing code to GitHub repositories. Generates safe schema templates with empty values directly to file or stdout.</p>
                            <div class="bg-black p-2 rounded text-[11px] font-mono text-zinc-400 border border-zinc-900">$ keysha template .env.example</div>
                        </div>

                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <code class="text-emerald-400 font-mono text-xs font-bold">keysha pull [dev|prod] [filepath]</code>
                                <span class="text-[10px] font-mono text-zinc-400 bg-zinc-900 border border-zinc-800 px-2 py-0.5 rounded">Export / Local .env</span>
                            </div>
                            <p class="text-xs text-zinc-300 font-medium">Pull decrypted .env file to local disk or monorepo</p>
                            <p class="text-[11px] text-zinc-500">Export variables into local <code class="bg-zinc-900 px-1 py-0.5 rounded text-emerald-400">.env</code> or monorepo subfolder (<code class="bg-zinc-900 px-1 py-0.5 rounded text-emerald-400">apps/web/.env</code>) without overwriting unmanaged comments.</p>
                            <div class="bg-black p-2 rounded text-[11px] font-mono text-zinc-400 border border-zinc-900">$ keysha pull dev apps/web/src/.env</div>
                        </div>
                    </div>
                </div>

                <!-- Self-Hosting & Open Source Cloud Section -->
                <div id="self-host" class="pt-12 text-left space-y-6 max-w-4xl mx-auto border-t border-zinc-800">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-white tracking-tight">Self-Host Keysha Cloud Vault</h2>
                            <p class="text-xs text-zinc-400 mt-1">Deploy your own private cloud vault server on your infrastructure with zero vendor lock-in.</p>
                        </div>
                        <a href="https://github.com/Rocktown-Labs/keysha" target="_blank" rel="noopener noreferrer" class="px-3.5 py-1.5 bg-zinc-900 border border-zinc-800 hover:bg-zinc-800 text-xs font-mono text-zinc-200 rounded-md transition-colors flex items-center gap-2 cursor-pointer w-fit shrink-0">
                            <svg class="size-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/></svg>
                            <span>Star on GitHub</span>
                        </a>
                    </div>

                    <!-- Technology Stack & Architecture Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-3.5 space-y-1">
                            <span class="text-[10px] font-mono text-emerald-400 uppercase tracking-wider font-semibold">Crypto Core</span>
                            <h3 class="text-xs font-bold text-white">Libsodium AEAD</h3>
                            <p class="text-[11px] text-zinc-400">XChaCha20-Poly1305 envelope encryption with isolated workspace keys.</p>
                        </div>
                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-3.5 space-y-1">
                            <span class="text-[10px] font-mono text-emerald-400 uppercase tracking-wider font-semibold">Server & API</span>
                            <h3 class="text-xs font-bold text-white">Laravel 12 / PHP 8.5</h3>
                            <p class="text-[11px] text-zinc-400">RESTful Sanctum API endpoints, device code authorization, and audit logs.</p>
                        </div>
                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-3.5 space-y-1">
                            <span class="text-[10px] font-mono text-emerald-400 uppercase tracking-wider font-semibold">Web Vault UI</span>
                            <h3 class="text-xs font-bold text-white">Livewire 3 + Flux UI</h3>
                            <p class="text-[11px] text-zinc-400">Reactive dashboard, Vercel-style env import drawers, and step-up authorization.</p>
                        </div>
                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-3.5 space-y-1">
                            <span class="text-[10px] font-mono text-emerald-400 uppercase tracking-wider font-semibold">CLI Engine</span>
                            <h3 class="text-xs font-bold text-white">TypeScript & Bun</h3>
                            <p class="text-[11px] text-zinc-400">Compiled standalone binary executable for macOS, Linux, and Windows.</p>
                        </div>
                    </div>

                    <!-- Self-Hosting Deployment Options -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-5 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-white uppercase tracking-wider font-mono">Option 1: Docker Compose</span>
                                <span class="text-[10px] font-mono text-emerald-400 bg-emerald-950/40 border border-emerald-900/60 px-2 py-0.5 rounded">Recommended</span>
                            </div>
                            <p class="text-xs text-zinc-400">Spin up an isolated production stack with PostgreSQL, Redis, and Keysha server pre-configured.</p>
                            <div class="bg-black p-3 rounded font-mono text-[11px] text-zinc-300 border border-zinc-900 space-y-1">
                                <div><span class="text-zinc-600">$</span> git clone https://github.com/Rocktown-Labs/keysha.git</div>
                                <div><span class="text-zinc-600">$</span> cd keysha</div>
                                <div><span class="text-zinc-600">$</span> docker compose up -d</div>
                            </div>
                        </div>

                        <div class="bg-zinc-950 border border-zinc-800/90 rounded-lg p-5 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-white uppercase tracking-wider font-mono">Option 2: Standalone Laravel</span>
                                <span class="text-[10px] font-mono text-zinc-400 bg-zinc-900 border border-zinc-800 px-2 py-0.5 rounded">PHP 8.5 / SQLite</span>
                            </div>
                            <p class="text-xs text-zinc-400">Run directly on any VPS or local machine with PHP 8.5+ and sodium extension.</p>
                            <div class="bg-black p-3 rounded font-mono text-[11px] text-zinc-300 border border-zinc-900 space-y-1">
                                <div><span class="text-zinc-600">$</span> composer install</div>
                                <div><span class="text-zinc-600">$</span> cp .env.example .env</div>
                                <div><span class="text-zinc-600">$</span> php artisan migrate --seed</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="max-w-6xl mx-auto w-full text-center text-xs text-zinc-600 border-t border-zinc-800 pt-6">
            Keysha Vault &copy; {{ date('Y') }} — Built with Laravel & Libsodium.
        </footer>

        <!-- Package Manager Tab Switcher Script -->
        <script>
            const installCmds = {
                bun: 'bun add -g keysha-cli',
                pnpm: 'pnpm add -g keysha-cli',
                npm: 'npm install -g keysha-cli',
                yarn: 'yarn global add keysha-cli',
                curl: 'curl -fsSL https://keysha.dev/install.sh | sh'
            };
            let currentPm = 'bun';

            function setPm(pm) {
                currentPm = pm;
                document.getElementById('install-cmd').innerText = installCmds[pm];
                
                document.querySelectorAll('.pm-btn').forEach(btn => {
                    btn.className = 'pm-btn px-2 py-1 rounded cursor-pointer transition-colors uppercase text-[11px] text-zinc-500 hover:text-zinc-300';
                });
                
                const activeBtn = document.getElementById('tab-' + pm);
                if (activeBtn) {
                    activeBtn.className = 'pm-btn px-2 py-1 rounded cursor-pointer transition-colors uppercase text-[11px] bg-zinc-800 text-white font-bold shadow-xs';
                }
            }

            function copyInstallCmd() {
                const cmd = installCmds[currentPm];
                navigator.clipboard.writeText(cmd);
                const btnText = document.getElementById('copy-btn-text');
                btnText.innerText = '✓ Copied';
                setTimeout(() => { btnText.innerText = '📋 Copy'; }, 2000);
            }
        </script>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>

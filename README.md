# 🔑 KEYSHA — Developer Secret & Environment Vault

[![Open-Source Vault](https://img.shields.io/badge/Keysha-Vault_v1.0-emerald?style=flat-square&logo=shield)](https://github.com/Rocktown-Labs/keysha)
[![Laravel 12](https://img.shields.io/badge/Laravel-12.x-red?style=flat-square&logo=laravel)](https://laravel.com)
[![Bun CLI](https://img.shields.io/badge/Bun-CLI_1.0-orange?style=flat-square&logo=bun)](https://bun.sh)
[![Libsodium Encryption](https://img.shields.io/badge/Crypto-Libsodium_XChaCha20--Poly1305-blue?style=flat-square)](https://php.net/manual/en/book.sodium.php)

**Keysha** is an open-source secret vault for environment variables, API keys, and project tokens. Built with **Laravel 12**, **Livewire 3**, **Flux UI**, and a standalone **Bun CLI**, Keysha keeps your secrets safe and synchronized across Development, Preview, and Production.

---

## ⚡ Quickstart — Keysha CLI

Install the CLI globally with your package manager or shell script:

```bash
# Using Bun (recommended)
bun add -g keysha-cli

# Using pnpm / npm / yarn
pnpm add -g keysha-cli
npm install -g keysha-cli
yarn global add keysha-cli

# Standalone Shell Script
curl -fsSL https://keysha.dev/install.sh | sh
```

### Developer Quickstart Workflow

```bash
# 1. Authorize terminal session via browser OAuth
keysha login

# 2. Create a new project
keysha project create mingle

# 3. Set active project for your terminal session
keysha use mingle

# 4. Save an encrypted secret
keysha set STRIPE_SECRET_KEY dev

# 5. Export decrypted .env file to your local repository
keysha pull dev .env.local
```

---

## 🛠️ CLI Command Reference

Commands organized step-by-step from login to local file export:

### 1. Authentication & Device Setup
- `keysha login` — Authorize terminal session via browser OAuth.
- `keysha whoami` — Show active user email, workspace, and server host.
- `keysha logout` — Revoke session credentials and clear local device tokens.

### 2. Projects & Workspace Context
- `keysha projects` — List workspace projects, environments, and variable stats.
- `keysha project create <name>` — Create a project initialized with `Development`, `Preview`, and `Production`.
- `keysha use <project>` — Set active project so you do not need `--project=slug` on every command.

### 3. Reading & Writing Credentials
- `keysha set <key> [dev|prod]` — Encrypt and save a variable value.
- `keysha get <key> [dev|prod]` — Decrypt and print secret value to terminal.
- `keysha copy <key> [dev|prod]` — Copy decrypted secret directly to OS clipboard.
- `keysha inspect <key>` — View key classification, provider hint, and sharing status.
- `keysha list [dev|prod]` — Audit configured versus missing variables in target environment.

### 4. Environment Parity & Auditing
- `keysha diff [dev|prod]` — Compare variable parity side-by-side between Development and Production (e.g. `keysha diff dev prod`).

### 5. Exporting & Local File Sync
- `keysha template [filepath]` — Write safe `.env.example` schema with empty values.
- `keysha pull [dev|prod] [filepath]` — Export decrypted `.env` file to disk without overwriting unmanaged comments.

---

## 🚀 Self-Hosting Keysha Vault

### Option 1: Docker Compose (Recommended)

Run a production stack with PostgreSQL, Redis, and Keysha pre-configured:

```bash
git clone https://github.com/Rocktown-Labs/keysha.git
cd keysha
docker compose up -d
```

### Option 2: Standalone Laravel Setup

Run directly on any VPS or local machine with PHP 8.5+ and sodium extension:

```bash
# Install dependencies
composer install
bun install

# Configure environment & master key
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Start dev server
composer run dev
```

---

## 🔒 Cryptographic Vault Architecture

Keysha uses zero-trust **envelope encryption**:

1. **Master Key (`KEYSHA_MASTER_KEY`)**: Dedicated 32-byte Libsodium key used to wrap workspace keys.
2. **Workspace Keys**: Unique 256-bit symmetric keys unwrapped on demand using the master key.
3. **Data Encryption Keys (DEK)**: Unique per-version symmetric keys encrypting secret payloads via Libsodium XChaCha20-Poly1305.
4. **Immutable Versioning**: Every credential update creates a new immutable `VaultEntryVersion`, preserving audit history.

### Security Invariants & Hardening
- **Cache Prevention**: Strict `Cache-Control: no-store, private` headers on reveal endpoints.
- **Sanctum Capabilities**: Token capability checks (`secret:reveal`, `secret:write`, `metadata:read`).
- **Security Headers**: Automatic `X-Content-Type-Options`, `X-Frame-Options`, and `Referrer-Policy` middleware.

---

## 🧪 Testing

Run the Pest test suite and Pint code formatter:

```bash
# Run test suite
php artisan test --compact

# Format code
vendor/bin/pint --dirty --format agent
```

---

## 📄 License

Keysha is open-source software licensed under the [GNU AGPL-3.0 License](LICENSE). Built with ❤️ by [Rocktown Labs](https://rocktownlabs.com).

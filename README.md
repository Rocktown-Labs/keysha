# 🔑 KEYSHA — Durable Configuration & Credential Vault

[![Open-Source Vault](https://img.shields.io/badge/Keysha-Vault_v1.0-emerald?style=flat-square&logo=shield)](https://github.com/Rocktown-Labs/keysha)
[![Laravel 12](https://img.shields.io/badge/Laravel-12.x-red?style=flat-square&logo=laravel)](https://laravel.com)
[![Bun CLI](https://img.shields.io/badge/Bun-CLI_1.0-orange?style=flat-square&logo=bun)](https://bun.sh)
[![Libsodium Encryption](https://img.shields.io/badge/Crypto-Libsodium_XChaCha20--Poly1305-blue?style=flat-square)](https://php.net/manual/en/book.sodium.php)

**Keysha** is an open-source, developer-first envelope encryption vault for managing environment variables, API tokens, and project credentials. Built with **Laravel 12**, **Livewire 3**, **Flux UI**, and a standalone **TypeScript/Bun CLI**, Keysha serves as the canonical source of truth for your configuration files across Development, Preview, and Production.

---

## ⚡ Quickstart — Keysha CLI

Install the standalone CLI globally using your favorite package manager or direct installer script:

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
# 1. Authorize your terminal session via browser OAuth
keysha login

# 2. Initialize a new project container
keysha project create mingle

# 3. Set active project context for your terminal session
keysha use mingle

# 4. Encrypt and save a credential key
keysha set STRIPE_SECRET_KEY dev

# 5. Pull decrypted .env file directly into your local codebase
keysha pull dev .env.local
```

---

## 🛠️ CLI Command Reference

Commands are organized in natural step-by-step order:

### 1. Authentication & Device Setup
- `keysha login` — Authorize CLI device with your web vault account via browser OAuth.
- `keysha whoami` — Display active user email, workspace name, and server host.
- `keysha logout` — Revoke session credentials and clear local device tokens.

### 2. Projects & Workspace Context
- `keysha projects` — List workspace projects, environments, and expected variable counts.
- `keysha project create <name>` — Create a project initialized with `Development`, `Preview`, and `Production`.
- `keysha use <project>` — Set active target project for your terminal session so you don't need `--project=slug` on every command.

### 3. Reading & Writing Credentials
- `keysha set <key> [dev|prod]` — Encrypt & save a credential value using Libsodium.
- `keysha get <key> [dev|prod]` — Decrypt & output plaintext variable value to `stdout`.
- `keysha copy <key> [dev|prod]` — Decrypt secret directly into OS system clipboard (`pbcopy`).
- `keysha inspect <key>` — View variable metadata, provider hint, and sharing mode.
- `keysha list [dev|prod]` — Audit configured vs missing variables in target environment.

### 4. Environment Parity & Auditing
- `keysha diff [dev|prod]` — Compare variable completeness side-by-side between Development and Production (e.g. `keysha diff dev prod`).

### 5. Exporting & Local File Sync
- `keysha template [filepath]` — Output safe `.env.example` schema directly to file or `stdout`.
- `keysha pull [dev|prod] [filepath]` — Pull decrypted `.env` file directly to local disk or monorepo subfolder (`apps/web/.env`) without overwriting comments.

---

## 🚀 Self-Hosting Keysha Vault

### Option 1: Docker Compose (Recommended)

Deploy a production stack with PostgreSQL, Redis, and Keysha server pre-configured:

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

# Configure environment & master keys
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Start dev server
composer run dev
```

---

## 🔒 Cryptographic Vault Architecture

Keysha implements a zero-trust **envelope encryption** design:

1. **Master Key (`KEYSHA_MASTER_KEY`)**: Dedicated 32-byte Libsodium key used to wrap workspace keys.
2. **Workspace Keys**: Unique 256-bit symmetric keys unwrapped on demand using the master key.
3. **Data Encryption Keys (DEK)**: Unique per-version symmetric keys encrypting secret payloads via Libsodium XChaCha20-Poly1305.
4. **Immutable Versioning**: Every credential update creates a new immutable `VaultEntryVersion`, preserving complete audit history.

### Security Invariants & Hardening
- **Cache Prevention**: Strict `Cache-Control: no-store, private` headers on all reveal endpoints.
- **Sanctum Capabilities**: Token capability checks (`secret:reveal`, `secret:write`, `metadata:read`).
- **Security Headers**: Automatic `X-Content-Type-Options`, `X-Frame-Options`, and `Referrer-Policy` middleware.

---

## 🧪 Testing

Run the Pest PHP test suite and Pint code formatter:

```bash
# Run test suite
php artisan test --compact

# Code formatting
vendor/bin/pint --dirty --format agent
```

---

## 📄 License

Keysha is open-source software licensed under the [GNU AGPL-3.0 License](LICENSE). Built with ❤️ by [Rocktown Labs](https://rocktownlabs.com).

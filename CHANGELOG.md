# Changelog

All notable changes to **Keysha** will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] - 2026-08-10

### Added
- **Envelope Encryption Vault**: Libsodium XChaCha20-Poly1305 AEAD secret encryption with master-key & workspace-key isolation.
- **Web Vault Dashboard**: Livewire 3 + Flux UI reactive interface for project, environment, and credential management.
- **Vercel-Style Env Drawer**: Bulk `.env` paste drawer supporting provider hint detection, master/environment scoping, and key classification.
- **Standalone Bun CLI (`keysha-cli`)**: Cross-platform executable supporting `login`, `whoami`, `projects`, `project create`, `use`, `set`, `get`, `copy`, `inspect`, `list`, `diff`, `template`, and `pull`.
- **Short Environment Aliases**: Native support for `dev`, `prev`, and `prod` positional aliases and command flags across CLI commands.
- **`keysha pull` Command**: Export decrypted `.env` files to local disk or monorepo subfolders without overwriting file comments.
- **Security Hardening Pass**: Added `SecurityHeaders` middleware, Sanctum ability enforcement (`secret:reveal`, `secret:write`), and canonical `DeviceAuthorizationCode` normalization.
- **Self-Hosting Section & GitHub Docs**: Homepage documentation grid and Docker Compose setup guide.

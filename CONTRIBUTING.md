# Contributing to Keysha

Thank you for your interest in contributing to **Keysha**! Keysha is an open-source credential and environment variable vault built with Laravel, Livewire, Flux UI, and a Bun CLI.

---

## 🛠️ Development & Submission Workflow

To keep our codebase clean, audit-friendly, and maintainable, all contributions follow this standard team workflow:

### 1. Board & Issue Scoping
- Before submitting a pull request for a new feature or non-trivial fix, check existing GitHub Issues or open a new Issue describing the problem or proposal.
- Every Pull Request must reference its corresponding Issue (e.g., `Closes #123`). Merging the PR must automatically close the linked issue.

### 2. Single Commit per PR
- Please package your changes into a clean, unified commit per PR update or rebase before merging.

### 3. Update the Changelog
- Every PR must update `CHANGELOG.md` under the `[Unreleased]` section with a brief, human-readable summary of what was added, changed, or fixed.

---

## 🤖 AI Code & Transparency Policy

We embrace AI tools (such as Claude, Gemini, ChatGPT, Cursor, and Antigravity) to accelerate developer velocity, but we enforce strict quality and audit standards:

1. **No Low-Effort AI Slop**: Submissions generated blindly by AI without human verification, proper test coverage, or code review will be rejected.
2. **AI Session Disclosure**: If an AI assistant or autonomous agent generated or assisted with your code, **you MUST attach or link the AI agent conversation/session log artifact** in your PR description. This ensures maintainers can review the rationale, context, and prompts used.

---

## 🧪 Testing & Code Formatting

Before submitting a PR, make sure your code passes all formatting and automated test checks:

```bash
# 1. Run Pint PHP Code Formatter
vendor/bin/pint --dirty --format agent

# 2. Run Pest Test Suite
php artisan test --compact

# 3. Compile Web Assets & CLI
bun run build
cd cli && bun run build
```

---

## 📄 License & Copyleft

By contributing to Keysha, you agree that your contributions will be licensed under the project's [GNU AGPL-3.0 License](LICENSE).

# AGENTS.md — Appendix (committed)

> This file is an **appendix** to the master `AGENTS.md` at `/home/kevin/Documents/AGENTS.md`
> (which forbids modification). No exception there applies to this file — it is the
> committed home for cross-repo guidelines.

---

## Architecture-doc hardlinks

Canonical architecture/ecosystem notes live at `/home/kevin/Documents/`:

| Doc                         | Purpose                                   |
|-----------------------------|-------------------------------------------|
| `MODULE_DIRECTORY.md`       | Ecosystem map: every package, purpose, namespace, dependencies. **Read first.** |
| `APP_TAB_ARCHITECTURE.md`   | App/tab plugin architecture (host/child/plugin layers, traits, roadmap). |
| `PACKAGIST.md`              | Our packagist packages catalogue — check before writing new infrastructure. |

### Rule for every FA / WP related repo

1. **Hardlink** these docs into the repo root before first use:
   `ln -f /home/kevin/Documents/<doc> <repo>/<doc>`
2. Keep them **mode `0444`** (read-only). Editing is intentional only:
   `chmod 644`, edit, `chmod 444` — then re-run step 1.
3. Hardlinks share one inode, so local edits propagate instantly to every link.

### Caveats (do not fight them — plan around them)

- **Hardlinks do NOT survive git.** `git pull`, `git checkout`, and `git clone`
  overwrite the file with a fresh copy — breaking the link and resetting mode
  to `0644`. After any git operation, re-run the `ln -f` step.
- For **clone portability**, commit a plain copy (snapshot) in the repo; if you
  later update the canonical doc, re-copy + commit. This is the only git-native,
  clone-safe distribution mechanism (the Git server prefers plain files over
  symlinks).
- Existing `MODULE_DIRECTORY.md` **symlinks** committed in ksf_FA_CRM, ksf_FA_HRM,
  ksf_FA_Square, ksf_FA_Woocommerce point at an absolute out-of-repo target and
  are dangling on clone. **Leave them as-is** unless an owner explicitly asks to
  replace them with hardlinks/plain files.
- New repo that needs an architecture doc: hardlink `MODULE_DIRECTORY.md`,
  `APP_TAB_ARCHITECTURE.md`, `PACKAGIST.md`, and any future `*_ARCHITECTURE.md`
  notes, per this section.

### Current hardlink carriers

`FA_ProductAttributes`, `FA_ProductAttributes_Core`, `Traits`, `ksf_FA_Common`,
`ksf_FA_CRM`, `ksf_FA_HRM`, `ksf_FA_ProjectManagement`, `ksf_ProjectManagement`,
`ksf_CRM`, `ksf_HRM`, `ksf_FA_Square`, `ksf_FA_Woocommerce`,
`ksf_FA_Calendar`, `ksf_Calendar`. (Repos with a pre-existing tracked
`MODULE_DIRECTORY.md` symlink keep their symlink; new docs are hardlinked.)

---

## ksf_FA_Common class-loading architecture (single-source)

Decided 2026-08-29, fixed the productionization fatals. Canonical doc:
`ksf_FA_Common/doc/ProjectDocuments/LOADING_ARCHITECTURE.md`.

- **Rule:** every class loads via exactly one mechanism per process. Never
  `require_once` a class file that is autoloadable; never give two autoloaders
  the same prefix.
- **ksf_FA_Common stays a deployed FA module = the ONE authoritative source**
  for `ksfraser\FrontAccounting\Common\` and `Ksfraser\Frontaccounting\HTML\`.
  Its `src/autoload.php` registers an `spl_autoload_register` loader; registered
  from `hooks_ksf_FA_Common::__construct()` AND first-line in standalone pages
  (e.g. FA_ProductAttributes `public/*.php`) before their composer autoload.
- Vendored `ksfraser/ksf-fa-common` composer copies are **inert** now; purge
  from client `composer.json` + delete vendored dirs post-launch (or move to one
  shared composer root at the FA webroot — the preferred endgame).
- **Only packages that are ALSO an FA module directory risk redeclare fatals**
  (today only ksf_FA_Common). Plain vendored packages (ksf-modules-dao, famock,
  ksf-schemamanager) only suffer version skew across copies — keep copies in
  sync; do NOT convert them into modules.
- **Guards:** `class_exists(..., false)` + top-level `return` is a trap under
  opcache — still fatals on re-require. Use a `define()` constant with the class
  declaration **inside** the `if` (ComposerDependencies.php, src/autoload.php).
- **Activation order:** dependents must verify ksf_FA_Common is active in
  `activate_extension()` via `defined('KSF_FA_COMMON_LOADER_REGISTERED')` —
  if not, echo a warning and `return false`. Implemented in FA_ProductAttributes;
  TODO: HRM, RBAC, Mail, SuggestedPurchaseOrder, Calendar, Square, Woocommerce,
  ProjectManagement, CRM, DataIntegrity, Logging.
- **opcache gotcha:** UAT (podman `ksf73`, php:7.3-alpine, :8080) caches
  pages for revalidate_freq — after editing `public/*.php`/hooks deploy, run an
  `opcache_reset()` probe once (e.g. `modules/reset.php`) and remove it.
# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

LMPA (Laragon MultiPHP per App) is a Windows-only PHP CLI tool for managing multiple PHP versions per application in Laragon. It handles PHP version installation/removal, PECL module management, native module toggling, per-app PHP ini configuration, and Apache vhost setup.

**Version:** 0.4.0
**Platform:** Windows only (`PHP_OS === 'WINNT'`)
**Entry point:** `index.php` — interactive CLI menu using League\CLImate

## Running

```bash
# Direct execution (must be on Windows with Laragon)
php index.php

# Or double-click LMPA.lnk shortcut
```

Install dependencies: `composer install`

Build executable: uses Box.phar with `box.json` config to bundle into a .phar

No test suite exists.

## Architecture

**Menu-driven CLI** — `index.php` displays a main menu and instantiates the selected controller, passing in the CLImate instance.

### Controllers (`controllers/`)

Each controller handles one feature domain. They receive a `$climate` (CLImate) instance via constructor and drive all user interaction through it.

| Controller | Purpose |
|---|---|
| `PHPVersionsController` | Download/remove PHP versions from windows.php.net, SHA256 verification, batch alias creation, ini merging |
| `PHPPECLModulesController` | Scrape PECL module database, install/remove PECL DLLs, update php.ini extensions |
| `PHPModulesController` | Enable/disable native PHP modules by editing php.ini extension entries |
| `AppsController` | Per-app PHP version switching via Apache vhost config, .user.ini management, PHP parameter compatibility checking |
| `PHPIniController` | Static utility class for parsing/writing/merging ini files; contains hardcoded version compatibility matrix (PHP 5.5–8.1) |
| `SetupController` | First-run wizard: SSL verification, phpMyAdmin install, Composer install, mod_fcgid setup, CLI aliases directory |

### Libraries (`lib/`)

- **`helpers.php`** — Global utility functions (`menu()`, `path()`, `human_filesize()`, `curl_progress_bar()`, `removeDirectory()`, etc.). Auto-loaded via composer.
- **`CompactTable.php`** — Custom CLImate extension for rendering multi-line CLI tables with dynamic column widths.
- **`PECL.php`** — Scrapes windows.php.net/downloads/pecl/releases to build `pecl.json` module database.

### Namespaces (PSR-4)

- `Controller\` → `controllers/`
- `Lib\` → `lib/`

## Key Patterns

- **Phar-aware paths:** The `path()` helper resolves file paths differently when running inside a .phar bundle vs development. Always use `path()` for file references.
- **Apache config markers:** `AppsController` uses `# --- LMPA_START_* --- #` / `# --- LMPA_END_* --- #` markers in Apache .conf files for idempotent section management.
- **INI handling:** `PHPIniController::parse_ini()` does line-by-line parsing that preserves comments and structure — don't use PHP's native `parse_ini_file()`.
- **PECL cache:** Module data is scraped from the web and cached in `pecl.json`. Rebuilt via `PECL::buildDatabase()`.

## Dependencies

- `league/climate` — CLI output formatting, menus, progress bars
- `mervick/curl-helper` — HTTP requests for downloading PHP/PECL
- `bugsnag/bugsnag` — Error tracking (initialized in index.php)
- Required extensions: `ext-curl`, `ext-zip`, `ext-json`

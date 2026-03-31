# Changelog

All notable changes to LMPA (Laragon MultiPHP per App) will be documented in this file.

## [0.6.0] - 2026-03-30

### Added
- Status overview dashboard showing all installed PHP versions and which apps use each
- Unused PHP versions highlighted in status view
- Change document root (ROOT) per app from CLI
- PHP version in-use safety check when removing a version
- Version replacement flow when deleting a PHP version used by apps
- LMPA logo and menu re-displayed after each action

### Changed
- "Status" added as first item in main menu

## [0.5.3] - 2026-03-30

### Fixed
- JIT key typos in opcache configuration
- Packaged (PHAR) config file path detection using `Phar::running()`

## [0.5.2] - 2026-03-30

### Changed
- CLI flow is now non-fatal and menu-driven (continuous loop instead of exit after each action)
- Controllers return instead of calling `exit()`/`die()`

## [0.5.1] - 2026-03-30

### Added
- Automated GitHub Actions release pipeline for building and publishing PHAR

## [0.5.0] - 2026-03-30

### Added
- PHP extensions API integration with phpext.phptools.online
- Native HTTP client (`HttpClient`) replacing external curl-helper dependency
- Settings menu for API token configuration
- API error message extraction and display

### Removed
- `mervick/curl-helper` dependency
- PECL HTML scraping system (`PECL.php`, `pecl.json`)

## [0.4.0] - 2023-01-16

### Changed
- Added PHP 8.0 and 8.1 support in composer constraints

## [0.3.6] - 2023-01-16

### Fixed
- Composer setup in initial wizard

## [0.3.5] - 2022-04-04

### Fixed
- Composer installation in setup wizard

## [0.3.4] - 2022-03-27

### Fixed
- LMPA now works from any folder or drive (not just default Laragon location)

## [0.3.3] - 2022-03-22

### Fixed
- PECL database file path resolution
- Apache LoadModule directive parsing

## [0.3.2] - 2022-03-21

### Fixed
- `pecl_http` extension name mapping during install
- Composer CLI aliases generation

### Added
- Bugsnag error tracking

### Changed
- Updated PECL module database

## [0.3.1] - 2022-03-04

### Added
- Remove CLI aliases (php/composer .bat files) when removing a PHP version

## [0.3.0] - 2022-03-02

### Added
- PHP 7.3.33 to deprecated versions list
- `_aliases` directory for CLI shortcuts (php74, php80, composer74, etc.)

## [0.2.1] - 2022-02-28

### Fixed
- Manager shortcut path

## [0.2.0] - 2022-02-28

### Added
- Initial setup wizard (SSL check, phpMyAdmin install, Composer install, mod_fcgid setup)
- Desktop shortcut for launching LMPA

## [0.0.1] - 2022-02-21

### Fixed
- Auto-prefix rename when converting Laragon-managed apps to LMPA-managed

## [0.0.0] - 2022-02-21

### Added
- Initial release
- PHP version management (download/remove from windows.php.net with SHA256 verification)
- PECL module management (scrape, install, remove DLLs)
- Native PHP module toggling (enable/disable via php.ini)
- Per-app PHP version switching via Apache vhost configuration
- Per-app php.ini settings via .user.ini
- Apache vhost management with LMPA markers for idempotent config sections
- INI file parser preserving comments and structure
- Support for deprecated PHP versions (5.5, 5.6, 7.0, 7.1, 7.2)

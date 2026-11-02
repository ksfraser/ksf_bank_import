# ARCH-010: Compatibility and Deployment Architecture

## Design Target: BR-010 (Compatibility) / NFR-011 (PHP Compatibility) / NFR-012 (FA Compatibility) / NFR-013 (Browser Compatibility)

## Design Elements
- composer.json / composer.lock (PHP 7.4 pin, config.platform.php)
- hooks.php / _init/config (extension activation, version compatibility gate)
- DEPLOYMENT_GUIDE.md / SETUP.md / INCREMENTAL_DEPLOYMENT_PLAN.md (deployment procedures)
- FA 2.4.3 compatibility: uses native db_* calls, no prepared statements (PDO is standalone only)
- docs/ARCHITECTURE.md (§Deployment / Compatibility)

## References
- PACKAGIST.md (published package conventions)
- AGENTS.md (§ComposerDependencies, container PHP pin, bind verification)
- docs/REQUIREMENTS_SPECIFICATION.md (NFR-011 through NFR-016)

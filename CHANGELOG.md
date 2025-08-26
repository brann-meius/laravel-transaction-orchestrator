# Changelog

All major changes and improvements to **Laravel Transaction Orchestrator** are documented in this file.

## [0.1.0]

### Added

- `#[Transactional]` attribute for wrapping controller methods in database transactions.
    - Support for retries and backoff policies.
    - Configurable `noRollbackOn` exception list.
    - HTTP response - driven rollback policies (`HttpRollbackPolicy` or explicit status codes).
    - Multi-connection support.
- `#[LockForUpdate]` attribute for pessimistic row-level locking.
- `#[SharedLock]` attribute for shared row-level locking.
- Automatic integration with Laravel router for lock - aware route model binding.
- Database driver support aligned with Laravel’s native locking API.

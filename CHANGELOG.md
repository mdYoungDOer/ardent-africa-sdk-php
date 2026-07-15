# Changelog

All notable changes to `ardent-africa/sdk` are documented here. This project follows
[Semantic Versioning](https://semver.org). The client is read-only over the Ardent Africa
public API (`/public/v1`).

## [1.1.0] - 2026-07-15

### Added
- **Jobs**: `listJobs(array $query = [])` and `getJob(string $slug)` for the public Job
  Portal (`GET /jobs`, `GET /jobs/{slug}`), returning open postings only. Never any
  candidate, application, or message data.
- **Reviews**: `listReviewEntities(array $query = [])` and `getReviewEntity(string $slug)`
  for reviewed entities and their TrustScore (`GET /reviews/entities`,
  `GET /reviews/entities/{slug}`).

No breaking changes. All existing methods are unchanged.

## [1.0.0] - Initial release

- Read-only PHP client for `/public/v1`: campaigns, petitions, blog posts, public profiles,
  platform stats, marketplace services + categories, and events.
- Dependency-free (`ext-curl` + `ext-json`), PHP 7.4+, injectable transport for tests.

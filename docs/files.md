# Files subsystem (v1 foundation)

This project now uses a first-class **Files** subsystem for uploaded assets and documents.

## Why "Files" and not "Media"

`Files` is intentionally broader than image-only or media-only concerns:

- supports images and documents (`pdf`, `docx`, `xlsx`, etc.)
- cleanly models upload metadata independent from future rendering concerns
- prepares for future protected/private access patterns
- avoids coupling naming to front-end media use cases

## Current schema

Table: `files`

- `id`
- `original_name`
- `stored_name`
- `slug`
- `mime_type`
- `extension`
- `size_bytes`
- `visibility`
- `storage_disk`
- `storage_path`
- `checksum_sha256` nullable
- `uploaded_by_user_id` nullable
- `created_at`
- `updated_at`

## Domain model

- `FileAsset` immutable aggregate root for uploaded file metadata.
- `FileVisibility` enum with v1 states:
  - `public`
  - `authenticated`
  - `private`

## Repository and storage abstraction

- `FileRepositoryInterface`
- `MySqlFileRepository`
- `FileStorageInterface`
- `LocalFileStorage`

`LocalFileStorage` writes to runtime storage under `storage/files/` and enforces relative normalized storage paths.

## Upload service

`FileUploadService` responsibilities:

- validates uploaded metadata
- computes SHA-256 checksum
- generates stable stored filenames from slug + checksum prefix
- writes content through `FileStorageInterface`
- persists `FileAsset` through `FileRepositoryInterface`

## Admin UI

Administrators manage uploaded Files (not Media) from:

- `/admin/files` list and browse
- `/admin/files/upload` multipart upload flow
- `/admin/files/{id}/edit` metadata inspection/edit (`slug`, `visibility`)

Delete flow is storage-safe: deleting a file in admin removes both the database row and the storage object through `FileStorageInterface`.

## Content field integration (v1)

`content_type_fields.field_type` values `image` and `file` are integrated with the Files subsystem:

- Admin content create/edit renders these fields as file selectors sourced from uploaded `files`.
- New writes persist `content_items.field_values_json` values as:
  - file ID (`int`) when selected
  - `null` when cleared (if field is not required)
- Validation enforces that selected IDs reference existing `FileAsset` rows.

Backward compatibility:

- Older rows may still contain legacy URL/string values for `image`/`file` fields.
- Runtime reads these values safely (no fatal errors).
- Legacy values should be migrated to file IDs during data cleanup or content re-save workflows.

## Future extension points

This v1 foundation is intentionally minimal but future-ready for:

- public vs authenticated vs private delivery controllers
- clean public URLs and download endpoints
- relation tables linking Files to content items and structured file fields
- non-local disks (S3/object storage) via `FileStorageInterface`

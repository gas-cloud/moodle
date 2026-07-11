# Changelog

## v1.0.0 (2026-07-02)

- Initial release
- Three web service functions: create/update, delete, and read group overrides
- Upsert behavior: create_group_override automatically updates if override already exists for same quiz+group
- Full capability checking (mod/quiz:manageoverrides)
- Course-group validation (prevents cross-course override creation)

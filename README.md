# Exam Override Web Service (local_examoverride)

A Moodle local plugin that exposes quiz group override management via Web Services (REST API). This fills a gap in Moodle core, which provides no built-in web service for creating, reading, or deleting quiz group overrides programmatically.

## Why this plugin?

Moodle core allows managing quiz group overrides through the UI (Quiz → Quiz administration → Group overrides), but provides **no web service API** for it. This makes it impossible to automate exam scheduling workflows — such as:

- Setting per-group quiz open/close times from an external exam supervision system
- Programmatically creating makeup/remedial exam windows
- Batch-managing override schedules across many courses

This plugin solves that by exposing three simple, well-scoped web service functions.

## Features

| Function | Description |
|---|---|
| `local_examoverride_create_group_override` | Create or update a group override for a quiz (upsert: if an override already exists for the same quiz+group, it updates instead of duplicating) |
| `local_examoverride_delete_group_override` | Delete an existing group override |
| `local_examoverride_get_group_overrides` | Read all group overrides for a quiz |

### Override fields supported

- `timeopen` — Quiz open timestamp
- `timeclose` — Quiz close timestamp
- `timelimit` — Time limit in seconds
- `password` — Quiz access password
- `attempts` — Number of allowed attempts (0 = unlimited)

## Requirements

- Moodle 3.11 or later (tested up to Moodle 4.x)
- The calling user/token must have `mod/quiz:manageoverrides` capability in the relevant quiz context

## Installation

### Method 1: ZIP upload (recommended for most users)

1. Download the latest release ZIP from the [Releases](../../releases) page
2. In Moodle, go to **Site Administration → Plugins → Install plugins**
3. Upload the ZIP file, select plugin type "Local plugin"
4. Follow the on-screen prompts, or run `php admin/cli/upgrade.php` via CLI

### Method 2: Manual (Git clone)

```bash
cd /path/to/moodle/local
git clone https://github.com/gas-cloud/moodle.git examoverride
```

Then visit your Moodle site as admin, or run:

```bash
php admin/cli/upgrade.php
```

### Post-installation setup

1. Go to **Site Administration → Server → Web services → External services**
2. Select your service (or create a new one)
3. Click **Add functions** and search for `examoverride`
4. Add all three functions:
   - `local_examoverride_create_group_override`
   - `local_examoverride_delete_group_override`
   - `local_examoverride_get_group_overrides`
5. Make sure the token user has the `mod/quiz:manageoverrides` capability (Manager role at system level, or a custom role with this capability)

## API Reference

### create_group_override

Creates a new group override, or updates it if one already exists for the same quiz+group combination.

**Parameters:**

| Name | Type | Required | Description |
|---|---|---|---|
| `quizid` | int | Yes | Quiz instance ID |
| `groupid` | int | Yes | Moodle group ID (must belong to the same course as the quiz) |
| `timeopen` | int | No | Unix timestamp for quiz open time. 0 = don't override |
| `timeclose` | int | No | Unix timestamp for quiz close time. 0 = don't override |
| `timelimit` | int | No | Time limit in seconds. -1 = don't override |
| `password` | string | No | Quiz password. Empty string = don't override |
| `attempts` | int | No | Number of attempts allowed. 0 = unlimited, -1 = don't override |

**Example request:**

```
wsfunction=local_examoverride_create_group_override
&quizid=136
&groupid=2471
&timeopen=1720410600
&timeclose=1720415100
&timelimit=4500
&password=482
&attempts=1
```

**Response:**

```json
{
  "status": true,
  "overrideid": 1087,
  "action": "created"
}
```

### delete_group_override

Deletes an existing group override.

**Parameters:**

| Name | Type | Required | Description |
|---|---|---|---|
| `quizid` | int | Yes | Quiz instance ID |
| `groupid` | int | Yes | Moodle group ID |

**Response:**

```json
{
  "status": true,
  "msg": "Override berhasil dihapus."
}
```

### get_group_overrides

Reads all group overrides for a given quiz.

**Parameters:**

| Name | Type | Required | Description |
|---|---|---|---|
| `quizid` | int | Yes | Quiz instance ID |

**Response:**

```json
{
  "status": true,
  "overrides": [
    {
      "overrideid": 1087,
      "groupid": 2471,
      "groupname": "1|Senin|1-2 (07.30 s/d 08.45 WIB)",
      "timeopen": 1720410600,
      "timeclose": 1720415100,
      "timelimit": 4500,
      "attempts": 1,
      "password": "482"
    }
  ]
}
```

## Security

- All functions validate context and require `mod/quiz:manageoverrides` capability
- Group membership is validated against the quiz's course (you cannot create an override for a group that doesn't belong to the quiz's course)
- No custom capabilities are introduced; the plugin uses Moodle's existing permission model
- This plugin does NOT create calendar events (by design, to avoid compatibility issues across Moodle versions)

## Use Cases

- **Exam supervision systems** — Automatically set quiz windows per exam room/session
- **Makeup/remedial exams** — Create new groups and set custom time windows for students who need to retake
- **Batch scheduling** — Set overrides for many groups across courses from a central admin tool
- **LTI/external tool integration** — Any external system that needs to control quiz timing via API

## License

This plugin is licensed under the [GNU GPL v3](https://www.gnu.org/licenses/gpl-3.0.html), the same license as Moodle itself.

## Credits

Developed for the exam supervision system at Universitas Muhammadiyah Malang (UMM), Indonesia.

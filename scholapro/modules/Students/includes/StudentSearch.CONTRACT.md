# searchStudents() API Contract

## Signature (v2 — current)

```php
searchStudents( int $school_id, string $query, array $options = [] ): array
```

## Parameters

| Parameter   | Type   | Required | Default | Description |
|-------------|--------|----------|---------|-------------|
| `$school_id`| int    | Yes      | —       | School ID to scope results. |
| `$query`    | string | Yes      | —       | Search term. Matched via `LIKE` against `first_name` and `last_name` (case-insensitive). Empty string returns zero results immediately. |
| `$options`  | array  | No       | `[]`    | Optional filters and pagination. See `$options` keys below. |

### `$options` keys

| Key            | Type   | Values                  | Default    | Description |
|----------------|--------|-------------------------|------------|-------------|
| `grade_level`  | string | Grade level ID or empty  | `''`       | Filter by grade level. Empty = no filter. |
| `status`       | string | `'active'`, `'inactive'`, `'all'` | `'active'` | Enrollment status filter. Active = `END_DATE IS NULL`. |
| `limit`        | int    | ≥ 1                     | `50`       | Max results per page. |
| `offset`       | int    | ≥ 0                     | `0`        | Row offset for pagination. |

## Return Value

```php
[
    'students' => [ ... ],  // indexed by STUDENT_ID, or empty array
    'total'    => int,       // total matching rows (ignoring LIMIT/OFFSET)
    'has_more' => bool,      // true if offset + limit < total
]
```

### Each student record

| Column            | Type   | Description |
|-------------------|--------|-------------|
| `STUDENT_ID`      | int    | Student primary key. |
| `FIRST_NAME`      | string | First name. |
| `LAST_NAME`       | string | Last name. |
| `NAME_PREFIX`     | string | Name prefix (Mr., Mrs., etc.). |
| `GRADE_ID`        | int    | Grade level ID from enrollment. |
| `SCHOOL_ID`       | int    | School ID from enrollment. |
| `ENROLLMENT_STATUS`| string| `'active'` or `'inactive'`. |

## Tables Joined

- `students` (s)
- `student_enrollment` (se) — joined on `s.STUDENT_ID = se.STUDENT_ID`

## Filtered by

- `se.SYEAR` = current school year (`UserSyear()`)
- `se.SCHOOL_ID` = provided `$school_id`
- Name LIKE match on `s.FIRST_NAME` or `s.LAST_NAME`

## Ordering

`ORDER BY s.LAST_NAME, s.FIRST_NAME`

## Pagination

Results are ordered deterministically. Use `offset` + `limit` for cursor-based pagination.
The `total` and `has_more` fields allow UI to render page controls.

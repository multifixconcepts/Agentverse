# StudentSearch.fnc.php — CHANGELOG & Migration Guide

## v2 (Current) — Disruptive Rewrite (ScholaPro 12.6)

### Breaking Changes

1. **Function signature changed.** Third parameter `$options` added (optional, backward-compatible at call site).
2. **Return format changed entirely.** v1 returned a flat array of student records. v2 returns a structured response:
   ```php
   // OLD (v1) — REMOVED
   $students = searchStudents( $school_id, 'Smith' );
   foreach ( $students as $student ) {
       echo $student['FIRST_NAME'];
   }

   // NEW (v2)
   $result = searchStudents( $school_id, 'Smith', [
       'status' => 'all',
       'limit'  => 25,
   ] );
   foreach ( $result['students'] as $student ) {
       echo $student['FIRST_NAME'];
   }
   echo $result['total'] . ' total matches';
   if ( $result['has_more'] ) { /* show "Next" button */ }
   ```
3. **New `ENROLLMENT_STATUS` column** in each student record (`'active'` or `'inactive'`).

### Migration Steps for Callers

| # | Action |
|---|--------|
| 1 | Search your codebase for `searchStudents(`. |
| 2 | At every call site, capture the return value into a variable (it is now an associative array with keys `students`, `total`, `has_more`). |
| 3 | Replace any `foreach ( searchStudents(...) as ... )` with `foreach ( $result['students'] as ... )`. |
| 4 | If your caller previously relied on only active students (the default v1 behavior), no change is needed — `$options['status']` defaults to `'active'`. |
| 5 | If you need inactive students too, pass `'status' => 'all'`. |
| 6 | For paginated UIs, use `$result['total']` and `$result['has_more']` to render page controls. |

### New Capabilities (v2)

- **Grade level filter**: `$options['grade_level'] = $grade_id`
- **Status filter**: `'active'`, `'inactive'`, or `'all'`
- **Pagination**: `$options['limit']` and `$options['offset']`
- **Structured response**: total count and has_more flag without extra queries

### Files Affected

| File | Impact |
|------|--------|
| `modules/Students/includes/StudentSearch.fnc.php` | **Rewritten** (this file). |
| `modules/Students/includes/StudentSearch.CONTRACT.md` | **Created** (API contract). |
| Any caller of `searchStudents()` | Must migrate return value handling. |

### Backward Compatibility

The v1 signature `searchStudents($school_id, $query)` is **removed**. There is no deprecation shim because the return type changed incompatibly. All callers MUST be updated.

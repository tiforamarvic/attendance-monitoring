# Bulk Remove Students From a Class — Design

## Problem

The teacher sometimes imports a class list (Excel/CSV) into the wrong `ClassRoom` (wrong section). Today, fixing that means clicking "Remove" one student at a time on the class show page (`resources/views/classes/show.blade.php`). For a full class list this is tedious. We need a way to select many students at once and remove them from a class in one action.

## Scope

- Bulk **remove** (unenroll) students from the class they're currently being viewed on — this only detaches the `class_room_student` pivot row. It does **not** touch the `Student` record itself, does not affect the student's enrollment in other classes, and does not delete any `attendance_records`.
- Bulk **permanent delete** is out of scope for this feature. The existing single-row "Delete" (permanent, cascades everywhere) action is untouched and remains a per-row action only.
- No pagination/filtering concerns — the class show page already renders the full student list for that class with no pagination.

## Backend

### Route

Add to the existing `classes/{classRoom}/students` group in `routes/web.php`, placed **before** the existing `DELETE /{student}` (`classes.students.destroy`) route so the literal path isn't swallowed by the `{student}` wildcard:

```php
Route::delete('/bulk-remove', [StudentController::class, 'bulkRemove'])->name('bulk-remove');
```

Resulting name: `classes.students.bulk-remove`.

### Form Request

New `App\Http\Requests\BulkRemoveStudentsRequest`:

- `student_ids` — `required|array|min:1`
- `student_ids.*` — `integer|distinct|exists:students,id`

Follows the existing convention set by `StoreStudentRequest` / `ImportStudentsRequest`.

### Controller

`StudentController::bulkRemove(BulkRemoveStudentsRequest $request, ClassRoom $classRoom): RedirectResponse`

```php
$ids = $request->validated('student_ids');
$count = $classRoom->students()->detach($ids);

return redirect()->route('classes.show', $classRoom)
    ->with('success', "{$count} student(s) removed from this class.");
```

`detach()` silently ignores any id not currently enrolled in this class, so no extra guarding is needed — the count returned by `detach()` reflects how many pivot rows were actually removed, which is what gets shown to the user.

## Frontend (`resources/views/classes/show.blade.php`)

### Selection UI

- Add a checkbox column as the **first** column of the students table.
  - Header cell: a "select all" checkbox (`id="select-all-students"`).
  - Each row: `<input type="checkbox" name="student_ids[]" value="{{ $student->id }}" form="bulk-remove-form" class="student-select-checkbox">`.
- Existing per-row "Remove" / "Delete" `<form>` elements inside the last `<td>` are untouched.

### Standalone bulk form

Because the per-row actions already use `<form>` elements inside table cells, the table cannot itself be wrapped in another `<form>` (forms cannot nest). Instead, an empty, visually hidden form is placed once, near the table (e.g. immediately before it):

```html
<form id="bulk-remove-form" method="POST"
      action="{{ route('classes.students.bulk-remove', $classRoom) }}"
      data-confirm
      data-confirm-title="Remove Selected Students"
      data-confirm-message=""
      data-confirm-ok="Remove">
    @csrf
    @method('DELETE')
</form>
```

Checkboxes and the bulk submit button reference it via the HTML `form="bulk-remove-form"` attribute, so they can live anywhere on the page without being descendants of it.

### Bulk action bar

A bar rendered above the table, hidden by default (`hidden` attribute / `display:none`), shown via JS once ≥1 checkbox is checked:

- Text: "`N selected`"
- "Remove Selected" button — `type="submit" form="bulk-remove-form"`
- "Clear" button/link — unchecks all checkboxes and re-hides the bar

Styling matches the existing action-bar conventions in the app (slate/primary palette, `rounded-lg`, consistent with buttons already in `classes/show.blade.php`).

### JS behavior (inline `<script>`, same pattern as the existing confirm-modal script)

- On any `.student-select-checkbox` `change` event, or the select-all checkbox's `change` event:
  - Sync checked state (select-all ↔ individual boxes).
  - Recompute the checked count.
  - Toggle the bulk action bar's visibility based on count > 0.
  - Update the bulk form's `data-confirm-message` attribute to `` `Remove ${count} student(s) from this class? Their records and attendance history will remain intact.` `` (mirrors the wording already used in the existing single-row "Remove" confirm message) so the shared confirm-modal script (`layouts/app.blade.php`) picks it up at submit time — no changes needed to that shared script since it already reads `data-confirm-message` fresh from the form on every submit.
- No AJAX; this is a normal form POST (with `_method=DELETE` spoofing), consistent with the rest of the app.

### Empty state

If the class has no students, the checkbox column/table isn't rendered at all (existing empty-state branch is unchanged).

## Testing (Pest feature tests)

New test file `tests/Feature/BulkRemoveStudentsTest.php`:

1. Authenticated teacher can bulk-remove selected students from a class → pivot rows for those students are gone; students not selected remain enrolled.
2. Bulk-removing a student does **not** delete the `Student` record and does **not** affect their enrollment in a *different* class (seed a student in two classes, bulk-remove from one, assert still enrolled in the other).
3. Bulk-removing does **not** delete `attendance_records` tied to the student.
4. Validation: submitting with no `student_ids` (or an empty array) fails validation and the class page is unchanged.
5. Unauthenticated request is redirected to login (route sits behind `auth` middleware, consistent with the rest of the group).
6. An id that doesn't correspond to any `Student` row fails validation (`exists:students,id`). An id that *does* belong to a real student but one not enrolled in this class passes validation and is silently ignored by `detach()` (no error, count simply doesn't include it).

## Out of scope / explicit non-goals

- No "select all across pages" concept (no pagination exists).
- No bulk permanent-delete action.
- No undo — this mirrors the existing single "Remove" action's lack of undo.

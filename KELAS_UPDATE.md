# SPARK Class Management Update

Completed: 5 October 2026.

## Result

- Tambah Pengguna uses an Alpine role toggle. Guru hides, clears and disables the academic-year and class fields on initial load and role changes, including validation returns. Backend validation prohibits these fields for Guru and requires both for Pelajar. Guru accounts remain active immediately.
- Admin and public student registration both use the same Kelas database model, ordered by nama. Submitted kelas_id values must exist.
- Urus Kelas is available at /guru/kelas and in the teacher sidebar. Active teachers can add trimmed, unique class names and remove unused classes. Assigned classes cannot be removed, including classes used by pending or historical students. Bahasa Melayu success and error messages explain the result.
- Student class storage now uses users.kelas_id with a restrictive foreign key to kelas.id. Reports, dashboards, password-reset details and the user list display the related class name. Reports and the user list filter by kelas_id.
- The initial class list contains exactly Ibnu Batutta, Ibnu Haitham, Ibnu Khaldun, Ibnu Qaiyim and Ibnu Sina. Names do not include a year prefix.

## Migrations and Seeder

These commands were already run successfully on your local database:

```powershell
cd "C:\Users\User\Documents\FYP 2\spark"
php artisan migrate --no-interaction
php artisan db:seed --class=KelasSeeder --no-interaction
php artisan view:clear
npm run build
```

The new migrations run in this order:

1. 2026_10_05_051114_create_kelas_table_and_add_kelas_id_to_users
2. 2026_10_05_051115_backfill_student_kelas
3. 2026_10_05_051116_replace_users_class_name_with_kelas_id

The backfill creates the five initial class records before mapping existing students. KelasSeeder is idempotent and adds only these five initial names without deleting custom classes.

The local migration mapped:
- 6 Ibnu Sina -> Ibnu Sina
- 6 Ibnu Batutta -> Ibnu Batutta

No existing class name was unmatched. All 5 users, 15 quiz attempts and 101 quiz answers were preserved. The original assignment backup is [student-classes-before-kelas-20261005-052140.json](<C:/Users/User/Documents/FYP 2/spark/storage/app/private/backups/student-classes-before-kelas-20261005-052140.json>).

For other databases, unfamiliar nonempty class names are preserved as additional class records and logged in storage/logs/laravel.log. Empty legacy class names stop migration before the old column is dropped. Schema rollback restores class names from the related kelas record; use the backup if original prefixes or spelling are needed.

## Manual Checks

1. Open /guru/pengguna/tambah. Select Pelajar and choose an academic year and class. Switch to Guru: both student fields must disappear. Switch back: both should be empty. Create a Guru account and verify its status is Aktif and it has no class.
2. Create a Pelajar account with a class from the dropdown. Verify the class in Urus Pengguna. Current-year students become Aktif; upcoming-year students become Diluluskan.
3. In a separate signed-out browser session, open /daftar and register a student with a dropdown class. Registration requires an upcoming academic year and creates a Menunggu account.
4. Open Urus Kelas. Add a temporary class with a name only. Verify it appears in both registration forms and report filters. Delete the unused class and verify the success message.
5. Attempt to delete Ibnu Sina or Ibnu Batutta, which currently have assigned students. Expect the Bahasa Melayu error message and no deletion.
6. Filter Urus Pengguna, Pantau Kemajuan and Analitik Kelas by class. Check historical academic-year reports as well.
7. Submit an incomplete Guru form and verify the role remains Guru and student fields remain hidden after validation.

## Verification

- Full automated suite: 250 passed, 10 skipped, 1,290 assertions.
- PHPStan: zero errors.
- Laravel Pint: changed PHP files formatted.
- Blade compilation and npm production build passed.
- Browser checks confirmed role switching, field clearing/disabling, validation-return state, the class-management layout, and the live public registration dropdown.
- Temporary browser preview files were removed. No demonstration accounts were added to the live database.

## Changed Files

- [app/Models/Kelas.php](<C:/Users/User/Documents/FYP 2/spark/app/Models/Kelas.php>)
- [app/Models/User.php](<C:/Users/User/Documents/FYP 2/spark/app/Models/User.php>)
- [app/Actions/Auth/RegisterStudent.php](<C:/Users/User/Documents/FYP 2/spark/app/Actions/Auth/RegisterStudent.php>)
- [app/Actions/Teacher/CreateUserAccount.php](<C:/Users/User/Documents/FYP 2/spark/app/Actions/Teacher/CreateUserAccount.php>)
- [app/Actions/Teacher/BuildProgressReports.php](<C:/Users/User/Documents/FYP 2/spark/app/Actions/Teacher/BuildProgressReports.php>)
- [app/Http/Controllers/Auth/RegisterStudentController.php](<C:/Users/User/Documents/FYP 2/spark/app/Http/Controllers/Auth/RegisterStudentController.php>)
- [app/Http/Controllers/Teacher/UserAccountManagementController.php](<C:/Users/User/Documents/FYP 2/spark/app/Http/Controllers/Teacher/UserAccountManagementController.php>)
- [app/Http/Controllers/Teacher/ProgressReportController.php](<C:/Users/User/Documents/FYP 2/spark/app/Http/Controllers/Teacher/ProgressReportController.php>)
- [app/Http/Controllers/Teacher/KelasController.php](<C:/Users/User/Documents/FYP 2/spark/app/Http/Controllers/Teacher/KelasController.php>)
- [app/Http/Requests/Auth/StoreStudentRegistrationRequest.php](<C:/Users/User/Documents/FYP 2/spark/app/Http/Requests/Auth/StoreStudentRegistrationRequest.php>)
- [app/Http/Requests/Teacher/StoreUserAccountRequest.php](<C:/Users/User/Documents/FYP 2/spark/app/Http/Requests/Teacher/StoreUserAccountRequest.php>)
- [app/Http/Requests/Teacher/FilterUserAccountsRequest.php](<C:/Users/User/Documents/FYP 2/spark/app/Http/Requests/Teacher/FilterUserAccountsRequest.php>)
- [app/Http/Requests/Teacher/FilterProgressReportsRequest.php](<C:/Users/User/Documents/FYP 2/spark/app/Http/Requests/Teacher/FilterProgressReportsRequest.php>)
- [app/Http/Requests/Teacher/StoreKelasRequest.php](<C:/Users/User/Documents/FYP 2/spark/app/Http/Requests/Teacher/StoreKelasRequest.php>)
- [database/factories/KelasFactory.php](<C:/Users/User/Documents/FYP 2/spark/database/factories/KelasFactory.php>)
- [database/factories/UserFactory.php](<C:/Users/User/Documents/FYP 2/spark/database/factories/UserFactory.php>)
- [database/seeders/KelasSeeder.php](<C:/Users/User/Documents/FYP 2/spark/database/seeders/KelasSeeder.php>)
- [database/seeders/DatabaseSeeder.php](<C:/Users/User/Documents/FYP 2/spark/database/seeders/DatabaseSeeder.php>)
- [database/migrations/2026_10_05_051114_create_kelas_table_and_add_kelas_id_to_users.php](<C:/Users/User/Documents/FYP 2/spark/database/migrations/2026_10_05_051114_create_kelas_table_and_add_kelas_id_to_users.php>)
- [database/migrations/2026_10_05_051115_backfill_student_kelas.php](<C:/Users/User/Documents/FYP 2/spark/database/migrations/2026_10_05_051115_backfill_student_kelas.php>)
- [database/migrations/2026_10_05_051116_replace_users_class_name_with_kelas_id.php](<C:/Users/User/Documents/FYP 2/spark/database/migrations/2026_10_05_051116_replace_users_class_name_with_kelas_id.php>)
- [routes/web.php](<C:/Users/User/Documents/FYP 2/spark/routes/web.php>)
- [lang/ms/validation.php](<C:/Users/User/Documents/FYP 2/spark/lang/ms/validation.php>)
- [resources/views/teacher/kelas/index.blade.php](<C:/Users/User/Documents/FYP 2/spark/resources/views/teacher/kelas/index.blade.php>)
- [resources/views/teacher/users/create.blade.php](<C:/Users/User/Documents/FYP 2/spark/resources/views/teacher/users/create.blade.php>)
- [resources/views/teacher/users/index.blade.php](<C:/Users/User/Documents/FYP 2/spark/resources/views/teacher/users/index.blade.php>)
- [resources/views/teacher/users/reset-password.blade.php](<C:/Users/User/Documents/FYP 2/spark/resources/views/teacher/users/reset-password.blade.php>)
- [resources/views/livewire/auth/register.blade.php](<C:/Users/User/Documents/FYP 2/spark/resources/views/livewire/auth/register.blade.php>)
- [resources/views/layouts/app/sidebar.blade.php](<C:/Users/User/Documents/FYP 2/spark/resources/views/layouts/app/sidebar.blade.php>)
- [resources/views/dashboards/teacher.blade.php](<C:/Users/User/Documents/FYP 2/spark/resources/views/dashboards/teacher.blade.php>)
- [resources/views/teacher/reports/index.blade.php](<C:/Users/User/Documents/FYP 2/spark/resources/views/teacher/reports/index.blade.php>)
- [resources/views/teacher/reports/analytics.blade.php](<C:/Users/User/Documents/FYP 2/spark/resources/views/teacher/reports/analytics.blade.php>)
- [resources/views/teacher/reports/show.blade.php](<C:/Users/User/Documents/FYP 2/spark/resources/views/teacher/reports/show.blade.php>)
- [resources/views/components/report-filters.blade.php](<C:/Users/User/Documents/FYP 2/spark/resources/views/components/report-filters.blade.php>)
- [resources/views/components/report-students.blade.php](<C:/Users/User/Documents/FYP 2/spark/resources/views/components/report-students.blade.php>)
- [tests/Feature/KelasManagementTest.php](<C:/Users/User/Documents/FYP 2/spark/tests/Feature/KelasManagementTest.php>)
- [tests/Feature/UserAccountManagementTest.php](<C:/Users/User/Documents/FYP 2/spark/tests/Feature/UserAccountManagementTest.php>)
- [tests/Feature/TeacherProgressReportTest.php](<C:/Users/User/Documents/FYP 2/spark/tests/Feature/TeacherProgressReportTest.php>)
- [tests/Feature/StudentRegistrationTest.php](<C:/Users/User/Documents/FYP 2/spark/tests/Feature/StudentRegistrationTest.php>)
- [tests/Feature/Auth/RegistrationTest.php](<C:/Users/User/Documents/FYP 2/spark/tests/Feature/Auth/RegistrationTest.php>)
- [tests/Feature/Database/DatabaseFoundationTest.php](<C:/Users/User/Documents/FYP 2/spark/tests/Feature/Database/DatabaseFoundationTest.php>)

This implementation note is saved as KELAS_UPDATE.md.

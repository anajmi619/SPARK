<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\QuizAttempt;
use App\Models\User;
use Database\Seeders\KelasSeeder;
use Database\Seeders\LevelSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->withSession(['auth.password_confirmed_at' => time()]);
});

test('initial class seeding is exact and idempotent', function () {
    $this->seed(KelasSeeder::class);
    $this->seed(KelasSeeder::class);
    expect(Kelas::query()->orderBy('nama')->pluck('nama')->all())->toBe([
        'Ibnu Batutta', 'Ibnu Haitham', 'Ibnu Khaldun', 'Ibnu Qaiyim', 'Ibnu Sina',
    ]);
});

test('both registration forms read the same database class list', function () {
    $kelas = Kelas::factory()->create(['nama' => 'Kelas Baharu']);
    $this->get(route('register'))->assertOk()
        ->assertSee('name="kelas_id"', false)->assertDontSee('name="class_name"', false)
        ->assertSee('-- Pilih Kelas --')->assertSee($kelas->nama)
        ->assertViewHas('classes', fn ($classes) => $classes->pluck('id')->all() === Kelas::query()->orderBy('nama')->pluck('id')->all());
    $this->actingAs(User::factory()->create())->get(route('teacher.users.create'))->assertOk()
        ->assertSee('name="kelas_id"', false)->assertDontSee('name="class_name"', false)
        ->assertSee($kelas->nama)
        ->assertViewHas('classes', fn ($classes) => $classes->pluck('id')->all() === Kelas::query()->orderBy('nama')->pluck('id')->all());
});

test('teacher validation rerender keeps student fields hidden and disabled', function () {
    $teacher = User::factory()->create();
    $this->actingAs($teacher)->from(route('teacher.users.create'))->post(route('teacher.users.store'), [
        'role' => UserRole::Teacher->value, 'username' => 'guru_baru',
    ])->assertSessionHasErrors('full_name');
    $this->get(route('teacher.users.create'))->assertOk()
        ->assertSee('style="display: none"', false)
        ->assertSee('disabled', false)
        ->assertSee('Akaun Guru akan menjadi Aktif serta-merta dan hanya boleh melihat kelas tugasannya.');
});

test('student fields are prohibited for teachers and required for students', function () {
    $year = AcademicYear::factory()->active()->create();
    $kelas = Kelas::query()->firstOrFail();
    $this->actingAs(User::factory()->create())->post(route('teacher.users.store'), [
        'role' => 'teacher', 'full_name' => 'Guru Baru', 'username' => 'guru_baru',
        'password' => 'password', 'password_confirmation' => 'password',
        'academic_year_id' => $year->academic_year_id, 'kelas_id' => $kelas->id,
    ])->assertSessionHasErrors(['academic_year_id', 'kelas_id']);
    $this->post(route('teacher.users.store'), [
        'role' => 'student', 'full_name' => 'Pelajar Baru', 'username' => 'pelajar_baru',
        'password' => 'password', 'password_confirmation' => 'password',
    ])->assertSessionHasErrors(['academic_year_id', 'kelas_id']);
});

test('both forms reject invented or malformed class ids', function ($invalid) {
    $this->seed(LevelSeeder::class);
    $year = AcademicYear::factory()->create();
    $data = [
        'full_name' => 'Pelajar Baru', 'username' => 'pelajar_baru',
        'password' => 'password', 'password_confirmation' => 'password', 'kelas_id' => $invalid,
    ];
    $this->post(route('register.store'), $data)->assertSessionHasErrors('kelas_id');
    $this->actingAs(User::factory()->create())->post(route('teacher.users.store'), $data + [
        'role' => 'student', 'academic_year_id' => $year->academic_year_id,
    ])->assertSessionHasErrors('kelas_id');
    expect(User::query()->where('username', 'pelajar_baru')->exists())->toBeFalse();
})->with([999999, 'Ibnu Sina', [['nama' => 'Ibnu Sina']]]);

test('teachers can add trimmed classes and delete unused classes', function () {
    $this->actingAs(User::factory()->create())->post(route('teacher.kelas.store'), ['nama' => '  Ibnu   Rushd  '])
        ->assertRedirect(route('teacher.kelas.index'))->assertSessionHas('status', 'Kelas berjaya ditambah.');
    $kelas = Kelas::query()->where('nama', 'Ibnu Rushd')->sole();
    $this->get(route('teacher.kelas.index'))->assertOk()->assertSee('Ibnu Rushd')->assertSee('0');
    $this->post(route('teacher.kelas.store'), ['nama' => ' Ibnu Rushd '])->assertSessionHasErrors('nama');
    $this->delete(route('teacher.kelas.destroy', $kelas))->assertRedirect(route('teacher.kelas.index'))
        ->assertSessionHas('status', 'Kelas berjaya dipadam.');
    $this->assertModelMissing($kelas);
});

test('invalid class names are rejected', function ($nama) {
    $this->actingAs(User::factory()->create())->post(route('teacher.kelas.store'), ['nama' => $nama])
        ->assertSessionHasErrors('nama');
})->with(['   ', '6 Ibnu Sina', 'Tahun 6 Ibnu Sina', str_repeat('x', 51), [['invalid']]]);

test('assigned classes cannot be deleted regardless of student status', function (UserStatus $status) {
    $student = User::factory()->student()->create(['status' => $status]);
    $kelas = $student->kelas;
    $this->actingAs(User::factory()->create())->delete(route('teacher.kelas.destroy', $kelas))
        ->assertRedirect(route('teacher.kelas.index'))
        ->assertSessionHas('error', 'Kelas tidak boleh dipadam kerana masih mempunyai pelajar atau guru yang ditugaskan.');
    $this->assertModelExists($kelas);
    expect($student->fresh()->kelas_id)->toBe($kelas->id);
})->with(UserStatus::cases());

test('database foreign key protects assigned classes from direct deletion', function () {
    $student = User::factory()->student()->create();
    expect(fn () => $student->kelas->delete())->toThrow(QueryException::class);
});

test('class management requires an active teacher for every route', function () {
    $kelas = Kelas::query()->firstOrFail();
    $this->get(route('teacher.kelas.index'))->assertRedirect(route('login'));
    $this->post(route('teacher.kelas.store'), ['nama' => 'Ujian'])->assertRedirect(route('login'));
    $this->delete(route('teacher.kelas.destroy', $kelas))->assertRedirect(route('login'));
    $year = AcademicYear::factory()->active()->create();
    $student = User::factory()->student()->active()->create(['academic_year_id' => $year->academic_year_id]);
    $this->actingAs($student)->get(route('teacher.kelas.index'))->assertForbidden();
    $this->post(route('teacher.kelas.store'), ['nama' => 'Ujian'])->assertForbidden();
    $this->delete(route('teacher.kelas.destroy', $kelas))->assertForbidden();
    $teacher = User::factory()->create(['status' => UserStatus::Inactive]);
    $this->actingAs($teacher)->get(route('teacher.kelas.index'))->assertRedirect(route('login'));
});

test('user list filters by the class relationship', function () {
    $chosen = User::factory()->student()->create(['full_name' => 'Pelajar Pilihan']);
    User::factory()->student()->create(['full_name' => 'Pelajar Lain', 'current_level_id' => $chosen->current_level_id]);
    $this->actingAs(User::factory()->create())->get(route('teacher.users.index', ['kelas_id' => $chosen->kelas_id]))
        ->assertOk()->assertSee('Pelajar Pilihan')->assertDontSee('Pelajar Lain')->assertSee($chosen->kelas->nama);
});

test('legacy migration normalizes classes and preserves unmatched names and quiz records', function () {
    $student = User::factory()->student()->create();
    $other = User::factory()->student()->create(['current_level_id' => $student->current_level_id]);
    $attempt = QuizAttempt::factory()->completed(8)->create(['student_id' => $student->user_id]);
    $before = $attempt->fresh()->getAttributes();
    $schema = require database_path('migrations/2026_10_05_051116_replace_users_class_name_with_kelas_id.php');
    $backfill = require database_path('migrations/2026_10_05_051115_backfill_student_kelas.php');
    $schema->down();
    DB::table('users')->where('user_id', $student->user_id)->update(['class_name' => '  6   ibnu sina  ', 'kelas_id' => null]);
    DB::table('users')->where('user_id', $other->user_id)->update(['class_name' => '6 Kelas Warisan', 'kelas_id' => null]);
    Log::spy();
    $backfill->up();
    $schema->up();
    expect($student->fresh()->kelas->nama)->toBe('Ibnu Sina')
        ->and($other->fresh()->kelas->nama)->toBe('Kelas Warisan')
        ->and($attempt->fresh()->getAttributes())->toBe($before)
        ->and(Schema::hasColumn('users', 'class_name'))->toBeFalse();
    Log::shouldHaveReceived('warning')->once();
});

test('legacy migration stops on empty classes without discarding student records', function () {
    $student = User::factory()->student()->create();
    $schema = require database_path('migrations/2026_10_05_051116_replace_users_class_name_with_kelas_id.php');
    $backfill = require database_path('migrations/2026_10_05_051115_backfill_student_kelas.php');
    $schema->down();
    DB::table('users')->where('user_id', $student->user_id)->update(['class_name' => ' ', 'kelas_id' => null]);
    expect(fn () => $backfill->up())->toThrow(RuntimeException::class);
    expect(Schema::hasColumn('users', 'class_name'))->toBeTrue();
    $this->assertModelExists($student);
});

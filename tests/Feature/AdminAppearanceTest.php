<?php

use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\LevelSeeder;

test('staff pages share the SPARK admin shell', function (string $routeName) {
    AcademicYear::factory()->active()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
        ->get(route($routeName))->assertOk()
        ->assertSee('spark-admin', false)->assertSee('admin-topbar', false)
        ->assertSee('Pemantauan')->assertSee('Pengurusan')->assertSee('Sistem')
        ->assertSee('Pentadbir')->assertSee('Jejak navigasi')
        ->assertSee('Urus Pengguna')->assertSee('Urus Kelas')->assertSee('Tahun Akademik');
})->with([
    'teacher.dashboard', 'teacher.topics.index', 'teacher.topics.create',
    'teacher.users.index', 'teacher.users.create', 'teacher.kelas.index',
    'teacher.academic-years.index', 'teacher.progress.index', 'teacher.analytics.index',
]);

test('teachers keep only permitted navigation in the new shell', function () {
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertOk()
        ->assertSee('spark-admin', false)->assertSee('Urus Modul Pembelajaran')
        ->assertDontSee('Urus Pengguna')->assertDontSee('Urus Kelas')
        ->assertDontSee(route('teacher.academic-years.index'), false)
        ->assertDontSee('Penggunaan Maklum Balas AI Hari Ini');
});

test('dashboard metric tiles retain all six report values', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('teacher.dashboard'))->assertOk()
        ->assertSee('Ringkasan Prestasi')->assertSee('Jumlah Pelajar')
        ->assertSee('Pelajar Aktif')->assertSee('Pelajar Memerlukan Sokongan')
        ->assertSee('Belum Selesai Sebarang Kuiz')->assertSee('Purata Skor Terbaik')
        ->assertSee('Kuiz Topik Selesai (12 Topik)')->assertSee('Tiada Keputusan');
});

test('question management keeps the module sidebar item selected', function () {
    $topic = Topic::factory()->create();
    $response = $this->actingAs(User::factory()->admin()->create())
        ->get(route('teacher.questions.index', $topic))->assertOk();

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//a[@data-flux-sidebar-item and @data-current and @href="'.route('teacher.topics.index').'"]')->length)->toBe(1);
});

test('student dashboard does not receive the admin theme', function () {
    $this->seed(LevelSeeder::class);
    $year = AcademicYear::factory()->active()->create();
    $student = User::factory()->student()->active()->create([
        'academic_year_id' => $year->academic_year_id,
        'current_level_id' => Level::query()->where('level_number', 1)->sole()->level_id,
    ]);

    $this->actingAs($student)->get(route('student.dashboard'))->assertOk()
        ->assertSee('spark-ui', false)->assertDontSee('spark-admin', false)
        ->assertDontSee('admin-sidebar', false);
});

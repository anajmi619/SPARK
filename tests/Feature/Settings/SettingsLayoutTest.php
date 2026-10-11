<?php

use App\Enums\TopicStatus;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\LevelSeeder;

test('student settings use the student shell and retain settings navigation', function (string $page) {
    $student = User::factory()->student()->active()->create([
        'academic_year_id' => AcademicYear::factory()->active()->create()->academic_year_id,
    ]);

    $this->actingAs($student)->get(route($page.'.edit'))
        ->assertOk()
        ->assertSee('spark-settings-page', false)
        ->assertSee('Navigasi pelajar')
        ->assertSee(route('student.dashboard'), false)
        ->assertSee(route('profile.edit'), false)
        ->assertSee(route('security.edit'), false)
        ->assertSee(route('appearance.edit'), false)
        ->assertSee('data-flux-toast', false)
        ->assertSee("window.Flux.applyAppearance(window.localStorage.getItem('flux.appearance') || 'system')", false)
        ->assertDontSee('Urus Pengguna');
})->with(['profile', 'security', 'appearance']);

test('student dashboard and learning pages load the saved appearance', function () {
    $this->seed(LevelSeeder::class);
    $student = User::factory()->student()->active()->create([
        'academic_year_id' => AcademicYear::factory()->active()->create()->academic_year_id,
        'current_level_id' => Level::query()->where('level_number', 1)->sole()->level_id,
    ]);
    $topic = Topic::factory()->create(['status' => TopicStatus::Published]);

    foreach ([route('student.dashboard'), route('student.learning.show', $topic)] as $url) {
        $this->actingAs($student)->get($url)->assertOk()
            ->assertSee("window.Flux.applyAppearance(window.localStorage.getItem('flux.appearance') || 'system')", false);
    }
});

test('teacher settings retain the teacher shell', function (string $page) {
    $this->actingAs(User::factory()->create())->get(route($page.'.edit'))
        ->assertOk()
        ->assertSee('Urus Pengguna')
        ->assertDontSee('spark-settings-page', false)
        ->assertDontSee('Navigasi pelajar');
})->with(['profile', 'security', 'appearance']);

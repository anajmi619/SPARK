<?php

namespace Database\Seeders;

use App\Actions\Student\CalculateStudentProgress;
use App\Enums\AcademicYearStatus;
use App\Enums\AiFeedbackStatus;
use App\Enums\AnswerOption;
use App\Enums\AttemptStatus;
use App\Enums\QuestionStatus;
use App\Enums\TopicStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Level;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Topic;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SampleStudentSeeder extends Seeder
{
    /** @var list<string> */
    private const CLASS_NAMES = [
        'Ibnu Batutta',
        'Ibnu Haitham',
        'Ibnu Khaldun',
        'Ibnu Qaiyim',
        'Ibnu Sina',
    ];

    /** @var list<string> */
    private const STUDENT_NAMES = [
        'Aina Sofea', 'Danish Hakim', 'Nur Alya', 'Irfan Zikri',
        'Siti Hawa', 'Adam Rayyan', 'Nur Iman', 'Amir Danish',
        'Qaisara Humaira', 'Harith Iskandar', 'Zara Aleesya', 'Aqil Firdaus',
        'Nurin Balqis', 'Imran Haziq', 'Aisyah Humaira', 'Faris Izzuddin',
        'Sara Qistina', 'Arif Haikal', 'Maryam Batrisyia', 'Luqman Hakim',
    ];

    public function run(): void
    {
        $password = (string) config('demo.sample_student_password');

        if (mb_strlen($password) < 8) {
            throw new RuntimeException('Tetapkan SAMPLE_STUDENT_PASSWORD sekurang-kurangnya 8 aksara sebelum menjalankan data pelajar contoh.');
        }

        $activeYear = AcademicYear::query()
            ->where('status', AcademicYearStatus::Active->value)
            ->first();

        if ($activeYear === null) {
            throw new RuntimeException('Tahun akademik aktif diperlukan sebelum data pelajar contoh boleh dicipta.');
        }

        $levelOne = Level::query()->where('level_number', 1)->first();
        if ($levelOne === null || Level::query()->whereIn('level_number', range(1, 7))->count() !== 7) {
            throw new RuntimeException('Semua tujuh tahap pelajar mesti tersedia sebelum data pelajar contoh boleh dicipta.');
        }

        $classes = Kelas::query()
            ->whereIn('nama', self::CLASS_NAMES)
            ->get()
            ->keyBy('nama');

        if ($classes->count() !== count(self::CLASS_NAMES)) {
            throw new RuntimeException('Lima kelas SPARK mesti tersedia sebelum data pelajar contoh boleh dicipta.');
        }

        $topics = Topic::query()
            ->whereIn('topic_order', [1, 2, 3, 4])
            ->where('status', TopicStatus::Published->value)
            ->with(['questions' => fn ($query) => $query
                ->where('status', QuestionStatus::Active->value)
                ->orderBy('question_order')])
            ->orderBy('topic_order')
            ->get()
            ->keyBy('topic_order');

        if ($topics->count() !== 4 || $topics->contains(fn (Topic $topic): bool => $topic->questions->count() !== 10)) {
            throw new RuntimeException('Topik 1 hingga 4 mesti diterbitkan dan mempunyai tepat 10 soalan aktif setiap satu.');
        }

        DB::transaction(function () use ($activeYear, $classes, $levelOne, $password, $topics): void {
            $progressCalculator = app(CalculateStudentProgress::class);
            $profiles = $this->profiles();
            $cohortStart = CarbonImmutable::create(
                $activeYear->year,
                7,
                1,
                9,
                0,
                0,
                'Asia/Kuala_Lumpur',
            )->utc();

            foreach (self::STUDENT_NAMES as $index => $fullName) {
                $studentNumber = $index + 1;
                $className = self::CLASS_NAMES[intdiv($index, 4)];
                $profile = $profiles[$index % 4];
                $student = User::query()->updateOrCreate(
                    ['username' => sprintf('pelajar_demo%02d', $studentNumber)],
                    [
                        'academic_year_id' => $activeYear->academic_year_id,
                        'current_level_id' => $levelOne->level_id,
                        'full_name' => $fullName,
                        'password_hash' => $password,
                        'role' => UserRole::Student,
                        'kelas_id' => $classes->get($className)->id,
                        'status' => UserStatus::Active,
                        'recovery_requested_at' => null,
                    ],
                );

                $this->seedAttempts($student, $studentNumber, $profile, $topics, $cohortStart);
                $progressCalculator->synchronize($student);
            }
        });

        $this->command->info('20 akaun pelajar contoh berjaya disediakan.');
        $this->command->line('Nama pengguna: pelajar_demo01 hingga pelajar_demo20');
        $this->command->line('Kata laluan menggunakan nilai SAMPLE_STUDENT_PASSWORD daripada fail .env tempatan.');
    }

    /**
     * @return list<array{label: string, attempts: array<int, list<int>>}>
     */
    private function profiles(): array
    {
        return [
            ['label' => 'cemerlang', 'attempts' => [1 => [10], 2 => [7, 9], 3 => [10], 4 => [9]]],
            ['label' => 'baik', 'attempts' => [1 => [6, 8], 2 => [7], 3 => [8], 4 => [7]]],
            ['label' => 'berkembang', 'attempts' => [1 => [7], 2 => [2, 4], 3 => [6]]],
            ['label' => 'perlukan_sokongan', 'attempts' => [1 => [2, 4], 2 => [2]]],
        ];
    }

    /**
     * @param  array{label: string, attempts: array<int, list<int>>}  $profile
     * @param  Collection<int, Topic>  $topics
     */
    private function seedAttempts(
        User $student,
        int $studentNumber,
        array $profile,
        Collection $topics,
        CarbonImmutable $cohortStart,
    ): void {
        foreach ($profile['attempts'] as $topicOrder => $scores) {
            $topic = $topics->get($topicOrder);

            foreach ($scores as $attemptIndex => $score) {
                $attemptNumber = $attemptIndex + 1;
                $startedAt = $cohortStart->addDays(
                    (($studentNumber - 1) * 2) + (($topicOrder - 1) * 3) + ($attemptIndex * 2),
                );
                $attempt = QuizAttempt::query()->updateOrCreate(
                    [
                        'student_id' => $student->user_id,
                        'topic_id' => $topic->topic_id,
                        'attempt_number' => $attemptNumber,
                    ],
                    [
                        'score' => $score,
                        'status' => AttemptStatus::Completed,
                        'started_at' => $startedAt,
                        'completed_at' => $startedAt->addMinutes(20),
                    ],
                );

                foreach ($topic->questions as $question) {
                    $isCorrect = $this->isCorrect(
                        $question->question_order,
                        $score,
                        $studentNumber,
                        $topicOrder,
                        $attemptNumber,
                    );
                    $selectedOption = $isCorrect
                        ? $question->correct_option
                        : $this->wrongOption($question->correct_option);

                    QuizAnswer::query()->updateOrCreate(
                        [
                            'attempt_id' => $attempt->attempt_id,
                            'question_id' => $question->question_id,
                        ],
                        [
                            'selected_option' => $selectedOption,
                            'is_correct' => $isCorrect,
                            'question_snapshot' => $question->question_text,
                            'selected_answer_snapshot' => $this->optionText($question, $selectedOption),
                            'correct_answer_snapshot' => $this->optionText($question, $question->correct_option),
                            'explanation_snapshot' => $question->explanation,
                            'presentation_snapshot' => $this->presentationSnapshot($question),
                            'ai_feedback_text' => null,
                            'ai_feedback_status' => $isCorrect
                                ? AiFeedbackStatus::NotRequired
                                : AiFeedbackStatus::Failed,
                            'ai_feedback_source' => null,
                            'answered_at' => $startedAt->addMinutes($question->question_order + 1),
                        ],
                    );
                }
            }
        }
    }

    private function isCorrect(
        int $questionOrder,
        int $score,
        int $studentNumber,
        int $topicOrder,
        int $attemptNumber,
    ): bool {
        $rotatedPosition = (($questionOrder + $studentNumber + $topicOrder + $attemptNumber - 4) % 10) + 1;

        return $rotatedPosition <= $score;
    }

    private function wrongOption(AnswerOption $correctOption): AnswerOption
    {
        return match ($correctOption) {
            AnswerOption::A => AnswerOption::B,
            AnswerOption::B, AnswerOption::C, AnswerOption::D => AnswerOption::A,
        };
    }

    private function optionText(QuizQuestion $question, AnswerOption $option): string
    {
        return (string) $question->getAttribute('option_'.strtolower($option->value));
    }

    /** @return array{image: string|null, options: array<string, array{text: string, image: string|null}>, correct: string} */
    private function presentationSnapshot(QuizQuestion $question): array
    {
        $options = [];
        foreach (AnswerOption::cases() as $option) {
            $key = strtolower($option->value);
            $options[$option->value] = [
                'text' => (string) $question->getAttribute('option_'.$key),
                'image' => $question->getAttribute('option_'.$key.'_image_path'),
            ];
        }

        return [
            'image' => $question->image_path,
            'options' => $options,
            'correct' => $question->correct_option->value,
        ];
    }
}

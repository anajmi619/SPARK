<?php

namespace Database\Seeders;

use App\Enums\AnswerOption;
use App\Enums\QuestionStatus;
use App\Enums\TopicStatus;
use App\Models\QuizQuestion;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class HumanTopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sourceImage = database_path('seeders/assets/sains-tahun-6-unit-2.jpg');
        $imagePath = 'topics/images/sains-tahun-6-unit-2.jpg';

        if (! File::exists($sourceImage)) {
            throw new RuntimeException('Fail nota Manusia tidak ditemui.');
        }

        Storage::disk('public')->put($imagePath, File::get($sourceImage));

        DB::transaction(function () use ($imagePath): void {
            $topic = Topic::query()->updateOrCreate(
                ['topic_order' => 2],
                [
                    'topic_name' => 'Manusia',
                    'content_text' => implode("\n\n", [
                        'Pembiakan manusia ialah proses menghasilkan zuriat untuk memastikan kesinambungan hidup manusia. Organ pembiakan lelaki menghasilkan sperma, manakala organ pembiakan perempuan menghasilkan ovum dan menyediakan tempat untuk perkembangan bayi.',
                        'Persenyawaan berlaku apabila sperma bercantum dengan ovum. Zigot yang terbentuk akan berkembang menjadi embrio dan seterusnya janin di dalam rahim sebelum bayi dilahirkan selepas kira-kira sembilan bulan.',
                        'Sistem saraf manusia mengawal dan menyelaraskan aktiviti badan. Sistem saraf pusat terdiri daripada otak dan saraf tunjang, manakala sistem saraf periferi menghubungkan sistem saraf pusat dengan seluruh badan. Sistem saraf perlu dijaga melalui pemakanan sihat, senaman, rehat yang cukup serta menjauhi dadah dan alkohol.',
                    ]),
                    'image_path' => $imagePath,
                    'status' => TopicStatus::Published,
                ],
            );

            foreach ($this->questions() as $index => $question) {
                QuizQuestion::query()->updateOrCreate(
                    ['topic_id' => $topic->topic_id, 'question_order' => $index + 1],
                    [
                        'question_text' => $question['question'],
                        'option_a' => $question['options']['A'],
                        'option_b' => $question['options']['B'],
                        'option_c' => $question['options']['C'],
                        'option_d' => $question['options']['D'],
                        'correct_option' => $question['answer'],
                        'curriculum_context' => $question['explanation'],
                        'explanation' => $question['explanation'],
                        'status' => QuestionStatus::Active,
                    ],
                );
            }
        });
    }

    /**
     * @return list<array{question: string, options: array{A: string, B: string, C: string, D: string}, answer: AnswerOption, explanation: string}>
     */
    private function questions(): array
    {
        return [
            [
                'question' => 'Apakah maksud pembiakan manusia?',
                'options' => [
                    'A' => 'Proses mengawal pergerakan badan',
                    'B' => 'Proses menukarkan makanan kepada tenaga',
                    'C' => 'Proses menghasilkan zuriat untuk mengekalkan keturunan manusia',
                    'D' => 'Proses menghantar maklumat ke otak',
                ],
                'answer' => AnswerOption::C,
                'explanation' => 'Pembiakan manusia ialah proses menghasilkan zuriat supaya keturunan manusia dapat diteruskan.',
            ],
            [
                'question' => 'Organ pembiakan lelaki yang menghasilkan sperma ialah',
                'options' => ['A' => 'Testis', 'B' => 'Rahim', 'C' => 'Ovari', 'D' => 'Faraj'],
                'answer' => AnswerOption::A,
                'explanation' => 'Testis ialah organ pembiakan lelaki yang menghasilkan sperma.',
            ],
            [
                'question' => 'Organ pembiakan perempuan yang menghasilkan ovum ialah',
                'options' => ['A' => 'Zakar', 'B' => 'Ovari', 'C' => 'Skrotum', 'D' => 'Rahim'],
                'answer' => AnswerOption::B,
                'explanation' => 'Ovari menghasilkan ovum, iaitu sel pembiakan perempuan.',
            ],
            [
                'question' => 'Di manakah embrio dan janin berkembang?',
                'options' => ['A' => 'Ovari', 'B' => 'Testis', 'C' => 'Saraf tunjang', 'D' => 'Rahim'],
                'answer' => AnswerOption::D,
                'explanation' => 'Rahim ialah organ yang menjadi tempat embrio dan janin berkembang sehingga bayi dilahirkan.',
            ],
            [
                'question' => 'Apakah yang berlaku apabila sperma bercantum dengan ovum?',
                'options' => ['A' => 'Bayi terus dilahirkan', 'B' => 'Sistem saraf terbentuk serta-merta', 'C' => 'Persenyawaan berlaku dan zigot terbentuk', 'D' => 'Ovum berubah menjadi sperma'],
                'answer' => AnswerOption::C,
                'explanation' => 'Persenyawaan berlaku apabila sperma bercantum dengan ovum dan menghasilkan zigot.',
            ],
            [
                'question' => 'Berapakah anggaran tempoh bayi berada di dalam kandungan?',
                'options' => ['A' => 'Tiga bulan', 'B' => 'Sembilan bulan', 'C' => 'Dua belas bulan', 'D' => 'Dua puluh empat bulan'],
                'answer' => AnswerOption::B,
                'explanation' => 'Tempoh perkembangan bayi di dalam kandungan manusia ialah kira-kira sembilan bulan.',
            ],
            [
                'question' => 'Apakah bahagian utama sistem saraf pusat?',
                'options' => ['A' => 'Otak dan saraf tunjang', 'B' => 'Otak dan saraf periferi', 'C' => 'Jantung dan otak', 'D' => 'Saraf tunjang dan otot'],
                'answer' => AnswerOption::A,
                'explanation' => 'Sistem saraf pusat terdiri daripada otak dan saraf tunjang.',
            ],
            [
                'question' => 'Apakah fungsi sistem saraf periferi?',
                'options' => ['A' => 'Menghasilkan sel pembiakan', 'B' => 'Mengepam darah ke seluruh badan', 'C' => 'Menjadi pusat kawalan utama badan', 'D' => 'Menghantar maklumat ke otak dan dari otak'],
                'answer' => AnswerOption::D,
                'explanation' => 'Sistem saraf periferi menghubungkan sistem saraf pusat dengan seluruh badan serta menghantar maklumat ke otak dan dari otak.',
            ],
            [
                'question' => 'Tangan ditarik dengan cepat apabila tersentuh objek panas. Situasi ini menunjukkan sistem saraf membantu badan untuk',
                'options' => ['A' => 'menghasilkan ovum', 'B' => 'mencerna makanan', 'C' => 'memberi tindak balas terhadap rangsangan', 'D' => 'menghasilkan zuriat'],
                'answer' => AnswerOption::C,
                'explanation' => 'Sistem saraf membolehkan badan mengesan rangsangan dan memberikan tindak balas dengan cepat.',
            ],
            [
                'question' => 'Antara berikut, yang manakah cara menjaga kesihatan sistem saraf?',
                'options' => ['A' => 'Mengurangkan waktu tidur', 'B' => 'Makan makanan sihat dan mendapat rehat yang cukup', 'C' => 'Mengambil dadah untuk kekal aktif', 'D' => 'Mengelakkan semua aktiviti fizikal'],
                'answer' => AnswerOption::B,
                'explanation' => 'Pemakanan sihat, senaman dan rehat yang cukup membantu menjaga sistem saraf. Dadah dan alkohol perlu dielakkan.',
            ],
        ];
    }
}

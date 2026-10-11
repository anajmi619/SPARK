<?php

namespace Database\Seeders;

use App\Enums\AnswerOption;
use App\Enums\QuestionStatus;
use App\Enums\TopicStatus;
use App\Models\QuizQuestion;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LivingInteractionTopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $topic = Topic::query()->updateOrCreate(
                ['topic_order' => 4],
                [
                    'topic_name' => 'Interaksi Antara Hidupan',
                    'content_text' => implode("\n\n", [
                        'Interaksi antara hidupan ialah hubungan yang berlaku antara hidupan dalam sesuatu habitat untuk mendapatkan keperluan asas seperti makanan, air, udara, tempat tinggal dan pasangan. Interaksi boleh berlaku antara hidupan daripada spesies yang sama atau spesies yang berlainan.',
                        'Antara bentuk interaksi ialah persaingan, pemangsaan dan simbiosis. Persaingan berlaku apabila hidupan berebut sumber yang terhad, manakala pemangsaan melibatkan pemangsa memburu dan memakan mangsa. Simbiosis merangkumi mutualisme yang menguntungkan kedua-dua hidupan, komensalisme yang menguntungkan satu hidupan tanpa menjejaskan hidupan lain, dan parasitisme yang menguntungkan parasit tetapi memudaratkan perumah.',
                        'Interaksi antara hidupan membantu mengekalkan keseimbangan ekosistem. Perubahan bilangan sesuatu organisma boleh mempengaruhi sumber makanan dan populasi organisma lain dalam rantai makanan. Oleh itu, habitat dan hidupan perlu dipelihara supaya hubungan dalam ekosistem terus seimbang.',
                    ]),
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
                'question' => 'Apakah maksud interaksi antara hidupan?',
                'options' => [
                    'A' => 'Perubahan cuaca yang berlaku di sesuatu habitat',
                    'B' => 'Hubungan yang berlaku antara hidupan dalam sesuatu habitat',
                    'C' => 'Proses hidupan menghasilkan makanannya sendiri',
                    'D' => 'Pergerakan air dari satu tempat ke tempat lain',
                ],
                'answer' => AnswerOption::B,
                'explanation' => 'Interaksi antara hidupan ialah hubungan yang berlaku antara hidupan yang tinggal dalam sesuatu habitat.',
            ],
            [
                'question' => 'Mengapakah hidupan berinteraksi antara satu sama lain?',
                'options' => [
                    'A' => 'Untuk menukar keadaan cuaca',
                    'B' => 'Untuk menghapuskan semua hidupan lain',
                    'C' => 'Untuk mendapatkan keperluan asas seperti makanan dan tempat tinggal',
                    'D' => 'Untuk menghentikan pertumbuhan tumbuhan',
                ],
                'answer' => AnswerOption::C,
                'explanation' => 'Hidupan berinteraksi untuk memperoleh keperluan asas termasuk makanan, air, udara, tempat tinggal dan pasangan.',
            ],
            [
                'question' => 'Antara hidupan yang manakah interaksi boleh berlaku?',
                'options' => [
                    'A' => 'Hidupan daripada spesies yang sama atau spesies yang berlainan',
                    'B' => 'Haiwan darat sahaja',
                    'C' => 'Tumbuhan berbunga sahaja',
                    'D' => 'Hidupan daripada spesies yang sama sahaja',
                ],
                'answer' => AnswerOption::A,
                'explanation' => 'Interaksi boleh berlaku sesama spesies yang sama dan juga antara spesies yang berlainan.',
            ],
            [
                'question' => 'Bilakah persaingan antara hidupan berlaku?',
                'options' => [
                    'A' => 'Apabila semua sumber tersedia tanpa had',
                    'B' => 'Apabila hidupan tidak memerlukan makanan',
                    'C' => 'Apabila tiada hidupan lain dalam habitat',
                    'D' => 'Apabila hidupan berebut sumber yang terhad',
                ],
                'answer' => AnswerOption::D,
                'explanation' => 'Persaingan berlaku apabila dua atau lebih hidupan memerlukan sumber yang sama tetapi sumber tersebut terhad.',
            ],
            [
                'question' => 'Apakah maksud pemangsaan?',
                'options' => [
                    'A' => 'Kedua-dua hidupan mendapat manfaat',
                    'B' => 'Pemangsa memburu dan memakan mangsa',
                    'C' => 'Hidupan berebut tempat tinggal sahaja',
                    'D' => 'Satu hidupan menumpang tanpa menjejaskan hidupan lain',
                ],
                'answer' => AnswerOption::B,
                'explanation' => 'Pemangsaan ialah interaksi apabila pemangsa memburu dan memakan hidupan lain yang dikenali sebagai mangsa.',
            ],
            [
                'question' => 'Situasi manakah merupakan contoh pemangsaan?',
                'options' => [
                    'A' => 'Dua ekor rusa berebut pasangan',
                    'B' => 'Orkid tumbuh pada batang pokok',
                    'C' => 'Burung helang menangkap dan memakan tikus',
                    'D' => 'Lebah mendapatkan nektar daripada bunga',
                ],
                'answer' => AnswerOption::C,
                'explanation' => 'Burung helang ialah pemangsa yang memburu dan memakan tikus sebagai mangsanya.',
            ],
            [
                'question' => 'Apakah yang berlaku dalam interaksi mutualisme?',
                'options' => [
                    'A' => 'Kedua-dua hidupan mendapat manfaat',
                    'B' => 'Kedua-dua hidupan mengalami kerugian',
                    'C' => 'Satu hidupan mendapat manfaat dan satu lagi dimudaratkan',
                    'D' => 'Satu hidupan mendapat manfaat dan satu lagi tidak terjejas',
                ],
                'answer' => AnswerOption::A,
                'explanation' => 'Mutualisme ialah interaksi simbiosis yang memberikan manfaat kepada kedua-dua hidupan.',
            ],
            [
                'question' => 'Pernyataan manakah menerangkan komensalisme?',
                'options' => [
                    'A' => 'Pemangsa memburu dan memakan mangsa',
                    'B' => 'Kedua-dua hidupan mendapat manfaat',
                    'C' => 'Parasit mendapat manfaat dan perumah dimudaratkan',
                    'D' => 'Satu hidupan mendapat manfaat manakala hidupan lain tidak terjejas',
                ],
                'answer' => AnswerOption::D,
                'explanation' => 'Dalam komensalisme, satu hidupan mendapat manfaat manakala hidupan yang satu lagi tidak mendapat manfaat dan tidak dimudaratkan.',
            ],
            [
                'question' => 'Apakah yang berlaku dalam interaksi parasitisme?',
                'options' => [
                    'A' => 'Kedua-dua hidupan saling membantu',
                    'B' => 'Parasit mendapat manfaat tetapi perumah dimudaratkan',
                    'C' => 'Perumah mendapat manfaat tetapi parasit dimudaratkan',
                    'D' => 'Kedua-dua hidupan tidak terjejas',
                ],
                'answer' => AnswerOption::B,
                'explanation' => 'Parasitisme menguntungkan parasit yang memperoleh keperluannya tetapi menyebabkan kemudaratan kepada perumah.',
            ],
            [
                'question' => 'Mengapakah interaksi antara hidupan penting dalam ekosistem?',
                'options' => [
                    'A' => 'Untuk menghapuskan semua persaingan',
                    'B' => 'Untuk memastikan hanya satu spesies dapat hidup',
                    'C' => 'Untuk membantu mengekalkan keseimbangan ekosistem',
                    'D' => 'Untuk mengurangkan semua sumber makanan',
                ],
                'answer' => AnswerOption::C,
                'explanation' => 'Interaksi menghubungkan populasi dan sumber makanan, lalu membantu mengekalkan keseimbangan sesebuah ekosistem.',
            ],
        ];
    }
}

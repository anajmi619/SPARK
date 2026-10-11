<?php

namespace Database\Seeders;

use App\Enums\AnswerOption;
use App\Enums\QuestionStatus;
use App\Enums\TopicStatus;
use App\Models\QuizQuestion;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MicroorganismTopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $topic = Topic::query()->updateOrCreate(
                ['topic_order' => 3],
                [
                    'topic_name' => 'Mikroorganisma',
                    'content_text' => implode("\n\n", [
                        'Mikroorganisma ialah hidupan seni yang terlalu kecil untuk dilihat dengan mata kasar dan biasanya dapat dilihat menggunakan mikroskop. Antara jenis mikroorganisma ialah bakteria, fungi, protozoa, alga dan virus. Mikroorganisma boleh hidup di udara, air, tanah serta di dalam badan hidupan.',
                        'Mikroorganisma menjalankan proses hidup seperti bernafas, bergerak dan bertumbuh. Pertumbuhannya dipengaruhi oleh air, udara, suhu yang sesuai, nutrien dan keasidan. Contohnya, yis berkembang dengan lebih aktif apabila memperoleh air, nutrien dan suhu yang sesuai.',
                        'Mikroorganisma mempunyai kesan yang berguna dan berbahaya. Mikroorganisma digunakan untuk menghasilkan makanan seperti roti, tapai, tempe dan yogurt serta membantu penguraian bahan organik. Namun, sesetengah mikroorganisma boleh menyebabkan penyakit dan merosakkan makanan. Kebersihan diri, penyediaan makanan yang bersih dan penyimpanan makanan yang betul dapat mengurangkan kesan buruk mikroorganisma.',
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
                'question' => 'Apakah maksud mikroorganisma?',
                'options' => [
                    'A' => 'Hidupan besar yang tinggal di dalam hutan',
                    'B' => 'Hidupan seni yang terlalu kecil untuk dilihat dengan mata kasar',
                    'C' => 'Benda bukan hidup yang hanya terdapat di dalam air',
                    'D' => 'Haiwan kecil yang boleh dilihat dengan jelas',
                ],
                'answer' => AnswerOption::B,
                'explanation' => 'Mikroorganisma ialah hidupan seni yang biasanya hanya dapat dilihat dengan bantuan mikroskop.',
            ],
            [
                'question' => 'Apakah alat yang digunakan untuk melihat kebanyakan mikroorganisma?',
                'options' => ['A' => 'Termometer', 'B' => 'Teleskop', 'C' => 'Mikroskop', 'D' => 'Jam randik'],
                'answer' => AnswerOption::C,
                'explanation' => 'Mikroskop membesarkan imej objek seni supaya mikroorganisma dapat dilihat dengan lebih jelas.',
            ],
            [
                'question' => 'Antara berikut, yang manakah merupakan sejenis mikroorganisma?',
                'options' => ['A' => 'Bakteria', 'B' => 'Burung', 'C' => 'Pokok kelapa', 'D' => 'Ikan'],
                'answer' => AnswerOption::A,
                'explanation' => 'Bakteria ialah salah satu jenis mikroorganisma selain fungi, protozoa, alga dan virus.',
            ],
            [
                'question' => 'Di manakah mikroorganisma boleh hidup?',
                'options' => [
                    'A' => 'Di dalam air sahaja',
                    'B' => 'Di dalam tanah sahaja',
                    'C' => 'Di dalam badan hidupan sahaja',
                    'D' => 'Di udara, air, tanah dan dalam badan hidupan',
                ],
                'answer' => AnswerOption::D,
                'explanation' => 'Mikroorganisma boleh ditemui di pelbagai tempat termasuk udara, air, tanah dan badan hidupan.',
            ],
            [
                'question' => 'Antara berikut, yang manakah menunjukkan proses hidup mikroorganisma?',
                'options' => [
                    'A' => 'Bertukar menjadi batu',
                    'B' => 'Menghasilkan cahaya matahari',
                    'C' => 'Bernafas dan bertumbuh',
                    'D' => 'Membeku sepanjang masa',
                ],
                'answer' => AnswerOption::C,
                'explanation' => 'Mikroorganisma ialah hidupan yang menjalankan proses hidup seperti bernafas, bergerak dan bertumbuh.',
            ],
            [
                'question' => 'Keadaan manakah membantu yis berkembang dengan lebih aktif?',
                'options' => [
                    'A' => 'Tiada air dan tiada nutrien',
                    'B' => 'Air, nutrien dan suhu yang sesuai',
                    'C' => 'Suhu yang terlalu tinggi sahaja',
                    'D' => 'Tempat yang kering tanpa udara',
                ],
                'answer' => AnswerOption::B,
                'explanation' => 'Yis berkembang dengan lebih aktif apabila mendapat air, nutrien dan suhu yang sesuai.',
            ],
            [
                'question' => 'Mikroorganisma manakah digunakan untuk membantu menghasilkan roti?',
                'options' => ['A' => 'Yis', 'B' => 'Virus', 'C' => 'Protozoa', 'D' => 'Alga'],
                'answer' => AnswerOption::A,
                'explanation' => 'Yis digunakan dalam pembuatan roti kerana aktivitinya membantu doh mengembang.',
            ],
            [
                'question' => 'Apakah kegunaan mikroorganisma kepada alam sekitar?',
                'options' => [
                    'A' => 'Menghentikan semua tumbuhan daripada bertumbuh',
                    'B' => 'Menukarkan air menjadi batu',
                    'C' => 'Membantu menguraikan bahan organik',
                    'D' => 'Menghapuskan udara di dalam tanah',
                ],
                'answer' => AnswerOption::C,
                'explanation' => 'Sesetengah mikroorganisma bertindak sebagai pengurai yang menguraikan bahan organik.',
            ],
            [
                'question' => 'Apakah kesan berbahaya sesetengah mikroorganisma?',
                'options' => [
                    'A' => 'Menghasilkan semua jenis makanan',
                    'B' => 'Menyebabkan penyakit dan merosakkan makanan',
                    'C' => 'Membersihkan semua sumber air secara semula jadi',
                    'D' => 'Menjadikan semua manusia lebih sihat',
                ],
                'answer' => AnswerOption::B,
                'explanation' => 'Sesetengah mikroorganisma boleh menyebabkan penyakit serta menjadikan makanan rosak dan tidak selamat dimakan.',
            ],
            [
                'question' => 'Apakah langkah terbaik untuk mengurangkan kesan buruk mikroorganisma?',
                'options' => [
                    'A' => 'Membiarkan makanan terdedah',
                    'B' => 'Berkongsi peralatan peribadi yang kotor',
                    'C' => 'Menyimpan makanan di tempat yang tidak sesuai',
                    'D' => 'Menjaga kebersihan dan menyimpan makanan dengan betul',
                ],
                'answer' => AnswerOption::D,
                'explanation' => 'Kebersihan diri, penyediaan makanan yang bersih dan penyimpanan yang betul dapat mengurangkan pembiakan mikroorganisma berbahaya.',
            ],
        ];
    }
}

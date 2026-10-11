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

class ScientificSkillsTopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sourceImage = database_path('seeders/assets/sains-tahun-6-unit-1.jpg');
        $imagePath = 'topics/images/sains-tahun-6-unit-1.jpg';

        if (! File::exists($sourceImage)) {
            throw new RuntimeException('Fail nota Kemahiran Saintifik tidak ditemui.');
        }

        Storage::disk('public')->put($imagePath, File::get($sourceImage));

        DB::transaction(function () use ($imagePath): void {
            $topic = Topic::query()->updateOrCreate(
                ['topic_order' => 1],
                [
                    'topic_name' => 'Kemahiran Saintifik',
                    'content_text' => implode("\n\n", [
                        'Kemahiran saintifik ialah kemahiran yang digunakan semasa menjalankan penyiasatan dan eksperimen sains. Kemahiran ini membantu murid membuat pemerhatian, mengumpul maklumat, menyelesaikan masalah dan membuat keputusan dengan betul.',
                        'Terdapat 12 kemahiran proses sains, iaitu memerhati, mengelas, mengukur dan menggunakan nombor, membuat inferens, meramal, berkomunikasi, menggunakan perhubungan ruang dan masa, mentafsir data, mendefinisi secara operasi, mengawal pemboleh ubah, membuat hipotesis dan mengeksperimen.',
                        'Penguasaan kemahiran saintifik membantu murid berfikir secara logik, kreatif, kritis dan sistematik ketika menjalankan penyiasatan.',
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
                'question' => 'Apakah maksud kemahiran saintifik?',
                'options' => [
                    'A' => 'Kemahiran menghafal fakta sains sahaja',
                    'B' => 'Kemahiran yang digunakan semasa menjalankan penyiasatan dan eksperimen sains',
                    'C' => 'Kemahiran melukis peralatan makmal',
                    'D' => 'Kemahiran membaca buku teks dengan pantas',
                ],
                'answer' => AnswerOption::B,
                'explanation' => 'Kemahiran saintifik digunakan untuk menjalankan penyiasatan dan eksperimen secara teratur dan saintifik.',
            ],
            [
                'question' => 'Seorang murid menyatakan bahawa sebiji lemon berasa masam. Deria manakah yang digunakan?',
                'options' => ['A' => 'Mata', 'B' => 'Hidung', 'C' => 'Lidah', 'D' => 'Telinga'],
                'answer' => AnswerOption::C,
                'explanation' => 'Rasa masam dikesan menggunakan deria rasa melalui lidah.',
            ],
            [
                'question' => 'Apakah tujuan kemahiran mengelas?',
                'options' => [
                    'A' => 'Mengumpulkan objek mengikut ciri yang sama',
                    'B' => 'Meneka kejadian yang akan berlaku',
                    'C' => 'Mengubah semua pemboleh ubah',
                    'D' => 'Mengukur suhu sesuatu objek',
                ],
                'answer' => AnswerOption::A,
                'explanation' => 'Mengelas bermaksud mengumpulkan objek ke dalam kumpulan berdasarkan ciri-ciri yang sama.',
            ],
            [
                'question' => 'Alat manakah paling sesuai untuk mengukur panjang sebatang pensel?',
                'options' => ['A' => 'Termometer', 'B' => 'Jam randik', 'C' => 'Penimbang', 'D' => 'Pembaris'],
                'answer' => AnswerOption::D,
                'explanation' => 'Pembaris digunakan untuk mengukur panjang dengan unit yang sesuai seperti sentimeter.',
            ],
            [
                'question' => 'Lampu suluh tidak menyala. Murid menyatakan bahawa baterinya mungkin telah habis. Pernyataan ini ialah contoh kemahiran apa?',
                'options' => ['A' => 'Mengelas', 'B' => 'Membuat inferens', 'C' => 'Mengukur', 'D' => 'Berkomunikasi'],
                'answer' => AnswerOption::B,
                'explanation' => 'Inferens ialah penerangan atau sebab yang dibuat berdasarkan sesuatu pemerhatian.',
            ],
            [
                'question' => 'Langit kelihatan gelap dan dipenuhi awan tebal. Apakah ramalan yang paling munasabah?',
                'options' => ['A' => 'Hari akan menjadi lebih panas', 'B' => 'Angin akan berhenti sepenuhnya', 'C' => 'Hujan mungkin akan turun', 'D' => 'Matahari akan terbit pada waktu malam'],
                'answer' => AnswerOption::C,
                'explanation' => 'Ramalan ialah jangkaan tentang perkara yang akan berlaku berdasarkan maklumat dan pengalaman terdahulu.',
            ],
            [
                'question' => 'Antara berikut, yang manakah bukan cara untuk berkomunikasi dalam sains?',
                'options' => ['A' => 'Jadual', 'B' => 'Graf', 'C' => 'Carta', 'D' => 'Meneka tanpa merekod'],
                'answer' => AnswerOption::D,
                'explanation' => 'Maklumat sains boleh disampaikan melalui tulisan, jadual, graf, lukisan dan carta. Meneka tanpa merekod bukan cara berkomunikasi secara saintifik.',
            ],
            [
                'question' => 'Satu graf menunjukkan tumbuhan yang menerima lebih banyak air membesar dengan lebih cepat. Apakah kemahiran yang digunakan untuk membuat kesimpulan ini?',
                'options' => ['A' => 'Mentafsir data', 'B' => 'Memerhati', 'C' => 'Mengelas', 'D' => 'Menggunakan deria rasa'],
                'answer' => AnswerOption::A,
                'explanation' => 'Mentafsir data ialah memberikan penerangan berdasarkan data yang telah dikumpulkan.',
            ],
            [
                'question' => 'Pernyataan "pertumbuhan pokok diukur berdasarkan ketinggian pokok" ialah contoh kemahiran apa?',
                'options' => ['A' => 'Meramal', 'B' => 'Membuat hipotesis', 'C' => 'Mendefinisi secara operasi', 'D' => 'Berkomunikasi'],
                'answer' => AnswerOption::C,
                'explanation' => 'Definisi secara operasi menerangkan cara sesuatu perkara ditentukan atau diukur melalui pemerhatian yang boleh dibuat.',
            ],
            [
                'question' => 'Dalam eksperimen pertumbuhan pokok, jumlah air diubah manakala jenis pokok dikekalkan sama. Apakah pemboleh ubah dimanipulasikan?',
                'options' => ['A' => 'Ketinggian pokok', 'B' => 'Jumlah air', 'C' => 'Jenis pokok', 'D' => 'Bilangan daun selepas eksperimen'],
                'answer' => AnswerOption::B,
                'explanation' => 'Pemboleh ubah dimanipulasikan ialah faktor yang sengaja diubah, iaitu jumlah air dalam eksperimen ini.',
            ],
        ];
    }
}

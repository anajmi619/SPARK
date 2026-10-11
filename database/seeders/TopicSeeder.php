<?php

namespace Database\Seeders;

use App\Enums\TopicStatus;
use App\Models\Topic;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $topics = [
            [
                1,
                'Kemahiran Saintifik',
                'Kandungan Sains KSSR Tahun 6 bagi topik Kemahiran Saintifik.',
            ],
            [
                2,
                'Manusia',
                'Kandungan Sains KSSR Tahun 6 bagi topik Manusia.',
            ],
            [
                3,
                'Mikroorganisma',
                implode("\n\n", [
                    'Mikroorganisma ialah hidupan seni yang terlalu kecil untuk dilihat dengan mata kasar dan biasanya dapat dilihat menggunakan mikroskop. Antara jenis mikroorganisma ialah bakteria, fungi, protozoa, alga dan virus. Mikroorganisma boleh hidup di udara, air, tanah serta di dalam badan hidupan.',
                    'Mikroorganisma menjalankan proses hidup seperti bernafas, bergerak dan bertumbuh. Pertumbuhannya dipengaruhi oleh air, udara, suhu yang sesuai, nutrien dan keasidan. Contohnya, yis berkembang dengan lebih aktif apabila memperoleh air, nutrien dan suhu yang sesuai.',
                    'Mikroorganisma mempunyai kesan yang berguna dan berbahaya. Mikroorganisma digunakan untuk menghasilkan makanan seperti roti, tapai, tempe dan yogurt serta membantu penguraian bahan organik. Namun, sesetengah mikroorganisma boleh menyebabkan penyakit dan merosakkan makanan. Kebersihan diri, penyediaan makanan yang bersih dan penyimpanan makanan yang betul dapat mengurangkan kesan buruk mikroorganisma.',
                ]),
            ],
            [
                4,
                'Interaksi Antara Hidupan',
                implode("\n\n", [
                    'Interaksi antara hidupan ialah hubungan yang berlaku antara hidupan dalam sesuatu habitat untuk mendapatkan keperluan asas seperti makanan, air, udara, tempat tinggal dan pasangan. Interaksi boleh berlaku antara hidupan daripada spesies yang sama atau spesies yang berlainan.',
                    'Antara bentuk interaksi ialah persaingan, pemangsaan dan simbiosis. Persaingan berlaku apabila hidupan berebut sumber yang terhad, manakala pemangsaan melibatkan pemangsa memburu dan memakan mangsa. Simbiosis merangkumi mutualisme yang menguntungkan kedua-dua hidupan, komensalisme yang menguntungkan satu hidupan tanpa menjejaskan hidupan lain, dan parasitisme yang menguntungkan parasit tetapi memudaratkan perumah.',
                    'Interaksi antara hidupan membantu mengekalkan keseimbangan ekosistem. Perubahan bilangan sesuatu organisma boleh mempengaruhi sumber makanan dan populasi organisma lain dalam rantai makanan. Oleh itu, habitat dan hidupan perlu dipelihara supaya hubungan dalam ekosistem terus seimbang.',
                ]),
            ],
        ];

        foreach ($topics as [$order, $name, $content]) {
            Topic::query()->updateOrCreate(
                ['topic_order' => $order],
                [
                    'topic_name' => $name,
                    'content_text' => $content,
                    'image_path' => null,
                    'youtube_url' => null,
                    'status' => TopicStatus::Draft,
                    'created_by' => null,
                ],
            );
        }
    }
}

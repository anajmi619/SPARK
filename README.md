# SPARK

SPARK ialah sistem pembelajaran Sains KSSR Tahun 6 berasaskan web. Sistem ini menyediakan modul pembelajaran, kuiz dengan maklum balas segera, markah dan tahap pelajar, pemantauan kemajuan, analitik kelas, serta pengurusan tahun akademik.

## Keperluan

- PHP 8.3 atau lebih baharu
- Composer 2
- Node.js 22 atau lebih baharu
- MySQL 8

## Pemasangan tempatan

```powershell
git clone https://github.com/anajmi619/SPARK.git spark
cd spark
composer install
Copy-Item .env.example .env
php artisan key:generate
npm install
```

Kemudian kemas kini sambungan pangkalan data dalam fail `.env` tempatan. Contoh berikut menggunakan port MySQL `3307`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=KATA_LALUAN_MYSQL_ANDA
```

Jangan masukkan kata laluan sebenar ke dalam `.env.example`, kod sumber, isu GitHub, atau commit Git.

Sediakan pangkalan data dan aset:

```powershell
php artisan migrate --seed
php artisan storage:link
npm run build
```

Untuk menjalankan aplikasi semasa pembangunan:

```powershell
composer run dev
```

Aplikasi boleh dibuka di `http://127.0.0.1:8000`.

## Akaun guru dan data demonstrasi

Cipta akaun guru secara interaktif:

```powershell
php artisan spark:create-teacher
```

Data 20 pelajar demonstrasi adalah pilihan. Tetapkan kata laluan tempatan dalam `.env`, kemudian jalankan seedernya:

```dotenv
SAMPLE_STUDENT_PASSWORD=KATA_LALUAN_DEMO_ANDA
```

```powershell
php artisan config:clear
php artisan db:seed --class=SampleStudentSeeder
```

Nilai `SAMPLE_STUDENT_PASSWORD` tidak disimpan dalam repositori.

## Maklum balas Gemini

Integrasi Gemini dimatikan secara lalai. Tanpa API, gunakan:

```dotenv
QUIZ_AI_FEEDBACK_ENABLED=false
GEMINI_API_KEY=
```

Untuk ujian terkawal, masukkan kunci hanya dalam `.env` tempatan dan aktifkan ciri tersebut:

```dotenv
QUIZ_AI_FEEDBACK_ENABLED=true
GEMINI_API_KEY=KUNCI_API_ANDA
```

Selepas menukar konfigurasi:

```powershell
php artisan config:clear
```

Jangan commit atau kongsi kunci API. Jika kunci pernah terdedah, batalkan kunci itu di Google AI Studio dan cipta kunci baharu.

## Pemeriksaan kualiti

```powershell
composer test
npm run build
composer audit
```

Fail `.env`, pangkalan data SQLite tempatan, log, cache, fail muat naik, `vendor`, `node_modules`, dan aset binaan tidak disimpan dalam Git.

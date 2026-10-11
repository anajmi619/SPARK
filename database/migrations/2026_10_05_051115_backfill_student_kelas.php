<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $initial = ['Ibnu Batutta', 'Ibnu Haitham', 'Ibnu Khaldun', 'Ibnu Qaiyim', 'Ibnu Sina'];
            $now = now();
            foreach ($initial as $nama) {
                DB::table('kelas')->insertOrIgnore(['nama' => $nama, 'created_at' => $now, 'updated_at' => $now]);
            }
            foreach (DB::table('users')->where('role', 'student')->select('class_name')->distinct()->get() as $group) {
                $original = $group->class_name;
                $nama = trim(preg_replace('/\\s+/u', ' ', (string) $original));
                $nama = trim(preg_replace('/^(?:tahun\\s+)?[1-6]\\s+/iu', '', $nama));
                if ($nama === '') {
                    throw new RuntimeException('Kelas pelajar kosong. Lengkapkan class_name sebelum meneruskan migrasi.');
                }
                $known = collect($initial)->first(fn (string $name): bool => mb_strtolower($name) === mb_strtolower($nama));
                $nama = $known ?? $nama;
                if ($known === null) {
                    Log::warning('Kelas lama tidak sepadan dengan senarai awal; dikekalkan sebagai kelas tambahan.', [
                        'class_name' => $original, 'nama' => $nama,
                    ]);
                }
                $id = DB::table('kelas')->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->value('id');
                if ($id === null) {
                    $id = DB::table('kelas')->insertGetId(['nama' => $nama, 'created_at' => $now, 'updated_at' => $now]);
                }
                DB::table('users')->where('role', 'student')->where('class_name', $original)->update(['kelas_id' => $id]);
            }
            if (DB::table('users')->where('role', 'student')->whereNull('kelas_id')->exists()) {
                throw new RuntimeException('Terdapat pelajar yang belum dipadankan dengan kelas.');
            }
        });
    }

    public function down(): void
    {
        // The later schema migration restores class names before this backfill is reversed.
        DB::table('users')->update(['kelas_id' => null]);
    }
};

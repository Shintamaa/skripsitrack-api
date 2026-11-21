<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{User, Thesis, ThesisStage, Submission, Comment, ActivityLog};
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Berkas contoh agar tautan unggahan seeder benar-benar bisa dibuka
        $sample = database_path('seeders/files/contoh.pdf');
        if (file_exists($sample)) {
            foreach (['contoh.pdf', 'contoh_bab1_v1.pdf', 'contoh_bab1_v2.pdf'] as $f) {
                Storage::disk('public')->put("submissions/$f", file_get_contents($sample));
            }
        }

        $dosen = User::create([
            'name' => 'Dr. Budi Santoso, M.Kom',
            'email' => 'dosen@test.com',
            'role' => 'dosen',
            'nim_nidn' => '198001012005011001',
            'password' => Hash::make('password'),
        ]);

        $mhs = User::create([
            'name' => 'Adhitya Lingga Pratama',
            'email' => 'mahasiswa@test.com',
            'role' => 'mahasiswa',
            'nim_nidn' => '2403114274',
            'password' => Hash::make('password'),
        ]);

        $thesis = Thesis::create([
            'student_id' => $mhs->id,
            'supervisor_id' => $dosen->id,
            'title' => 'Sistem Informasi Monitoring Skripsi Berbasis Android',
            'start_date' => now(),
            'target_date' => now()->addMonths(6),
        ]);

        $stages = [
            'Pengajuan Judul',
            'BAB I Pendahuluan',
            'BAB II Tinjauan Pustaka',
            'BAB III Metodologi',
            'BAB IV Hasil dan Pembahasan',
            'BAB V Penutup',
            'Sidang Akhir'
        ];

        foreach ($stages as $i => $name) {
            $stage = ThesisStage::create([
                'thesis_id' => $thesis->id,
                'name' => $name,
                'sort_order' => $i + 1,
                'target_date' => now()->addWeeks(($i + 1) * 3),
                'status' => $i === 0 ? 'selesai' : ($i === 1 ? 'revisi' : 'belum'),
            ]);

            if ($i === 1) {
                $v1 = Submission::create([
                    'stage_id' => $stage->id,
                    'version' => 1,
                    'file_path' => 'submissions/contoh_bab1_v1.pdf',
                    'file_type' => 'pdf',
                    'note' => 'Draft awal BAB I',
                    'status' => 'perlu_revisi'
                ]);

                Comment::create([
                    'submission_id' => $v1->id,
                    'user_id' => $dosen->id,
                    'body' => 'Rumusan masalah belum spesifik, mohon diperbaiki.'
                ]);

                Submission::create([
                    'stage_id' => $stage->id,
                    'version' => 2,
                    'file_path' => 'submissions/contoh_bab1_v2.pdf',
                    'file_type' => 'pdf',
                    'note' => 'Revisi rumusan masalah',
                    'status' => 'menunggu'
                ]);
            }
        }

        ActivityLog::create([
            'user_id' => $mhs->id,
            'thesis_id' => $thesis->id,
            'action' => 'upload',
            'description' => 'Mengunggah BAB I versi 2'
        ]);

        User::create([
            'name' => 'Dr. Siti Rahma, M.T.', 'email' => 'dosen2@test.com',
            'role' => 'dosen', 'nim_nidn' => '198505052010012002',
            'password' => Hash::make('password'),
        ]);

        $mhs2 = User::create([
            'name' => 'Rizky Ramadhan', 'email' => 'mahasiswa2@test.com',
            'role' => 'mahasiswa', 'nim_nidn' => '2403114301',
            'password' => Hash::make('password'),
        ]);

        // Mahasiswa tanpa skripsi: untuk menampilkan kondisi data kosong
        User::create([
            'name' => 'Salsabila Putri', 'email' => 'mahasiswa3@test.com',
            'role' => 'mahasiswa', 'nim_nidn' => '2403114302',
            'password' => Hash::make('password'),
        ]);

        $t2 = Thesis::create([
            'student_id' => $mhs2->id, 'supervisor_id' => $dosen->id,
            'title' => 'Rancang Bangun Aplikasi Reservasi Ruang Berbasis Android',
            'start_date' => now()->subMonths(2), 'target_date' => now()->addMonths(4),
        ]);

        foreach ($stages as $i => $name) {
            $target = match (true) {
                $i < 3     => now()->subWeeks(3 - $i),   // sudah lewat dan selesai
                $i === 3   => now()->subDays(5),         // terlambat
                default    => now()->addWeeks($i - 3),
            };

            $stage = ThesisStage::create([
                'thesis_id'    => $t2->id, 'name' => $name, 'sort_order' => $i + 1,
                'target_date'  => $target,
                'completed_at' => $i < 3 ? $target : null,
                'status'       => $i < 3 ? 'selesai' : ($i === 3 ? 'proses' : 'belum'),
            ]);

            if ($i < 3) {
                Submission::create(['stage_id' => $stage->id, 'version' => 1,
                    'file_path' => 'submissions/contoh.pdf', 'file_type' => 'pdf',
                    'note' => "Draf {$name}", 'status' => 'disetujui']);
            }
            if ($i === 3) {
                Submission::create(['stage_id' => $stage->id, 'version' => 1,
                    'file_path' => 'submissions/contoh.pdf', 'file_type' => 'pdf',
                    'note' => 'Draf awal metodologi', 'status' => 'menunggu']);
            }
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\Thesis;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;

class ThesisController extends Controller
{
    public function index(Request $r)
    {
        $u = $r->user();
        $q = Thesis::with(['student:id,name,nim_nidn', 'supervisor:id,name'])
            ->withCount('stages');

        $q->where($u->role === 'dosen' ? 'supervisor_id' : 'student_id', $u->id);

        if ($r->filled('q'))      $q->where('title', 'like', '%' . $r->q . '%');
        if ($r->filled('status')) $q->where('status', $r->status);

        return response()->json(['success' => true, 'data' => $q->latest()->get()]);
    }

    public function store(Request $r)   // route dibatasi role:mahasiswa
    {
        $data = $r->validate([
            'supervisor_id' => 'required|exists:users,id',
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'start_date'    => 'nullable|date',
            'target_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $thesis = Thesis::create($data + ['student_id' => $r->user()->id]);

        $names = ['Pengajuan Judul', 'BAB I Pendahuluan', 'BAB II Tinjauan Pustaka',
                'BAB III Metodologi', 'BAB IV Hasil dan Pembahasan', 'BAB V Penutup', 'Sidang Akhir'];
        foreach ($names as $i => $n) {
            $thesis->stages()->create(['name' => $n, 'sort_order' => $i + 1]);
        }

        ActivityLog::create(['user_id' => $r->user()->id, 'thesis_id' => $thesis->id,
            'action' => 'buat_skripsi', 'description' => 'Membuat skripsi: ' . $thesis->title]);

        return response()->json(['success' => true, 'message' => 'Skripsi dibuat', 'data' => $thesis], 201);
    }

    public function show(Request $r, Thesis $thesis)
    {
        abort_unless($thesis->canAccess($r->user()), 403);
        return response()->json(['success' => true,
            'data' => $thesis->load(['student:id,name', 'supervisor:id,name', 'stages'])]);
    }

    public function update(Request $r, Thesis $thesis)
    {
        abort_unless($thesis->student_id === $r->user()->id, 403);
        $data = $r->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:berjalan,selesai,ditunda',
            'target_date' => 'nullable|date',
        ]);
        $thesis->update($data);
        return response()->json(['success' => true, 'message' => 'Skripsi diperbarui', 'data' => $thesis]);
    }

    public function destroy(Request $r, Thesis $thesis)
    {
        abort_unless($thesis->student_id === $r->user()->id, 403);
        $thesis->delete();
        return response()->json(['success' => true, 'message' => 'Skripsi dihapus']);
    }
}

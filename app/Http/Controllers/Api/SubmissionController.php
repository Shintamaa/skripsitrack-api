<?php

namespace App\Http\Controllers\Api;

use App\Models\Submission;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\ThesisStage;
use App\Models\Thesis;
use App\Models\User;

class SubmissionController extends Controller
{
    public function index(Request $r, ThesisStage $stage)
    {
        abort_unless($stage->thesis->canAccess($r->user()), 403);
        $q = $stage->submissions()->with('comments');
        if ($r->filled('status')) $q->where('status', $r->status);
        return response()->json(['success' => true, 'data' => $q->get()]);
    }

    public function store(Request $r, ThesisStage $stage)   // role:mahasiswa
    {
        abort_unless($stage->thesis->student_id === $r->user()->id, 403);

        if ($stage->status === 'selesai') {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Tahapan ini sudah disetujui, tidak dapat mengunggah lagi'
                ],
                422
            );
        }

        $r->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
            'note' => 'nullable|string|max:500',
        ]);

        $file = $r->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        $sub = Submission::create([
            'stage_id'  => $stage->id,
            'version'   => ($stage->submissions()->max('version') ?? 0) + 1,
            'file_path' => $file->store('submissions', 'public'),
            'file_type' => in_array($ext, ['jpg', 'jpeg', 'png'])
                ? 'image'
                : ($ext === 'pdf' ? 'pdf' : 'doc'),
            'note'      => $r->note,
        ]);

        $stage->syncStatus();

        ActivityLog::create([
            'user_id' => $r->user()->id,
            'thesis_id' => $stage->thesis_id,
            'action' => 'upload',
            'description' => "Mengunggah {$stage->name} versi {$sub->version}",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berkas diunggah',
            'data' => $sub
        ], 201);
    }

    public function updateStatus(Request $r, Submission $submission)   // role:dosen
    {
        $stage = $submission->stage;

        abort_unless($stage->thesis->supervisor_id === $r->user()->id, 403);

        $r->validate([
            'status'  => 'required|in:perlu_revisi,disetujui',
            'comment' => 'required_if:status,perlu_revisi|nullable|string|max:1000',
        ]);

        if ((int) $submission->version !== (int) $stage->submissions()->max('version')) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Hanya versi terbaru yang dapat ditinjau'
                ],
                422
            );
        }

        if ($submission->status === 'disetujui') {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Unggahan ini sudah disetujui'
                ],
                422
            );
        }

        $submission->update([
            'status' => $r->status
        ]);

        $stage->syncStatus();

        if ($r->filled('comment')) {
            $submission->comments()->create([
                'user_id' => $r->user()->id,
                'body' => $r->comment
            ]);
        }

        ActivityLog::create([
            'user_id' => $r->user()->id,
            'thesis_id' => $stage->thesis_id,
            'action' => 'ubah_status',
            'description' => "{$stage->name} v{$submission->version}: " .
                ($r->status === 'disetujui' ? 'disetujui' : 'perlu revisi'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status diperbarui',
            'data' => $submission->load('comments')
        ]);
    }

    public function destroy(Request $r, Submission $submission)
    {
        $stage = $submission->stage;

        abort_unless($stage->thesis->student_id === $r->user()->id, 403);

        if ($submission->status === 'disetujui') {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Unggahan yang sudah disetujui tidak dapat dihapus'
                ],
                422
            );
        }

        Storage::disk('public')->delete($submission->file_path);

        $submission->delete();

        $stage->syncStatus();

        ActivityLog::create([
            'user_id' => $r->user()->id,
            'thesis_id' => $stage->thesis_id,
            'action' => 'hapus',
            'description' => "Menghapus {$stage->name} versi {$submission->version}",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Unggahan dihapus'
        ]);
    }

    public function bulkDestroy(Request $r)
    {
        $r->validate(['ids' => 'required|array|min:1|max:50', 'ids.*' => 'integer']);

        // Hanya unggahan milik skripsi mahasiswa ini; ID lain diabaikan diam-diam
        $subs = Submission::with('stage')
            ->whereIn('id', $r->ids)
            ->whereHas('stage.thesis', fn($q) => $q->where('student_id', $r->user()->id))
            ->get();

        $deleted = 0;
        $skipped = 0;
        $stages  = [];

        foreach ($subs as $s) {
            if ($s->status === 'disetujui') { $skipped++; continue; }
            Storage::disk('public')->delete($s->file_path);
            $stages[$s->stage_id] = $s->stage;
            $s->delete();
            $deleted++;
        }

        if ($deleted === 0) {
            return response()->json(
                ['success' => false, 'message' => 'Tidak ada unggahan yang dapat dihapus'], 422);
        }

        foreach ($stages as $st) {
            $st->syncStatus();
            ActivityLog::create([
                'user_id' => $r->user()->id, 'thesis_id' => $st->thesis_id,
                'action' => 'hapus', 'description' => "Menghapus unggahan pada {$st->name}",
            ]);
        }

        $msg = "$deleted unggahan dihapus" . ($skipped ? ", $skipped dilewati (sudah disetujui)" : '');
        return response()->json(['success' => true, 'message' => $msg,
            'data' => ['deleted' => $deleted, 'skipped' => $skipped]]);
    }
}
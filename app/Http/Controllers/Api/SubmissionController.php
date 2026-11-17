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
        $r->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
            'note' => 'nullable|string|max:500',
        ]);

        $file = $r->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        $sub = Submission::create([
            'stage_id'  => $stage->id,
            'version'   => ($stage->submissions()->max('version') ?? 0) + 1,   // v1, v2, v3 ...
            'file_path' => $file->store('submissions', 'public'),
            'file_type' => in_array($ext, ['jpg', 'jpeg', 'png']) ? 'image' : ($ext === 'pdf' ? 'pdf' : 'doc'),
            'note'      => $r->note,
        ]);

        $stage->update(['status' => 'proses']);
        ActivityLog::create(['user_id' => $r->user()->id, 'thesis_id' => $stage->thesis_id,
            'action' => 'upload',
            'description' => "Mengunggah {$stage->name} versi {$sub->version}"]);

        return response()->json(['success' => true, 'message' => 'Berkas diunggah', 'data' => $sub], 201);
    }

    public function updateStatus(Request $r, Submission $submission)   // role:dosen
    {
        $stage = $submission->stage;
        abort_unless($stage->thesis->supervisor_id === $r->user()->id, 403);

        $r->validate(['status' => 'required|in:perlu_revisi,disetujui', 'comment' => 'nullable|string']);
        $submission->update(['status' => $r->status]);

        if ($r->status === 'disetujui') {
            $stage->update(['status' => 'selesai', 'completed_at' => now()]);
        } else {
            $stage->update(['status' => 'revisi']);
        }
        if ($r->filled('comment')) {
            $submission->comments()->create(['user_id' => $r->user()->id, 'body' => $r->comment]);
        }

        ActivityLog::create(['user_id' => $r->user()->id, 'thesis_id' => $stage->thesis_id,
            'action' => 'ubah_status',
            'description' => "{$stage->name} v{$submission->version}: {$r->status}"]);

        return response()->json(['success' => true, 'message' => 'Status diperbarui', 'data' => $submission]);
    }

    public function destroy(Request $r, Submission $submission)
    {
        abort_unless($submission->stage->thesis->student_id === $r->user()->id, 403);
        Storage::disk('public')->delete($submission->file_path);
        $submission->delete();
        return response()->json(['success' => true, 'message' => 'Unggahan dihapus']);
    }

    public function bulkDestroy(Request $r)
    {
        $r->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer']);

        $subs = Submission::whereIn('id', $r->ids)
            ->whereHas('stage.thesis', fn($q) => $q->where('student_id', $r->user()->id))
            ->get();

        foreach ($subs as $s) {
            Storage::disk('public')->delete($s->file_path);
            $s->delete();
        }
        return response()->json(['success' => true, 'message' => $subs->count() . ' unggahan dihapus']);
    }
}

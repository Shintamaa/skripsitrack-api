<?php

namespace App\Http\Controllers\Api;

use App\Models\Comment;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\Submission;
use App\Models\Thesis;
use App\Models\ThesisStage;
use App\Models\User;

class DashboardController extends Controller
{
    public function index(Request $r)
    {
        $u = $r->user();
        $col = $u->role === 'dosen' ? 'supervisor_id' : 'student_id';
        $theses = Thesis::where($col, $u->id)->pluck('id');

        $stages = ThesisStage::whereIn('thesis_id', $theses);
        $total  = (clone $stages)->count();
        $done   = (clone $stages)->where('status', 'selesai')->count();

        $subs = Submission::whereHas('stage', fn($q) => $q->whereIn('thesis_id', $theses));

        return response()->json(['success' => true, 'data' => [
            'total_skripsi'     => $theses->count(),
            'progress_persen'   => $total ? round($done / $total * 100) : 0,
            'tahapan_selesai'   => $done,
            'total_tahapan'     => $total,
            'menunggu_review'   => (clone $subs)->where('status', 'menunggu')->count(),
            'perlu_revisi'      => (clone $subs)->where('status', 'perlu_revisi')->count(),
            'deadline_terdekat' => (clone $stages)->where('status', '!=', 'selesai')
                                    ->whereNotNull('target_date')->orderBy('target_date')->first(),
            'aktivitas_terbaru' => ActivityLog::whereIn('thesis_id', $theses)
                                    ->with('user:id,name')->latest()->limit(10)->get(),
        ]]);
    }

    public function activities(Request $r)   // riwayat lengkap, bisa difilter ?action=upload
    {
        $col = $r->user()->role === 'dosen' ? 'supervisor_id' : 'student_id';
        $q = ActivityLog::whereIn('thesis_id', Thesis::where($col, $r->user()->id)->pluck('id'))
            ->with('user:id,name')->latest();
        if ($r->filled('action')) $q->where('action', $r->action);
        return response()->json(['success' => true, 'data' => $q->paginate(20)]);
    }
}

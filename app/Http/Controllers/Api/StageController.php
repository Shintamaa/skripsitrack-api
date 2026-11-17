<?php

namespace App\Http\Controllers\Api;

use App\Models\Thesis;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ThesisStage;


class StageController extends Controller
{
    public function index(Request $r, Thesis $thesis)
    {
        abort_unless($thesis->canAccess($r->user()), 403);
        return response()->json(['success' => true,
            'data' => $thesis->stages()->withCount('submissions')->get()]);
    }

    public function update(Request $r, ThesisStage $stage)
    {
        abort_unless($stage->thesis->canAccess($r->user()), 403);
        $data = $r->validate([
            'name' => 'sometimes|string|max:255',
            'target_date' => 'nullable|date',
        ]);
        $stage->update($data);
        return response()->json(['success' => true, 'message' => 'Tahapan diperbarui', 'data' => $stage]);
    }
}

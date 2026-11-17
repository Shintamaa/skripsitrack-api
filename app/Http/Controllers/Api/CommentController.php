<?php

namespace App\Http\Controllers\Api;

use App\Models\Comment;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\Submission;

class CommentController extends Controller
{   
    public function store(Request $r, Submission $submission)
    {
        abort_unless($submission->stage->thesis->canAccess($r->user()), 403);
        $r->validate(['body' => 'required|string|max:1000']);
        $c = $submission->comments()->create(['user_id' => $r->user()->id, 'body' => $r->body]);

        ActivityLog::create(['user_id' => $r->user()->id,
            'thesis_id' => $submission->stage->thesis_id,
            'action' => 'komentar', 'description' => 'Menambahkan komentar']);

        return response()->json(['success' => true, 'data' => $c->load('user:id,name,role')], 201);
    }   
}

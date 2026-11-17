<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $guarded = [];
    protected $appends = ['file_url'];

    public function stage()    { return $this->belongsTo(ThesisStage::class, 'stage_id'); }
    public function comments() { return $this->hasMany(Comment::class)->with('user:id,name,role')->latest(); }
    public function getFileUrlAttribute() { return asset('storage/' . $this->file_path); }
}

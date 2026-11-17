<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThesisStage extends Model
{
    protected $guarded = [];
    public function thesis()      { return $this->belongsTo(Thesis::class); }
    public function submissions() { return $this->hasMany(Submission::class, 'stage_id')->orderByDesc('version'); }
}

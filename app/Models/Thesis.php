<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Thesis extends Model
{
    protected $table = 'theses';
    protected $guarded = [];

    public function student()    { return $this->belongsTo(User::class, 'student_id'); }
    public function supervisor() { return $this->belongsTo(User::class, 'supervisor_id'); }
    public function stages()     { return $this->hasMany(ThesisStage::class)->orderBy('sort_order'); }

    public function canAccess(User $u): bool
    {
        return $u->id === $this->student_id || $u->id === $this->supervisor_id;
    }
}
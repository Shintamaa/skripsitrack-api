<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThesisStage extends Model
{
    protected $guarded = [];
    public function thesis()      { return $this->belongsTo(Thesis::class); }
    public function submissions() { return $this->hasMany(Submission::class, 'stage_id')->orderByDesc('version'); }
    public function syncStatus(): void
    {
        $latest = $this->submissions()->first();   // relasi sudah urut versi terbesar

        if (!$latest) {
            $this->update(['status' => 'belum', 'completed_at' => null]);
            return;
        }

        $map = [
            'menunggu'     => 'proses',
            'perlu_revisi' => 'revisi',
            'disetujui'    => 'selesai',
        ];

        $this->update([
            'status'       => $map[$latest->status],
            'completed_at' => $latest->status === 'disetujui'
                ? ($this->completed_at ?? now())
                : null,
        ]);
    }
}

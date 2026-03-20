<?php

namespace Modules\Essentials\Entities;

use App\Business;
use App\User;
use Illuminate\Database\Eloquent\Model;

class EssentialsTranscript extends Model
{
    protected $table = 'essentials_transcripts';

    protected $guarded = ['id'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}

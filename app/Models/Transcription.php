<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transcription extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'title',
        'status',
        'drive_file_id',
        'drive_web_view_link',
        'drive_download_link',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

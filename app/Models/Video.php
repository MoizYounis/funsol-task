<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_project_id',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'duration',
        'width',
        'height',
        'start_time',
        'end_time',
        'order',
    ];

    protected $casts = [
        'duration' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'start_time' => 'integer',
        'end_time' => 'integer',
        'order' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(VideoProject::class, 'video_project_id');
    }

    public function getTrimmedDurationAttribute(): int
    {
        if ($this->end_time && $this->start_time) {
            return $this->end_time - $this->start_time;
        }
        return $this->duration ?? 0;
    }

    public function isTrimmed(): bool
    {
        return $this->start_time > 0 || ($this->end_time && $this->end_time < $this->duration);
    }

    public function getFullPathAttribute(): string
    {
        return storage_path('app/private/' . $this->file_path);
    }

    public function getPublicUrlAttribute(): string
    {
        return route('video-editor.videos.stream', $this->id);
    }
}

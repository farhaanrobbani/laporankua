<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Download extends Model
{
    protected $fillable = [
        'title',
        'description',
        'file_path',
        'external_url',
        'file_type',
        'file_size',
        'uploaded_by',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isExternal(): bool
    {
        return filled($this->external_url);
    }

    public function isFile(): bool
    {
        return filled($this->file_path);
    }
}

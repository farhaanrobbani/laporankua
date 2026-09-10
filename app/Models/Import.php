<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'file_size',
        'sheet_name',
        'total_rows',
        'imported_rows',
        'failed_rows',
        'status',
        'imported_at',
        'error_log',
    ];

    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
            'error_log' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function importData(): HasMany
    {
        return $this->hasMany(ImportData::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}

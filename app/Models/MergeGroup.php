<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MergeGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'join_column',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function imports(): BelongsToMany
    {
        return $this->belongsToMany(Import::class, 'merge_group_imports')
            ->withTimestamps()
            ->orderByPivot('created_at');
    }

    /**
     * Cek apakah kolom tertentu ada di semua import dalam grup ini.
     */
    public function hasJoinColumnInAllImports(): bool
    {
        $imports = $this->imports;

        if ($imports->isEmpty()) {
            return false;
        }

        foreach ($imports as $import) {
            $columns = $import->availableColumns();

            if (! in_array($this->join_column, $columns, true)) {
                return false;
            }
        }

        return true;
    }
}

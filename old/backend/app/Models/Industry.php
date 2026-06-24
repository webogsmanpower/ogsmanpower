<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Industry extends Model
{
    protected $fillable = [
        'name',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function findOrCreateByName(string $name, ?int $createdBy = null): self
    {
        $industry = static::where('name', $name)->first();
        
        if (!$industry) {
            $industry = static::create([
                'name' => trim($name),
                'created_by' => $createdBy,
            ]);
        }

        return $industry;
    }
}

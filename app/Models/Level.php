<?php

namespace App\Models;

use Database\Factories\LevelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $level_id
 * @property int $level_number
 * @property string $level_name
 * @property int $minimum_points
 * @property int $maximum_points
 */
#[Fillable(['level_number', 'level_name', 'minimum_points', 'maximum_points'])]
class Level extends Model
{
    /** @use HasFactory<LevelFactory> */
    use HasFactory;

    protected $primaryKey = 'level_id';

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'current_level_id', 'level_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level_number' => 'integer',
            'minimum_points' => 'integer',
            'maximum_points' => 'integer',
        ];
    }
}

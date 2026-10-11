<?php

namespace App\Models;

use App\Enums\AcademicYearStatus;
use Carbon\CarbonImmutable;
use Database\Factories\AcademicYearFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $academic_year_id
 * @property int $year
 * @property AcademicYearStatus $status
 * @property CarbonImmutable|null $rollover_at
 * @property int|null $scheduled_by
 */
#[Fillable(['year', 'status', 'rollover_at', 'activated_at', 'closed_at', 'scheduled_by'])]
class AcademicYear extends Model
{
    /** @use HasFactory<AcademicYearFactory> */
    use HasFactory;

    protected $primaryKey = 'academic_year_id';

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'academic_year_id', 'academic_year_id');
    }

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'status' => AcademicYearStatus::class,
            'rollover_at' => 'immutable_datetime',
            'activated_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }
}

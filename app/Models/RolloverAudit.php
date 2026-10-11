<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $event
 * @property CarbonImmutable $occurred_at
 * @property CarbonImmutable|null $scheduled_at
 * @property int|null $activated_count
 * @property int|null $deactivated_count
 * @property string|null $error_type
 */
#[Fillable(['academic_year_id', 'previous_year_id', 'performed_by', 'event', 'scheduled_at', 'occurred_at', 'activated_count', 'deactivated_count', 'error_type'])]
class RolloverAudit extends Model
{
    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id', 'academic_year_id');
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function previousYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'previous_year_id', 'academic_year_id');
    }

    /** @return BelongsTo<User, $this> */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by', 'user_id');
    }

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'immutable_datetime',
            'occurred_at' => 'immutable_datetime',
            'activated_count' => 'integer',
            'deactivated_count' => 'integer',
        ];
    }
}

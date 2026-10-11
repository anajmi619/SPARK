<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';

    /** @deprecated Retained only so old databases can be migrated safely. */
    case TeacherAdmin = 'teacher_admin';

    case Teacher = 'teacher';
    case Admin = 'admin';

    public function isStaff(): bool
    {
        return in_array($this, [self::Teacher, self::Admin], true);
    }
}

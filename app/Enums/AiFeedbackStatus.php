<?php

namespace App\Enums;

enum AiFeedbackStatus: string
{
    case NotRequired = 'not_required';
    case Generated = 'generated';
    case Failed = 'failed';
}

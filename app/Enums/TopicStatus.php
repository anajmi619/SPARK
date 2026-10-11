<?php

namespace App\Enums;

enum TopicStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}

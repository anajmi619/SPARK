<?php

namespace App\Enums;

enum AiFeedbackSource: string
{
    case Api = 'api';
    case Cache = 'cache';
}

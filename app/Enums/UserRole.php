<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Mentor = 'mentor';
    case Admin = 'admin';
}

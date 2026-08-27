<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Pending = 'pending';
    case Enrolling = 'enrolling';
    case Enrolled = 'enrolled';
    case Failed = 'failed';
    case Unenrolled = 'unenrolled';
}

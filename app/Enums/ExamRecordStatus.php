<?php

namespace App\Enums;

enum ExamRecordStatus: int
{
    case InProgress = 0;
    case Submitted = 1;
    case AutoGraded = 2;
    case PendingReview = 3;
    case Graded = 4;
    case Rejected = 5;
    case Retaken = 6;
}

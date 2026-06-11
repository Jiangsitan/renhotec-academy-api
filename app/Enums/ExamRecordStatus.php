<?php

namespace App\Enums;

enum ExamRecordStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case AutoGraded = 'auto_graded';
    case PendingReview = 'pending_review';
    case Graded = 'graded';
    case Rejected = 'rejected';
}

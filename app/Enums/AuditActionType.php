<?php

namespace App\Enums;

enum AuditActionType: string
{
    case Login = 'login';
    case Logout = 'logout';
    case DownloadAttachment = 'download_attachment';
    case CompleteCourse = 'complete_course';
    case SubmitExam = 'submit_exam';
    case CreateUser = 'create_user';
    case UpdateUser = 'update_user';
    case DisableUser = 'disable_user';
    case CreateCourse = 'create_course';
    case UpdateCourse = 'update_course';
    case DeleteCourse = 'delete_course';
    case CreateExam = 'create_exam';
    case UpdateExam = 'update_exam';
    case DeleteExam = 'delete_exam';
    case CreateMentorBinding = 'create_mentor_binding';
    case DeleteMentorBinding = 'delete_mentor_binding';
    case ResetPassword = 'reset_password';
}

<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminCommentController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\FileUploadController;
use App\Http\Controllers\Api\FileServeController;
use App\Http\Controllers\Api\HomepageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SeriesController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SsoController;
use Illuminate\Support\Facades\Route;

// 公开路由 - 认证
Route::post('/login', [AuthController::class, 'login']);

// 公开路由 - 系统设置（所有用户可读）
Route::get('/settings', [SettingController::class, 'index']);

// 需要认证的路由
Route::middleware('auth.api')->group(function () {
    // 认证相关
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // 首页数据
    Route::get('/homepage', [HomepageController::class, 'index']);

    // 文件预览（认证用户可访问）
    Route::get('/files/preview/{path}', [FileServeController::class, 'preview'])
        ->where('path', '.*')
        ->name('files.preview');

    // Office 文件预览（认证用户可访问）
    Route::get('/files/preview-office/{path}', [FileServeController::class, 'previewOffice'])
        ->where('path', '.*')
        ->name('files.preview-office');

    // 学员路由（需要 active 状态）
    Route::middleware('student.active')->group(function () {
        // 分类（简化版）
        Route::get('/categories', [\App\Http\Controllers\Api\CourseController::class, 'categories']);

        // 系列
        Route::get('/series', [SeriesController::class, 'index']);
        Route::get('/series/{series}', [SeriesController::class, 'show']);

        // 课程（视频）
        Route::get('/courses', [\App\Http\Controllers\Api\CourseController::class, 'index']);
        Route::get('/courses/{course}', [\App\Http\Controllers\Api\CourseController::class, 'show']);

        // 学习进度
        Route::post('/learning/progress/sync', [\App\Http\Controllers\Api\LearningProgressController::class, 'syncProgress']);
        Route::post('/learning/progress/complete', [\App\Http\Controllers\Api\LearningProgressController::class, 'completeDocument'])->name('learning.complete');
        Route::get('/learning/progress/{courseId}', [\App\Http\Controllers\Api\LearningProgressController::class, 'getProgress']);

        // 附件下载（带审计日志）
        Route::get('/attachments/{attachment}/download', [\App\Http\Controllers\Api\AttachmentController::class, 'download'])->name('attachments.download');
        Route::post('/attachments/batch-download', [\App\Http\Controllers\Api\AttachmentController::class, 'batchDownload'])->name('attachments.batch-download');

        // 评论
        Route::get('/series/{series}/comments', [CommentController::class, 'index']);
        Route::post('/series/{series}/comments', [CommentController::class, 'store']);
        Route::put('/comments/{comment}', [CommentController::class, 'update']);
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
        Route::post('/comments/{comment}/like', [CommentController::class, 'toggleLike']);
        Route::get('/series/{series}/mention-users', [CommentController::class, 'mentionUsers']);

        // 通知
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::put('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

        // 考试
        Route::get('/exams', [\App\Http\Controllers\Api\ExamController::class, 'index']);
        Route::get('/exams/{exam}', [\App\Http\Controllers\Api\ExamController::class, 'show']);
        Route::post('/exams/{exam}/submit', [\App\Http\Controllers\Api\ExamRecordController::class, 'submit'])->name('exams.submit');
        Route::post('/exams/{exam}/cheat', [\App\Http\Controllers\Api\ExamRecordController::class, 'recordCheat']);
        Route::get('/exam-records/{examRecord}', [\App\Http\Controllers\Api\ExamRecordController::class, 'show']);
        Route::get('/exam-records/{examRecord}/wrong-questions', [\App\Http\Controllers\Api\ExamRecordController::class, 'wrongQuestions']);
        Route::get('/my-exam-records', [\App\Http\Controllers\Api\ExamRecordController::class, 'myRecords']);
    });

    // 导师路由
    Route::middleware('role:mentor,admin')->group(function () {
        Route::get('/mentor/pending-reviews', [\App\Http\Controllers\Api\MentorController::class, 'pendingReviews']);
        Route::get('/mentor/reviewed-records', [\App\Http\Controllers\Api\MentorController::class, 'reviewedRecords']);
        Route::post('/mentor/review/{examRecord}', [\App\Http\Controllers\Api\MentorController::class, 'review']);
    });

    // 管理员路由
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // 仪表盘
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        // 用户管理
        Route::get('/users/import-template', [AdminController::class, 'downloadImportTemplate']);
        Route::post('/users/import', [AdminController::class, 'importUsers']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::put('/users/{user}', [AdminController::class, 'updateUser']);
        Route::put('/users/{user}/status', [AdminController::class, 'updateUserStatus']);
        Route::put('/users/{user}/role', [AdminController::class, 'updateUserRole']);
        Route::put('/users/{user}/reset-password', [AdminController::class, 'resetPassword']);

        // 导师绑定管理
        Route::get('/mentor-bindings', [AdminController::class, 'mentorBindings']);
        Route::post('/mentor-bindings', [AdminController::class, 'createMentorBinding']);
        Route::delete('/mentor-bindings/{binding}', [AdminController::class, 'deleteMentorBinding']);

        // 分类管理
        Route::get('/categories', [AdminController::class, 'categories']);
        Route::post('/categories', [AdminController::class, 'createCategory']);
        Route::put('/categories/reorder', [AdminController::class, 'reorderCategories']);
        Route::put('/categories/{category}', [AdminController::class, 'updateCategory']);
        Route::delete('/categories/{category}', [AdminController::class, 'deleteCategory']);

        // 系列管理
        Route::get('/series', [AdminController::class, 'series']);
        Route::post('/series', [AdminController::class, 'createSeries']);
        Route::put('/series/{series}', [AdminController::class, 'updateSeries']);
        Route::put('/series/{series}/status', [AdminController::class, 'updateSeriesStatus']);
        Route::put('/series/{series}/courses/reorder', [AdminController::class, 'reorderSeriesCourses']);
        Route::delete('/series/{series}', [AdminController::class, 'deleteSeries']);

        // 课程管理
        Route::get('/courses/import-template', [AdminController::class, 'downloadCourseImportTemplate']);
        Route::post('/courses/import', [AdminController::class, 'importCourses']);
        Route::get('/courses', [AdminController::class, 'courses']);
        Route::get('/courses/{course}', [AdminController::class, 'getCourse']);
        Route::post('/courses', [AdminController::class, 'createCourse']);
        Route::put('/courses/{course}', [AdminController::class, 'updateCourse']);
        Route::put('/courses/{course}/status', [AdminController::class, 'updateCourseStatus']);
        Route::delete('/courses/{course}', [AdminController::class, 'deleteCourse']);

        // 附件管理
        Route::post('/courses/{course}/attachments', [AdminController::class, 'addAttachment']);
        Route::delete('/courses/{course}/attachments/{attachment}', [AdminController::class, 'deleteAttachment']);

        // 考试管理
        Route::get('/exams', [AdminController::class, 'exams']);
        Route::post('/exams', [AdminController::class, 'createExam']);
        Route::put('/exams/{exam}', [AdminController::class, 'updateExam']);
        Route::put('/exams/{exam}/status', [AdminController::class, 'updateExamStatus']);
        Route::delete('/exams/{exam}', [AdminController::class, 'deleteExam']);
        Route::get('/exams/{exam}/questions', [AdminController::class, 'examQuestions']);
        Route::post('/exams/{exam}/questions', [AdminController::class, 'createQuestion']);
        Route::put('/exams/{exam}/questions/{question}', [AdminController::class, 'updateQuestion']);
        Route::delete('/exams/{exam}/questions/{question}', [AdminController::class, 'deleteQuestion']);
        Route::get('/exams/import-template', [AdminController::class, 'downloadExamImportTemplate']);
        Route::post('/exams/import', [AdminController::class, 'importExams']);

        // 考试记录
        Route::get('/exam-records', [AdminController::class, 'examRecords']);
        Route::post('/exam-records/batch-assign', [AdminController::class, 'batchAssignReview']);
        Route::get('/exam-records/export', [AdminController::class, 'exportExamRecords']);

        // 审计日志
        Route::get('/audit-logs', [AdminController::class, 'auditLogs']);
        Route::get('/audit-logs/stats', [AdminController::class, 'auditLogStats']);

        // 学习进度管理
        Route::get('/learning-progress', [AdminController::class, 'learningProgress']);
        Route::get('/learning-progress/stats', [AdminController::class, 'learningStats']);

        // 待批改管理
        Route::get('/pending-reviews', [AdminController::class, 'pendingReviews']);
        Route::post('/assign-review/{examRecord}', [AdminController::class, 'assignReview']);

        // 评论管理
        Route::get('/comments', [AdminCommentController::class, 'index']);
        Route::put('/comments/{comment}', [AdminCommentController::class, 'update']);
        Route::delete('/comments/{comment}', [AdminCommentController::class, 'destroy']);
        Route::post('/comments/{comment}/reply', [AdminCommentController::class, 'reply']);

        // 系统设置
        Route::get('/settings', [AdminController::class, 'getSettings']);
        Route::put('/settings', [AdminController::class, 'updateSettings']);

        // 文件上传（旧接口，保留兼容）
        Route::post('/upload/file', [FileUploadController::class, 'uploadFile']);
        Route::post('/upload/init', [FileUploadController::class, 'uploadInit']);
        Route::post('/upload/chunk', [FileUploadController::class, 'uploadChunk']);
        Route::post('/upload/complete', [FileUploadController::class, 'uploadComplete']);
        Route::get('/upload/{uploadId}/status', [FileUploadController::class, 'uploadStatus']);
        Route::get('/upload/conversion-status', [FileUploadController::class, 'conversionStatus']);
        Route::delete('/upload/{uploadId}/cancel', [FileUploadController::class, 'cancelUpload']);

        // OSS 直传
        Route::post('/upload/presign', [FileUploadController::class, 'presign']);
        Route::post('/upload/multipart/init', [FileUploadController::class, 'multipartInit']);
        Route::post('/upload/multipart/sign', [FileUploadController::class, 'multipartSign']);
        Route::post('/upload/multipart/complete', [FileUploadController::class, 'multipartComplete']);
        Route::post('/upload/oss-complete', [FileUploadController::class, 'ossUploadComplete']);

        // SSO 登出
        Route::post('/sso/logout', [SsoController::class, 'logout']);
    });

    // SSO 管理接口（仅 admin）
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::post('/sso/sync-all', [SsoController::class, 'syncAllUsers']);
        Route::post('/sso/fill-uid', [SsoController::class, 'fillSsoUid']);
    });

    // SSO 中心拉取用户（需要 client_id + client_secret 验证，无需用户认证）
    Route::post('/sso/users/pull', [SsoController::class, 'pullUsers']);

});

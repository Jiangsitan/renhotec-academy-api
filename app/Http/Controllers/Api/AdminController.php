<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\AuditActionType;
use App\Enums\ExamRecordStatus;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\LearningProgress;
use App\Models\MentorStudent;
use App\Models\Question;
use App\Models\Series;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\NewCourseNotification;
use App\Services\AuditLogService;
use App\Services\ExamGradingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    public function __construct(
        private AuditLogService $auditService,
        private \App\Services\ExamGradingService $gradingService,
    ) {
    }

    // ==================== 仪表盘 ====================

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = [
            'total_users' => User::count(),
            'total_students' => User::where('role', 'student')->count(),
            'total_mentors' => User::where('role', 'mentor')->count(),
            'total_courses' => Course::count(),
            'published_courses' => Course::where('status', 'published')->count(),
            'total_exams' => Exam::count(),
            'active_exams' => Exam::where('status', 'active')->count(),
            'total_exam_records' => ExamRecord::count(),
            'pending_reviews' => ExamRecord::where('status', 'pending_review')->count(),
            'today_active_users' => AuditLog::whereDate('created_at', today())
                ->distinct('user_id')
                ->count('user_id'),
            'today_logins' => AuditLog::where('action_type', AuditActionType::Login)
                ->whereDate('created_at', today())
                ->count(),
        ];

        $recentLogs = AuditLog::with('user:id,name,employee_no')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => [
                'stats' => $stats,
                'recent_logs' => $recentLogs,
            ],
        ]);
    }

    // ==================== 用户管理 ====================

    public function users(Request $request): JsonResponse
    {
        $query = User::query()->with(['mentors' => function ($q) {
            $q->wherePivot('status', 'active')->select('users.id', 'users.name', 'users.employee_no');
        }]);

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('employee_no', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('department')) {
            $query->where('department', $request->input('department'));
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['data' => $users]);
    }

    public function createUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'employee_no' => 'required|string|max:50|unique:users',
            'email' => 'nullable|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => ['required', 'string', Password::min(6)],
            'department' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'role' => 'required|in:student,mentor,admin',
            'hire_date' => 'nullable|date',
            'trial_end_date' => 'nullable|date|after_or_equal:hire_date',
            'mentor_id' => 'nullable|exists:users,id',
        ]);

        $mentorId = $validated['mentor_id'] ?? null;
        unset($validated['mentor_id']);

        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = 'active';

        $user = User::create($validated);

        // 自动绑定导师
        if ($mentorId && $validated['role'] === 'student') {
            $mentor = User::find($mentorId);
            if ($mentor && in_array($mentor->role->value, ['mentor', 'admin'])) {
                $user->mentors()->syncWithoutDetaching([$mentorId => ['status' => 'active']]);
            }
        }

        $this->auditService->log(
            user: $request->user(),
            actionType: AuditActionType::CreateUser,
            targetType: 'user',
            targetId: $user->id,
            request: $request,
            extraData: ['employee_no' => $user->employee_no, 'role' => $user->role->value]
        );

        return response()->json([
            'message' => '用户创建成功',
            'data' => $user->load('mentors'),
        ], 201);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'hire_date' => 'nullable|date',
            'trial_end_date' => 'nullable|date',
            'mentor_id' => 'nullable|exists:users,id',
        ]);

        $mentorId = $validated['mentor_id'] ?? null;
        unset($validated['mentor_id']);

        $user->update($validated);

        // 更新导师绑定（仅学员角色）
        if ($mentorId !== null && $user->role->value === 'student') {
            // 先移除旧的活跃绑定
            $user->mentors()->wherePivot('status', 'active')->detach();
            // 绑定新导师
            if ($mentorId) {
                $mentor = User::find($mentorId);
                if ($mentor && in_array($mentor->role->value, ['mentor', 'admin'])) {
                    $user->mentors()->syncWithoutDetaching([$mentorId => ['status' => 'active']]);
                }
            }
        }

        $this->auditService->log(
            user: $request->user(),
            actionType: AuditActionType::UpdateUser,
            targetType: 'user',
            targetId: $user->id,
            request: $request
        );

        return response()->json([
            'message' => '用户信息已更新',
            'data' => $user->fresh()->load('mentors'),
        ]);
    }

    public function updateUserStatus(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,locked',
        ]);

        $oldStatus = $user->status;
        $user->update(['status' => $validated['status']]);

        if ($validated['status'] === 'inactive' || $validated['status'] === 'locked') {
            $this->auditService->log(
                user: $request->user(),
                actionType: AuditActionType::DisableUser,
                targetType: 'user',
                targetId: $user->id,
                request: $request,
                extraData: ['old_status' => $oldStatus, 'new_status' => $validated['status']]
            );
        }

        return response()->json([
            'message' => '用户状态已更新',
            'data' => $user->fresh(),
        ]);
    }

    public function updateUserRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'required|in:student,mentor,admin',
        ]);

        $user->update(['role' => $validated['role']]);

        return response()->json([
            'message' => '用户角色已更新',
            'data' => $user->fresh(),
        ]);
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', Password::min(6)],
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        $this->auditService->log(
            user: $request->user(),
            actionType: AuditActionType::ResetPassword,
            targetType: 'user',
            targetId: $user->id,
            request: $request
        );

        return response()->json(['message' => '密码已重置']);
    }

    // ==================== 导师绑定管理 ====================

    public function mentorBindings(Request $request): JsonResponse
    {
        $query = MentorStudent::with(['mentor:id,name,employee_no,department', 'student:id,name,employee_no,department,trial_end_date']);

        if ($request->filled('mentor_id')) {
            $query->where('mentor_id', $request->input('mentor_id'));
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $bindings = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['data' => $bindings]);
    }

    public function createMentorBinding(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mentor_id' => 'required|exists:users,id',
            'student_id' => 'required|exists:users,id|different:mentor_id',
            'trial_end_date' => 'nullable|date',
        ]);

        $mentor = User::findOrFail($validated['mentor_id']);
        $student = User::findOrFail($validated['student_id']);

        if ($mentor->role->value !== 'mentor' && $mentor->role->value !== 'admin') {
            return response()->json(['message' => '导师用户角色必须是 mentor 或 admin'], 422);
        }

        if ($student->role->value !== 'student') {
            return response()->json(['message' => '学员用户角色必须是 student'], 422);
        }

        $exists = MentorStudent::where('mentor_id', $validated['mentor_id'])
            ->where('student_id', $validated['student_id'])
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            return response()->json(['message' => '该导师-学员绑定关系已存在'], 422);
        }

        $binding = MentorStudent::create([
            'mentor_id' => $validated['mentor_id'],
            'student_id' => $validated['student_id'],
            'status' => 'active',
        ]);

        if (!empty($validated['trial_end_date'])) {
            $student->update(['trial_end_date' => $validated['trial_end_date']]);
        }

        $this->auditService->log(
            user: $request->user(),
            actionType: AuditActionType::CreateMentorBinding,
            targetType: 'mentor_student',
            targetId: $binding->id,
            request: $request,
            extraData: ['mentor' => $mentor->name, 'student' => $student->name]
        );

        return response()->json([
            'message' => '导师绑定成功',
            'data' => $binding->load(['mentor:id,name,employee_no', 'student:id,name,employee_no,trial_end_date']),
        ], 201);
    }

    public function deleteMentorBinding(Request $request, MentorStudent $binding): JsonResponse
    {
        $binding->update(['status' => 'inactive']);

        $this->auditService->log(
            user: $request->user(),
            actionType: AuditActionType::DeleteMentorBinding,
            targetType: 'mentor_student',
            targetId: $binding->id,
            request: $request
        );

        return response()->json(['message' => '导师绑定已解除']);
    }

    // ==================== 分类管理 ====================

    public function categories(): JsonResponse
    {
        $categories = Category::with('parent')
            ->withCount('series')
            ->withCount('children')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $categories]);
    }

    public function createCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // 验证：二级分类的 parent 必须是一级分类（parent_id 为 null）
        if (!empty($validated['parent_id'])) {
            $parent = Category::findOrFail($validated['parent_id']);
            if ($parent->parent_id !== null) {
                return response()->json(['message' => '最多支持两级分类'], 422);
            }
        }

        $category = Category::create($validated);

        return response()->json([
            'message' => '分类创建成功',
            'data' => $category,
        ], 201);
    }

    public function updateCategory(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'parent_id' => 'sometimes|nullable|exists:categories,id',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        // 防止将分类设为自己的子分类
        if (isset($validated['parent_id']) && $validated['parent_id'] == $category->id) {
            return response()->json(['message' => '不能将分类设为自己的子分类'], 422);
        }

        // 防止循环引用（不能将分类设为自己子分类的子分类）
        if (isset($validated['parent_id']) && $validated['parent_id']) {
            $children = Category::where('parent_id', $category->id)->pluck('id')->toArray();
            if (in_array($validated['parent_id'], $children)) {
                return response()->json(['message' => '不能将分类设为自己子分类的子分类'], 422);
            }
        }

        $category->update($validated);

        return response()->json([
            'message' => '分类已更新',
            'data' => $category->fresh(),
        ]);
    }

    public function deleteCategory(Request $request, Category $category): JsonResponse
    {
        // 检查是否有子分类
        $hasChildren = Category::where('parent_id', $category->id)->exists();
        if ($hasChildren) {
            return response()->json(['message' => '该分类下存在子分类，无法删除'], 422);
        }

        $hasSeries = Series::where('category_id', $category->id)->exists();
        if ($hasSeries) {
            return response()->json(['message' => '该分类下存在系列，无法删除'], 422);
        }

        $category->delete();

        return response()->json(['message' => '分类已删除']);
    }

    // ==================== 系列管理 ====================

    public function series(Request $request): JsonResponse
    {
        $query = Series::with('category')->withCount('courses');

        if ($request->filled('keyword')) {
            $query->where('name', 'like', '%' . $request->input('keyword') . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $series = $query->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['data' => $series]);
    }

    public function createSeries(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'cover_image' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['status'] = 'draft';

        $series = Series::create($validated);

        return response()->json([
            'message' => '系列创建成功',
            'data' => $series->load('category'),
        ], 201);
    }

    public function updateSeries(Request $request, Series $series): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:200',
            'description' => 'nullable|string',
            'category_id' => 'sometimes|exists:categories,id',
            'cover_image' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $series->update($validated);

        return response()->json([
            'message' => '系列已更新',
            'data' => $series->fresh()->load('category'),
        ]);
    }

    public function updateSeriesStatus(Request $request, Series $series): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,published',
        ]);

        $series->update(['status' => $validated['status']]);

        return response()->json([
            'message' => '系列状态已更新',
            'data' => $series->fresh(),
        ]);
    }

    public function deleteSeries(Request $request, Series $series): JsonResponse
    {
        $hasCourses = Course::where('series_id', $series->id)->exists();

        if ($hasCourses) {
            return response()->json(['message' => '该系列下存在视频，无法删除'], 422);
        }

        $series->delete();

        return response()->json(['message' => '系列已删除']);
    }

    // ==================== 课程管理 ====================

    public function courses(Request $request): JsonResponse
    {
        $query = Course::with(['category', 'series']);

        if ($request->filled('keyword')) {
            $query->where('title', 'like', '%' . $request->input('keyword') . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('series_id')) {
            $query->where('series_id', $request->input('series_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $courses = $query->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['data' => $courses]);
    }

    public function getCourse(Course $course): JsonResponse
    {
        $course->load(['category', 'series', 'mentor', 'attachments']);

        return response()->json(['data' => $course]);
    }

    public function createCourse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'series_id' => 'required|exists:series,id',
            'mentor_id' => 'nullable|exists:users,id',
            'type' => 'required|in:document,video',
            'content_source' => 'required|in:online,local',
            'content_url' => 'required|string|max:1000',
            'content_type' => 'nullable|string|in:pdf,images,video',
            'images' => 'nullable|array',
            'cover_image' => 'nullable|string|max:500',
            'file_name' => 'nullable|string|max:255',
            'file_size' => 'nullable|integer|min:0',
            'min_read_time' => 'nullable|integer|min:1',
            'duration' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // 自动从系列获取分类 ID
        $series = Series::findOrFail($validated['series_id']);
        $validated['category_id'] = $series->category_id;

        if ($validated['type'] === 'document') {
            $validated['duration'] = 0;
        }
        // 视频课程保留 min_read_time（用于 DocumentReader 完成机制）

        $validated['status'] = 'draft';

        $course = Course::create($validated);

        $this->auditService->log(
            user: $request->user(),
            actionType: AuditActionType::CreateCourse,
            targetType: 'course',
            targetId: $course->id,
            request: $request,
            extraData: ['title' => $course->title, 'content_source' => $validated['content_source']]
        );

        return response()->json([
            'message' => '课程创建成功',
            'data' => $course->load('category'),
        ], 201);
    }

    public function updateCourse(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:200',
            'description' => 'nullable|string',
            'series_id' => 'sometimes|exists:series,id',
            'mentor_id' => 'nullable|exists:users,id',
            'type' => 'sometimes|in:document,video',
            'content_source' => 'sometimes|in:online,local',
            'content_url' => 'sometimes|string|max:1000',
            'content_type' => 'nullable|string|max:50',
            'images' => 'nullable|array',
            'cover_image' => 'nullable|string|max:500',
            'file_name' => 'nullable|string|max:255',
            'file_size' => 'nullable|integer|min:0',
            'min_read_time' => 'nullable|integer|min:1',
            'duration' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // 如果更新了系列，自动更新分类
        if (isset($validated['series_id'])) {
            $series = Series::findOrFail($validated['series_id']);
            $validated['category_id'] = $series->category_id;
        }

        // 如果更新了 content_url，清理旧文件
        if (isset($validated['content_url']) && $validated['content_url'] !== $course->content_url) {
            $this->cleanupOldCourseFiles($course);
        }

        $course->update($validated);

        $this->auditService->log(
            user: $request->user(),
            actionType: AuditActionType::UpdateCourse,
            targetType: 'course',
            targetId: $course->id,
            request: $request
        );

        return response()->json([
            'message' => '课程已更新',
            'data' => $course->fresh()->load('category'),
        ]);
    }

    /**
     * 清理课程的旧文件
     */
    private function cleanupOldCourseFiles(Course $course): void
    {
        $disk = \Storage::disk('oss');
        $filesToDelete = [];

        // 清理旧的 content_url 文件
        if ($course->content_url) {
            $filesToDelete[] = $course->content_url;
        }

        // 清理旧的 images 文件（WebP 图片序列）
        if ($course->images && is_array($course->images)) {
            $filesToDelete = array_merge($filesToDelete, $course->images);
        }

        // 批量删除文件
        if (!empty($filesToDelete)) {
            $disk->delete($filesToDelete);
            \Log::info('已清理课程旧文件', [
                'course_id' => $course->id,
                'files' => $filesToDelete,
            ]);
        }
    }

    public function updateCourseStatus(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,published',
        ]);

        $course->update(['status' => $validated['status']]);

        // 新课程发布时，通知已学习该系列的学员
        if ($validated['status'] === 'published' && $course->series) {
            $series = $course->series;
            $students = User::where('role', 'student')
                ->where('status', 'active')
                ->whereHas('learningProgress', function ($q) use ($series) {
                    $q->whereHas('course', function ($cq) use ($series) {
                        $cq->where('series_id', $series->id);
                    });
                })
                ->get();

            foreach ($students as $student) {
                // 检查是否已通知过（避免重复通知）
                $alreadyNotified = $student->notifications()
                    ->where('data->type', 'new_course')
                    ->where('data->course_id', $course->id)
                    ->exists();

                if (!$alreadyNotified) {
                    $student->notify(new NewCourseNotification($course, $series));
                }
            }
        }

        return response()->json([
            'message' => '课程状态已更新',
            'data' => $course->fresh(),
        ]);
    }

    public function deleteCourse(Request $request, Course $course): JsonResponse
    {
        $hasProgress = \App\Models\LearningProgress::where('course_id', $course->id)->exists();

        if ($hasProgress) {
            return response()->json(['message' => '该课程已有学员学习记录，建议归档而非删除'], 422);
        }

        $course->delete();

        $this->auditService->log(
            user: $request->user(),
            actionType: AuditActionType::DeleteCourse,
            targetType: 'course',
            targetId: $course->id,
            request: $request
        );

        return response()->json(['message' => '课程已删除']);
    }

    // ==================== 附件管理 ====================

    public function addAttachment(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'file_name' => 'required|string|max:255',
            'file_path' => 'required|string|max:500',
            'file_size' => 'required|integer|min:0',
            'mime_type' => 'nullable|string|max:100',
        ]);

        $attachment = $course->attachments()->create($validated);

        return response()->json([
            'message' => '附件已添加',
            'data' => $attachment,
        ], 201);
    }

    public function deleteAttachment(Request $request, Course $course, Attachment $attachment): JsonResponse
    {
        if ($attachment->course_id !== $course->id) {
            return response()->json(['message' => '附件不属于该课程'], 422);
        }

        // 删除文件（从 OSS）
        if (\Storage::disk('oss')->exists($attachment->file_path)) {
            \Storage::disk('oss')->delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->json(['message' => '附件已删除']);
    }

    // ==================== 考试管理 ====================

    public function exams(Request $request): JsonResponse
    {
        $query = Exam::withCount('questions')->with('courses.series');

        if ($request->filled('keyword')) {
            $query->where('title', 'like', '%' . $request->input('keyword') . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $exams = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['data' => $exams]);
    }

    public function createExam(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
            'time_limit' => 'required|integer|min:1',
            'passing_score' => 'required|numeric|min:0|max:100',
        ]);

        $courseIds = $validated['course_ids'] ?? [];
        unset($validated['course_ids']);

        $validated['status'] = 'draft';

        $exam = Exam::create($validated);

        if (!empty($courseIds)) {
            $exam->courses()->sync($courseIds);
        }

        $this->auditService->log(
            user: $request->user(),
            actionType: AuditActionType::CreateExam,
            targetType: 'exam',
            targetId: $exam->id,
            request: $request
        );

        return response()->json([
            'message' => '考试创建成功',
            'data' => $exam->load('courses.series'),
        ], 201);
    }

    public function updateExam(Request $request, Exam $exam): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:200',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
            'time_limit' => 'sometimes|integer|min:1',
            'passing_score' => 'sometimes|numeric|min:0|max:100',
        ]);

        $courseIds = $validated['course_ids'] ?? null;
        unset($validated['course_ids']);

        $exam->update($validated);

        if ($courseIds !== null) {
            $exam->courses()->sync($courseIds);
        }

        $this->auditService->log(
            user: $request->user(),
            actionType: AuditActionType::UpdateExam,
            targetType: 'exam',
            targetId: $exam->id,
            request: $request
        );

        return response()->json([
            'message' => '考试已更新',
            'data' => $exam->fresh()->load('courses.series'),
        ]);
    }

    public function updateExamStatus(Request $request, Exam $exam): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,active',
        ]);

        $exam->update(['status' => $validated['status']]);

        return response()->json([
            'message' => '考试状态已更新',
            'data' => $exam->fresh(),
        ]);
    }

    public function deleteExam(Request $request, Exam $exam): JsonResponse
    {
        $hasRecords = ExamRecord::where('exam_id', $exam->id)->exists();

        if ($hasRecords) {
            return response()->json(['message' => '该考试已有答卷记录，建议归档而非删除'], 422);
        }

        $exam->delete();

        $this->auditService->log(
            user: $request->user(),
            actionType: AuditActionType::DeleteExam,
            targetType: 'exam',
            targetId: $exam->id,
            request: $request
        );

        return response()->json(['message' => '考试已删除']);
    }

    public function examQuestions(Exam $exam): JsonResponse
    {
        $questions = $exam->questions()->orderBy('sort_order')->get();

        return response()->json(['data' => $questions]);
    }

    public function createQuestion(Request $request, Exam $exam): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:single,multiple,truefalse,short_answer,fill_blank',
            'content' => 'required|string',
            'options' => 'nullable|array',
            'options.*.key' => 'required|string',
            'options.*.value' => 'required|string',
            'correct_answer' => 'required_unless:type,short_answer|nullable',
            'score' => 'required|numeric|min:0',
            'course_id' => 'nullable|exists:courses,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['exam_id'] = $exam->id;

        // 检查总分是否超过 100
        $currentTotal = $exam->questions()->sum('score');
        $newScore = $validated['score'] ?? 0;

        if ($currentTotal + $newScore > 100) {
            return response()->json([
                'message' => "总分不能超过 100 分（当前：{$currentTotal} 分，新增：{$newScore} 分）",
            ], 422);
        }

        if ($validated['type'] === 'short_answer') {
            $validated['options'] = null;
            $validated['correct_answer'] = $validated['correct_answer'] ?: null;
        }

        if ($validated['type'] === 'fill_blank') {
            $validated['options'] = null;
            // correct_answer 存储 JSON 数组，如 ["前锁","前锁","后锁","后锁"]
            if (is_string($validated['correct_answer'])) {
                // 支持逗号或中文逗号分隔
                $parts = preg_split('/[,，]/', $validated['correct_answer']);
                $validated['correct_answer'] = json_encode(array_map('trim', array_filter($parts)));
            }
        }

        $question = \App\Models\Question::create($validated);

        return response()->json([
            'message' => '题目添加成功',
            'data' => $question,
        ], 201);
    }

    public function updateQuestion(Request $request, Exam $exam, \App\Models\Question $question): JsonResponse
    {
        if ($question->exam_id !== $exam->id) {
            return response()->json(['message' => '题目不属于该考试'], 403);
        }

        $validated = $request->validate([
            'type' => 'sometimes|in:single,multiple,truefalse,short_answer,fill_blank',
            'content' => 'sometimes|string',
            'options' => 'nullable|array',
            'options.*.key' => 'required|string',
            'options.*.value' => 'required|string',
            'correct_answer' => 'sometimes|nullable',
            'score' => 'sometimes|numeric|min:0',
            'course_id' => 'nullable|exists:courses,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // 填空题：将逗号分隔的答案转为 JSON 数组
        if (isset($validated['type']) && $validated['type'] === 'fill_blank' && isset($validated['correct_answer']) && is_string($validated['correct_answer'])) {
            // 支持逗号或中文逗号分隔
            $parts = preg_split('/[,，]/', $validated['correct_answer']);
            $validated['correct_answer'] = json_encode(array_map('trim', array_filter($parts)));
        } elseif (isset($validated['correct_answer']) && is_string($validated['correct_answer']) && isset($question->type) && $question->type === 'fill_blank') {
            // 支持逗号或中文逗号分隔
            $parts = preg_split('/[,，]/', $validated['correct_answer']);
            $validated['correct_answer'] = json_encode(array_map('trim', array_filter($parts)));
        }

        $question->update($validated);

        return response()->json([
            'message' => '题目已更新',
            'data' => $question->fresh(),
        ]);
    }

    public function deleteQuestion(Request $request, Exam $exam, \App\Models\Question $question): JsonResponse
    {
        if ($question->exam_id !== $exam->id) {
            return response()->json(['message' => '题目不属于该考试'], 403);
        }

        $question->delete();

        return response()->json(['message' => '题目已删除']);
    }

    public function examRecords(Request $request): JsonResponse
    {
        $query = ExamRecord::with([
            'user:id,name,employee_no,department',
            'exam:id,title,passing_score',
            'assignee:id,name',
        ]);

        // 状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // 部门筛选
        if ($request->filled('department')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('department', $request->input('department'));
            });
        }

        // 关键词搜索
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->whereHas('user', function ($uq) use ($keyword) {
                    $uq->where('name', 'like', "%{$keyword}%")
                        ->orWhere('employee_no', 'like', "%{$keyword}%");
                })->orWhereHas('exam', function ($eq) use ($keyword) {
                    $eq->where('title', 'like', "%{$keyword}%");
                });
            });
        }

        // 作弊筛选
        if ($request->input('cheat_filter') === 'has_cheats') {
            $query->has('cheats');
        } elseif ($request->input('cheat_filter') === 'no_cheats') {
            $query->doesntHave('cheats');
        }

        // 添加作弊次数
        $query->withCount('cheats as cheat_count');

        $records = $query->orderBy('submitted_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['data' => $records]);
    }

    // ==================== 审计日志 ====================

    public function auditLogs(Request $request): JsonResponse
    {
        $query = AuditLog::with('user:id,name,employee_no');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->input('action_type'));
        }

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->input('target_type'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json(['data' => $logs]);
    }

    public function auditLogStats(Request $request): JsonResponse
    {
        $today = today();

        $stats = [
            'today_total' => AuditLog::whereDate('created_at', $today)->count(),
            'today_logins' => AuditLog::where('action_type', AuditActionType::Login)
                ->whereDate('created_at', $today)->count(),
            'today_downloads' => AuditLog::where('action_type', AuditActionType::DownloadAttachment)
                ->whereDate('created_at', $today)->count(),
            'today_completions' => AuditLog::where('action_type', AuditActionType::CompleteCourse)
                ->whereDate('created_at', $today)->count(),
            'today_exams' => AuditLog::where('action_type', AuditActionType::SubmitExam)
                ->whereDate('created_at', $today)->count(),
        ];

        $actionCounts = AuditLog::select('action_type', DB::raw('count(*) as count'))
            ->groupBy('action_type')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'data' => [
                'stats' => $stats,
                'action_counts' => $actionCounts,
            ],
        ]);
    }

    // ==================== 待批改管理 ====================

    public function pendingReviews(Request $request): JsonResponse
    {
        $query = ExamRecord::with(['user:id,name,employee_no', 'exam:id,title', 'assignee:id,name,employee_no'])
            ->where('status', ExamRecordStatus::PendingReview);

        if ($request->filled('assigned_to')) {
            if ($request->input('assigned_to') === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->input('assigned_to'));
            }
        }

        $records = $query->orderBy('submitted_at', 'asc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['data' => $records]);
    }

    public function assignReview(Request $request, ExamRecord $examRecord): JsonResponse
    {
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'note' => 'nullable|string|max:500',
        ]);

        $reviewer = User::findOrFail($validated['assigned_to']);

        if (!in_array($reviewer->role->value, ['mentor', 'admin'])) {
            return response()->json(['message' => '批改人必须是导师或管理员'], 422);
        }

        $this->gradingService->assignReviewer($examRecord, $reviewer, $validated['note'] ?? null);

        return response()->json([
            'message' => '已分配批改人',
            'data' => $examRecord->fresh(),
        ]);
    }

    // ==================== 系统设置 ====================

    public function getSettings(): JsonResponse
    {
        $settings = Setting::all()->mapWithKeys(fn ($s) => [$s->key => $s->value]);

        return response()->json(['data' => $settings]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|max:100',
            'settings.*.value' => 'nullable|string',
        ]);

        foreach ($validated['settings'] as $item) {
            Setting::setValue($item['key'], $item['value'] ?? '');
        }

        return response()->json(['message' => '设置已保存']);
    }

    // ==================== 批量导入 ====================

    public function downloadImportTemplate()
    {
        $header = ['工号', '姓名', '邮箱', '手机', '密码', '角色', '部门', '岗位', '入职日期', '试用期截止', '导师工号'];
        $example = ['EMP001', '张三', 'zhangsan@example.com', '13800138000', '123456', 'student', '技术部', '工程师', '2024-01-15', '2024-07-15', 'MENTOR001'];

        $csv = implode(',', $header) . "\n" . implode(',', $example) . "\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="user_import_template.csv"',
        ]);
    }

    public function importUsers(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');

        if (!$handle) {
            return response()->json(['message' => '无法读取文件'], 422);
        }

        // 读取表头
        $header = fgetcsv($handle);
        if (!$header || count($header) < 6) {
            fclose($handle);
            return response()->json(['message' => 'CSV 格式不正确，至少需要 6 列'], 422);
        }

        // 列映射
        $columnMap = [
            '工号' => 0, '姓名' => 1, '邮箱' => 2, '手机' => 3,
            '密码' => 4, '角色' => 5, '部门' => 6, '岗位' => 7,
            '入职日期' => 8, '试用期截止' => 9, '导师工号' => 10,
        ];

        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            // 跳过空行
            if (empty(array_filter($row))) continue;

            try {
                // 基本验证
                $employeeNo = trim($row[$columnMap['工号']] ?? '');
                $name = trim($row[$columnMap['姓名']] ?? '');
                $email = trim($row[$columnMap['邮箱']] ?? '');
                $phone = trim($row[$columnMap['手机']] ?? '');
                $password = trim($row[$columnMap['密码']] ?? '');
                $role = trim($row[$columnMap['角色']] ?? 'student');
                $department = trim($row[$columnMap['部门']] ?? '');
                $position = trim($row[$columnMap['岗位']] ?? '');
                $hireDate = trim($row[$columnMap['入职日期']] ?? '');
                $trialEndDate = trim($row[$columnMap['试用期截止']] ?? '');
                $mentorEmployeeNo = trim($row[$columnMap['导师工号']] ?? '');

                // 必填验证
                if (!$employeeNo || !$name || !$role) {
                    throw new \Exception('工号、姓名、角色为必填项');
                }

                // 角色验证
                if (!in_array($role, ['student', 'mentor', 'admin'])) {
                    throw new \Exception('角色必须是 student/mentor/admin');
                }

                // 查找已有用户
                $existingUser = User::where('employee_no', $employeeNo)->first();

                if ($existingUser) {
                    // 部分更新：只更新提供的字段
                    $updateData = [];
                    if ($name) $updateData['name'] = $name;
                    if ($email) {
                        $emailConflict = User::where('email', $email)->where('id', '!=', $existingUser->id)->exists();
                        if ($emailConflict) {
                            throw new \Exception("邮箱 {$email} 已被其他用户使用");
                        }
                        $updateData['email'] = $email;
                    }
                    if ($phone) $updateData['phone'] = $phone;
                    if ($password) $updateData['password'] = Hash::make($password);
                    if ($role) $updateData['role'] = $role;
                    if ($department) $updateData['department'] = $department;
                    if ($position) $updateData['position'] = $position;
                    if ($hireDate) $updateData['hire_date'] = $hireDate;
                    if ($trialEndDate) $updateData['trial_end_date'] = $trialEndDate;

                    if (!empty($updateData)) {
                        $existingUser->update($updateData);
                    }

                    // 更新导师绑定
                    if ($mentorEmployeeNo && $role === 'student') {
                        $mentor = User::where('employee_no', $mentorEmployeeNo)
                            ->whereIn('role', ['mentor', 'admin'])
                            ->first();
                        if ($mentor) {
                            $existingUser->mentors()->syncWithoutDetaching([$mentor->id => ['status' => 'active']]);
                        }
                    }

                    $updated++;
                } else {
                    // 检查邮箱唯一性（仅在提供邮箱时）
                    if ($email && User::where('email', $email)->exists()) {
                        throw new \Exception("邮箱 {$email} 已被其他用户使用");
                    }

                    // 创建新用户
                    $user = User::create([
                        'name' => $name,
                        'employee_no' => $employeeNo,
                        'email' => $email,
                        'phone' => $phone ?: null,
                        'password' => Hash::make($password ?: '123456'),
                        'role' => $role,
                        'department' => $department ?: null,
                        'position' => $position ?: null,
                        'hire_date' => $hireDate ?: null,
                        'trial_end_date' => $trialEndDate ?: null,
                        'status' => 'active',
                    ]);

                    // 绑定导师
                    if ($mentorEmployeeNo && $role === 'student') {
                        $mentor = User::where('employee_no', $mentorEmployeeNo)
                            ->whereIn('role', ['mentor', 'admin'])
                            ->first();
                        if ($mentor) {
                            $user->mentors()->syncWithoutDetaching([$mentor->id => ['status' => 'active']]);
                        }
                    }

                    $created++;
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = ['row' => $rowNum, 'message' => $e->getMessage()];
            }
        }

        fclose($handle);

        return response()->json([
            'message' => "导入完成：创建 {$created} 条，更新 {$updated} 条，失败 {$failed} 条",
            'data' => [
                'created' => $created,
                'updated' => $updated,
                'failed' => $failed,
                'errors' => $errors,
            ],
        ]);
    }

    // ==================== 学习进度管理 ====================

    public function learningProgress(Request $request): JsonResponse
    {
        $query = \App\Models\LearningProgress::with(['user', 'course']);

        // 按课程筛选
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->input('course_id'));
        }

        // 按学员筛选
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // 按完成状态筛选
        if ($request->filled('is_completed')) {
            $query->where('is_completed', $request->boolean('is_completed'));
        }

        // 按部门筛选
        if ($request->filled('department')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('department', $request->input('department'));
            });
        }

        // 按关键词搜索（学员姓名/工号/课程标题）
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->whereHas('user', function ($uq) use ($keyword) {
                    $uq->where('name', 'like', "%{$keyword}%")
                        ->orWhere('employee_no', 'like', "%{$keyword}%");
                })->orWhereHas('course', function ($cq) use ($keyword) {
                    $cq->where('title', 'like', "%{$keyword}%");
                });
            });
        }

        $progresses = $query->orderBy('updated_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['data' => $progresses]);
    }

    public function learningStats(): JsonResponse
    {
        $totalStudents = \App\Models\User::where('role', 'student')->count();
        $totalCourses = \App\Models\Course::where('status', 'published')->count();
        $totalCompleted = \App\Models\LearningProgress::where('is_completed', true)->count();
        $totalLearningTime = \App\Models\LearningProgress::where('is_completed', true)->sum('total_learning_time');

        // 课程完成率
        $totalProgressRecords = \App\Models\LearningProgress::count();
        $completionRate = $totalProgressRecords > 0
            ? round(($totalCompleted / $totalProgressRecords) * 100, 1)
            : 0;

        return response()->json([
            'data' => [
                'total_students' => $totalStudents,
                'total_courses' => $totalCourses,
                'total_completed' => $totalCompleted,
                'total_learning_time' => $totalLearningTime,
                'completion_rate' => $completionRate,
            ],
        ]);
    }

    // ==================== 课程批量导入 ====================

    public function downloadCourseImportTemplate()
    {
        $lines = [
            '==================== 课程批量导入模板 ====================',
            '使用说明：',
            '1. 将本 CSV 文件和课程文件放在同一个 ZIP 压缩包中',
            '2. 视频文件建议放在 videos/ 文件夹，文档文件建议放在 documents/ 文件夹',
            '3. 压缩包格式：.zip，最大支持 500MB',
            '4. 系统会自动上传文件并创建课程记录',
            '',
            '=================== 字段说明 ===================',
            '系列ID：必填。课程所属系列的ID号。可在"系列管理"页面查看。',
            '课程标题：必填。课程名称，最大200字符。',
            '课程简介：选填。课程的简要描述。',
            '类型：必填。video（视频）或 document（文档）。',
            '内容来源：必填。online（在线链接）或 local（本地文件）。',
            '内容地址：必填。在线填写URL；本地填写ZIP包内的相对路径（如 videos/xxx.mp4）。',
            '封面图：选填。课程封面图片URL。',
            '导师工号：选填。绑定的导师工号，需系统中已存在。',
            '最低学习时长(秒)：选填。默认30秒。',
            '排序：选填。默认0。',
            '',
            '=================== 示例数据 ===================',
            '系列ID,课程标题,课程简介,类型,内容来源,内容地址,封面图,导师工号,最低学习时长(秒),排序',
            '1,产品知识培训第1集,本课程介绍产品基础知识,video,online,https://www.youtube.com/watch?v=xxx,,EMP001,30,1',
            '1,产品知识培训第2集,本课程深入讲解产品特性,video,online,https://vimeo.com/123456,,EMP001,60,2',
            '1,公司规章制度手册,公司规章制度文档,document,online,https://example.com/handbook.pdf,,,120,3',
            '2,销售技巧培训视频,内部培训视频,video,local,videos/销售技巧培训.mp4,,,180,1',
            '2,产品说明书,产品详细说明文档,document,local,documents/产品说明书.pdf,,,60,2',
        ];

        $csv = implode("\n", $lines) . "\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="course_import_template.csv"',
        ]);
    }

    public function importCourses(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:512000', // 500MB
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'zip') {
            return $this->importFromZip($file);
        } elseif (in_array($extension, ['csv', 'txt'])) {
            return $this->importFromCsv($file, null);
        } else {
            return response()->json(['message' => '请上传 .csv 或 .zip 文件'], 422);
        }
    }

    private function importFromZip($zipFile): JsonResponse
    {
        $tempDir = storage_path('app/temp/import_' . uniqid());

        try {
            // 解压 ZIP
            $zip = new \ZipArchive();
            if ($zip->open($zipFile->getRealPath()) !== true) {
                return response()->json(['message' => '无法打开 ZIP 文件'], 422);
            }
            $zip->extractTo($tempDir);
            $zip->close();

            // 查找 CSV 文件
            $csvFile = $this->findCsvFile($tempDir);
            if (!$csvFile) {
                return response()->json(['message' => 'ZIP 包中未找到 CSV 文件'], 422);
            }

            // 处理 CSV
            return $this->importFromCsv(new \Illuminate\Http\UploadedFile(
                $csvFile,
                basename($csvFile),
                mime_content_type($csvFile),
                null,
                true // 测试模式，不检查上传错误
            ), $tempDir);
        } finally {
            // 清理临时目录
            $this->deleteDirectory($tempDir);
        }
    }

    private function findCsvFile(string $dir): ?string
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), ['csv', 'txt'])) {
                return $file->getRealPath();
            }
        }
        return null;
    }

    private function importFromCsv($file, ?string $zipTempDir): JsonResponse
    {
        $handle = fopen($file->getPathname(), 'r');

        if (!$handle) {
            return response()->json(['message' => '无法读取文件'], 422);
        }

        // 查找表头行（跳过说明行）
        $header = null;
        while (($row = fgetcsv($handle)) !== false) {
            // 跳过空行
            if (empty(array_filter($row))) continue;
            // 跳过说明行（以 = 开头）
            if (str_starts_with(trim($row[0]), '=')) continue;
            // 第一个非说明行作为表头
            $header = $row;
            break;
        }

        if (!$header || count($header) < 6) {
            fclose($handle);
            return response()->json(['message' => 'CSV 格式不正确，至少需要 6 列'], 422);
        }

        // 列映射
        $columnMap = [
            '系列ID' => 0, '课程标题' => 1, '课程简介' => 2,
            '类型' => 3, '内容来源' => 4, '内容地址' => 5,
            '封面图' => 6, '导师工号' => 7, '最低学习时长(秒)' => 8, '排序' => 9,
        ];

        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            // 跳过空行和说明行
            if (empty(array_filter($row))) continue;
            if (str_starts_with(trim($row[0]), '=')) continue;

            try {
                $seriesId = trim($row[$columnMap['系列ID']] ?? '');
                $title = trim($row[$columnMap['课程标题']] ?? '');
                $description = trim($row[$columnMap['课程简介']] ?? '');
                $type = trim($row[$columnMap['类型']] ?? 'video');
                $contentSource = trim($row[$columnMap['内容来源']] ?? 'online');
                $contentUrl = trim($row[$columnMap['内容地址']] ?? '');
                $coverImage = trim($row[$columnMap['封面图']] ?? '');
                $mentorEmployeeNo = trim($row[$columnMap['导师工号']] ?? '');
                $minReadTime = intval(trim($row[$columnMap['最低学习时长(秒)']] ?? 30));
                $sortOrder = intval(trim($row[$columnMap['排序']] ?? 0));

                // 必填验证
                if (!$seriesId || !$title || !$type || !$contentSource || !$contentUrl) {
                    throw new \Exception('系列ID、课程标题、类型、内容来源、内容地址为必填项');
                }

                // 类型验证
                if (!in_array($type, ['video', 'document'])) {
                    throw new \Exception('类型必须是 video 或 document');
                }

                // 内容来源验证
                if (!in_array($contentSource, ['online', 'local'])) {
                    throw new \Exception('内容来源必须是 online 或 local');
                }

                // 系列验证
                $series = \App\Models\Series::find($seriesId);
                if (!$series) {
                    throw new \Exception("系列ID {$seriesId} 不存在");
                }

                // 如果是 local，处理本地文件
                if ($contentSource === 'local') {
                    if ($zipTempDir) {
                        // ZIP 模式：在 ZIP 中查找文件
                        $sourceFile = $zipTempDir . '/' . $contentUrl;
                        if (!file_exists($sourceFile)) {
                            throw new \Exception("ZIP 中未找到文件：{$contentUrl}");
                        }

                        // 上传文件到 OSS
                        $extension = pathinfo($contentUrl, PATHINFO_EXTENSION);
                        $fileName = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $extension;
                        $type = $type === 'video' ? 'video' : 'document';
                        $ossPath = \App\Helpers\OssHelper::path($type, $fileName);
                        
                        $fileContent = file_get_contents($sourceFile);
                        \Storage::disk('oss')->put($ossPath, $fileContent);
                        $contentUrl = $ossPath;
                    } else {
                        // CSV 模式：验证文件是否存在于 OSS
                        if (!\Storage::disk('oss')->exists($contentUrl)) {
                            throw new \Exception("OSS 文件 {$contentUrl} 不存在");
                        }
                    }
                }

                // 导师验证（可选）
                $mentorId = null;
                if ($mentorEmployeeNo) {
                    $mentor = \App\Models\User::where('employee_no', $mentorEmployeeNo)
                        ->whereIn('role', ['mentor', 'admin'])
                        ->first();
                    if (!$mentor) {
                        throw new \Exception("导师工号 {$mentorEmployeeNo} 不存在或不是导师");
                    }
                    $mentorId = $mentor->id;
                }

                // 查找已有课程
                $existingCourse = \App\Models\Course::where('series_id', $seriesId)
                    ->where('title', $title)
                    ->first();

                if ($existingCourse) {
                    // 已有课程：跳过，不更新关联文件
                    $updated++;
                } else {
                    // 创建新课程
                    \App\Models\Course::create([
                        'title' => $title,
                        'description' => $description ?: null,
                        'series_id' => $seriesId,
                        'category_id' => $series->category_id,
                        'mentor_id' => $mentorId,
                        'type' => $type,
                        'content_source' => $contentSource,
                        'content_url' => $contentUrl,
                        'cover_image' => $coverImage ?: null,
                        'min_read_time' => $minReadTime > 0 ? $minReadTime : 30,
                        'duration' => 0,
                        'status' => 'draft',
                        'sort_order' => $sortOrder,
                    ]);

                    $created++;
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = ['row' => $rowNum, 'message' => $e->getMessage()];
            }
        }

        fclose($handle);

        return response()->json([
            'message' => "导入完成：创建 {$created} 条，更新 {$updated} 条，失败 {$failed} 条",
            'data' => [
                'created' => $created,
                'updated' => $updated,
                'failed' => $failed,
                'errors' => $errors,
            ],
        ]);
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }
        rmdir($dir);
    }

    // ==================== 考试批量导入 ====================

    public function downloadExamImportTemplate()
    {
        $lines = [
            '==================== 考试批量导入模板 ====================',
            '',
            '【使用说明】',
            '1. 每个题目占一行，同一考试的题目使用相同的"考试名称"',
            '2. 系统会自动创建考试并关联题目',
            '3. 如果考试名称已存在，自动在名称后追加 _1、_2 等后缀',
            '4. 同一考试所有题目分值之和不能超过 100 分',
            '5. 请勿修改表头行（第一行数据）',
            '',
            '【字段说明】',
            '考试名称：必填。考试的名称，相同名称的行归为同一考试。',
            '关联课程ID：选填。关联的课程ID，多个用逗号分隔（如 1,2,3）。学员需完成所有课程才能参加考试。',
            '考试时长(分钟)：必填。考试时间限制（分钟），如 60。',
            '及格分数：必填。0-100 之间的数字，如 60。',
            '题型：必填。只能填以下值：single / multiple / truefalse / short_answer / fill_blank',
            '题目内容：必填。题目的文字内容。填空题用（）标记空位，每个（）对应一个空。如需插入图片，使用 HTML 格式：<img src="图片URL">',
            '选项：单选/多选/判断题必填，其他题型留空。格式：A.选项1|B.选项2|C.选项3|D.选项4。选项中也可插入图片：<img src="URL">选项文字',
            '正确答案：单选填字母（如 A），多选填逗号分隔（如 A,B,D），判断填 A(对)或 B(错)，简答填参考答案（可选），填空填逗号分隔答案（如 外壳,胶芯,中心导体）。',
            '分值：必填。该题分值，数字格式。',
            '题目关联课程ID：选填。答错时跳转的课程ID。',
            '排序：选填。题目显示顺序，默认0。',
            '',
            '【题型示例】',
            '',
            '--- 单选题 ---',
            '题目内容：直接写问题',
            '选项：A.选项1|B.选项2|C.选项3|D.选项4',
            '正确答案：填一个字母，如 D',
            '',
            '--- 多选题 ---',
            '题目内容：直接写问题',
            '选项：A.选项1|B.选项2|C.选项3|D.选项4',
            '正确答案：填多个字母，逗号分隔，如 A,B,C',
            '',
            '--- 判断题 ---',
            '题目内容：直接写陈述句',
            '选项：A.正确|B.错误',
            '正确答案：填 A（正确）或 B（错误）',
            '',
            '--- 简答题 ---',
            '题目内容：直接写问题',
            '选项：留空',
            '正确答案：填参考答案（可选，用于自动评分参考）',
            '',
            '--- 填空题 ---',
            '题目内容：用（）标记空位，如"产品由（外壳）、（胶芯）和（中心导体）组成"',
            '选项：留空',
            '正确答案：逗号分隔每个空的答案，如 外壳,胶芯,中心导体',
            '注意：答案数量必须与（）数量一致',
            '',
            '--- 插入图片 ---',
            '题目中插入图片：<img src="https://oss.xxx/image.webp">请看图回答问题',
            '选项中插入图片：A.<img src="https://oss.xxx/a.webp">选项A|B.<img src="https://oss.xxx/b.webp">选项B',
            '图片需先上传到 OSS 获取 URL，再填入 CSV',
            '',
            '=================== 示例数据 ===================',
            '考试名称,关联课程ID,考试时长(分钟),及格分数,题型,题目内容,选项,正确答案,分值,题目关联课程ID,排序',
            'Lemo系列连接器考试,46,60,60,short_answer,列举出雷莫公司的下属品牌及其产品主要应用领域。,,瑞泰REDEL及科沃COELVER品牌被广泛应用于医疗仪器、军工、航空、粒子研究、广播通讯和测量检测设备等各种专业场合。,10,,1',
            'Lemo系列连接器考试,46,60,60,single,雷莫产品的推拉自锁结构属于什么类型的连接方式？,A.螺纹连接|B.推拉自锁|C.卡扣连接|D.焊接连接,B,10,,2',
            'Lemo系列连接器考试,46,60,60,multiple,以下哪些是雷莫产品的特点？,A.推拉自锁结构|B.模块式设计|C.360°屏蔽保护|D.精密制造、性能可靠,"A,B,C,D",10,,3',
            'Lemo系列连接器考试,46,60,60,truefalse,雷莫产品的K系列防护等级为室外IP66/IP68。,A.正确|B.错误,A,10,,4',
            'Lemo系列连接器考试,46,60,60,fill_blank,雷莫产品由（外壳）、（胶芯）和（中心导体）三部分组成。,,"外壳,胶芯,中心导体",10,,5',
            'Lemo系列连接器考试,46,60,60,fill_blank,B系列0B尺寸插头开孔为（7mm），插座开孔为（9mm）。,,"7mm,9mm",10,,6',
            '销售技巧考试,,30,70,single,销售漏斗的第一步是什么？,A.寻找潜在客户|B.需求分析|C.产品演示|D.成交,A,20,,1',
            '销售技巧考试,,30,70,truefalse,客户异议是销售过程中的障碍。,A.正确|B.错误,B,20,,2',
        ];

        $csv = implode("\n", $lines) . "\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="exam_import_template.csv"',
        ]);
    }

    public function importExams(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');

        if (!$handle) {
            return response()->json(['message' => '无法读取文件'], 422);
        }

        // 查找表头行（跳过说明行）
        $header = null;
        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) continue;
            if (str_starts_with(trim($row[0]), '=')) continue;
            $header = $row;
            break;
        }

        if (!$header || count($header) < 9) {
            fclose($handle);
            return response()->json(['message' => 'CSV 格式不正确，至少需要 9 列'], 422);
        }

        // 列映射
        $columnMap = [
            '考试名称' => 0, '关联课程ID' => 1, '考试时长(分钟)' => 2, '及格分数' => 3,
            '题型' => 4, '题目内容' => 5, '选项' => 6, '正确答案' => 7,
            '分值' => 8, '题目关联课程ID' => 9, '排序' => 10,
        ];

        // 读取所有行，按考试名称分组
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) continue;
            if (str_starts_with(trim($row[0]), '=')) continue;
            $rows[] = $row;
        }
        fclose($handle);

        if (empty($rows)) {
            return response()->json(['message' => 'CSV 文件中没有数据'], 422);
        }

        // 按考试名称分组
        $examGroups = [];
        foreach ($rows as $row) {
            $examName = trim($row[$columnMap['考试名称']] ?? '');
            if (!$examName) continue;
            $examGroups[$examName][] = $row;
        }

        $created = 0;
        $failed = 0;
        $errors = [];
        $examNameMap = []; // 原始名称 -> 实际创建的名称

        foreach ($examGroups as $examName => $examRows) {
            try {
                // 从第一行提取考试信息
                $firstRow = $examRows[0];
                $courseIdsStr = trim($firstRow[$columnMap['关联课程ID']] ?? '') ?: '';
                $courseIds = $courseIdsStr ? array_map('trim', explode(',', $courseIdsStr)) : [];
                $timeLimit = intval(trim($firstRow[$columnMap['考试时长(分钟)']] ?? 60));
                $passingScore = floatval(trim($firstRow[$columnMap['及格分数']] ?? 60));

                // 必填验证
                if (!$timeLimit || $timeLimit < 1) {
                    throw new \Exception('考试时长必须大于0');
                }
                if ($passingScore < 0 || $passingScore > 100) {
                    throw new \Exception('及格分数必须在0-100之间');
                }

                // 检查考试名称是否已存在，如果存在则追加后缀
                $finalName = $examName;
                $suffix = 1;
                while (\App\Models\Exam::where('title', $finalName)->exists()) {
                    $finalName = $examName . '_' . $suffix;
                    $suffix++;
                }
                $examNameMap[$examName] = $finalName;

                // 关联课程验证
                foreach ($courseIds as $cid) {
                    if (!\App\Models\Course::find($cid)) {
                        throw new \Exception("关联课程ID {$cid} 不存在");
                    }
                }

                // 创建考试
                $exam = \App\Models\Exam::create([
                    'title' => $finalName,
                    'time_limit' => $timeLimit,
                    'passing_score' => $passingScore,
                    'status' => 'draft',
                ]);

                // 关联多课程
                if (!empty($courseIds)) {
                    $exam->courses()->sync($courseIds);
                }

                // 创建题目
                $totalScore = 0;
                foreach ($examRows as $rowNum => $row) {
                    try {
                        $type = trim($row[$columnMap['题型']] ?? '');
                        $content = trim($row[$columnMap['题目内容']] ?? '');
                        $optionsStr = trim($row[$columnMap['选项']] ?? '');
                        $correctAnswer = trim($row[$columnMap['正确答案']] ?? '');
                        $score = floatval(trim($row[$columnMap['分值']] ?? 0));
                        $questionCourseId = trim($row[$columnMap['题目关联课程ID']] ?? '') ?: null;
                        $sortOrder = intval(trim($row[$columnMap['排序']] ?? 0));

                        // 必填验证
                        if (!$type || !$content || !$score) {
                            throw new \Exception('题型、题目内容、分值为必填项');
                        }

                        // 题型验证
                        if (!in_array($type, ['single', 'multiple', 'truefalse', 'short_answer', 'fill_blank'])) {
                            throw new \Exception('题型必须是 single、multiple、truefalse、short_answer 或 fill_blank');
                        }

                        // 分值验证
                        $totalScore += $score;
                        if ($totalScore > 100) {
                            throw new \Exception("总分超过100分（当前：{$totalScore}分）");
                        }

                        // 选项解析
                        $options = null;
                        if (in_array($type, ['single', 'multiple', 'truefalse']) && $optionsStr) {
                            $options = [];
                            $optionParts = explode('|', $optionsStr);
                            foreach ($optionParts as $part) {
                                $part = trim($part);
                                if (preg_match('/^([A-Za-z])[.、．)\s]+(.+)/', $part, $matches)) {
                                    $options[] = ['key' => strtoupper($matches[1]), 'value' => trim($matches[2])];
                                }
                            }
                            if (empty($options)) {
                                throw new \Exception('选项格式不正确，应为 A.选项1|B.选项2');
                            }
                        }

                        // 正确答案处理
                        if ($type === 'fill_blank') {
                            // 填空题：逗号分隔转JSON数组
                            $correctAnswer = json_encode(array_map('trim', explode(',', $correctAnswer)));
                        } elseif ($type === 'short_answer') {
                            $correctAnswer = $correctAnswer ?: null;
                        }

                        // 创建题目
                        \App\Models\Question::create([
                            'exam_id' => $exam->id,
                            'type' => $type,
                            'content' => $content,
                            'options' => $options,
                            'correct_answer' => $correctAnswer,
                            'score' => $score,
                            'course_id' => $questionCourseId,
                            'sort_order' => $sortOrder,
                        ]);
                    } catch (\Exception $e) {
                        $failed++;
                        $errors[] = ['row' => array_search($row, $rows) + 2, 'message' => "考试[{$examName}]：{$e->getMessage()}"];
                    }
                }

                $created++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = ['row' => 0, 'message' => "考试[{$examName}]：{$e->getMessage()}"];
            }
        }

        return response()->json([
            'message' => "导入完成：创建 {$created} 个考试，失败 {$failed} 条",
            'data' => [
                'created' => $created,
                'failed' => $failed,
                'errors' => $errors,
            ],
        ]);
    }
}

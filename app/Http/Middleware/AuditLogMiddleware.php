<?php

namespace App\Http\Middleware;

use App\Enums\AuditActionType;
use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    public function __construct(private AuditLogService $auditService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $actionType = $this->detectActionType($request);

            if ($actionType && $request->user()) {
                $this->auditService->log(
                    user: $request->user(),
                    actionType: $actionType,
                    targetType: $request->route('targetType') ?? 'unknown',
                    targetId: $request->route('targetId') ?? 0,
                    request: $request
                );
            }
        }

        return $response;
    }

    private function detectActionType(Request $request): ?AuditActionType
    {
        $routeName = $request->route()?->getName();

        return match ($routeName) {
            'attachments.download' => AuditActionType::DownloadAttachment,
            'learning.complete' => AuditActionType::CompleteCourse,
            'exams.submit' => AuditActionType::SubmitExam,
            default => null,
        };
    }
}

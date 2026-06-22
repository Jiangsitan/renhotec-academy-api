<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Question;
use App\Services\WordImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExamImportController extends Controller
{
    public function __construct(
        private WordImportService $wordImportService
    ) {}

    /**
     * 从 Word 导入题目
     */
    public function importFromWord(Request $request, Exam $exam): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:docx|max:20480', // 最大 20MB
        ]);

        $file = $request->file('file');
        
        // 上传到临时目录
        $tempPath = $file->storeAs('temp/word_import', $file->getClientOriginalName());
        $fullPath = storage_path('app/' . $tempPath);

        try {
            // 解析 Word 文档
            $result = $this->wordImportService->parseWord($fullPath, $exam->id);

            if (!$result['success']) {
                return response()->json([
                    'message' => $result['message'] ?? '解析失败',
                ], 422);
            }

            $questions = $result['questions'];
            $createdCount = 0;

            // 保存题目到数据库
            foreach ($questions as $index => $questionData) {
                $question = Question::create([
                    'exam_id' => $exam->id,
                    'type' => $questionData['type'],
                    'content' => $questionData['content'],
                    'options' => !empty($questionData['options']) ? json_encode($questionData['options'], JSON_UNESCAPED_UNICODE) : null,
                    'correct_answer' => $questionData['correct_answer'],
                    'score' => $questionData['score'],
                    'sort_order' => $index + 1,
                ]);
                
                $createdCount++;
            }

            // 清理临时文件
            Storage::delete($tempPath);

            return response()->json([
                'message' => "成功导入 {$createdCount} 道题目",
                'data' => [
                    'total' => $result['total'],
                    'created' => $createdCount,
                    'questions' => $questions,
                ],
            ]);

        } catch (\Exception $e) {
            // 清理临时文件
            Storage::delete($tempPath);
            
            Log::error('Word 导入失败', [
                'exam_id' => $exam->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => '导入失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 下载模板
     */
    public function downloadTemplate(): JsonResponse
    {
        // 返回模板说明
        return response()->json([
            'data' => [
                'format' => 'docx',
                'structure' => [
                    'title' => '考试标题',
                    'sections' => [
                        [
                            'name' => '一、基本概况：（10\'）',
                            'questions' => [
                                'type' => 'short_answer',
                                'format' => '1. 题目内容\n答案：答案内容\n分值：2',
                            ],
                        ],
                        [
                            'name' => '二、产品外观辨别：（50\'）',
                            'questions' => [
                                'type' => 'fill_blank',
                                'format' => '表格格式：图片 | 描述',
                            ],
                        ],
                    ],
                ],
                'examples' => [
                    'single' => [
                        'content' => '1. 以下哪个是连接器的类型？\nA. M12\nB. USB\nC. HDMI\nD. 以上都是\n答案：D\n分值：2',
                    ],
                    'multiple' => [
                        'content' => '2. 以下哪些是连接器的常见故障？（多选）\nA. 接触不良\nB. 绝缘损坏\nC. 机械磨损\nD. 以上都是\n答案：A,B,C\n分值：3',
                    ],
                    'fill_blank' => [
                        'content' => '3. 连接器由（外壳）、（接触件）和（绝缘体）组成。\n答案：外壳,接触件,绝缘体\n分值：4',
                    ],
                    'short_answer' => [
                        'content' => '4. 请简述连接器选型时需要考虑的主要因素。\n参考答案：选择连接器时需要考虑电气参数、机械参数、环境参数等。\n分值：10',
                    ],
                ],
            ],
        ]);
    }
}

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
        $fullPath = $file->getPathname(); // 直接使用临时文件路径

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

            // 不需要手动清理临时文件，PHP 会自动清理

            return response()->json([
                'message' => "成功导入 {$createdCount} 道题目",
                'data' => [
                    'total' => $result['total'],
                    'created' => $createdCount,
                    'questions' => $questions,
                ],
            ]);

        } catch (\Exception $e) {
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
    public function downloadTemplate()
    {
        // 创建 Word 模板
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        
        // 添加标题
        $section->addTitle('考试题目导入模板', 1);
        $section->addText('请按照以下格式准备题目，然后导入系统。');
        $section->addTextBreak(1);
        
        // 添加单选题示例
        $section->addTitle('一、单选题', 2);
        $section->addText('1. 以下哪个是连接器的类型？');
        $section->addText('A. M12');
        $section->addText('B. USB');
        $section->addText('C. HDMI');
        $section->addText('D. 以上都是');
        $section->addText('答案：D');
        $section->addText('分值：2');
        $section->addTextBreak(1);
        
        // 添加多选题示例
        $section->addTitle('二、多选题', 2);
        $section->addText('2. 以下哪些是连接器的常见故障？（多选）');
        $section->addText('A. 接触不良');
        $section->addText('B. 绝缘损坏');
        $section->addText('C. 机械磨损');
        $section->addText('D. 以上都是');
        $section->addText('答案：A,B,C');
        $section->addText('分值：3');
        $section->addTextBreak(1);
        
        // 添加填空题示例
        $section->addTitle('三、填空题', 2);
        $section->addText('3. 连接器由（外壳）、（接触件）和（绝缘体）组成。');
        $section->addText('答案：外壳,接触件,绝缘体');
        $section->addText('分值：4');
        $section->addTextBreak(1);
        
        // 添加简答题示例
        $section->addTitle('四、简答题', 2);
        $section->addText('4. 请简述连接器选型时需要考虑的主要因素。');
        $section->addText('参考答案：选择连接器时需要考虑电气参数、机械参数、环境参数等。');
        $section->addText('分值：10');
        
        // 保存到临时文件
        $tempFile = tempnam(sys_get_temp_dir(), 'word_template_') . '.docx';
        $phpWord->save($tempFile);
        
        // 返回文件下载
        return response()->download($tempFile, '考试题目导入模板.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}

<?php

namespace App\Services;

use App\Helpers\OssHelper;
use App\Models\Question;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Table;

class WordImportService
{
    /**
     * 解析 Word 文档
     *
     * @param string $filePath Word 文件路径
     * @param int $examId 考试 ID
     * @return array 解析结果
     */
    public function parseWord(string $filePath, int $examId): array
    {
        try {
            $phpWord = IOFactory::load($filePath);
            $sections = $phpWord->getSections();
            
            $questions = [];
            $currentSection = '';
            $currentContent = '';
            
            foreach ($sections as $section) {
                $elements = $section->getElements();
                
                foreach ($elements as $element) {
                    // 处理标题
                    if ($element instanceof \PhpOffice\PhpWord\Element\Title) {
                        $text = $this->extractTextFromElement($element);
                        if (preg_match('/^[一二三四五六七八九十]+、/', $text)) {
                            $currentSection = $text;
                        }
                    }
                    
                    // 处理文本
                    if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                        $text = $this->extractTextFromElement($element);
                        $currentContent .= $text . "\n";
                    }
                    
                    // 处理表格
                    if ($element instanceof Table) {
                        $tableQuestions = $this->parseTableQuestion($element, $examId, $currentSection);
                        $questions = array_merge($questions, $tableQuestions);
                    }
                    
                    // 处理换行
                    if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                        $text = $this->extractTextFromElement($element);
                        if (!empty(trim($text))) {
                            $currentContent .= $text . "\n";
                        }
                    }
                }
            }
            
            // 解析文本内容中的题目
            if (!empty($currentContent)) {
                $textQuestions = $this->parseTextContent($currentContent, $examId);
                $questions = array_merge($questions, $textQuestions);
            }
            
            return [
                'success' => true,
                'questions' => $questions,
                'total' => count($questions),
            ];
            
        } catch (\Exception $e) {
            Log::error('Word 文档解析失败', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Word 文档解析失败: ' . $e->getMessage(),
                'questions' => [],
                'total' => 0,
            ];
        }
    }
    
    /**
     * 解析文本内容中的题目
     */
    protected function parseTextContent(string $content, int $examId): array
    {
        $questions = [];
        
        // 按数字编号分割题目
        $pattern = '/^(\d+)\.\s*(.+?)(?=\n\d+\.|$)/ms';
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $questionContent = trim($match[2]);
                
                if (empty($questionContent)) {
                    continue;
                }
                
                // 自动识别题型
                $type = $this->detectQuestionType($questionContent);
                
                // 提取答案
                $answer = $this->extractAnswer($questionContent);
                
                // 提取分值
                $score = $this->extractScore($questionContent);
                
                // 提取选项（如果是选择题）
                $options = [];
                if (in_array($type, ['single', 'multiple'])) {
                    $options = $this->extractOptions($questionContent);
                }
                
                // 清理题目内容（移除答案、分值等）
                $cleanContent = $this->cleanQuestionContent($questionContent);
                
                $questions[] = [
                    'type' => $type,
                    'content' => $cleanContent,
                    'options' => $options,
                    'correct_answer' => $answer,
                    'score' => $score > 0 ? $score : 1,
                    'source' => 'word',
                ];
            }
        }
        
        return $questions;
    }
    
    /**
     * 解析表格题（图片 + 填空）
     */
    protected function parseTableQuestion(Table $table, int $examId, string $section): array
    {
        $questions = [];
        $rows = $table->getRows();
        
        foreach ($rows as $rowIndex => $row) {
            $cells = $row->getCells();
            
            // 跳过表头行
            if ($rowIndex === 0) {
                continue;
            }
            
            $images = [];
            $descriptions = [];
            
            foreach ($cells as $cellIndex => $cell) {
                $cellContent = $this->extractCellContent($cell);
                
                // 检查是否包含图片
                $cellImages = $this->extractImagesFromCell($cell, $examId);
                if (!empty($cellImages)) {
                    $images = array_merge($images, $cellImages);
                }
                
                // 提取描述文本
                if (!empty(trim($cellContent))) {
                    $descriptions[] = trim($cellContent);
                }
            }
            
            // 如果有图片，创建表格填空题
            if (!empty($images)) {
                $questionContent = implode(' | ', $descriptions);
                
                $questions[] = [
                    'type' => 'fill_blank',
                    'content' => $questionContent,
                    'options' => [],
                    'correct_answer' => '',
                    'score' => 2,
                    'images' => $images,
                    'source' => 'word_table',
                ];
            }
        }
        
        return $questions;
    }
    
    /**
     * 提取单元格内容
     */
    protected function extractCellContent($cell): string
    {
        $content = '';
        $elements = $cell->getElements();
        
        foreach ($elements as $element) {
            if (method_exists($element, 'getText')) {
                $content .= $element->getText() . ' ';
            }
        }
        
        return trim($content);
    }
    
    /**
     * 从单元格提取图片
     */
    protected function extractImagesFromCell($cell, int $examId): array
    {
        $images = [];
        $elements = $cell->getElements();
        
        foreach ($elements as $element) {
            if ($element instanceof \PhpOffice\PhpWord\Element\Image) {
                $imageData = $element->getImageString();
                if (!empty($imageData)) {
                    $ossPath = $this->uploadImageToOss($imageData, $examId);
                    if ($ossPath) {
                        $images[] = $ossPath;
                    }
                }
            }
        }
        
        return $images;
    }
    
    /**
     * 上传图片到 OSS
     */
    protected function uploadImageToOss(string $imageData, int $examId): ?string
    {
        try {
            // 生成文件名
            $fileName = 'exam_' . $examId . '_' . time() . '_' . Str::random(6) . '.webp';
            
            // 生成 OSS 路径
            $ossPath = OssHelper::path('exam', $fileName);
            
            // 转换为 WebP 格式
            $webpData = $this->convertToWebp($imageData);
            
            // 上传到 OSS
            Storage::disk('oss')->put($ossPath, $webpData);
            
            return $ossPath;
            
        } catch (\Exception $e) {
            Log::error('图片上传失败', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * 转换图片为 WebP 格式
     */
    protected function convertToWebp(string $imageData): string
    {
        try {
            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            $image = $manager->read($imageData);
            
            return $image->toWebp(85)->toString();
            
        } catch (\Exception $e) {
            Log::error('WebP 转换失败', ['error' => $e->getMessage()]);
            return $imageData; // 转换失败，返回原始数据
        }
    }
    
    /**
     * 自动识别题型
     */
    protected function detectQuestionType(string $content): string
    {
        // 填空题：包含括号
        if (preg_match('/（(.+?)）/', $content) || preg_match('/\((.+?)\)/', $content)) {
            return 'fill_blank';
        }
        
        // 表格题：包含图片或表格
        if (preg_match('/\[图片\]|<图片>/', $content)) {
            return 'fill_blank';
        }
        
        // 多选题：包含"多选"关键词
        if (mb_strpos($content, '多选') !== false) {
            return 'multiple';
        }
        
        // 简答题：包含关键词
        $shortAnswerKeywords = ['列举', '简述', '如何', '写出', '区分', '说明', '描述', '解释', '简要作答'];
        foreach ($shortAnswerKeywords as $keyword) {
            if (mb_strpos($content, $keyword) !== false) {
                return 'short_answer';
            }
        }
        
        // 单选题：包含选项 A B C D
        if (preg_match('/^[A-D]\.\s/m', $content)) {
            return 'single';
        }
        
        // 默认为简答题
        return 'short_answer';
    }
    
    /**
     * 提取答案
     */
    protected function extractAnswer(string $content): string
    {
        // 提取"答案："后面的内容
        if (preg_match('/答案[：:]\s*(.+?)$/m', $content, $matches)) {
            return trim($matches[1]);
        }
        
        // 提取"参考答案："后面的内容
        if (preg_match('/参考答案[：:]\s*(.+?)$/m', $content, $matches)) {
            return trim($matches[1]);
        }
        
        return '';
    }
    
    /**
     * 提取分值
     */
    protected function extractScore(string $content): int
    {
        // 提取括号中的分值，如（10'）或（10分）
        if (preg_match('/[（(]\s*(\d+)\s*[\'′分]/', $content, $matches)) {
            return (int) $matches[1];
        }
        
        // 提取"分值："后面的数字
        if (preg_match('/分值[：:]\s*(\d+)/', $content, $matches)) {
            return (int) $matches[1];
        }
        
        return 0;
    }
    
    /**
     * 提取选项
     */
    protected function extractOptions(string $content): array
    {
        $options = [];
        
        // 匹配 A. B. C. D. 格式的选项
        if (preg_match_all('/^([A-D])\.\s*(.+?)$/m', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $options[] = [
                    'label' => $match[1],
                    'text' => trim($match[2]),
                ];
            }
        }
        
        return $options;
    }
    
    /**
     * 清理题目内容
     */
    protected function cleanQuestionContent(string $content): string
    {
        // 移除答案行
        $content = preg_replace('/^答案[：:].*$/m', '', $content);
        $content = preg_replace('/^参考答案[：:].*$/m', '', $content);
        
        // 移除分值行
        $content = preg_replace('/^分值[：:].*$/m', '', $content);
        $content = preg_match('/[（(]\s*\d+\s*[\'′分][）)]/', $content) ? preg_replace('/[（(]\s*\d+\s*[\'′分][）)]/', '', $content) : $content;
        
        // 移除多余空行
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        return trim($content);
    }
    
    /**
     * 从元素提取文本
     */
    protected function extractTextFromElement($element): string
    {
        if (method_exists($element, 'getText')) {
            return $element->getText();
        }
        
        if (method_exists($element, 'getElements')) {
            $text = '';
            foreach ($element->getElements() as $subElement) {
                $text .= $this->extractTextFromElement($subElement);
            }
            return $text;
        }
        
        return '';
    }
}

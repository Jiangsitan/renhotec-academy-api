<?php

namespace Tests\Unit;

use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class QuestionAnswerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    // =========================================================================
    // Answer replacement tests (Task 019)
    // =========================================================================

    public function test_update_question_replaces_answer_not_appends(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->singleChoice()->for($exam)->create([
            'correct_answer' => 'A',
        ]);

        // Update via controller
        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/exams/{$exam->id}/questions/{$question->id}", [
                'correct_answer' => 'B',
            ]);

        $response->assertOk();
        $this->assertEquals('B', $question->fresh()->correct_answer);
        $this->assertStringNotContainsString('A', $question->fresh()->correct_answer);
    }

    public function test_update_question_with_chinese_answer(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->shortAnswer()->for($exam)->create([
            'correct_answer' => '直径≤11',
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/exams/{$exam->id}/questions/{$question->id}", [
                'correct_answer' => '连接器是有源器件的器件',
            ]);

        $response->assertOk();
        $fresh = $question->fresh();
        $this->assertEquals('连接器是有源器件的器件', $fresh->correct_answer);
        $this->assertTrue(mb_check_encoding($fresh->correct_answer, 'UTF-8'));
    }

    public function test_update_short_answer_preserves_encoding(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->shortAnswer()->for($exam)->create([
            'correct_answer' => '前锁',
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/exams/{$exam->id}/questions/{$question->id}", [
                'correct_answer' => '后锁',
            ]);

        $response->assertOk();
        $this->assertEquals('后锁', $question->fresh()->correct_answer);
    }

    // =========================================================================
    // Fill-blank question tests (Task 020)
    // =========================================================================

    public function test_update_question_preserves_json_array_for_fill_blank(): void
    {
        $exam = Exam::factory()->create();
        $jsonAnswer = '["前锁","后锁"]';
        $question = Question::factory()->fillBlank()->for($exam)->create([
            'correct_answer' => $jsonAnswer,
        ]);

        // Update with same JSON array
        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/exams/{$exam->id}/questions/{$question->id}", [
                'correct_answer' => '["左锁","右锁"]',
            ]);

        $response->assertOk();
        $fresh = $question->fresh();
        $decoded = json_decode($fresh->correct_answer, true);
        $this->assertIsArray($decoded);
        $this->assertEquals(['左锁', '右锁'], $decoded);
    }

    public function test_update_question_with_comma_separated_fill_blank(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->fillBlank()->for($exam)->create([
            'correct_answer' => '["前锁","后锁"]',
        ]);

        // Update with comma-separated string (should be converted to JSON)
        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/exams/{$exam->id}/questions/{$question->id}", [
                'correct_answer' => '左锁,右锁',
            ]);

        $response->assertOk();
        $fresh = $question->fresh();
        $decoded = json_decode($fresh->correct_answer, true);
        $this->assertIsArray($decoded);
        $this->assertEquals(['左锁', '右锁'], $decoded);
    }

    public function test_update_question_with_chinese_json_array(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->fillBlank()->for($exam)->create([
            'correct_answer' => '["直径≤11"]',
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/exams/{$exam->id}/questions/{$question->id}", [
                'correct_answer' => '["温度≥40℃","长度×宽度÷2"]',
            ]);

        $response->assertOk();
        $fresh = $question->fresh();
        $decoded = json_decode($fresh->correct_answer, true);
        $this->assertIsArray($decoded);
        $this->assertEquals(['温度≥40℃', '长度×宽度÷2'], $decoded);
        $this->assertTrue(mb_check_encoding($fresh->correct_answer, 'UTF-8'));
    }
}

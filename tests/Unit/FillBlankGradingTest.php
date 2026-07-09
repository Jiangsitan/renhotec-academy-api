<?php

namespace Tests\Unit;

use App\Services\ExamGradingService;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use App\Models\ExamRecord;
use App\Enums\ExamRecordStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FillBlankGradingTest extends TestCase
{
    use RefreshDatabase;

    private ExamGradingService $service;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExamGradingService();
        $this->admin = User::factory()->admin()->create();
    }

    private function createRecordWithAnswers(
        User $user,
        Exam $exam,
        array $answers,
    ): ExamRecord {
        return ExamRecord::factory()
            ->submitted()
            ->for($user)
            ->for($exam)
            ->create(['answers' => $answers]);
    }

    // =========================================================================
    // Fill-Blank: JSON Array Format
    // =========================================================================

    public function test_fill_blank_json_array_correct(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->fillBlank()->for($exam)->withScore(10)->create([
            'correct_answer' => '["前锁","后锁"]',
        ]);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => ['前锁', '后锁']],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        // Fill-blank is subjective → pending review
        $this->assertEquals(ExamRecordStatus::PendingReview, $record->status);
        $this->assertFalse($record->answers[0]['auto_graded']);
        $this->assertNull($record->answers[0]['is_correct']);
    }

    public function test_fill_blank_comma_separated_correct(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->fillBlank()->for($exam)->withScore(10)->create([
            'correct_answer' => '前锁,后锁',
        ]);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => '前锁,后锁'],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals(ExamRecordStatus::PendingReview, $record->status);
        $this->assertFalse($record->answers[0]['auto_graded']);
    }

    // =========================================================================
    // Fill-Blank: Chinese Characters
    // =========================================================================

    public function test_fill_blank_with_chinese_special_chars(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->fillBlank()->for($exam)->withScore(10)->create([
            'correct_answer' => '["直径≤11","前锁"]',
        ]);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => ['直径≤11', '前锁']],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        // Still subjective → pending review
        $this->assertEquals(ExamRecordStatus::PendingReview, $record->status);
        $this->assertFalse($record->answers[0]['auto_graded']);
    }

    // =========================================================================
    // Fill-Blank: Multiple Blanks
    // =========================================================================

    public function test_fill_blank_with_five_blanks(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->fillBlank()->for($exam)->withScore(10)->create([
            'correct_answer' => '["A","B","C","D","E"]',
        ]);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => ['A', 'B', 'C', 'D', 'E']],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals(ExamRecordStatus::PendingReview, $record->status);
    }

    // =========================================================================
    // Fill-Blank: Empty Answers
    // =========================================================================

    public function test_fill_blank_empty_answer(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->fillBlank()->for($exam)->withScore(10)->create([
            'correct_answer' => '["PHP"]',
        ]);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => ''],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals(ExamRecordStatus::PendingReview, $record->status);
        $this->assertFalse($record->answers[0]['auto_graded']);
    }

    // =========================================================================
    // Fill-Blank: Mixed with Objective Questions
    // =========================================================================

    public function test_mixed_objective_and_fill_blank_grading(): void
    {
        $exam = Exam::factory()->create();
        $singleQ = Question::factory()->singleChoice()->for($exam)->withScore(10)->create(['correct_answer' => 'A']);
        $fillQ = Question::factory()->fillBlank()->for($exam)->withScore(10)->create([
            'correct_answer' => '["PHP","Laravel"]',
        ]);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $singleQ->id, 'answer' => 'A'],
            ['question_id' => $fillQ->id, 'answer' => ['PHP', 'Laravel']],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        // Objective score only includes single choice
        $this->assertEquals(10.0, (float) $record->objective_score);
        // Fill-blank is subjective → pending review
        $this->assertEquals(ExamRecordStatus::PendingReview, $record->status);

        // Check single choice answer is auto-graded
        $singleAnswer = collect($record->answers)->firstWhere('question_id', $singleQ->id);
        $this->assertTrue($singleAnswer['auto_graded']);
        $this->assertTrue($singleAnswer['is_correct']);
        $this->assertEquals(10.0, (float) $singleAnswer['score_awarded']);

        // Check fill-blank answer is NOT auto-graded
        $fillAnswer = collect($record->answers)->firstWhere('question_id', $fillQ->id);
        $this->assertFalse($fillAnswer['auto_graded']);
        $this->assertNull($fillAnswer['is_correct']);
    }

    // =========================================================================
    // Fill-Blank: Correct Answer Formats
    // =========================================================================

    public function test_fill_blank_correct_answer_json_array_with_spaces(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->fillBlank()->for($exam)->withScore(10)->create([
            'correct_answer' => '[" PHP "," Laravel "]',
        ]);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => ['PHP', 'Laravel']],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals(ExamRecordStatus::PendingReview, $record->status);
    }

    public function test_fill_blank_correct_answer_chinese_comma_separated(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->fillBlank()->for($exam)->withScore(10)->create([
            'correct_answer' => '前锁，后锁', // Chinese comma
        ]);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => '前锁，后锁'],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals(ExamRecordStatus::PendingReview, $record->status);
    }

    // =========================================================================
    // Fill-Blank: Mentor Review Scoring
    // =========================================================================

    public function test_mentor_can_grade_fill_blank_manually(): void
    {
        $exam = Exam::factory()->create();
        $q = Question::factory()->fillBlank()->for($exam)->withScore(10)->create([
            'correct_answer' => '["PHP","Laravel"]',
        ]);
        $mentor = User::factory()->mentor()->create();
        $user = User::factory()->student()->permanentEmployee()->create();

        $record = ExamRecord::factory()
            ->pendingReview()
            ->for($user)
            ->for($exam)
            ->create([
                'objective_score' => 0.0,
                'answers' => [
                    ['question_id' => $q->id, 'answer' => ['PHP', 'Laravel'], 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                ],
            ]);

        $this->service->mentorReview($record, $mentor, [
            $q->id => 8.0,
        ], [
            $q->id => true,
        ], 'Almost perfect');

        $record->refresh();
        $this->assertEquals(8.0, (float) $record->subjective_score);
        $this->assertEquals(8.0, (float) $record->total_score);
        $this->assertTrue($record->answers[0]['is_correct']);
        $this->assertEquals(8.0, (float) $record->answers[0]['score_awarded']);
    }
}

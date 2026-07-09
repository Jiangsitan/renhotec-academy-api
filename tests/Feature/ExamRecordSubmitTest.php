<?php

namespace Tests\Feature;

use App\Enums\ExamRecordStatus;
use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamRecordSubmitTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $admin;
    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->student = User::factory()->student()->permanentEmployee()->create();
        $this->exam = Exam::factory()->create(['passing_score' => 60]);
    }

    // =========================================================================
    // Happy Path
    // =========================================================================

    public function test_student_can_submit_exam(): void
    {
        $q1 = Question::factory()->singleChoice()->for($this->exam)->withScore(10)->create(['correct_answer' => 'A']);
        $q2 = Question::factory()->trueFalse()->for($this->exam)->withScore(10)->create(['correct_answer' => 'True']);

        $response = $this->actingAs($this->student)
            ->postJson("/api/exams/{$this->exam->id}/submit", [
                'answers' => [
                    ['question_id' => $q1->id, 'answer' => 'A'],
                    ['question_id' => $q2->id, 'answer' => 'True'],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'status', 'objective_score'],
            ]);

        $this->assertDatabaseHas('exam_records', [
            'user_id' => $this->student->id,
            'exam_id' => $this->exam->id,
        ]);
    }

    public function test_submit_auto_grades_objective_questions(): void
    {
        $q1 = Question::factory()->singleChoice()->for($this->exam)->withScore(10)->create(['correct_answer' => 'A']);
        $q2 = Question::factory()->trueFalse()->for($this->exam)->withScore(10)->create(['correct_answer' => 'True']);

        $response = $this->actingAs($this->student)
            ->postJson("/api/exams/{$this->exam->id}/submit", [
                'answers' => [
                    ['question_id' => $q1->id, 'answer' => 'A'],
                    ['question_id' => $q2->id, 'answer' => 'False'],
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals(10.0, (float) $data['objective_score']);
        $this->assertEquals(ExamRecordStatus::PendingReview->value, $data['status']);
    }

    public function test_submit_creates_record_with_pending_review_status(): void
    {
        $q = Question::factory()->shortAnswer()->for($this->exam)->withScore(20)->create();

        $response = $this->actingAs($this->student)
            ->postJson("/api/exams/{$this->exam->id}/submit", [
                'answers' => [
                    ['question_id' => $q->id, 'answer' => 'My answer'],
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals(ExamRecordStatus::PendingReview->value, $data['status']);
    }

    // =========================================================================
    // Duplicate Submission Prevention
    // =========================================================================

    public function test_cannot_submit_exam_twice_when_pending(): void
    {
        $q = Question::factory()->singleChoice()->for($this->exam)->withScore(10)->create();

        // First submission
        $this->actingAs($this->student)
            ->postJson("/api/exams/{$this->exam->id}/submit", [
                'answers' => [['question_id' => $q->id, 'answer' => 'A']],
            ]);

        // Second submission
        $response = $this->actingAs($this->student)
            ->postJson("/api/exams/{$this->exam->id}/submit", [
                'answers' => [['question_id' => $q->id, 'answer' => 'B']],
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => '您已提交过此考试']);
    }

    public function test_cannot_submit_exam_twice_when_submitted(): void
    {
        $q = Question::factory()->singleChoice()->for($this->exam)->withScore(10)->create();

        ExamRecord::factory()->submitted()->for($this->student)->for($this->exam)->create();

        $response = $this->actingAs($this->student)
            ->postJson("/api/exams/{$this->exam->id}/submit", [
                'answers' => [['question_id' => $q->id, 'answer' => 'A']],
            ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // Retake Logic
    // =========================================================================

    public function test_can_retake_failed_exam(): void
    {
        $q = Question::factory()->singleChoice()->for($this->exam)->withScore(10)->create(['correct_answer' => 'A']);

        // Previous graded record below passing score
        ExamRecord::factory()->graded()->for($this->student)->for($this->exam)->create([
            'total_score' => 40.0,
        ]);

        $response = $this->actingAs($this->student)
            ->postJson("/api/exams/{$this->exam->id}/submit", [
                'answers' => [['question_id' => $q->id, 'answer' => 'A']],
            ]);

        $response->assertStatus(200);
    }

    public function test_cannot_retake_passed_exam(): void
    {
        $q = Question::factory()->singleChoice()->for($this->exam)->withScore(10)->create(['correct_answer' => 'A']);

        // Previous graded record above passing score
        ExamRecord::factory()->graded()->for($this->student)->for($this->exam)->create([
            'total_score' => 80.0,
        ]);

        $response = $this->actingAs($this->student)
            ->postJson("/api/exams/{$this->exam->id}/submit", [
                'answers' => [['question_id' => $q->id, 'answer' => 'A']],
            ]);

        $response->assertStatus(422);
    }

    public function test_retake_marks_old_record_as_retaken(): void
    {
        $q = Question::factory()->singleChoice()->for($this->exam)->withScore(10)->create(['correct_answer' => 'A']);

        $oldRecord = ExamRecord::factory()->graded()->for($this->student)->for($this->exam)->create([
            'total_score' => 40.0,
        ]);

        $this->actingAs($this->student)
            ->postJson("/api/exams/{$this->exam->id}/submit", [
                'answers' => [['question_id' => $q->id, 'answer' => 'A']],
            ]);

        $oldRecord->refresh();
        $this->assertEquals(ExamRecordStatus::Retaken, $oldRecord->status);
    }

    // =========================================================================
    // Validation
    // =========================================================================

    public function test_submit_requires_answers(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson("/api/exams/{$this->exam->id}/submit", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('answers');
    }

    public function test_submit_requires_valid_question_ids(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson("/api/exams/{$this->exam->id}/submit", [
                'answers' => [
                    ['question_id' => 99999, 'answer' => 'A'],
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_submit_requires_answers_array(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson("/api/exams/{$this->exam->id}/submit", [
                'answers' => 'not-an-array',
            ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // Authentication
    // =========================================================================

    public function test_unauthenticated_user_cannot_submit(): void
    {
        $response = $this->postJson("/api/exams/{$this->exam->id}/submit", [
            'answers' => [['question_id' => 1, 'answer' => 'A']],
        ]);

        $response->assertStatus(401);
    }

    // =========================================================================
    // Mixed Question Types
    // =========================================================================

    public function test_submit_with_all_question_types(): void
    {
        $single = Question::factory()->singleChoice()->for($this->exam)->withScore(10)->create(['correct_answer' => 'A']);
        $multiple = Question::factory()->multipleChoice()->for($this->exam)->withScore(10)->create(['correct_answer' => 'A,C']);
        $trueFalse = Question::factory()->trueFalse()->for($this->exam)->withScore(10)->create(['correct_answer' => 'True']);
        $short = Question::factory()->shortAnswer()->for($this->exam)->withScore(20)->create();
        $fillBlank = Question::factory()->fillBlank()->for($this->exam)->withScore(10)->create();

        $response = $this->actingAs($this->student)
            ->postJson("/api/exams/{$this->exam->id}/submit", [
                'answers' => [
                    ['question_id' => $single->id, 'answer' => 'A'],
                    ['question_id' => $multiple->id, 'answer' => ['A', 'C']],
                    ['question_id' => $trueFalse->id, 'answer' => 'True'],
                    ['question_id' => $short->id, 'answer' => 'Laravel is a framework'],
                    ['question_id' => $fillBlank->id, 'answer' => 'PHP'],
                ],
            ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        // Objective: single(10) + multiple(10) + truefalse(10) = 30
        $this->assertEquals(30.0, (float) $data['objective_score']);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\ExamRecordStatus;
use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentorReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $mentor;
    private User $admin;
    private User $student;
    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mentor = User::factory()->mentor()->create();
        $this->admin = User::factory()->admin()->create();
        $this->student = User::factory()->student()->permanentEmployee()->create();
        $this->exam = Exam::factory()->create(['passing_score' => 60]);
    }

    // =========================================================================
    // Happy Path
    // =========================================================================

    public function test_mentor_can_review_assigned_record(): void
    {
        $q1 = Question::factory()->shortAnswer()->for($this->exam)->withScore(20)->create();
        $q2 = Question::factory()->fillBlank()->for($this->exam)->withScore(10)->create();

        $record = ExamRecord::factory()
            ->pendingReview()
            ->for($this->student)
            ->for($this->exam)
            ->assignedTo($this->mentor)
            ->create([
                'objective_score' => 30.0,
                'answers' => [
                    ['question_id' => $q1->id, 'answer' => 'Answer 1', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                    ['question_id' => $q2->id, 'answer' => 'PHP', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                ],
            ]);

        $response = $this->actingAs($this->mentor)
            ->postJson("/api/mentor/review/{$record->id}", [
                'scores' => [$q1->id => 18.0, $q2->id => 8.0],
                'correctness' => [$q1->id => true, $q2->id => false],
                'comment' => 'Good work',
                'action' => 'approve',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => '批改完成']);

        $record->refresh();
        $this->assertEquals(ExamRecordStatus::Graded, $record->status);
        $this->assertEquals(26.0, (float) $record->subjective_score);
        $this->assertEquals(56.0, (float) $record->total_score);
        $this->assertEquals($this->mentor->id, $record->graded_by);
        $this->assertEquals('Good work', $record->mentor_comment);
    }

    public function test_admin_can_review_any_record(): void
    {
        $q = Question::factory()->shortAnswer()->for($this->exam)->withScore(20)->create();

        $record = ExamRecord::factory()
            ->pendingReview()
            ->for($this->student)
            ->for($this->exam)
            ->assignedTo($this->mentor) // assigned to mentor, not admin
            ->create([
                'objective_score' => 0.0,
                'answers' => [
                    ['question_id' => $q->id, 'answer' => 'Answer', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                ],
            ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/mentor/review/{$record->id}", [
                'scores' => [$q->id => 15.0],
                'correctness' => [$q->id => true],
                'action' => 'approve',
            ]);

        $response->assertStatus(200);
    }

    // =========================================================================
    // Permission Checks
    // =========================================================================

    public function test_mentor_cannot_review_unassigned_record(): void
    {
        $otherMentor = User::factory()->mentor()->create();
        $q = Question::factory()->shortAnswer()->for($this->exam)->withScore(20)->create();

        $record = ExamRecord::factory()
            ->pendingReview()
            ->for($this->student)
            ->for($this->exam)
            ->assignedTo($otherMentor) // assigned to different mentor
            ->create([
                'answers' => [
                    ['question_id' => $q->id, 'answer' => 'Answer', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                ],
            ]);

        $response = $this->actingAs($this->mentor)
            ->postJson("/api/mentor/review/{$record->id}", [
                'scores' => [$q->id => 15.0],
                'action' => 'approve',
            ]);

        $response->assertStatus(403);
    }

    public function test_student_cannot_review_records(): void
    {
        $record = ExamRecord::factory()
            ->pendingReview()
            ->for($this->student)
            ->for($this->exam)
            ->create();

        $response = $this->actingAs($this->student)
            ->postJson("/api/mentor/review/{$record->id}", [
                'scores' => [],
                'action' => 'approve',
            ]);

        $response->assertStatus(403);
    }

    // =========================================================================
    // Status Validation
    // =========================================================================

    public function test_cannot_review_already_graded_record(): void
    {
        $q = Question::factory()->shortAnswer()->for($this->exam)->withScore(20)->create();

        $record = ExamRecord::factory()
            ->graded()
            ->for($this->student)
            ->for($this->exam)
            ->assignedTo($this->mentor)
            ->create([
                'answers' => [
                    ['question_id' => $q->id, 'answer' => 'Answer', 'is_correct' => true, 'score_awarded' => 15, 'auto_graded' => true],
                ],
            ]);

        $response = $this->actingAs($this->mentor)
            ->postJson("/api/mentor/review/{$record->id}", [
                'scores' => [$q->id => 18.0],
                'action' => 'approve',
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => '此答卷不需要批改']);
    }

    // =========================================================================
    // Validation
    // =========================================================================

    public function test_review_requires_action_field(): void
    {
        $record = ExamRecord::factory()
            ->pendingReview()
            ->for($this->student)
            ->for($this->exam)
            ->assignedTo($this->mentor)
            ->create();

        $response = $this->actingAs($this->mentor)
            ->postJson("/api/mentor/review/{$record->id}", [
                'scores' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('action');
    }

    public function test_review_validates_score_is_numeric(): void
    {
        $q = Question::factory()->shortAnswer()->for($this->exam)->withScore(20)->create();

        $record = ExamRecord::factory()
            ->pendingReview()
            ->for($this->student)
            ->for($this->exam)
            ->assignedTo($this->mentor)
            ->create([
                'answers' => [
                    ['question_id' => $q->id, 'answer' => 'Answer', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                ],
            ]);

        $response = $this->actingAs($this->mentor)
            ->postJson("/api/mentor/review/{$record->id}", [
                'scores' => [$q->id => 'not-a-number'],
                'action' => 'approve',
            ]);

        $response->assertStatus(422);
    }

    public function test_review_validates_correctness_is_boolean(): void
    {
        $q = Question::factory()->shortAnswer()->for($this->exam)->withScore(20)->create();

        $record = ExamRecord::factory()
            ->pendingReview()
            ->for($this->student)
            ->for($this->exam)
            ->assignedTo($this->mentor)
            ->create([
                'answers' => [
                    ['question_id' => $q->id, 'answer' => 'Answer', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                ],
            ]);

        $response = $this->actingAs($this->mentor)
            ->postJson("/api/mentor/review/{$record->id}", [
                'correctness' => [$q->id => 'not-boolean'],
                'action' => 'approve',
            ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // Score Calculation
    // =========================================================================

    public function test_review_calculates_total_score_correctly(): void
    {
        $q1 = Question::factory()->shortAnswer()->for($this->exam)->withScore(20)->create();
        $q2 = Question::factory()->fillBlank()->for($this->exam)->withScore(10)->create();

        $record = ExamRecord::factory()
            ->pendingReview()
            ->for($this->student)
            ->for($this->exam)
            ->assignedTo($this->mentor)
            ->create([
                'objective_score' => 25.0,
                'answers' => [
                    ['question_id' => $q1->id, 'answer' => 'Answer 1', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                    ['question_id' => $q2->id, 'answer' => 'PHP', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                ],
            ]);

        $this->actingAs($this->mentor)
            ->postJson("/api/mentor/review/{$record->id}", [
                'scores' => [$q1->id => 15.0, $q2->id => 7.0],
                'action' => 'approve',
            ]);

        $record->refresh();
        // objective(25) + subjective(15+7=22) = 47
        $this->assertEquals(47.0, (float) $record->total_score);
    }

    public function test_review_preserves_objective_score_when_not_included(): void
    {
        $q = Question::factory()->shortAnswer()->for($this->exam)->withScore(20)->create();

        $record = ExamRecord::factory()
            ->pendingReview()
            ->for($this->student)
            ->for($this->exam)
            ->assignedTo($this->mentor)
            ->create([
                'objective_score' => 30.0,
                'answers' => [
                    ['question_id' => $q->id, 'answer' => 'Answer', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                ],
            ]);

        $this->actingAs($this->mentor)
            ->postJson("/api/mentor/review/{$record->id}", [
                'scores' => [$q->id => 18.0],
                'action' => 'approve',
            ]);

        $record->refresh();
        $this->assertEquals(30.0, (float) $record->objective_score);
        $this->assertEquals(48.0, (float) $record->total_score);
    }

    // =========================================================================
    // Authentication
    // =========================================================================

    public function test_unauthenticated_user_cannot_review(): void
    {
        $record = ExamRecord::factory()
            ->pendingReview()
            ->for($this->student)
            ->for($this->exam)
            ->create();

        $response = $this->postJson("/api/mentor/review/{$record->id}", [
            'scores' => [],
            'action' => 'approve',
        ]);

        $response->assertStatus(401);
    }
}

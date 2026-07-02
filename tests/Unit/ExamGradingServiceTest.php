<?php

namespace Tests\Unit;

use App\Enums\ExamRecordStatus;
use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\Question;
use App\Models\User;
use App\Services\ExamGradingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamGradingServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExamGradingService $service;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExamGradingService();
        // 创建管理员用户，所有测试都需要
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * Helper: create an ExamRecord with specific answers.
     */
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
    // autoGrade — Single Choice
    // =========================================================================

    public function test_auto_grade_single_choice_correct(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->singleChoice()->for($exam)->withScore(10)->create(['correct_answer' => 'B']);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => 'B'],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        // 所有考试统一进入待批改状态
        $this->assertEquals(ExamRecordStatus::PendingReview, $record->status);
        $this->assertEquals(10.0, (float) $record->objective_score);
        $this->assertNotNull($record->assigned_to);
    }

    public function test_auto_grade_single_choice_wrong(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->singleChoice()->for($exam)->withScore(10)->create(['correct_answer' => 'B']);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => 'A'],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        // 所有考试统一进入待批改状态
        $this->assertEquals(ExamRecordStatus::PendingReview, $record->status);
        $this->assertEquals(0.0, (float) $record->objective_score);
    }

    public function test_auto_grade_single_choice_case_insensitive(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->singleChoice()->for($exam)->withScore(10)->create(['correct_answer' => 'B']);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => 'b'],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals(10.0, (float) $record->objective_score);
    }

    public function test_auto_grade_single_choice_with_whitespace(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->singleChoice()->for($exam)->withScore(10)->create(['correct_answer' => 'B']);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => '  B  '],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals(10.0, (float) $record->objective_score);
    }

    // =========================================================================
    // autoGrade — True/False
    // =========================================================================

    public function test_auto_grade_true_false_correct(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->trueFalse()->for($exam)->withScore(5)->create(['correct_answer' => 'True']);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => 'True'],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals(5.0, (float) $record->objective_score);
    }

    public function test_auto_grade_true_false_wrong(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->trueFalse()->for($exam)->withScore(5)->create(['correct_answer' => 'True']);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => 'False'],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals(0.0, (float) $record->objective_score);
    }

    // =========================================================================
    // autoGrade — Multiple Choice
    // =========================================================================

    public function test_auto_grade_multiple_choice_correct_same_order(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->multipleChoice()->for($exam)->withScore(10)->create(['correct_answer' => 'A,C']);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => ['A', 'C']],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals(10.0, (float) $record->objective_score);
    }

    public function test_auto_grade_multiple_choice_correct_different_order(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->multipleChoice()->for($exam)->withScore(10)->create(['correct_answer' => 'A,C']);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => ['C', 'A']],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals(10.0, (float) $record->objective_score);
    }

    public function test_auto_grade_multiple_choice_wrong_missing_option(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->multipleChoice()->for($exam)->withScore(10)->create(['correct_answer' => 'A,C']);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => ['A']],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals(0.0, (float) $record->objective_score);
    }

    public function test_auto_grade_multiple_choice_wrong_extra_option(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->multipleChoice()->for($exam)->withScore(10)->create(['correct_answer' => 'A,C']);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => ['A', 'B', 'C']],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals(0.0, (float) $record->objective_score);
    }

    public function test_auto_grade_multiple_choice_comma_string_answer(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->multipleChoice()->for($exam)->withScore(10)->create(['correct_answer' => 'A,C']);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => 'A,C'],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals(10.0, (float) $record->objective_score);
    }

    // =========================================================================
    // autoGrade — Subjective (Short Answer / Fill Blank)
    // =========================================================================

    public function test_auto_grade_short_answer_pending_review(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->shortAnswer()->for($exam)->withScore(20)->create();
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => 'My answer'],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals(ExamRecordStatus::PendingReview, $record->status);
        $this->assertEquals(0.0, (float) $record->objective_score);
        $this->assertEquals(0.0, (float) $record->answers[0]['score_awarded']);
        $this->assertFalse($record->answers[0]['auto_graded']);
        $this->assertNull($record->answers[0]['is_correct']);
        $this->assertEquals($this->admin->id, $record->assigned_to);
    }

    public function test_auto_grade_fill_blank_pending_review(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->fillBlank()->for($exam)->withScore(10)->create();
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => 'PHP'],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals(ExamRecordStatus::PendingReview, $record->status);
        $this->assertFalse($record->answers[0]['auto_graded']);
    }

    // =========================================================================
    // autoGrade — Mixed Question Types
    // =========================================================================

    public function test_auto_grade_mixed_objective_and_subjective(): void
    {
        $exam = Exam::factory()->create();
        $singleQ = Question::factory()->singleChoice()->for($exam)->withScore(10)->create(['correct_answer' => 'A']);
        $shortQ = Question::factory()->shortAnswer()->for($exam)->withScore(20)->create();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $singleQ->id, 'answer' => 'A'],
            ['question_id' => $shortQ->id, 'answer' => 'My essay answer'],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals(ExamRecordStatus::PendingReview, $record->status);
        $this->assertEquals(10.0, (float) $record->objective_score);
        $this->assertNull($record->total_score);
    }

    public function test_auto_grade_all_objective_scores_total(): void
    {
        $exam = Exam::factory()->create();
        $q1 = Question::factory()->singleChoice()->for($exam)->withScore(10)->create(['correct_answer' => 'A']);
        $q2 = Question::factory()->trueFalse()->for($exam)->withScore(5)->create(['correct_answer' => 'True']);
        $q3 = Question::factory()->multipleChoice()->for($exam)->withScore(15)->create(['correct_answer' => 'B,D']);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $q1->id, 'answer' => 'A'],
            ['question_id' => $q2->id, 'answer' => 'True'],
            ['question_id' => $q3->id, 'answer' => ['B', 'D']],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        // 所有考试统一进入待批改状态
        $this->assertEquals(ExamRecordStatus::PendingReview, $record->status);
        $this->assertEquals(30.0, (float) $record->objective_score);
    }

    // =========================================================================
    // autoGrade — Edge Cases
    // =========================================================================

    public function test_auto_grade_empty_answers(): void
    {
        $exam = Exam::factory()->create();
        Question::factory()->singleChoice()->for($exam)->withScore(10)->create(['correct_answer' => 'A']);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, []);

        $this->service->autoGrade($record);
        $record->refresh();

        // 所有考试统一进入待批改状态
        $this->assertEquals(ExamRecordStatus::PendingReview, $record->status);
        $this->assertEquals(0.0, (float) $record->objective_score);
    }

    public function test_auto_grade_answer_for_nonexistent_question(): void
    {
        $exam = Exam::factory()->create();
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => 99999, 'answer' => 'A'],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        // 所有考试统一进入待批改状态
        $this->assertEquals(ExamRecordStatus::PendingReview, $record->status);
        $this->assertEquals(0.0, (float) $record->objective_score);
    }

    public function test_auto_grade_unknown_question_type(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->for($exam)->create([
            'type' => 'unknown_type',
            'correct_answer' => 'A',
            'score' => 10,
        ]);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => 'A'],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        // Unknown type → checkAnswer returns false → 0 score
        $this->assertEquals(0.0, (float) $record->objective_score);
    }

    public function test_auto_grade_empty_string_answer(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->singleChoice()->for($exam)->withScore(10)->create(['correct_answer' => 'A']);
        $user = User::factory()->student()->permanentEmployee()->create();
        $record = $this->createRecordWithAnswers($user, $exam, [
            ['question_id' => $question->id, 'answer' => ''],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals(0.0, (float) $record->objective_score);
    }

    // =========================================================================
    // autoGrade — Reviewer Assignment (resolveReviewer)
    // =========================================================================

    public function test_trial_employee_with_mentor_gets_mentor_assigned(): void
    {
        $mentor = User::factory()->mentor()->create();
        $student = User::factory()->student()->trialEmployee()->create();
        $student->mentors()->attach($mentor->id, ['status' => 'active']);

        $exam = Exam::factory()->create();
        $question = Question::factory()->shortAnswer()->for($exam)->withScore(10)->create();
        $record = $this->createRecordWithAnswers($student, $exam, [
            ['question_id' => $question->id, 'answer' => 'Answer'],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals($mentor->id, $record->assigned_to);
    }

    public function test_trial_employee_without_mentor_gets_admin(): void
    {
        $student = User::factory()->student()->trialEmployee()->create();

        $exam = Exam::factory()->create();
        $question = Question::factory()->shortAnswer()->for($exam)->withScore(10)->create();
        $record = $this->createRecordWithAnswers($student, $exam, [
            ['question_id' => $question->id, 'answer' => 'Answer'],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        // 无导师时 fallback 到管理员
        $this->assertEquals($this->admin->id, $record->assigned_to);
    }

    public function test_permanent_employee_gets_admin_assigned(): void
    {
        $student = User::factory()->student()->permanentEmployee()->create();

        $exam = Exam::factory()->create();
        $question = Question::factory()->shortAnswer()->for($exam)->withScore(10)->create();
        $record = $this->createRecordWithAnswers($student, $exam, [
            ['question_id' => $question->id, 'answer' => 'Answer'],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        // 正式员工 fallback 到管理员（使用 setUp 中创建的）
        $this->assertEquals($this->admin->id, $record->assigned_to);
    }

    public function test_no_admin_in_system_throws_exception(): void
    {
        // 删除所有管理员
        User::where('role', 'admin')->delete();

        $student = User::factory()->student()->permanentEmployee()->create();

        $exam = Exam::factory()->create();
        $question = Question::factory()->shortAnswer()->for($exam)->withScore(10)->create();
        $record = $this->createRecordWithAnswers($student, $exam, [
            ['question_id' => $question->id, 'answer' => 'Answer'],
        ]);

        // 无管理员时应抛出异常
        $this->expectException(\RuntimeException::class);
        $this->service->autoGrade($record);
    }

    public function test_inactive_mentor_gets_admin_fallback(): void
    {
        $mentor = User::factory()->mentor()->create();
        $student = User::factory()->student()->trialEmployee()->create();
        $student->mentors()->attach($mentor->id, ['status' => 'inactive']);

        $exam = Exam::factory()->create();
        $question = Question::factory()->shortAnswer()->for($exam)->withScore(10)->create();
        $record = $this->createRecordWithAnswers($student, $exam, [
            ['question_id' => $question->id, 'answer' => 'Answer'],
        ]);

        $this->service->autoGrade($record);
        $record->refresh();

        // inactive 导师不应被分配，应 fallback 到管理员
        $this->assertEquals($this->admin->id, $record->assigned_to);
    }

    // =========================================================================
    // assignReviewer
    // =========================================================================

    public function test_assign_reviewer_sets_id_and_note(): void
    {
        $mentor = User::factory()->mentor()->create();
        $exam = Exam::factory()->create();
        $user = User::factory()->student()->create();
        $record = ExamRecord::factory()->pendingReview()->for($user)->for($exam)->create();

        $this->service->assignReviewer($record, $mentor, 'Please review urgently');

        $record->refresh();
        $this->assertEquals($mentor->id, $record->assigned_to);
        $this->assertEquals('Please review urgently', $record->assignment_note);
    }

    public function test_assign_reviewer_with_null_note(): void
    {
        $mentor = User::factory()->mentor()->create();
        $exam = Exam::factory()->create();
        $user = User::factory()->student()->create();
        $record = ExamRecord::factory()->pendingReview()->for($user)->for($exam)->create();

        $this->service->assignReviewer($record, $mentor, null);

        $record->refresh();
        $this->assertEquals($mentor->id, $record->assigned_to);
        $this->assertNull($record->assignment_note);
    }

    // =========================================================================
    // mentorReview
    // =========================================================================

    public function test_mentor_review_grades_subjective_and_sets_total(): void
    {
        $exam = Exam::factory()->create();
        $q1 = Question::factory()->shortAnswer()->for($exam)->withScore(20)->create();
        $q2 = Question::factory()->fillBlank()->for($exam)->withScore(10)->create();
        $mentor = User::factory()->mentor()->create();
        $user = User::factory()->student()->permanentEmployee()->create();

        $record = ExamRecord::factory()
            ->submitted()
            ->for($user)
            ->for($exam)
            ->create([
                'objective_score' => 30.0,
                'answers' => [
                    ['question_id' => $q1->id, 'answer' => 'Answer 1', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                    ['question_id' => $q2->id, 'answer' => 'PHP', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                ],
            ]);

        $this->service->mentorReview($record, $mentor, [
            $q1->id => 18.0,
            $q2->id => 8.0,
        ], [], 'Good work overall');

        $record->refresh();
        $this->assertEquals(ExamRecordStatus::Graded, $record->status);
        $this->assertEquals(26.0, (float) $record->subjective_score);
        $this->assertEquals(56.0, (float) $record->total_score);
        $this->assertNotNull($record->graded_at);
        $this->assertEquals($mentor->id, $record->graded_by);
        $this->assertEquals('Good work overall', $record->mentor_comment);
    }

    public function test_mentor_review_partial_grading(): void
    {
        $exam = Exam::factory()->create();
        $q1 = Question::factory()->shortAnswer()->for($exam)->withScore(20)->create();
        $q2 = Question::factory()->fillBlank()->for($exam)->withScore(10)->create();
        $mentor = User::factory()->mentor()->create();
        $user = User::factory()->student()->permanentEmployee()->create();

        $record = ExamRecord::factory()
            ->submitted()
            ->for($user)
            ->for($exam)
            ->create([
                'objective_score' => 15.0,
                'answers' => [
                    ['question_id' => $q1->id, 'answer' => 'Answer 1', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                    ['question_id' => $q2->id, 'answer' => 'PHP', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                ],
            ]);

        // Only grade q1
        $this->service->mentorReview($record, $mentor, [
            $q1->id => 15.0,
        ], [], null);

        $record->refresh();
        $this->assertEquals(15.0, (float) $record->subjective_score);
        $this->assertEquals(30.0, (float) $record->total_score);
    }

    public function test_mentor_review_zero_scores(): void
    {
        $exam = Exam::factory()->create();
        $q1 = Question::factory()->shortAnswer()->for($exam)->withScore(20)->create();
        $mentor = User::factory()->mentor()->create();
        $user = User::factory()->student()->permanentEmployee()->create();

        $record = ExamRecord::factory()
            ->submitted()
            ->for($user)
            ->for($exam)
            ->create([
                'objective_score' => 10.0,
                'answers' => [
                    ['question_id' => $q1->id, 'answer' => 'Wrong', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                ],
            ]);

        $this->service->mentorReview($record, $mentor, [
            $q1->id => 0.0,
        ], [], 'No credit');

        $record->refresh();
        $this->assertEquals(0.0, (float) $record->subjective_score);
        $this->assertEquals(10.0, (float) $record->total_score);
        $this->assertEquals('No credit', $record->mentor_comment);
    }

    public function test_mentor_review_null_comment(): void
    {
        $exam = Exam::factory()->create();
        $q1 = Question::factory()->shortAnswer()->for($exam)->withScore(10)->create();
        $mentor = User::factory()->mentor()->create();
        $user = User::factory()->student()->permanentEmployee()->create();

        $record = ExamRecord::factory()
            ->submitted()
            ->for($user)
            ->for($exam)
            ->create([
                'objective_score' => 5.0,
                'answers' => [
                    ['question_id' => $q1->id, 'answer' => 'Answer', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                ],
            ]);

        $this->service->mentorReview($record, $mentor, [
            $q1->id => 5.0,
        ], [], null);

        $record->refresh();
        $this->assertNull($record->mentor_comment);
        $this->assertEquals(10.0, (float) $record->total_score);
    }

    public function test_mentor_review_sets_graded_by(): void
    {
        $exam = Exam::factory()->create();
        $q1 = Question::factory()->shortAnswer()->for($exam)->withScore(10)->create();
        $mentor = User::factory()->mentor()->create();
        $user = User::factory()->student()->permanentEmployee()->create();

        $record = ExamRecord::factory()
            ->submitted()
            ->for($user)
            ->for($exam)
            ->create([
                'objective_score' => 5.0,
                'answers' => [
                    ['question_id' => $q1->id, 'answer' => 'Answer', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                ],
            ]);

        $this->service->mentorReview($record, $mentor, [
            $q1->id => 5.0,
        ], [], null);

        $record->refresh();
        $this->assertEquals($mentor->id, $record->graded_by);
    }

    // =========================================================================
    // mentorReview — Correctness Tracking
    // =========================================================================

    public function test_mentor_review_updates_correctness(): void
    {
        $exam = Exam::factory()->create();
        $q1 = Question::factory()->shortAnswer()->for($exam)->withScore(20)->create();
        $q2 = Question::factory()->fillBlank()->for($exam)->withScore(10)->create();
        $mentor = User::factory()->mentor()->create();
        $user = User::factory()->student()->permanentEmployee()->create();

        $record = ExamRecord::factory()
            ->submitted()
            ->for($user)
            ->for($exam)
            ->create([
                'objective_score' => 0.0,
                'answers' => [
                    ['question_id' => $q1->id, 'answer' => 'Answer 1', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                    ['question_id' => $q2->id, 'answer' => 'PHP', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                ],
            ]);

        $this->service->mentorReview($record, $mentor, [
            $q1->id => 18.0,
            $q2->id => 8.0,
        ], [
            $q1->id => true,
            $q2->id => false,
        ], 'Partial credit');

        $record->refresh();
        $this->assertTrue($record->answers[0]['is_correct']);
        $this->assertFalse($record->answers[1]['is_correct']);
        $this->assertEquals(18.0, (float) $record->answers[0]['score_awarded']);
        $this->assertEquals(8.0, (float) $record->answers[1]['score_awarded']);
    }

    public function test_mentor_review_empty_correctness_keeps_existing(): void
    {
        $exam = Exam::factory()->create();
        $q1 = Question::factory()->shortAnswer()->for($exam)->withScore(20)->create();
        $mentor = User::factory()->mentor()->create();
        $user = User::factory()->student()->permanentEmployee()->create();

        $record = ExamRecord::factory()
            ->submitted()
            ->for($user)
            ->for($exam)
            ->create([
                'objective_score' => 0.0,
                'answers' => [
                    ['question_id' => $q1->id, 'answer' => 'Answer 1', 'is_correct' => true, 'score_awarded' => 15.0, 'auto_graded' => true],
                ],
            ]);

        // Empty correctness — should keep existing is_correct value
        $this->service->mentorReview($record, $mentor, [
            $q1->id => 18.0,
        ], [], null);

        $record->refresh();
        // is_correct should remain true since correctness was not provided
        $this->assertTrue($record->answers[0]['is_correct']);
        $this->assertEquals(18.0, (float) $record->answers[0]['score_awarded']);
    }

    // =========================================================================
    // mentorReview — Mixed Objective + Subjective (type classification)
    // =========================================================================

    public function test_mentor_review_classifies_objective_and_subjective_correctly(): void
    {
        $exam = Exam::factory()->create();
        // Objective types: single(1), multiple(2), truefalse(3)
        $qSingle = Question::factory()->singleChoice()->for($exam)->withScore(10)->create(['correct_answer' => 'A']);
        $qMultiple = Question::factory()->multipleChoice()->for($exam)->withScore(15)->create(['correct_answer' => 'A,B']);
        $qTrueFalse = Question::factory()->trueFalse()->for($exam)->withScore(5)->create(['correct_answer' => 'True']);
        // Subjective types: short_answer(4), fill_blank(5)
        $qShort = Question::factory()->shortAnswer()->for($exam)->withScore(20)->create();
        $qFill = Question::factory()->fillBlank()->for($exam)->withScore(10)->create();

        $mentor = User::factory()->mentor()->create();
        $user = User::factory()->student()->permanentEmployee()->create();

        $record = ExamRecord::factory()
            ->submitted()
            ->for($user)
            ->for($exam)
            ->create([
                'objective_score' => 0.0,
                'answers' => [
                    ['question_id' => $qSingle->id, 'answer' => 'A', 'is_correct' => true, 'score_awarded' => 0, 'auto_graded' => false],
                    ['question_id' => $qMultiple->id, 'answer' => ['A', 'B'], 'is_correct' => true, 'score_awarded' => 0, 'auto_graded' => false],
                    ['question_id' => $qTrueFalse->id, 'answer' => 'True', 'is_correct' => true, 'score_awarded' => 0, 'auto_graded' => false],
                    ['question_id' => $qShort->id, 'answer' => 'Essay answer', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                    ['question_id' => $qFill->id, 'answer' => 'Laravel', 'is_correct' => null, 'score_awarded' => 0, 'auto_graded' => false],
                ],
            ]);

        $this->service->mentorReview($record, $mentor, [
            $qSingle->id => 10.0,
            $qMultiple->id => 12.0,
            $qTrueFalse->id => 5.0,
            $qShort->id => 18.0,
            $qFill->id => 8.0,
        ], [
            $qSingle->id => true,
            $qMultiple->id => true,
            $qTrueFalse->id => true,
            $qShort->id => true,
            $qFill->id => false,
        ], 'Good');

        $record->refresh();

        // Objective: single(10) + multiple(12) + truefalse(5) = 27
        $this->assertEquals(27.0, (float) $record->objective_score);
        // Subjective: short(18) + fill(8) = 26
        $this->assertEquals(26.0, (float) $record->subjective_score);
        // Total: 27 + 26 = 53
        $this->assertEquals(53.0, (float) $record->total_score);
        $this->assertEquals(ExamRecordStatus::Graded, $record->status);
    }

    // =========================================================================
    // Full Integration: Submit → Auto-Grade → Mentor Review → Final
    // =========================================================================

    public function test_full_grading_flow(): void
    {
        // Setup
        $admin = User::factory()->admin()->create();
        $mentor = User::factory()->mentor()->create();
        $student = User::factory()->student()->trialEmployee()->create();
        $student->mentors()->attach($mentor->id, ['status' => 'active']);

        $exam = Exam::factory()->create(['passing_score' => 50]);
        $q1 = Question::factory()->singleChoice()->for($exam)->withScore(10)->create(['correct_answer' => 'A']);
        $q2 = Question::factory()->trueFalse()->for($exam)->withScore(10)->create(['correct_answer' => 'True']);
        $q3 = Question::factory()->shortAnswer()->for($exam)->withScore(20)->create();

        // Step 1: Student submits
        $record = ExamRecord::factory()
            ->submitted()
            ->for($student)
            ->for($exam)
            ->create([
                'answers' => [
                    ['question_id' => $q1->id, 'answer' => 'A'],      // correct
                    ['question_id' => $q2->id, 'answer' => 'False'],  // wrong
                    ['question_id' => $q3->id, 'answer' => 'Laravel framework'],
                ],
            ]);

        // Step 2: Auto-grade
        $this->service->autoGrade($record);
        $record->refresh();

        $this->assertEquals(ExamRecordStatus::PendingReview, $record->status);
        $this->assertEquals(10.0, (float) $record->objective_score);
        $this->assertEquals($mentor->id, $record->assigned_to);

        // Step 3: Mentor reviews
        $this->service->mentorReview($record, $mentor, [
            $q1->id => 10.0,
            $q2->id => 0.0,
            $q3->id => 18.0,
        ], [
            $q1->id => true,
            $q2->id => false,
            $q3->id => true,
        ], 'Good answer');

        $record->refresh();
        $this->assertEquals(ExamRecordStatus::Graded, $record->status);
        // Objective: single(10) + truefalse(0) = 10
        $this->assertEquals(10.0, (float) $record->objective_score);
        // Subjective: short_answer(18)
        $this->assertEquals(18.0, (float) $record->subjective_score);
        $this->assertEquals(28.0, (float) $record->total_score);
        $this->assertEquals('Good answer', $record->mentor_comment);
    }
}

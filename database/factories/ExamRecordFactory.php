<?php

namespace Database\Factories;

use App\Enums\ExamRecordStatus;
use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamRecord>
 */
class ExamRecordFactory extends Factory
{
    protected $model = ExamRecord::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'exam_id' => Exam::factory(),
            'answers' => [],
            'objective_score' => 0,
            'subjective_score' => null,
            'total_score' => null,
            'status' => ExamRecordStatus::InProgress,
            'submitted_at' => null,
            'graded_at' => null,
            'graded_by' => null,
            'assigned_to' => null,
            'assignment_note' => null,
            'mentor_comment' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExamRecordStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    public function graded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExamRecordStatus::Graded,
            'graded_at' => now(),
        ]);
    }

    public function pendingReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExamRecordStatus::PendingReview,
        ]);
    }

    public function withAnswers(array $answers): static
    {
        return $this->state(fn (array $attributes) => [
            'answers' => $answers,
        ]);
    }

    public function assignedTo(User $reviewer): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => $reviewer->id,
        ]);
    }
}

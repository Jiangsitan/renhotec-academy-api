<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'course_id' => null,
            'type' => 'single',
            'content' => fake()->sentence(),
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            'score' => fake()->randomFloat(2, 1, 10),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function singleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'single',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'B',
        ]);
    }

    public function multipleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'multiple',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A,C',
        ]);
    }

    public function trueFalse(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'truefalse',
            'options' => null,
            'correct_answer' => 'True',
        ]);
    }

    public function shortAnswer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'short_answer',
            'options' => null,
            'correct_answer' => 'Laravel is a PHP framework',
        ]);
    }

    public function fillBlank(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fill_blank',
            'options' => null,
            'correct_answer' => 'PHP',
        ]);
    }

    public function withScore(float $score): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $score,
        ]);
    }
}

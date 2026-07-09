<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\ExamRecordController;
use ReflectionMethod;
use Tests\TestCase;

class NormalizeQuestionTypeTest extends TestCase
{
    /**
     * Test ExamController::normalizeQuestionType via reflection
     */
    public function test_exam_controller_maps_single_to_1(): void
    {
        $method = new ReflectionMethod(ExamController::class, 'normalizeQuestionType');
        $method->setAccessible(true);

        $this->assertEquals(1, $method->invoke(null, 'single'));
    }

    public function test_exam_controller_maps_multiple_to_2(): void
    {
        $method = new ReflectionMethod(ExamController::class, 'normalizeQuestionType');
        $method->setAccessible(true);

        $this->assertEquals(2, $method->invoke(null, 'multiple'));
    }

    public function test_exam_controller_maps_truefalse_to_3(): void
    {
        $method = new ReflectionMethod(ExamController::class, 'normalizeQuestionType');
        $method->setAccessible(true);

        $this->assertEquals(3, $method->invoke(null, 'truefalse'));
    }

    public function test_exam_controller_maps_short_answer_to_4(): void
    {
        $method = new ReflectionMethod(ExamController::class, 'normalizeQuestionType');
        $method->setAccessible(true);

        $this->assertEquals(4, $method->invoke(null, 'short_answer'));
    }

    public function test_exam_controller_maps_fill_blank_to_5(): void
    {
        $method = new ReflectionMethod(ExamController::class, 'normalizeQuestionType');
        $method->setAccessible(true);

        $this->assertEquals(5, $method->invoke(null, 'fill_blank'));
    }

    public function test_exam_controller_maps_unknown_to_0(): void
    {
        $method = new ReflectionMethod(ExamController::class, 'normalizeQuestionType');
        $method->setAccessible(true);

        $this->assertEquals(0, $method->invoke(null, 'unknown'));
    }

    public function test_exam_controller_passthrough_integer(): void
    {
        $method = new ReflectionMethod(ExamController::class, 'normalizeQuestionType');
        $method->setAccessible(true);

        $this->assertEquals(3, $method->invoke(null, 3));
    }

    public function test_exam_controller_handles_null(): void
    {
        $method = new ReflectionMethod(ExamController::class, 'normalizeQuestionType');
        $method->setAccessible(true);

        $this->assertEquals(0, $method->invoke(null, null));
    }

    /**
     * Test ExamRecordController::normalizeQuestionType via reflection
     */
    public function test_record_controller_maps_single_to_1(): void
    {
        $method = new ReflectionMethod(ExamRecordController::class, 'normalizeQuestionType');
        $method->setAccessible(true);

        $this->assertEquals(1, $method->invoke(null, 'single'));
    }

    public function test_record_controller_maps_all_types(): void
    {
        $method = new ReflectionMethod(ExamRecordController::class, 'normalizeQuestionType');
        $method->setAccessible(true);

        $this->assertEquals(1, $method->invoke(null, 'single'));
        $this->assertEquals(2, $method->invoke(null, 'multiple'));
        $this->assertEquals(3, $method->invoke(null, 'truefalse'));
        $this->assertEquals(4, $method->invoke(null, 'short_answer'));
        $this->assertEquals(5, $method->invoke(null, 'fill_blank'));
    }

    public function test_record_controller_handles_null(): void
    {
        $method = new ReflectionMethod(ExamRecordController::class, 'normalizeQuestionType');
        $method->setAccessible(true);

        $this->assertEquals(0, $method->invoke(null, null));
    }

    public function test_record_controller_handles_integer(): void
    {
        $method = new ReflectionMethod(ExamRecordController::class, 'normalizeQuestionType');
        $method->setAccessible(true);

        $this->assertEquals(5, $method->invoke(null, 5));
    }

    public function test_record_controller_handles_unknown_string(): void
    {
        $method = new ReflectionMethod(ExamRecordController::class, 'normalizeQuestionType');
        $method->setAccessible(true);

        $this->assertEquals(0, $method->invoke(null, 'foobar'));
    }

    /**
     * Both controllers should produce identical results
     */
    public function test_both_controllers_produce_same_results(): void
    {
        $examMethod = new ReflectionMethod(ExamController::class, 'normalizeQuestionType');
        $examMethod->setAccessible(true);

        $recordMethod = new ReflectionMethod(ExamRecordController::class, 'normalizeQuestionType');
        $recordMethod->setAccessible(true);

        $types = ['single', 'multiple', 'truefalse', 'short_answer', 'fill_blank', 'unknown', null];

        foreach ($types as $type) {
            $examResult = $examMethod->invoke(null, $type);
            $recordResult = $recordMethod->invoke(null, $type);
            $this->assertEquals($examResult, $recordResult, "Mismatch for type: " . var_export($type, true));
        }
    }
}

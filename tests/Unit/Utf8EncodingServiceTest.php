<?php

namespace Tests\Unit;

use App\Services\Utf8EncodingService;
use Tests\TestCase;

class Utf8EncodingServiceTest extends TestCase
{
    // =========================================================================
    // detectEncoding
    // =========================================================================

    public function test_detect_encoding_identifies_double_encoded(): void
    {
        // Create a double-encoded UTF-8 string
        // "直径≤11" → GBK bytes → interpreted as ISO-8859-1 → encoded as UTF-8
        $original = '直径≤11';
        $gbkBytes = mb_convert_encoding($original, 'GBK', 'UTF-8');
        $doubleEncoded = mb_convert_encoding($gbkBytes, 'UTF-8', 'ISO-8859-1');

        $result = Utf8EncodingService::detectEncoding($doubleEncoded);
        $this->assertEquals('UTF-8_DOUBLE', $result);
    }

    public function test_detect_encoding_identifies_valid_utf8(): void
    {
        $valid = '直径≤11';
        $result = Utf8EncodingService::detectEncoding($valid);
        $this->assertEquals('UTF-8', $result);
    }

    public function test_detect_encoding_identifies_gbk(): void
    {
        $gbk = mb_convert_encoding('直径≤11', 'GBK', 'UTF-8');
        $result = Utf8EncodingService::detectEncoding($gbk);
        $this->assertEquals('GBK', $result);
    }

    // =========================================================================
    // ensureUtf8
    // =========================================================================

    public function test_ensure_utf8_preserves_chinese_characters(): void
    {
        $input = '直径≤11';
        $result = Utf8EncodingService::ensureUtf8($input);
        $this->assertEquals($input, $result);
        $this->assertTrue(mb_check_encoding($result, 'UTF-8'));
    }

    public function test_ensure_utf8_preserves_chinese_with_special_chars(): void
    {
        $testCases = [
            '前锁',
            '后锁',
            '直径≤11',
            '连接器是有源器件的器件',
            '温度≥40℃',
            '长度×宽度÷2',
        ];

        foreach ($testCases as $input) {
            $result = Utf8EncodingService::ensureUtf8($input);
            $this->assertEquals($input, $result, "Failed for: $input");
            $this->assertTrue(mb_check_encoding($result, 'UTF-8'));
        }
    }

    public function test_ensure_utf8_returns_empty_for_empty_string(): void
    {
        $this->assertEquals('', Utf8EncodingService::ensureUtf8(''));
    }

    // =========================================================================
    // isDoubleEncodedUtf8
    // =========================================================================

    public function test_is_double_encoded_utf8_detects_double_encoding(): void
    {
        $original = '直径≤11';
        $gbkBytes = mb_convert_encoding($original, 'GBK', 'UTF-8');
        $doubleEncoded = mb_convert_encoding($gbkBytes, 'UTF-8', 'ISO-8859-1');

        $this->assertTrue(Utf8EncodingService::isDoubleEncodedUtf8($doubleEncoded));
    }

    public function test_is_double_encoded_utf8_returns_false_for_valid_utf8(): void
    {
        $valid = '直径≤11';
        $this->assertFalse(Utf8EncodingService::isDoubleEncodedUtf8($valid));
    }

    public function test_is_double_encoded_utf8_returns_false_for_ascii(): void
    {
        $this->assertFalse(Utf8EncodingService::isDoubleEncodedUtf8('hello'));
    }

    // =========================================================================
    // decodeDoubleUtf8
    // =========================================================================

    public function test_decode_double_utf8_recovers_chinese(): void
    {
        $original = '直径≤11';
        $gbkBytes = mb_convert_encoding($original, 'GBK', 'UTF-8');
        $doubleEncoded = mb_convert_encoding($gbkBytes, 'UTF-8', 'ISO-8859-1');

        $decoded = Utf8EncodingService::decodeDoubleUtf8($doubleEncoded);
        $this->assertEquals($original, $decoded);
    }

    public function test_decode_double_utf8_preserves_already_valid(): void
    {
        $valid = '直径≤11';
        $result = Utf8EncodingService::decodeDoubleUtf8($valid);
        $this->assertEquals($valid, $result);
    }

    public function test_decode_double_utf8_returns_empty_for_empty_string(): void
    {
        $this->assertEquals('', Utf8EncodingService::decodeDoubleUtf8(''));
    }
}

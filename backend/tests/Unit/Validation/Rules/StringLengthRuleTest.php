<?php
declare(strict_types=1);
namespace Tests\Unit\Validation\Rules;

use PHPUnit\Framework\TestCase;
use App\Validation\Rules\StringLengthRule;

class StringLengthRuleTest extends TestCase {
    public function testStringBellowMinimumLengthFails(): void {
        $rule = new StringLengthRule(min: 5);
        $result = $rule->validate('abc');
        $this->assertFalse($result, 'String below minimum length should fail validation');
    }

    public function testStringAboveMaximumLengthFails(): void {
        $rule = new StringLengthRule(max: 5);
        $result = $rule->validate('abcdef');
        $this->assertFalse($result, 'String above maximum length should fail validation');
    }

    public function testStringWithMinimumLengthPasses(): void {
        $rule = new StringLengthRule(min: 3);
        $result = $rule->validate('abc');
        $this->assertTrue($result, 'String with minimum length should pass validation');
    }

    public function testStringWithMaximumLengthPasses(): void {
        $rule = new StringLengthRule(min: 3, max: 5);
        $result = $rule->validate('abcde');
        $this->assertTrue($result, 'String with exact length should pass validation');
    }

    public function testStringWithinLengthRangePasses(): void {
        $rule = new StringLengthRule(min: 3, max: 5);
        $result = $rule->validate('abcd');
        $this->assertTrue($result, 'String within length range should pass validation');
    }

    public function testEmptyStringWithMinimumZero(): void {
        $rule = new StringLengthRule(min: 0);
        $result = $rule->validate('');
        $this->assertTrue($result, 'Empty string with minimum length 0 should pass validation');
    }

    public function testNonStringValuesFail(): void {
        $rule = new StringLengthRule(min: 3, max: 5);
        $this->assertFalse($rule->validate(123), 'Integer should fail validation');
        $this->assertFalse($rule->validate(12.34), 'Float values should fail validation');
        $this->assertFalse($rule->validate([]), 'Integer should fail validation');
        $this->assertFalse($rule->validate(null), 'Null should fail validation');
        $this->assertFalse($rule->validate(true), 'Boolean should fail validation');
    }
}

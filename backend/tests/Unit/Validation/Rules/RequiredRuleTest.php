<?php
declare(strict_types=1);
namespace Tests\Unit\Validation\Rules;

use PHPUnit\Framework\TestCase;
use App\Validation\Rules\RequiredRule;

class RequiredRuleTest extends TestCase {
    private RequiredRule $rule;

    protected function setUp(): void {
        $this->rule = new RequiredRule();
    }

    public function testEmptyStringFailsValidation(): void {
        $result = $this->rule->validate('');
        $this->assertFalse($result, 'Empty string should fail validation');
    }

    public function testNullFailsValidation(): void {
        $result = $this->rule->validate(null);
        $this->assertFalse($result, 'Null should fail validation');
    }

    public function testZeroIntPassesValidation(): void {
        $result = $this->rule->validate(0);
        $this->assertFalse($result, 'Zero (int) should fail validation');
    }

    public function testNonEmptyStringPassesValidation(): void {
        $result = $this->rule->validate('test');
        $this->assertTrue($result, 'Non-empty string should pass validation');
    }

    public function testArrayPassesValidation(): void {
        $result = $this->rule->validate(['name' => 'Testus']);
        $this->assertTrue($result, 'Non-empty array should pass validation');
    }

    public function testFalseFailsValidation(): void {
        $result = $this->rule->validate(false);
        $this->assertFalse($result, 'False should fail validation');
    }

    public function testErrorMessageIsCorrect(): void {
        $message = $this->rule->getMessage();
        $this->assertEquals('This field is required', $message);
    }
}

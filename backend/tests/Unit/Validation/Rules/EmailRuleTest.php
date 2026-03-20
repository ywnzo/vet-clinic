<?php
declare(strict_types=1);
namespace Tests\Unit\Validation\Rules;

use PHPUnit\Framework\TestCase;
use App\Validation\Rules\EmailRule;

class EmailRuleTest extends TestCase {
    private EmailRule $rule;

    protected function setUp(): void {
        $this->rule = new EmailRule();
    }

    public function testValidEmailAddressesPass(): void {
        $validEmails = [
            'user@example.com',
            'john.doe@example.com',
            'jane_doe@example.com',
            'test+tag@example.co.uk',
            'first.last@subdomain.example.com'
        ];

        foreach ($validEmails as $email) {
            $result = $this->rule->validate($email);
            $this->assertTrue($result, "Email '{$email}' should pass validation");
        }
    }

    public function testInvalidEmailAddressesFail(): void {
        $invalidEmails = [
            'notemail',
            'missing@domai',
            '@example.com',
            'user@',
            'user @example.com',
            'user@exam ple.com'
        ];

        foreach ($invalidEmails as $email) {
            $result = $this->rule->validate($email);
            $this->assertFalse($result, "Email '{$email}' should fail validation");
        }
    }

    public function testEmptyStringFails(): void {
        $result = $this->rule->validate('');
        $this->assertFalse($result, 'Empty string should fail validation');
    }

    public function testNullFails(): void {
        $result = $this->rule->validate(null);
        $this->assertFalse($result, 'Null should fail validation');
    }

    public function testErrorMessageIsCorrect(): void {
        $message = $this->rule->getMessage();
        $this->assertEquals('Invalid email format', $message);
    }

}

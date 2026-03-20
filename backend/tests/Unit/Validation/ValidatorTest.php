<?php
declare(strict_types=1);
namespace Tests\Unit\Validation\Rules;

use PharIo\Manifest\Email;
use PHPUnit\Framework\TestCase;
use App\Validation\Validator;
use App\Validation\Rules\RequiredRule;
use App\Validation\Rules\EmailRule;
use App\Validation\Rules\StringLengthRule;
use App\Exception\ValidationException;


class ValidatorTest extends TestCase {
    private Validator $validator;

    protected function setUp(): void {
        $this->validator = new Validator();
    }

    public function testAddRuleAddsRuleToField(): void {
        $rule = new RequiredRule();
        $this->validator->addRule('name', $rule);

        $this->assertTrue($this->validator->isValid('name', 'Testus'));
        $this->assertFalse($this->validator->isValid('name', ''));
    }

    public function testAddRuleAddsMultipleRulesToField(): void {
        $requiredRule = new RequiredRule();
        $emailRule = new EmailRule();

        $this->validator->addRules('email', $requiredRule, $emailRule);

        $this->assertTrue($this->validator->isValid('email', 'test@example.com'));
        $this->assertFalse($this->validator->isValid('email', ''));
        $this->assertFalse($this->validator->isValid('email', 'invalid-email'));
    }

    public function testValidatePassesWithValidData(): void {
        $this->validator->addRule('name', new RequiredRule());
        $this->validator->addRule('email', new EmailRule());

        $data = [
            'name' => 'Testus',
            'email' => 'test@example.com',
        ];

        $result = $this->validator->validate($data);
        $this->assertTrue($result, 'Validation should pass with valid data');
    }

    public function testValidateThrowsValidationExceptionWithInvalidData(): void {
        $this->validator->addRule('name', new RequiredRule());

        $data = ['name' => ''];

        $this->expectException(ValidationException::class);
        $this->validator->validate($data);
    }

    public function testValidateIncludesErrorMessagesInException(): void {
        $this->validator->addRule('name', new RequiredRule());
        $this->validator->addRule('email', new EmailRule());

        $data = [
            'name' => '',
            'email' => 'invalid-email'
        ];

        try {
            $this->validator->validate($data);
            $this->fail('ValidationException should have been thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('name', $errors);
            $this->assertEquals('This field is required', $errors['name']);
        }
    }

    public function testIsValidReturnsTrueForValidFieldValue(): void {
        $this->validator->addRule('name', new RequiredRule());

        $result = $this->validator->isValid('name', 'John Doe');
        $this->assertTrue($result, 'isValid should return true with valid data');
    }

    public function testIsValidReturnsFalseForInvalidFieldValue(): void {
        $this->validator->addRule('name', new RequiredRule());

        $result = $this->validator->isValid('name', '');
        $this->assertFalse($result, 'isValid should return false with invalid data');
    }

    public function testMultipleRulesOnSameFieldFirstRuleThatFailsStopValidation(): void {
        $requiredRule = new RequiredRule();
        $emailRule = new EmailRule();

        $this->validator->addRules('email', $requiredRule, $emailRule);

        $data = ['email' => ''];

        try {
            $this->validator->validate($data);
            $this->fail('ValidationException should have been thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertEquals('This field is required', $errors['email']);
        }
    }

    public function testValidatorCanAddRuleMultipleTimes(): void {
        $this->validator->addRule('password', new StringLengthRule(min: 5));
        $this->validator->addRule('password', new StringLengthRule(min: 5, max: 20));

        $this->assertFalse($this->validator->isValid('password', 'abc'));
        $this->assertFalse($this->validator->isValid('password', 'abcdefghijklmnopqrstuvwxyz'));
        $this->assertTrue($this->validator->isValid('password', 'abcdef'));
    }

    public function testIsValidReturnsTrueForFieldWithoutRules(): void {
        // When a field has no rules, it should be considered valid
        $result = $this->validator->isValid('unknown_field', 'any value');
        $this->assertTrue($result, 'Field without rules should be valid');
    }

    public function testValidateWithMissingOptionalFields(): void {
        $this->validator->addRule('name', new RequiredRule());

        $data = []; // Missing 'name' field

        try {
            $this->validator->validate($data);
            $this->fail('ValidationException should have been thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('name', $errors);
        }
    }

    public function testValidatorReturnsFluentInterface(): void {
        $result = $this->validator->addRule('name', new RequiredRule());
        $this->assertInstanceOf(Validator::class, $result);

        $result2 = $this->validator->addRules('email', new EmailRule());
        $this->assertInstanceOf(Validator::class, $result2);

        $result3 = $this->validator->setFieldLabel('email', 'Email');
        $this->assertInstanceOf(Validator::class, $result3);
    }

    public function testComplexValidationScenarioWithMultipleRules(): void {
        $this->validator
            ->addRules('name', new RequiredRule(), new StringLengthRule(min: 2, max: 50))
            ->addRules('email', new RequiredRule(), new EmailRule())
            ->addRule('phone', new StringLengthRule(min: 7, max: 15));

        // Valid data
        $validData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890'
        ];
        $this->assertTrue($this->validator->validate($validData));

        // Invalid name - too short
        $invalidData1 = [
            'name' => 'J',
            'email' => 'john@example.com',
            'phone' => '1234567890'
        ];
        try {
            $this->validator->validate($invalidData1);
            $this->fail('Should throw ValidationException for short name');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->getErrors());
        }
    }
}

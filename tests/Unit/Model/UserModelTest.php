<?php

namespace tests\Unit\Model;

use app\model\UserModel;
use PHPUnit\Framework\TestCase;

// had to make a subclass to strip out the RULE_UNIQUE on email
// because that one hits the DB which we cant do in unit tests
class TestableUserModel extends UserModel
{
    public function rules(): array
    {
        return [
            'firstName'       => [self::RULE_REQUIRED],
            'lastName'        => [self::RULE_REQUIRED],
            'email'           => [self::RULE_REQUIRED, self::RULE_EMAIL],
            'password'        => [self::RULE_REQUIRED, self::RULE_PASSWORD_COMPLEXITY],
            'confirmPassword' => [self::RULE_REQUIRED, [self::RULE_MATCH, 'match' => 'password']],
            'jobTitle'        => [self::RULE_REQUIRED],
        ];
    }
}

class UserModelTest extends TestCase
{
    // start with everything valid, then break one thing per test
    private function makeValidUser(): TestableUserModel
    {
        $user = new TestableUserModel();
        $user->firstName       = 'Jane';
        $user->lastName        = 'Smith';
        $user->email           = 'jane@example.com';
        $user->jobTitle        = 'intern';
        $user->password        = 'ValidPass1!';
        $user->confirmPassword = 'ValidPass1!';
        return $user;
    }

    // just checking the table name and primary key are right
    public function test_tableName_returns_users(): void
    {
        $this->assertSame('users', UserModel::tableName());
    }

    public function test_primaryKey_is_uid(): void
    {
        $this->assertSame('uid', UserModel::primaryKey());
    }

    public function test_attributes_list_is_correct(): void
    {
        $expected = ['uid', 'email', 'firstName', 'lastName', 'jobTitle', 'accessLevel', 'password'];
        $this->assertSame($expected, UserModel::attributes());
    }

    // getDisplayName just sticks first and last name together
    public function test_getDisplayName_works(): void
    {
        $user = new UserModel();
        $user->firstName = 'John';
        $user->lastName  = 'Doe';
        $this->assertSame('John Doe', $user->getDisplayName());
    }

    public function test_getDisplayName_when_names_are_empty(): void
    {
        $user = new UserModel();
        // both default to '' so should just get a space
        $this->assertSame(' ', $user->getDisplayName());
    }

    // TC13 VALIDATION TESTS
    // password field tests
    public function test_password_with_no_uppercase_fails(): void
    {
        $user = $this->makeValidUser();
        $user->password        = 'lowercase1!';
        $user->confirmPassword = 'lowercase1!';
        $user->validate();
        $this->assertArrayHasKey('password', $user->errors);
    }

    public function test_password_with_no_lowercase_fails(): void
    {
        $user = $this->makeValidUser();
        $user->password        = 'UPPERCASE1!';
        $user->confirmPassword = 'UPPERCASE1!';
        $user->validate();
        $this->assertArrayHasKey('password', $user->errors);
    }

    public function test_password_with_no_number_fails(): void
    {
        $user = $this->makeValidUser();
        $user->password        = 'NoDigits!!A';
        $user->confirmPassword = 'NoDigits!!A';
        $user->validate();
        $this->assertArrayHasKey('password', $user->errors);
    }

    public function test_password_with_no_special_char_fails(): void
    {
        $user = $this->makeValidUser();
        $user->password        = 'NoSpecial1A';
        $user->confirmPassword = 'NoSpecial1A';
        $user->validate();
        $this->assertArrayHasKey('password', $user->errors);
    }

    public function test_password_too_short_fails(): void
    {
        $user = $this->makeValidUser();
        $user->password        = 'Ab1!';
        $user->confirmPassword = 'Ab1!';
        $user->validate();
        $this->assertArrayHasKey('password', $user->errors);
    }

    public function test_good_password_passes(): void
    {
        $user = $this->makeValidUser();
        $user->validate();
        $this->assertArrayNotHasKey('password', $user->errors);
    }

    // required field checks
    public function test_missing_firstName_fails(): void
    {
        $user = $this->makeValidUser();
        $user->firstName = '';
        $user->validate();
        $this->assertArrayHasKey('firstName', $user->errors);
    }

    public function test_missing_lastName_fails(): void
    {
        $user = $this->makeValidUser();
        $user->lastName = '';
        $user->validate();
        $this->assertArrayHasKey('lastName', $user->errors);
    }

    public function test_missing_jobTitle_fails(): void
    {
        $user = $this->makeValidUser();
        $user->jobTitle = '';
        $user->validate();
        $this->assertArrayHasKey('jobTitle', $user->errors);
    }

    // confirmPassword has to match password
    public function test_passwords_not_matching_fails(): void
    {
        $user = $this->makeValidUser();
        $user->confirmPassword = 'SomethingElse1!';
        $user->validate();
        $this->assertArrayHasKey('confirmPassword', $user->errors);
    }

    public function test_matching_passwords_pass(): void
    {
        $user = $this->makeValidUser();
        $user->validate();
        $this->assertArrayNotHasKey('confirmPassword', $user->errors);
    }
}
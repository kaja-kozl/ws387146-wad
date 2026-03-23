<?php

namespace tests\Unit\Model;

use app\model\LoginForm;
use PHPUnit\Framework\TestCase;

class LoginFormTest extends TestCase
{
    // Seeding data
    private function makeForm(string $email, string $password): LoginForm
    {
        $form = new LoginForm();
        $form->loadData(['email' => $email, 'password' => $password]);
        return $form;
    }

    // FAIL empty email
    public function test_empty_email_fails_validation(): void
    {
        $form = $this->makeForm('', 'SomePass1!');
        $form->validate();
        $this->assertArrayHasKey('email', $form->errors);
    }

    // FAIL random string with no @
    public function test_malformed_email_fails_validation(): void
    {
        $form = $this->makeForm('notanemail', 'SomePass1!');
        $form->validate();
        $this->assertArrayHasKey('email', $form->errors);
    }

    // FAIL no dot in the domain
    public function test_email_missing_tld_fails_validation(): void
    {
        $form = $this->makeForm('user@nodot', 'SomePass1!');
        $form->validate();
        $this->assertArrayHasKey('email', $form->errors);
    }

    // PASS correct password, correct email
    public function test_valid_email_passes(): void
    {
        $form = $this->makeForm('user@example.com', 'SomePass1!');
        $form->validate();
        $this->assertArrayNotHasKey('email', $form->errors);
    }

    // FAIL empty password
    public function test_empty_password_fails_validation(): void
    {
        $form = $this->makeForm('user@example.com', '');
        $form->validate();
        $this->assertArrayHasKey('password', $form->errors);
    }

    // FAIL password doesn't meet complexity
    public function test_non_empty_password_passes(): void
    {
        $form = $this->makeForm('user@example.com', 'anything');
        $form->validate();
        $this->assertArrayNotHasKey('password', $form->errors);
    }

    // FAIL both empty
    public function test_both_fields_empty_returns_false(): void
    {
        $form = $this->makeForm('', '');
        $this->assertFalse($form->validate());
    }

    // note: not testing login() 
    public function test_valid_data_returns_true(): void
    {
        $form = $this->makeForm('user@example.com', 'anything');
        $this->assertTrue($form->validate());
    }
}
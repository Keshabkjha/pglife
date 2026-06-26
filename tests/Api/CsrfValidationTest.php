<?php

use PHPUnit\Framework\TestCase;

class CsrfValidationTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
    }

    public function test_csrf_fails_without_token()
    {
        // Simulate missing CSRF token
        $_POST = ['email' => 'keshab@example.com', 'password' => 'wrongpassword'];
        $_SESSION = ['csrf_token' => 'valid-token'];
        
        $old_cwd = getcwd();
        chdir(__DIR__ . '/../../api');
        
        ob_start();
        require 'login_submit.php';
        $output = ob_get_clean();
        
        chdir($old_cwd);
        
        $this->assertStringContainsString('Security verification failed', $output);
    }

    public function test_csrf_passes_with_valid_token()
    {
        // Simulate valid CSRF token but incorrect credentials
        $_POST = [
            'email' => 'keshab@example.com',
            'password' => 'wrongpassword',
            'csrf_token' => 'valid-token'
        ];
        $_SESSION = ['csrf_token' => 'valid-token'];
        
        $old_cwd = getcwd();
        chdir(__DIR__ . '/../../api');
        
        ob_start();
        require 'login_submit.php';
        $output = ob_get_clean();
        
        chdir($old_cwd);
        
        $this->assertStringContainsString('Login failed', $output);
    }
}

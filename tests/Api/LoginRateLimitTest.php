<?php

use PHPUnit\Framework\TestCase;

class LoginRateLimitTest extends TestCase
{
    private $db_conn;
    private $test_ip = '127.0.0.99';

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $_POST = [];
        $_SERVER['REMOTE_ADDR'] = $this->test_ip;

        $db_host = getenv('DB_HOST') ?: "localhost:3307";
        $db_user = getenv('DB_USER') ?: "root";
        $db_password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : "";
        $db_name = getenv('DB_NAME') ?: "pglife";
        $this->db_conn = mysqli_connect($db_host, $db_user, $db_password, $db_name);
        
        if (mysqli_connect_errno()) {
            $this->markTestSkipped("Database connection unavailable for testing.");
        }
        
        // Clean rate limits
        mysqli_query($this->db_conn, "DELETE FROM rate_limits WHERE ip_address = '" . $this->test_ip . "'");
    }

    protected function tearDown(): void
    {
        if ($this->db_conn) {
            mysqli_query($this->db_conn, "DELETE FROM rate_limits WHERE ip_address = '" . $this->test_ip . "'");
            mysqli_close($this->db_conn);
        }
        $_SESSION = [];
        $_POST = [];
        unset($_SERVER['REMOTE_ADDR']);
    }

    public function test_login_rate_limiting_blocks_after_max_attempts()
    {
        $_SESSION['csrf_token'] = 'test-token';
        $_POST = [
            'email' => 'nonexistent@example.com',
            'password' => 'wrong',
            'csrf_token' => 'test-token'
        ];

        $old_cwd = getcwd();
        chdir(__DIR__ . '/../../api');

        // Execute 10 times (which is the max attempts)
        for ($i = 0; $i < 10; $i++) {
            ob_start();
            require 'login_submit.php';
            $output = ob_get_clean();
            
            $response = json_decode($output, true);
            $this->assertStringContainsString("Login failed", $response['message']);
        }

        // The 11th attempt should trigger the IP rate limit
        ob_start();
        require 'login_submit.php';
        $output = ob_get_clean();
        
        chdir($old_cwd);

        $response = json_decode($output, true);
        $this->assertFalse($response['success']);
        $this->assertStringContainsString("Too many login attempts", $response['message']);
    }
}

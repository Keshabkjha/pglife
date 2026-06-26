<?php

use PHPUnit\Framework\TestCase;

class ChatAuthorizationTest extends TestCase
{
    private $db_conn;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $_POST = [];

        $db_host = getenv('DB_HOST') ?: "localhost:3307";
        $db_user = getenv('DB_USER') ?: "root";
        $db_password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : "";
        $db_name = getenv('DB_NAME') ?: "pglife";
        $this->db_conn = mysqli_connect($db_host, $db_user, $db_password, $db_name);
        
        if (mysqli_connect_errno()) {
            $this->markTestSkipped("Database connection unavailable for testing.");
        }
        
        // Clean up first
        mysqli_query($this->db_conn, "DELETE FROM messages WHERE property_id = 9990");
        mysqli_query($this->db_conn, "DELETE FROM interested_users_properties WHERE user_id IN (9990, 9991, 9992) OR property_id = 9990");
        mysqli_query($this->db_conn, "DELETE FROM bookings WHERE user_id IN (9990, 9991, 9992) OR property_id = 9990");
        mysqli_query($this->db_conn, "DELETE FROM properties WHERE id = 9990");
        mysqli_query($this->db_conn, "DELETE FROM users WHERE id IN (9990, 9991, 9992)");
        
        // Insert owner, seeker1, seeker2
        mysqli_query($this->db_conn, "INSERT INTO users (id, email, password, full_name, phone, gender, institution_or_organization, role) VALUES (9990, 'owner@example.com', 'password', 'Owner User', '1111111110', 'male', 'Uni', 'owner')");
        mysqli_query($this->db_conn, "INSERT INTO users (id, email, password, full_name, phone, gender, institution_or_organization, role) VALUES (9991, 'seeker1@example.com', 'password', 'Seeker 1', '1111111111', 'male', 'Uni', 'seeker')");
        mysqli_query($this->db_conn, "INSERT INTO users (id, email, password, full_name, phone, gender, institution_or_organization, role) VALUES (9992, 'seeker2@example.com', 'password', 'Seeker 2', '1111111112', 'male', 'Uni', 'seeker')");
        
        // Insert property
        mysqli_query($this->db_conn, "INSERT INTO properties (id, city_id, owner_id, name, address, description, gender, rent, rating_clean, rating_food, rating_safety) VALUES (9990, 1, 9990, 'Test Chat Property', 'Address', 'Desc', 'male', 5000, 5, 5, 5)");
    }

    protected function tearDown(): void
    {
        if ($this->db_conn) {
            mysqli_query($this->db_conn, "DELETE FROM messages WHERE property_id = 9990");
            mysqli_query($this->db_conn, "DELETE FROM interested_users_properties WHERE user_id IN (9990, 9991, 9992) OR property_id = 9990");
            mysqli_query($this->db_conn, "DELETE FROM bookings WHERE user_id IN (9990, 9991, 9992) OR property_id = 9990");
            mysqli_query($this->db_conn, "DELETE FROM properties WHERE id = 9990");
            mysqli_query($this->db_conn, "DELETE FROM users WHERE id IN (9990, 9991, 9992)");
            mysqli_close($this->db_conn);
        }
        $_SESSION = [];
        $_POST = [];
    }

    public function test_unauthorized_seeker_cannot_message_owner()
    {
        $_SESSION = [
            'user_id' => 9991,
            'role' => 'seeker',
            'full_name' => 'Seeker 1',
            'csrf_token' => 'test-csrf-token'
        ];
        
        $_POST = [
            'receiver_id' => 9990,
            'property_id' => 9990,
            'message' => 'Hello owner!',
            'csrf_token' => 'test-csrf-token'
        ];

        $old_cwd = getcwd();
        chdir(__DIR__ . '/../../api');
        
        ob_start();
        require 'send_message.php';
        $output = ob_get_clean();
        
        chdir($old_cwd);

        $response = json_decode($output, true);
        $this->assertFalse($response['success'], "Seeker with no association should fail to send message.");
        $this->assertStringContainsString("You are not associated with this property", $response['message']);
    }

    public function test_authorized_seeker_can_message_owner()
    {
        // Associate Seeker 2 with property
        mysqli_query($this->db_conn, "INSERT INTO interested_users_properties (user_id, property_id) VALUES (9992, 9990)");

        $_SESSION = [
            'user_id' => 9992,
            'role' => 'seeker',
            'full_name' => 'Seeker 2',
            'csrf_token' => 'test-csrf-token'
        ];
        
        $_POST = [
            'receiver_id' => 9990,
            'property_id' => 9990,
            'message' => 'Hello owner, I am interested!',
            'csrf_token' => 'test-csrf-token'
        ];

        $old_cwd = getcwd();
        chdir(__DIR__ . '/../../api');
        
        ob_start();
        require 'send_message.php';
        $output = ob_get_clean();
        
        chdir($old_cwd);

        $response = json_decode($output, true);
        $this->assertTrue($response['success'], "Interested seeker should succeed sending message. Output: " . $output);
    }
}

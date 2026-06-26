<?php

use PHPUnit\Framework\TestCase;

class InventoryConcurrencyTest extends TestCase
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
        
        // Delete child references first
        mysqli_query($this->db_conn, "DELETE FROM interested_users_properties WHERE user_id IN (9999, 9998) OR user_id IN (SELECT id FROM users WHERE email IN ('testseeker@example.com', 'testseeker2@example.com'))");
        mysqli_query($this->db_conn, "DELETE b FROM bookings b INNER JOIN users u ON b.user_id = u.id WHERE u.email IN ('testseeker@example.com', 'testseeker2@example.com')");
        mysqli_query($this->db_conn, "DELETE FROM bookings WHERE user_id IN (9999, 9998) OR property_id = 9999");
        mysqli_query($this->db_conn, "DELETE FROM room_types WHERE property_id = 9999");
        mysqli_query($this->db_conn, "DELETE FROM properties WHERE id = 9999");
        mysqli_query($this->db_conn, "DELETE FROM users WHERE id IN (9999, 9998) OR email IN ('testseeker@example.com', 'testseeker2@example.com')");
        
        // Insert test user, property, and room type
        mysqli_query($this->db_conn, "INSERT INTO users (id, email, password, full_name, phone, gender, institution_or_organization, role) VALUES (9999, 'testseeker@example.com', 'password', 'Test Seeker', '1234567890', 'male', 'Uni', 'seeker')");
        mysqli_query($this->db_conn, "INSERT INTO properties (id, city_id, owner_id, name, address, description, gender, rent, rating_clean, rating_food, rating_safety) VALUES (9999, 1, null, 'Test Property', 'Address', 'Desc', 'male', 5000, 5, 5, 5)");
        mysqli_query($this->db_conn, "INSERT INTO room_types (id, property_id, room_type, label, price_per_month, total_beds, occupied_beds, is_active) VALUES (9999, 9999, 'single', 'AC Single', 5000, 1, 0, 1)");
    }

    protected function tearDown(): void
    {
        if ($this->db_conn) {
            mysqli_query($this->db_conn, "DELETE FROM interested_users_properties WHERE user_id IN (9999, 9998) OR user_id IN (SELECT id FROM users WHERE email IN ('testseeker@example.com', 'testseeker2@example.com'))");
            mysqli_query($this->db_conn, "DELETE b FROM bookings b INNER JOIN users u ON b.user_id = u.id WHERE u.email IN ('testseeker@example.com', 'testseeker2@example.com')");
            mysqli_query($this->db_conn, "DELETE FROM bookings WHERE user_id IN (9999, 9998) OR property_id = 9999");
            mysqli_query($this->db_conn, "DELETE FROM room_types WHERE property_id = 9999");
            mysqli_query($this->db_conn, "DELETE FROM properties WHERE id = 9999");
            mysqli_query($this->db_conn, "DELETE FROM users WHERE id IN (9999, 9998) OR email IN ('testseeker@example.com', 'testseeker2@example.com')");
            mysqli_close($this->db_conn);
        }
        $_SESSION = [];
        $_POST = [];
    }

    public function test_booking_enforces_room_inventory()
    {
        $_SESSION = [
            'user_id' => 9999,
            'role' => 'seeker',
            'full_name' => 'Test Seeker',
            'csrf_token' => 'test-csrf-token'
        ];
        
        $_POST = [
            'property_id' => 9999,
            'room_type_id' => 9999,
            'csrf_token' => 'test-csrf-token'
        ];

        // 1. First booking should succeed
        $old_cwd = getcwd();
        chdir(__DIR__ . '/../../api');
        
        ob_start();
        require 'book_property.php';
        $output = ob_get_clean();
        
        chdir($old_cwd);

        $response = json_decode($output, true);
        $this->assertTrue($response['success'], "First booking should succeed when beds are available. Output: " . $output);

        // Verify occupied_beds incremented
        $res = mysqli_query($this->db_conn, "SELECT occupied_beds FROM room_types WHERE id = 9999");
        $row = mysqli_fetch_assoc($res);
        $this->assertEquals(1, (int)$row['occupied_beds'], "Occupied beds should increment by 1.");

        // 2. Second booking attempt by a different user should fail because bed count is full (1 total_bed)
        mysqli_query($this->db_conn, "INSERT INTO users (id, email, password, full_name, phone, gender, institution_or_organization, role) VALUES (9998, 'testseeker2@example.com', 'password', 'Test Seeker 2', '1234567891', 'male', 'Uni', 'seeker')");
        
        $_SESSION['user_id'] = 9998;
        
        $old_cwd = getcwd();
        chdir(__DIR__ . '/../../api');
        
        ob_start();
        require 'book_property.php';
        $output2 = ob_get_clean();
        
        chdir($old_cwd);

        $response2 = json_decode($output2, true);
        $this->assertFalse($response2['success'], "Second booking should fail because the room is full. Output: " . $output2);
    }
}

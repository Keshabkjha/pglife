<?php

use PHPUnit\Framework\TestCase;

class E2eSmokeTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        // Simple curl helper
        $this->client = curl_init();
        curl_setopt($this->client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->client, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->client, CURLOPT_TIMEOUT, 5);
    }

    protected function tearDown(): void
    {
        if ($this->client) {
            curl_close($this->client);
        }
    }

    private function getBaseUrl()
    {
        // Inside docker network, access using container host port/name
        if (file_exists('/.dockerenv')) {
            return "http://localhost";
        }
        return "http://localhost:8080";
    }

    public function test_home_page_returns_200()
    {
        curl_setopt($this->client, CURLOPT_URL, $this->getBaseUrl() . "/home.php");
        curl_exec($this->client);
        $status = curl_getinfo($this->client, CURLINFO_HTTP_CODE);
        
        // Skip if server is offline or unreachable from test context
        if ($status === 0) {
            $this->markTestSkipped("Web server offline or unreachable.");
        }
        $this->assertEquals(200, $status);
    }

    public function test_health_endpoint_returns_json()
    {
        curl_setopt($this->client, CURLOPT_URL, $this->getBaseUrl() . "/api/health.php");
        $response = curl_exec($this->client);
        $status = curl_getinfo($this->client, CURLINFO_HTTP_CODE);
        
        if ($status === 0) {
            $this->markTestSkipped("Web server offline or unreachable.");
        }
        
        $this->assertEquals(200, $status);
        $data = json_decode($response, true);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('services', $data);
    }
}
?>

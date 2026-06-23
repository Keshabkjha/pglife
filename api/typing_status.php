<?php
    require("../includes/database_connect.php");
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "Not logged in."));
        return;
    }

    $user_id = (int)$_SESSION['user_id'];

    // Use a simple file-based cache for typing status (lightweight, avoids DB overhead)
    $cache_dir = sys_get_temp_dir() . '/pglife_typing';
    if (!file_exists($cache_dir)) {
        @mkdir($cache_dir, 0775, true);
    }

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        // Set typing status
        $contact_id = isset($_POST['contact_id']) ? (int)$_POST['contact_id'] : 0;
        $property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
        $is_typing = isset($_POST['is_typing']) ? (int)$_POST['is_typing'] : 0;

        if ($contact_id <= 0 || $property_id <= 0) {
            echo json_encode(array("success" => false, "message" => "Invalid parameters."));
            return;
        }

        // Store typing status: key = property_contact_user
        $cache_key = $property_id . '_' . $contact_id . '_' . $user_id;
        $cache_file = $cache_dir . '/' . $cache_key . '.json';

        $data = array(
            'user_id' => $user_id,
            'is_typing' => $is_typing,
            'timestamp' => time()
        );

        file_put_contents($cache_file, json_encode($data));

        echo json_encode(array("success" => true));

    } elseif ($method === 'GET') {
        // Check if a specific contact is typing
        $contact_id = isset($_GET['contact_id']) ? (int)$_GET['contact_id'] : 0;
        $property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;

        if ($contact_id <= 0 || $property_id <= 0) {
            echo json_encode(array("success" => false, "is_typing" => false));
            return;
        }

        // Check typing status of the contact towards us
        $cache_key = $property_id . '_' . $user_id . '_' . $contact_id;
        $cache_file = $cache_dir . '/' . $cache_key . '.json';

        $is_typing = false;
        if (file_exists($cache_file)) {
            $data = json_decode(file_get_contents($cache_file), true);
            if ($data && isset($data['is_typing']) && isset($data['timestamp'])) {
                // Typing status expires after 4 seconds
                if ($data['is_typing'] && (time() - $data['timestamp']) < 4) {
                    $is_typing = true;
                }
            }
        }

        echo json_encode(array("success" => true, "is_typing" => $is_typing));
    }

    // Cleanup old cache files (older than 1 hour)
    $files = glob($cache_dir . '/*.json');
    if ($files) {
        foreach ($files as $f) {
            if (filemtime($f) < time() - 3600) {
                @unlink($f);
            }
        }
    }
?>

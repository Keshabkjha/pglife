<?php
// api/update_room_availability.php
// Allows property owners to add, edit, or delete room types
// Seekers can express interest in a specific room type

header('Content-Type: application/json');
require_once '../includes/database_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

// Verify CSRF token
$csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($csrf) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF validation failed.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$role    = $_SESSION['role'] ?? 'seeker';
$action  = $_POST['action'] ?? '';

// ── OWNER: Save / Update room types for a property ────────────────────────────
if ($action === 'save_room_types' && $role === 'owner') {
    $property_id = (int)($_POST['property_id'] ?? 0);
    if (!$property_id) {
        echo json_encode(['success' => false, 'message' => 'Property ID is required.']);
        exit;
    }

    // Confirm the property belongs to this owner
    $check = $conn->prepare("SELECT id FROM properties WHERE id = ? AND owner_id = ?");
    $check->bind_param('ii', $property_id, $user_id);
    $check->execute();
    if (!$check->get_result()->fetch_assoc()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        exit;
    }

    // room_types_data comes as JSON array
    $rawData = $_POST['room_types_data'] ?? '[]';
    $roomsData = json_decode($rawData, true);
    if (!is_array($roomsData)) {
        echo json_encode(['success' => false, 'message' => 'Invalid room data.']);
        exit;
    }

    // Delete removed IDs
    $deletedIds = json_decode($_POST['deleted_ids'] ?? '[]', true);
    if (is_array($deletedIds) && count($deletedIds) > 0) {
        foreach ($deletedIds as $did) {
            $did = (int)$did;
            $del = $conn->prepare("DELETE FROM room_types WHERE id = ? AND property_id = ?");
            $del->bind_param('ii', $did, $property_id);
            $del->execute();
        }
    }

    // Upsert each room type
    foreach ($roomsData as $room) {
        $id           = (int)($room['id'] ?? 0);
        $room_type    = in_array($room['room_type'] ?? '', ['single','double','triple','dormitory','private'])
                        ? $room['room_type'] : 'single';
        $label        = trim(substr($room['label'] ?? '', 0, 100));
        $price        = max(0, (float)($room['price_per_month'] ?? 0));
        $total        = max(0, (int)($room['total_beds'] ?? 1));
        $occupied     = max(0, min($total, (int)($room['occupied_beds'] ?? 0)));
        $amenities    = trim(substr($room['amenities'] ?? '', 0, 500));
        $is_active    = isset($room['is_active']) ? (int)(bool)$room['is_active'] : 1;

        if ($label === '' || $price <= 0) continue; // Skip incomplete entries

        if ($id > 0) {
            // Update existing
            $stmt = $conn->prepare("UPDATE room_types SET room_type=?, label=?, price_per_month=?, total_beds=?, occupied_beds=?, amenities=?, is_active=?, updated_at=NOW() WHERE id=? AND property_id=?");
            $stmt->bind_param('ssdiisiii', $room_type, $label, $price, $total, $occupied, $amenities, $is_active, $id, $property_id);
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO room_types (property_id, room_type, label, price_per_month, total_beds, occupied_beds, amenities, is_active) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param('issdiisi', $property_id, $room_type, $label, $price, $total, $occupied, $amenities, $is_active);
        }
        $stmt->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Room types saved successfully!']);
    exit;
}

// ── GET: Fetch room types for a property (public) ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $property_id = (int)($_GET['property_id'] ?? 0);
    if (!$property_id) {
        echo json_encode(['success' => false, 'message' => 'Property ID is required.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, room_type, label, price_per_month, total_beds, occupied_beds, amenities, is_active FROM room_types WHERE property_id = ? ORDER BY price_per_month ASC");
    $stmt->bind_param('i', $property_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rooms  = [];
    while ($row = $result->fetch_assoc()) {
        $row['available_beds'] = max(0, (int)$row['total_beds'] - (int)$row['occupied_beds']);
        $rooms[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $rooms]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);

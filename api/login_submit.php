<?php
    session_start();
    require("../includes/database_connect.php");

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
        $response = array("success" => false, "message" => "Security verification failed (CSRF token mismatch).");
        echo json_encode($response);
        return;
    }

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($email) || empty($password)) {
        $response = array("success" => false, "message" => "Please fill in all fields.");
        echo json_encode($response);
        return;
    }

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        $response = array("success" => false, "message" => "Something went wrong!");
        echo json_encode($response);
        return;
    }

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        $response = array("success" => false, "message" => "Something went wrong!");
        echo json_encode($response);
        return;
    }

    $row = mysqli_fetch_assoc($result);
    if (!$row || !password_verify($password, $row['password'])) {
        $response = array("success" => false, "message" => "Login failed! Invalid email or password.");
        echo json_encode($response);
        return;
    }

    // Regenerate session ID to prevent Session Fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = $row['id'];
    $_SESSION['full_name'] = $row['full_name'];
    $_SESSION['email'] = $row['email'];

    $response = array("success" => true, "message" => "Login successful!");
    echo json_encode($response);
    mysqli_close($conn);
?>
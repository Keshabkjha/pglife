<?php
    require("../includes/database_connect.php");

    $email = $_POST['email'];
    $password = $_POST['password'];
    $password = sha1($password);
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $gender = $_POST['gender'];
    $college_name = $_POST['college_name'];

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $sql);
    if(!$result){
        $response = array("success" => false, "message" => "Something went wrong!");
        echo json_encode($response);
        return;
    }

    $row_count = mysqli_num_rows($result);
    if ($row_count != 0) {
        $response = array("success" => true, "message" => "This email id is already registered with us!");
        echo json_encode($response);
        return;
    }

    $sql = "INSERT INTO users (email, password, full_name, phone, gender, college_name) VALUES ('$email', '$password', '$full_name', '$phone', '$gender', '$college_name')";
    $result = mysqli_query($conn, $sql);
    if(!$result){
        $response = array("success" => false, "message" => "Something went wrong!");
        echo json_encode($response);
        return;
    }
    
    $response = array("success" => true, "message" => "Account successfully created!");
    echo json_encode($response);
    mysqli_close($conn);
?>
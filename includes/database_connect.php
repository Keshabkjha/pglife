<?php
    $conn = mysqli_connect("localhost:3307", "root", "", "pglife");
    
    if(mysqli_connect_errno()){
        echo "Connection Error: ".mysqli_connect_error();
        return;
    }
?>
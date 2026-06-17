<?php
    require_once __DIR__ . "/database_connect.php";

    $interested_count = 0;
    $booking_count = 0;
    if (isset($_SESSION['user_id'])) {
        $user_id = (int)$_SESSION['user_id'];
        
        // Count interested properties
        $sql_int_cnt = "SELECT COUNT(*) as cnt FROM interested_users_properties WHERE user_id = ?";
        $stmt_int_cnt = mysqli_prepare($conn, $sql_int_cnt);
        if ($stmt_int_cnt) {
            mysqli_stmt_bind_param($stmt_int_cnt, "i", $user_id);
            mysqli_stmt_execute($stmt_int_cnt);
            $res_int_cnt = mysqli_stmt_get_result($stmt_int_cnt);
            if ($res_int_cnt) {
                $row = mysqli_fetch_assoc($res_int_cnt);
                $interested_count = (int)$row['cnt'];
            }
            mysqli_stmt_close($stmt_int_cnt);
        }

        // Count booked properties
        $sql_book_cnt = "SELECT COUNT(*) as cnt FROM bookings WHERE user_id = ?";
        $stmt_book_cnt = mysqli_prepare($conn, $sql_book_cnt);
        if ($stmt_book_cnt) {
            mysqli_stmt_bind_param($stmt_book_cnt, "i", $user_id);
            mysqli_stmt_execute($stmt_book_cnt);
            $res_book_cnt = mysqli_stmt_get_result($stmt_book_cnt);
            if ($res_book_cnt) {
                $row = mysqli_fetch_assoc($res_book_cnt);
                $booking_count = (int)$row['cnt'];
            }
            mysqli_stmt_close($stmt_book_cnt);
        }
    }
?>
<div class="header sticky-top">
    <nav class="navbar navbar-expand-md navbar-light">
        <a class="navbar-brand" href="home.php">
            <img src="img/logo.png" />
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#my-navbar">
            <span class="navbar-toggler-icon"></span>
        </button>
            
        <div class="collapse navbar-collapse justify-content-end" id="my-navbar">
            <ul class="navbar-nav">
            <?php
                if (!isset($_SESSION['user_id'])) {
            ?>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-toggle="modal" data-target="#signup-modal">
                        <i class="fas fa-user"></i>Signup
                    </a>
                </li>
                <div class="nav-vl"></div>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-toggle="modal" data-target="#login-modal">
                        <i class="fas fa-sign-in-alt"></i>Login
                    </a>
                </li>
                <?php
                    } else {
                ?>
                <div class='nav-name'>
                    Hi, <?= htmlspecialchars($_SESSION["full_name"]) ?>
                </div>
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-user"></i>Dashboard
                        <?php if ($interested_count > 0 || $booking_count > 0) { ?>
                            <span class="badge badge-pill badge-primary" style="font-size: 10px; padding: 3px 6px;">
                                <?php if ($booking_count > 0) { ?>
                                    <i class="fas fa-bookmark" style="margin-right: 2px;"></i><?= $booking_count ?>
                                <?php } ?>
                                <?php if ($interested_count > 0) { ?>
                                    <i class="fas fa-heart" style="margin-right: 2px; margin-left: 4px;"></i><?= $interested_count ?>
                                <?php } ?>
                            </span>
                        <?php } ?>
                    </a>
                </li>
                <div class="nav-vl"></div>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>Logout
                    </a>
                </li> 
                <?php
                }
                ?>  
            </ul>
        </div>
    </nav>    
</div>

<div id="loading"></div>
<script>
    window.csrf_token = "<?= htmlspecialchars($_SESSION['csrf_token']) ?>";
</script>
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
        <a class="navbar-brand" href="/home">
            <img src="img/logo.png" alt="PG Life" />
        </a>
        <button class="mobile-menu-btn d-md-none" type="button" id="mobile-menu-btn" aria-label="Open navigation menu">
            <i class="fas fa-bars"></i>
        </button>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#my-navbar">
            <span class="navbar-toggler-icon"></span>
        </button>
            
        <div class="collapse navbar-collapse justify-content-end" id="my-navbar">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <button type="button" class="dark-mode-toggle" id="dark-mode-toggle" title="Toggle dark mode">
                        <i class="fas fa-moon" id="dark-mode-icon"></i>
                    </button>
                </li>
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
                <div class='nav-name d-flex align-items-center'>
                    <?php
                        $header_avatar = 'img/man.png';
                        if (!empty($_SESSION['profile_pic'])) {
                            $header_avatar = $_SESSION['profile_pic'];
                        } elseif (isset($_SESSION['gender']) && $_SESSION['gender'] === 'female') {
                            $header_avatar = 'img/Female_icon.png';
                        }
                    ?>
                    <img src="<?= htmlspecialchars($header_avatar) ?>" class="rounded-circle mr-2" style="width: 25px; height: 25px; object-fit: cover; border: 1px solid rgba(0,0,0,0.1);" alt="Avatar" />
                    Hi, <?= htmlspecialchars($_SESSION["full_name"]) ?>
                </div>
                <li class="nav-item">
                    <a class="nav-link" href="/dashboard">
                        <i class="fas fa-user"></i><?= (isset($_SESSION['role']) && $_SESSION['role'] === 'owner') ? 'Owner Dashboard' : 'Dashboard' ?>
                        <?php if (($interested_count > 0 || $booking_count > 0) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner')) { ?>
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
                    <a class="nav-link" href="/logout">
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

<!-- Mobile Slide-out Drawer -->
<div class="mobile-drawer-overlay" id="mobile-drawer-overlay"></div>
<nav class="mobile-drawer" id="mobile-drawer">
    <div class="mobile-drawer-header">
        <a class="navbar-brand" href="/home">
            <img src="img/logo.png" alt="PG Life" />
        </a>
        <button type="button" class="mobile-drawer-close" id="mobile-drawer-close" aria-label="Close menu">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <ul class="mobile-drawer-nav">
        <li class="mobile-drawer-item">
            <button type="button" class="dark-mode-toggle" id="dark-mode-toggle-drawer" title="Toggle dark mode">
                <i class="fas fa-moon" id="dark-mode-icon-drawer"></i> <span>Dark Mode</span>
            </button>
        </li>
    <?php if (!isset($_SESSION['user_id'])) { ?>
        <li class="mobile-drawer-item">
            <a href="#" data-toggle="modal" data-target="#signup-modal" class="mobile-drawer-link" data-close-drawer="1">
                <i class="fas fa-user"></i>Signup
            </a>
        </li>
        <li class="mobile-drawer-item">
            <a href="#" data-toggle="modal" data-target="#login-modal" class="mobile-drawer-link" data-close-drawer="1">
                <i class="fas fa-sign-in-alt"></i>Login
            </a>
        </li>
    <?php } else { ?>
        <li class="mobile-drawer-item mobile-drawer-user">
            <?php
                $drawer_avatar = 'img/man.png';
                if (!empty($_SESSION['profile_pic'])) {
                    $drawer_avatar = $_SESSION['profile_pic'];
                } elseif (isset($_SESSION['gender']) && $_SESSION['gender'] === 'female') {
                    $drawer_avatar = 'img/Female_icon.png';
                }
            ?>
            <img src="<?= htmlspecialchars($drawer_avatar) ?>" class="rounded-circle mr-2" style="width: 28px; height: 28px; object-fit: cover;" alt="Avatar" />
            <span>Hi, <?= htmlspecialchars($_SESSION["full_name"]) ?></span>
        </li>
        <li class="mobile-drawer-item">
            <a href="/dashboard" class="mobile-drawer-link">
                <i class="fas fa-tachometer-alt"></i><?= (isset($_SESSION['role']) && $_SESSION['role'] === 'owner') ? 'Owner Dashboard' : 'Dashboard' ?>
                <?php if (($interested_count > 0 || $booking_count > 0) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner')) { ?>
                    <span class="badge badge-pill badge-primary ml-2" style="font-size: 10px; padding: 3px 6px;">
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
        <li class="mobile-drawer-item">
            <a href="/logout" class="mobile-drawer-link">
                <i class="fas fa-sign-out-alt"></i>Logout
            </a>
        </li>
    <?php } ?>
    </ul>
</nav>
<script>
    window.csrf_token = "<?= htmlspecialchars($_SESSION['csrf_token']) ?>";
</script>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!---CSS--->
    <link rel="stylesheet" href="../css/global.css">
    <!---ICON--->
    <script src="https://kit.fontawesome.com/92cde7fc6f.js" crossorigin="anonymous"></script>
    <link rel="icon" type="image/x-icon" href="../assets/RESONO_logo.ico">
    <!---BOOTSTRAP--->
    <link rel="stylesheet" href="../node_modules/bootstrap/dist/css/bootstrap.min.css">
    <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!---FONT--->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <!----AOS LIBRARY---->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

</head>

<body>

    <div class="grid-container">
        <aside id="rsn-sidebar">
            <div class="logout-container">
                <img src="../assets/RESONO_logo_edited.png" width="100px" alt="">
                <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')"><button class="btn-logout"><i class="fa-solid fa-power-off"></i></button></a>
                <br>
                <p>Welcome, <strong><?php
                                    echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Guest';
                                    ?> </strong></p>
            </div>

            <ul class="sidebar-list" data-aos="fade-right">
                <li>
                    <a class="sidebar-dropdown d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#generalSubmenu" role="button" aria-expanded="false" aria-controls="generalSubmenu">
                        <span><i class="fa-solid fa-house"></i>GENERAL</span>
                        <i class="fa-solid fa-caret-down"></i>
                    </a>

                    <ul class="collapse sidebar-submenu list-unstyled ps-3" id="generalSubmenu">
                        <li class="sidebar-list-item" data-page="my-tracker" onclick="changePage('my-tracker')">My Tracker</li>
                        <li class="sidebar-list-item" data-page="monthly-summary" onclick="changePage('monthly-summary')">Monthly Summary</li>
                        <li class="sidebar-list-item" data-page="dtr-amendment" onclick="changePage('dtr-amendment')">DTR Amendment</li>
                    </ul>
                </li>

                <!----USER MANAGEMENT---->
                <li>
                    <a class="sidebar-dropdown d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#userManagementmenu" role="button" aria-expanded="false" aria-controls="userManagementmenu">
                        <span><i class="fa-solid fa-gear"></i>USER MANAGEMENT</span>
                        <i class="fa-solid fa-caret-down"></i>
                    </a>

                    <ul class="collapse sidebar-submenu list-unstyled ps-3" id="userManagementmenu">

                        <li class="sidebar-list-item" data-page="addUsers" onclick="changePage('addUsers')">Add Users</li>
                        <li class="sidebar-list-item" data-page="changePass" onclick="changePage('changePass')">Change Password</li>
                    </ul>
                </li>
                <!----USER MANAGEMENT END---->

                <!----SYSTEM SETTINGS---->
                <li>
                    <a class="sidebar-dropdown d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#systemSettingsmenu" role="button" aria-expanded="false" aria-controls="systemSettingsmenu">
                        <span><i class="fa-solid fa-gear"></i>SYSTEM SETTINGS</span>
                        <i class="fa-solid fa-caret-down"></i>
                    </a>

                    <ul class="collapse sidebar-submenu list-unstyled ps-3" id="systemSettingsmenu">

                        <li class="sidebar-list-item" data-page="addUsers" onclick="changePage('addUsers')">Add Users</li>
                        <li class="sidebar-list-item" data-page="create-work-mode" onclick="changePage('create-work-mode')">Create Work Mode</li>
                        <li class="sidebar-list-item" data-page="changePass" onclick="changePage('changePass')">Change Password</li>
                    </ul>
                </li>
                <!----SYSTEM SETTINGS END---->
            </ul>
        </aside>

        <div class="rsn-main-container">

            <!---MY TRACKER PAGE--->
            <div id="my-tracker-page" class="page-content">
                <div class="main-title">
                    <h1>MY TRACKER</h1>
                    <script src="javascripts/realTimeClock.js"></script>
                    <div class="time-container">
                        <h3 id="live-date" class="fw-bold"></h3>
                        <h6 id="live-time" class="text-muted"></h6>
                    </div>
                </div>
            </div>

            <div id="monthly-summary-page" class="page-content">
                <div class="main-title">
                    <h1>MONTHLY SUMMARY</h1>
                    <script src="javascripts/realTimeClock.js"></script>
                    <div class="time-container">
                        <h3 id="live-date" class="fw-bold"></h3>
                        <h6 id="live-time" class="text-muted"></h6>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!---JS LINKS HERE--->
    <script src="../js/sidebar.js"></script>
    <script src="../js/real-time-clock.js"></script>

    <!-- AOS JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            offset: 100, // Start animation 100px before the section is in view
            duration: 800, // Animation duration in milliseconds
            easing: 'ease-in-out', // Smooth transition effect
        });
    </script>

</body>

</html>
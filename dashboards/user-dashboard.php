<?php
session_start();
require_once "../backend/connection_db.php"; // Adjust path if needed

// Check if user is logged in and has an authorized role
$allowedRoles = ['user'];

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowedRoles)) {
    header("Location: ../backend/login.php"); // Adjust if your login page is in another folder
    exit;
}

$loggedInUserRole = $_SESSION['role'];

?>

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
    <!---SESSION STORAGE--->
    <script>
        sessionStorage.setItem("user_id", "<?php echo $_SESSION['user_id']; ?>");
    </script>
</head>

<body>

    <div class="grid-container">
        <aside id="rsn-sidebar">
            <div class="logout-container">  
                <img src="../assets/RESONO_logo_edited.png" width="100px" alt="">
                <a href="../backend/logout.php" onclick="return confirm('Are you sure you want to log out?')"><button class="btn-logout"><i class="fa-solid fa-power-off"></i></button></a>
                <br>
                <p class="text-center">Welcome, <br><strong><?php
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
                    </ul>
                </li>

                <!----USER MANAGEMENT---->
                <li>
                    <a class="sidebar-dropdown d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#userManagementmenu" role="button" aria-expanded="false" aria-controls="userManagementmenu">
                        <span><i class="fa-solid fa-gear"></i>USER MANAGEMENT</span>
                        <i class="fa-solid fa-caret-down"></i>
                    </a>

                    <ul class="collapse sidebar-submenu list-unstyled ps-3" id="userManagementmenu">
                        <li class="sidebar-list-item" data-page="edit-profile" onclick="changePage('edit-profile')">Edit Profile</li>
                    </ul>
                </li>
                <!----USER MANAGEMENT END---->

            </ul>
        </aside>

        <div class="rsn-main-container">

            <!---MY TRACKER PAGE--->
            <div id="my-tracker-page" class="page-content">
                <div class="main-title">
                    <h1>MY TRACKER</h1>
                    <div class="time-container">
                        <h3 id="live-date" class="fw-bold"></h3>
                        <h6 id="live-time" class="text-muted"></h6>
                    </div>
                </div>

                <div class="rsn-main-cards">

                    <!-- WMT Task Tagging -->
                    <div class="tag-container">
                        <!-- Work Mode and Task Selection -->
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <select id="workModeSelector" class="form-select w-auto" onchange="updateTaskOptions()">
                                <option value="">-- Select Work Mode --</option>
                                <!-- Options will be populated dynamically -->
                            </select>

                            <select id="taskSelector" class="form-select w-auto">
                                <option value="">-- Select Task --</option>
                                <!-- Task options will be populated based on selected Work Mode -->
                            </select>
                            <br>

                            <div id="slideButtonWrapper" class="slide-button-wrapper">
                                <div class="slide-button-handle" id="slideButtonHandle">▶ Slide to Tag</div>
                            </div>
                        </div>

                        <!-- Task Log Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered text-center" id="wmtLogTable">
                                <thead class="table">
                                    <tr>
                                        <th>Date</th>
                                        <th>Work Mode</th>
                                        <th>Task Description</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Total Time Spent</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Task rows go here dynamically -->
                                </tbody>
                            </table>
                            <!---button class="btn btn-danger mb-2" onclick="resetTaskLog()">Reset Table</button---CAN BE USE FOR TESTING PURPOSES--->
                        </div>
                    </div>
                </div>
            </div>


            <!---MONTHLY SUMMARY PAGE--->
            <div id="monthly-summary-page" class="page-content">
                <div class="main-title">
                    <h1>MONTHLY SUMMARY</h1>
                </div>

                <div class="filters mb-3 d-flex gap-2">
                    <input type="text" id="searchUser" placeholder="Search User" class="form-control" />
                    <input type="month" id="monthFilter" class="form-control" />
                    <button class="btn btn-primary" onclick="loadMonthlySummary()">Search</button>
                </div>

                <div id="summaryTableWrapper" class="table-responsive">
                    <table class="table table-bordered table-striped" id="summaryTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Login</th>
                                <th>Logout</th>
                                <th>Total Time</th>
                                <th>Production</th>
                                <th>Offphone</th>
                                <th>Training</th>
                                <th>Resono</th>
                                <th>Paid Break</th>
                                <th>Unpaid Break</th>
                                <th>Personal Time</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>


    <!-- GLOBAL OVERLAY LOADER WITH SPINNER -->
    <div id="globalOverlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.3);z-index:9999;justify-content:center;align-items:center;">
        <div class="text-center">
            <div class="spinner-border text-success" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="mt-3 text-white fw-bold">Processing...</div>
        </div>
    </div>



    <!---JS LINKS HERE--->
    <script src="../js/start-tag-task.js"></script>
    <script src="../js/slider-function.js"></script>
    <script src="../js/load-monthly-summary.js"></script>
    <script src="../js/sidebar.js"></script>
    <script src="../js/real-time-clock.js"></script>
    <script src="../js/toggle-password.js"></script>


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
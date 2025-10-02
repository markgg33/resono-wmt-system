<?php
session_start();
require_once "../backend/connection_db.php"; // Adjust path if needed


// Check if user is logged in and has an authorized role
$allowedRoles = ['client'];

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header("Location: ../errors/401.php");
    exit;
}

if (!in_array($_SESSION['role'], $allowedRoles)) {
    http_response_code(403);
    header("Location: ../errors/403.php");
    exit;
}

$loggedInUserRole = $_SESSION['role'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard</title>
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
        sessionStorage.setItem("userRole", "<?php echo $_SESSION['role']; ?>");
        const userRole = "<?php echo $loggedInUserRole; ?>"; // make it accessible as a JS variable
    </script>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>

</head>

<body>

    <div class="grid-container">

        <!---HEADER--->
        <header class="header">
            <img src="../assets/RESONO_logo_edited.png" width="50px" alt="">
            <div class="time-container text-center">
                <h5 id="live-date" class="fw-bold"></h5>
                <h6 id="live-time" class="text-muted"></h6>
            </div>
            <a href="../backend/logout.php" onclick="return confirm('Are you sure you want to log out?')"><button class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i></button></a>
        </header>

        <!---SIDEBAR--->
        <aside id="rsn-sidebar">
            <div class="profile-container">
                <br>
                <?php
                $profilePath = !empty($_SESSION['profile_image']) ? "../" . $_SESSION['profile_image'] : "";
                if (empty($profilePath) || !file_exists($profilePath)) {
                    $profilePath = "../assets/default-avatar.jpg";
                }
                ?>
                <img src="<?php echo htmlspecialchars($profilePath); ?>"
                    alt="Profile Image"
                    class="rounded-circle mb-2"
                    width="150" height="150"
                    style="object-fit: cover;">
                <p class="text-center">Welcome, <br><strong><?php
                                                            echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Guest';
                                                            ?> </strong></p>
            </div>

            <ul class="sidebar-list" data-aos="fade-right">
                <li>
                    <a class="sidebar-dropdown d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#statusSubmenu" role="button" aria-expanded="false" aria-controls="statusSubmenu">
                        <span><i class="fa-solid fa-gauge"></i>DASHBOARD</span>
                        <i class="fa-solid fa-caret-down"></i>
                    </a>

                    <ul class="collapse sidebar-submenu list-unstyled ps-3" id="statusSubmenu">
                        <li class="sidebar-list-item" data-page="data-visualization" onclick="changePage('data-visualization')">Data Visualization</li>
                        <li class="sidebar-list-item" data-page="status-dashboard" onclick="changePage('status-dashboard')">Status Dashboard</li>
                    </ul>
                </li>

                <!----SYSTEM SETTINGS---->
                <li>
                    <a class="sidebar-dropdown d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#systemSettingsmenu" role="button" aria-expanded="false" aria-controls="systemSettingsmenu">
                        <span><i class="fa-solid fa-gear"></i>SYSTEM SETTINGS</span>
                        <i class="fa-solid fa-caret-down"></i>
                    </a>

                    <ul class="collapse sidebar-submenu list-unstyled ps-3" id="systemSettingsmenu">
                        <li class="sidebar-list-item" data-page="edit-profile" onclick="changePage('edit-profile')">Edit Profile</li>
                    </ul>
                </li>
                <!----SYSTEM SETTINGS END---->
            </ul>
        </aside>

        <div class="rsn-main-container">

            <!-- DASHBOARD PAGE -->
            <div id="status-dashboard-page" class="page-content container-fluid">
                <div class="main-title mb-4 d-flex justify-content-between align-items-center">
                    <h1 class="fw-bold">STATUS DASHBOARD</h1>

                    <!-- Department Filter -->
                    <div>
                        <select id="dashDepartmentFilter" class="form-select shadow-sm">
                            <option value="">All Departments</option>
                        </select>
                    </div>
                </div>

                <div class="card shadow-sm rounded-3">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Department</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Latest Task</th>
                                    <th scope="col">Time Tagged</th>
                                </tr>
                            </thead>
                            <tbody id="statusTable"></tbody>
                        </table>
                        <div id="paginationControls" class="my-3 d-flex justify-content-center"></div>
                    </div>
                </div>
            </div>

            <!-- Floating Online Users Widget -->
            <div id="onlineWidget" class="position-fixed bottom-0 end-0 m-3">
                <!-- Toggle Button -->
                <button id="onlineToggle" class="btn btn-success rounded-circle shadow p-3">
                    <i class="fas fa-users fs-4"></i>
                </button>

                <!-- Online List (hidden by default) -->
                <div id="onlineUsersPopup"
                    class="bg-white border rounded-3 shadow p-4 mt-2"
                    style="display: none; max-height: 300px; width: 260px; overflow-y: auto; position: absolute; bottom: 60px; right: 0;">
                    <h6 class="fw-bold border-bottom pb-2 mb-2">Online Users</h6>
                    <ul id="onlineUsersList" class="list-group list-group-flush small"></ul>
                </div>
            </div>

            <!-- DATA VISUALIZATION PAGE -->
            <div id="data-visualization-page" class="page-content container-fluid py-4">
                <div class="main-title mb-4 text-center">
                    <h1 class="fw-bold">DATA VISUALIZATION</h1>
                </div>

                <div class="row">
                    <!-- Sidebar Controls -->
                    <div class="col-md-3">
                        <h5 class="fw-bold">Departments</h5>
                        <div id="department-buttons" class="d-flex flex-column gap-2 mb-4"></div>

                        <h5 class="fw-bold">Chart Type</h5>
                        <button class="btn btn-dark mb-2 chart-toggle" data-type="bar">
                            <i class="fas fa-chart-bar"></i> Production Hours
                        </button>
                        <button class="btn btn-dark mb-2 chart-toggle" data-type="pie">
                            <i class="fas fa-chart-pie"></i> Task Distribution
                        </button>
                        <div id="month-filter" style="display:none;">
                            <h5 class="fw-bold">Select Month</h5>
                            <select id="monthSelector" class="form-select"></select>
                        </div>
                    </div>

                    <!-- Chart + Data -->
                    <div class="col-md-9">
                        <div id="date-range-filter">
                            <h5 class="fw-bold">Select Date Range</h5>
                            <div class="row mb-2">
                                <div class="col">
                                    <input type="date" id="bar_Start_Date" class="form-control">
                                </div>
                                <div class="col">
                                    <input type="date" id="bar_End_Date" class="form-control">
                                </div>
                                <div class="col">
                                    <select id="barMode" class="form-select">
                                        <option value="daily">Daily View</option>
                                        <option value="monthly">Monthly View (FTE)</option>
                                    </select>
                                </div>
                                <div class="col">
                                    <button class="btn btn-success w-100" id="applyDateRange">Apply</button>
                                </div>
                            </div>
                        </div>
                        <div class="card shadow p-3 mb-4" style="height: 500px;">
                            <canvas id="visualizationChart"></canvas>
                            <div id="chartFallback" class="text-center text-muted fst-italic" style="display:none; padding:20px;">
                                No chart data available for this selection.
                            </div>
                        </div>
                        <div id="taskList" class="card shadow p-3"></div>
                    </div>
                </div>
            </div>



            <!---EDIT PROFILE USER--->
            <div id="edit-profile-page" class="page-content">
                <div class="main-title">
                    <h1 class="fw-bold">EDIT PROFILE</h1>
                </div>

                <div class="profile-card">
                    <!-- Tabs -->
                    <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profileInfo" type="button" role="tab">Profile Info</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#changePassword" type="button" role="tab">Change Password</button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content p-3" id="profileTabsContent">

                        <!-- Profile Info Tab -->
                        <div class="tab-pane fade show active" id="profileInfo" role="tabpanel">
                            <form id="updateProfileForm" class="modern-form" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label>Employee ID</label>
                                    <input type="text" id="edit_employee_id" class="form-control-modern">
                                </div>
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" id="edit_first_name" class="form-control-modern" required>
                                </div>
                                <div class="form-group">
                                    <label>Middle Name</label>
                                    <input type="text" id="edit_middle_name" class="form-control-modern">
                                </div>
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input type="text" id="edit_last_name" class="form-control-modern" required>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" id="edit_email" class="form-control-modern" disabled>
                                </div>
                                <div class="form-group">
                                    <label>Role</label>
                                    <input type="text" id="edit_role" class="form-control-modern" disabled>
                                </div>
                                <div class="form-group">
                                    <label>Department</label>
                                    <select id="edit_department_select" class="form-control-modern"></select>
                                </div>

                                <!-- ✅ Profile Image (preview + upload) -->
                                <div class="form-group">
                                    <label>Profile Image</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <img id="profilePreview" src="../assets/default-avatar.jpg" class="rounded-circle border" width="96" height="96" style="object-fit:cover;">
                                        <div class="w-100">
                                            <input type="file" id="edit_profile_image" class="form-control-modern" accept="image/*">
                                            <small class="text-muted">JPEG/PNG, up to 5MB.</small>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" id="profileSubmitBtn" class="btn-modern btn-primary-modern">Update Profile</button>
                            </form>
                            <div id="profileMessage" class="mt-2"></div>
                        </div>

                        <!-- Change Password Tab -->
                        <div class="tab-pane fade" id="changePassword" role="tabpanel">
                            <form id="changePasswordForm" class="modern-form">

                                <div class="form-group password-group">
                                    <label>Current Password</label>
                                    <div class="input-group">
                                        <input type="password" id="current_password" class="form-control" required>
                                        <span class="input-group-text toggle-password" onclick="togglePassword('current_password')">
                                            <i class="fa fa-eye-slash"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="form-group password-group">
                                    <label>New Password</label>
                                    <div class="input-group">
                                        <input type="password" id="new_password" class="form-control" required>
                                        <span class="input-group-text toggle-password" onclick="togglePassword('new_password')">
                                            <i class="fa fa-eye-slash"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="form-group password-group">
                                    <label>Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" id="confirm_password" class="form-control" required>
                                        <span class="input-group-text toggle-password" onclick="togglePassword('confirm_password')">
                                            <i class="fa fa-eye-slash"></i>
                                        </span>
                                    </div>
                                </div>

                                <button type="submit" class="btn-modern btn-warning-modern">Change Password</button>
                            </form>
                            <div id="passwordMessage" class="mt-2"></div>
                        </div>
                    </div>
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

    <!---PHP MODAL--->
    <?php include "../modals/edit-task-modal.php"; ?>
    <?php include "../modals/admin-amendment-modal.php"; ?>
    <?php include "../modals/user-amendment-modal.php"; ?>
    <?php include "../modals/admin-user-profile-modal.php"; ?>
    <?php include "../modals/admin-edit-request-modal.php"; ?>
    <?php include "../modals/edit-department-modal.php"; ?>

    <!---JS LINKS HERE--->
    <script src="../js/load-statuses.js"></script>
    <script src="../js/global-loader.js"></script>
    <script src="../js/data-visualization.js"></script>
    <script src="../js/departments.js"></script>
    <script src="../js/edit-profile.js"></script>
    <script src="../js/toggle-department.js"></script>
    <script src="../js/real-time-clock.js"></script>
    <script src="../js/toggle-password.js"></script>
    <script src="../js/sidebar-client.js"></script>
    <!---script src="../js/users-list.js"></script>
    <script src="../js/admin-request-render.js"></script>
    <script src="../js/admin-amendments.js"></script>
    <script src="../js/start-tag-task.js"></script>
    <script src="../js/archive.js"></script>
    <script src="../js/user-requests.js"></script>
    <script src="../js/admin-amendments-archive.js"></script>\
    <script src="../js/slider-function.js"></script>
    <script src="../js/create-work-mode.js"></script>
    <script src="../js/load-monthly-summary.js"></script>
    <script src="../js/js-modals/user-added-modal.js"></script>
    <script src="../js/tracker-edit-task.js"></script>
    <script src="../js/user-amendments.js"></script>
    <script src="../js/insert-task-in-between.js"></script>
    <script src="../js/assign-dept-wmt.js"></script--->




    <!---script>
        function confirmRegistration() {
            return confirm("Register the account?");
        }
    </script--->


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
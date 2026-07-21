<?php
require_once "../backend/session_config.php"; // Load lifetime settings first
session_start();
require_once "../backend/connection_db.php"; // Adjust path if needed

// Check if user is logged in and has an authorized role
$allowedRoles = ['user'];

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
    <title>User Dashboard</title>
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
    <!---SWEET ALERT--->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!---SESSION STORAGE--->
    <script>
        sessionStorage.setItem("user_id", "<?php echo $_SESSION['user_id']; ?>");
        sessionStorage.setItem("userRole", "<?php echo $_SESSION['role']; ?>");
        const userRole = "<?php echo $loggedInUserRole; ?>"; // make it accessible as a JS variable
        const loggedInUserName = "<?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Guest'; ?>";
        const USER_ROLE = "<?= strtolower($_SESSION['role']); ?>";

        // FIX FOR LEAVE REQUEST (USER) NOT WORKING
        const USER_ID = <?= intval($_SESSION['user_id']); ?>;
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>
</head>

<div class="grid-container">

    <!-----HEADER------>

    <header class="header">
        <img src="../assets/RESONO_logo_edited.png" width="40px" alt="Resono logo">
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
                <a class="sidebar-list-item sidebar-dropdown d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#statusSubmenu" role="button" aria-expanded="false" aria-controls="statusSubmenu" data-page="dashboard" onclick="changePage('dashboard')">
                    <span><i class="fa-solid fa-gauge"></i>DASHBOARD</span>
                </a>
            </li>
            <li>
                <a class="sidebar-dropdown d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#generalSubmenu" role="button" aria-expanded="false" aria-controls="generalSubmenu">
                    <span><i class="fa-solid fa-house"></i>GENERAL</span>
                    <i class="fa-solid fa-caret-down"></i>
                </a>

                <ul class="collapse sidebar-submenu list-unstyled ps-3" id="generalSubmenu">
                    <li class="sidebar-list-item" data-page="my-tracker" onclick="changePage('my-tracker')">My Tracker</li>
                    <li class="sidebar-list-item" data-page="ot-request" onclick="changePage('ot-request')">Overtime Request</li>
                    <li class="sidebar-list-item" data-page="leave-request" onclick="changePage('leave-request')">Leave Request</li>
                    <li class="sidebar-list-item" data-page="monthly-summary" onclick="changePage('monthly-summary')">Tracker Summary</li>
                    <li class="sidebar-list-item" data-page="dtr-amendment" onclick="changePage('dtr-amendment')">DTR Amendment</li>
                    <li class="sidebar-list-item" data-page="task-insertion" onclick="changePage('task-insertion')">Task Insertion</li>
                </ul>
            </li>

            <li>
                <a class="sidebar-dropdown d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#commendationSubmenu" role="button" aria-expanded="false" aria-controls="commendationSubmenu">
                    <span><i class="fa-solid fa-handshake"></i>COMMENDATIONS</span>
                    <i class="fa-solid fa-caret-down"></i>
                </a>

                <ul class="collapse sidebar-submenu list-unstyled ps-3" id="commendationSubmenu">
                    <li class="sidebar-list-item" data-page="commend-list" onclick="changePage('commend-list')">Commendations List</li>
                    <li class="sidebar-list-item" data-page="my-commendation" onclick="changePage('my-commendation')">My Commendations</li>
                </ul>
            </li>

            <!----USER MANAGEMENT---->
            <!---li>
                    <a class="sidebar-dropdown d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#userManagementmenu" role="button" aria-expanded="false" aria-controls="userManagementmenu">
                        <span><i class="fa-solid fa-gear"></i>USER MANAGEMENT</span>
                        <i class="fa-solid fa-caret-down"></i>
                    </a>
                </li--->
            <!----USER MANAGEMENT END---->

            <!----SYSTEM SETTINGS---->
            <li>
                <a class="sidebar-dropdown d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#systemSettingsmenu" role="button" aria-expanded="false" aria-controls="systemSettingsmenu">
                    <span><i class="fa-solid fa-gear"></i>SYSTEM SETTINGS</span>
                    <i class="fa-solid fa-caret-down"></i>
                </a>

                <ul class="collapse sidebar-submenu list-unstyled ps-3" id="systemSettingsmenu">
                    <li class="sidebar-list-item" data-page="edit-profile" onclick="changePage('edit-profile')">Edit Profile</li>
                </ul>

                <!---ul class="collapse sidebar-submenu list-unstyled ps-3" id="systemSettingsmenu">
                        <li class="sidebar-list-item" data-page="archive" onclick="changePage('archive')">Archives</li>
                    </ul--->

            </li>
            <!----SYSTEM SETTINGS END---->

        </ul>
    </aside>

    <div class="rsn-main-container">

        <!---MAIN-DASHBOARD PAGE--->
        <div id="dashboard-page" class="page-content dashboard-page">
            <div class="main-title">
                <h1 class="fw-bold">DASHBOARD</h1>
            </div>

            <div class="col-sm-4 d-flex gap-2 h-80">
                <button id="openCommendModal" class="btn btn-success w-100"
                    onclick="openCommendationModal('commend')">
                    <i class="fa-solid fa-thumbs-up"></i> Commend Employee
                </button>
            </div>

            <!-- ✅ Bottom Image Panel -->
            <!---div class="dashboard-banner mt-5 align-items-center justify-content-center d-flex">
                    <img src="../assets/Resono-Values-resized.jpg" alt="Resono Values" />
                </div--->
            <!---div class="announcement-container row mt-4" id="dashboardAnnouncements"></div--->
            <!---Carousel Announcements-->
            <div class="mt-5">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="fw-bold">Latest Announcements</h3>

                    <button class="btn btn-sm btn-outline-success"
                        onclick="openAnnouncementList()">
                        View All
                    </button>
                </div>

                <div id="dashboardAnnouncements"></div>

            </div>
        </div>

        <!---ANNOUNCEMENT LIST PAGE--->
        <div id="announcement-list-page" class="page-content">

            <div class="main-title">
                <h1 class="fw-bold">All Announcements</h1>
            </div>

            <button class="btn btn-sm btn-outline-success mb-3"
                onclick="changePage('dashboard')">
                ← Back to Dashboard
            </button>

            <div id="announcementListContainer" class="row"></div>

            <div id="announcementListPagination" class="text-center mt-3"></div>

        </div>

        <!---MY TRACKER PAGE--->
        <div id="my-tracker-page" class="page-content">
            <div class="main-title">
                <h1 class="fw-bold">MY TRACKER</h1>
            </div>

            <div class="rsn-main-cards">

                <!-- 🔹 Date Range Filter -->
                <div class="d-flex align-items-center gap-2 mb-3 px-3 flex-wrap">
                    <label for="startDateFilter" class="fw-semibold mb-0">Start Date:</label>
                    <input type="date" id="startDateFilter" class="form-control w-auto" />

                    <span>to</span>

                    <label for="endDateFilter" class="fw-semibold mb-0">End Date:</label>
                    <input type="date" id="endDateFilter" class="form-control w-auto" />

                    <button class="btn btn-success" onclick="filterTaskLogs()">Filter</button>
                    <button class="btn btn-secondary" onclick="resetTaskLogs()">Reset</button>
                </div>

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

                        <div id="slideButtonWrapper" class="slide-button-wrapper">
                            <div class="slide-button-handle" id="slideButtonHandle">▶ Slide to Tag</div>
                        </div>

                        <div class="call-time-dropdown">
                            <select id="callTimeSelect" class="form-select w-auto">
                                <option value="" class="text-center">-- --</option>
                                <option value="04:30:00">4:30 AM</option>
                                <option value="05:00:00">5:00 AM</option>
                                <option value="05:30:00">5:30 AM</option>
                                <option value="06:00:00">6:00 AM</option>
                                <option value="06:15:00">6:15 AM</option>
                                <option value="06:30:00">6:30 AM</option>
                                <option value="06:45:00">6:45 AM</option>
                                <option value="07:00:00">7:00 AM</option>
                                <option value="07:30:00">7:30 AM</option>
                                <option value="08:00:00">8:00 AM</option>
                                <option value="08:30:00">8:30 AM</option>
                                <option value="09:00:00">9:00 AM</option>
                                <option value="09:30:00">9:30 AM</option>
                                <option value="10:00:00">10:00 AM</option>
                                <option value="10:30:00">10:30 AM</option>
                                <option value="11:00:00">11:00 AM</option>
                                <option value="11:30:00">11:30 AM</option>
                                <option value="12:00:00">12:00 PM</option>
                                <option value="12:30:00">12:30 PM</option>
                                <option value="13:00:00">1:00 PM</option>
                                <option value="14:00:00">2:00 PM</option>
                                <option value="15:00:00">3:00 PM</option>
                                <option value="16:00:00">4:00 PM</option>
                                <option value="17:00:00">5:00 PM</option>
                                <option value="18:00:00">6:00 PM</option>
                                <option value="19:00:00">7:00 PM</option>
                                <option value="20:00:00">8:00 PM</option>
                                <option value="21:00:00">9:00 PM</option>
                                <option value="22:00:00">10:00 PM</option>
                                <option value="23:00:00">11:00 PM</option>
                                <option value="00:00:00">12:00 AM</option>
                            </select>
                        </div>


                        <!-- ✅ New Task Insertion Button -->
                        <button type="button" class="btn btn-success d-flex align-items-center" onclick="openTaskInsertionModal()">
                            <i class="fa-solid fa-plus me-2"></i> Insert Missed Task
                        </button>
                    </div>

                    <!-- Task Log Table -->
                    <div class="table-responsive">
                        <table class="table table-striped text-center" id="wmtLogTable">
                            <thead class="table">
                                <tr>
                                    <th>Date</th>
                                    <th>Work Mode</th>
                                    <th>Task Description</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Total Time Spent</th>
                                    <th>Remarks</th>
                                    <th>Volume</th> <!-- 🔹 NEW -->
                                    <th style="width: 120px;">Action</th>
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

        <!-- TASK INSERTION HISTORY PAGE>
            <div id="my-task-insertions -page" class="page-content">
                <div class="main-title d-flex justify-content-between align-items-center">
                    <h1 class="fw-bold">MY TASK INSERTION REQUESTS</h1>
                </div>

                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Request UID</th>
                            <th>Date</th>
                            <th>Work Mode</th>
                            <th>Task</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Status</th>
                            <th>Processed By</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody id="task-insertion-history-table">
                        
                    </tbody>
                </table>
            </div--->

        <!-- ✅ TASK INSERTION MANAGEMENT PAGE -->
        <div id="task-insertion-page" class="page-content">
            <div class="main-title d-flex justify-content-between align-items-center">
                <h1 class="fw-bold">TASK INSERTION MANAGEMENT</h1>
            </div>

            <!-- Filters -->
            <div class="row mt-3 mb-2">
                <div class="col-md-6 mb-2">
                    <input type="text" id="searchRequest" class="form-control" placeholder="Search by requestor or UID">
                </div>
                <div class="col-md-3 mb-2">
                    <select id="filterStatus" class="form-select">
                        <option value="Pending" selected>Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                        <option value="All">All</option>
                    </select>
                </div>
            </div>

            <!-- Unified Table -->
            <div class="table-responsive mt-3">
                <table class="table table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Request UID</th>
                            <th>Date</th>
                            <th>Work Mode</th>
                            <th>Task</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Status</th>
                            <th>Requestor</th>
                            <th>Processed By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="task-insertion-table"></tbody>
                </table>
            </div>
            <div id="task-pagination" class="pagination justify-content-center mt-3"></div>
        </div>

        <!-- MONTHLY SUMMARY PAGE -->
        <div id="monthly-summary-page" class="page-content">
            <div class="main-title">
                <h1 class="fw-bold">TRACKER SUMMARY</h1>
            </div>

            <!-- Filters -->
            <div class="filters mb-3">
                <div class="row g-2 justify-content-end">
                    <div class="col-auto">
                        <input type="month" id="monthFilter" class="form-control" />
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-success" onclick="loadMonthlySummary()">Search</button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div id="summaryTableWrapper" class="table-responsive">
                <table class="table table-bordered table-striped" id="summaryTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Login</th>
                            <th>Call Time</th>
                            <th>Logout</th>
                            <th>Total Time</th>
                            <th>Production</th>
                            <th>Offphone</th>
                            <th>Training</th>
                            <th>Resono</th>
                            <th>Paid Break</th>
                            <th>Unpaid Break</th>
                            <th>Personal Time</th>
                            <th>System Down</th>
                            <th>Leave Hours</th>
                            <th>Theoretical Paid Hours</th>
                            <th>Approved OT</th>
                            <th>Actual Paid Hours</th>
                            <!---th>Leave</th--->
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="summaryEmptyRow">
                            <td colspan="18" class="text-center text-muted py-4">
                                Please select a date range or month to view the summary.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Export Button -->
            <div class="text-end mt-3">
                <button id="exportPDFBtn" class="btn btn-danger" style="display:none;">
                    📄 Export to PDF
                </button>
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

        <!-- DTR AMENDMENT PAGE -->
        <div id="dtr-amendment-page" class="page-content">
            <div class="main-title">
                <h1 class="fw-bold">DTR AMENDMENT REQUESTS</h1>
            </div>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Request UID</th>
                        <!--th>Request #</th-->
                        <th>Date</th>
                        <th>Task</th>
                        <!---th>Field</th--->
                        <th>Old Value</th>
                        <th>New Value</th>
                        <th>Reason</th>
                        <th>Recipient</th>
                        <th>Status</th>
                        <th>Processed By</th>
                        <th>Requested At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="user-amendments-table">
                    <!-- Filled by JS -->
                </tbody>
            </table>
            <div id="user-amendments-pagination"></div>
        </div>

        <!---ARCHIVE PAGE>

            <div id="archive-page" class="page-content">
                <div class="main-title d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h1 class="fw-bold">THE ARCHIVE</h1>
                    <div class="d-flex align-items-center gap-2">
                        <select id="archiveYear" class="form-select w-auto"></select>
                        <select id="archiveMonth" class="form-select w-auto"></select>
                        <button id="archiveFilterBtn" class="btn btn-success">Filter</button>
                    </div>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-bordered text-center" id="archiveLogTable">
                        <thead class="table">
                            <tr>
                                <th>Date</th>
                                <th>Work Mode</th>
                                <th>Task Description</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Total Time Spent</th>
                                <th>Remarks</th>
                                <th>Volume Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                        </tbody>
                    </table>
                </div>
            </div--->

        <!---OT REQUEST PAGE (USER ROLE)--->
        <div id="ot-request-page" class="page-content">
            <div class="main-title mb-3">
                <h1 class="fw-bold">OVERTIME REQUEST</h1>
            </div>

            <!-- 🔍 Filter Section -->
            <div class="filters mb-3 row g-2 align-items-end">
                <div class="col-md-2">
                    <label for="otStartDate" class="form-label">From Date</label>
                    <input type="date" id="otStartDate" class="form-control" />
                </div>

                <div class="col-md-2">
                    <label for="otendDate" class="form-label">To Date</label>
                    <input type="date" id="otendDate" class="form-control" />
                </div>

                <div class="col-md-2">
                    <label for="otFilterStatus" class="form-label">Status</label>
                    <select id="otFilterStatus" class="form-select">
                        <option value="all">All</option>
                        <option value="pending" selected>Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <!-- Spacer column to push the Create button right -->
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-success w-50" onclick="filterOTRequests()">Filter</button>
                    <button class="btn btn-secondary w-50" onclick="resetOTRequests()">Reset</button>
                </div>

                <!-- Create Overtime Request Button aligned to far right -->
                <div class="col-md-3 d-flex justify-content-end">
                    <button class="btn btn-success" onclick="openCreateOvertimeModal()">
                        <i class="bi bi-plus-circle"></i> Create Overtime Request
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="ot-request-table">
                    <thead>
                        <tr>
                            <th>Date Created</th>
                            <th>Employee</th>
                            <th>Tracker Date</th>
                            <th>Hours</th>
                            <th>Status</th>
                            <th>Checked By</th>
                            <th>Remarks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <!-- ✅ Pagination for OT Requests -->
                <div id="otRequestsPagination" class="my-3 d-flex justify-content-center"></div>
            </div>
        </div>

        <!---LEAVE REQUEST PAGE (USER ROLE--->
        <div id="leave-request-page" class="page-content">
            <div class="main-title mb-3">
                <h1 class="fw-bold">LEAVE REQUESTS</h1>
            </div>

            <!-- 🔍 Filter Section -->
            <div class="filters mb-3 row g-2 align-items-end">
                <div class="col-md-2">
                    <label for="leaveStartDate" class="form-label">From Date</label>
                    <input type="date" id="leaveStartDate" class="form-control" />
                </div>

                <div class="col-md-2">
                    <label for="leaveendDate" class="form-label">To Date</label>
                    <input type="date" id="leaveendDate" class="form-control" />
                </div>

                <div class="col-md-2">
                    <label for="leaveFilterStatus" class="form-label">Status</label>
                    <select id="leaveFilterStatus" class="form-select">
                        <option value="all">All</option>
                        <option value="pending" selected>Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-success w-50" onclick="filterLeaveRequests()">Filter</button>
                    <button class="btn btn-secondary w-50" onclick="resetLeaveRequests()">Reset</button>
                </div>
                <div class="col-md-3 d-flex justify-content-end">
                    <button class="btn btn-success" onclick="openCreateLeaveModal()">
                        <i class="bi bi-plus-circle"></i>Leave Request
                    </button>
                </div>
            </div>

            <!-- Place Create button on a separate row below filters, left-aligned>
            <div class="row mb-3">
                <div class="col-md-2 d-flex justify-content-start">
                    <button class="btn btn-success" onclick="openCreateLeaveModal()">
                        <i class="bi bi-plus-circle"></i>Leave Request
                    </button>
                </div>
            </div -->

            <!---LEAVE REQUEST CARDS--->
            <div class="main-cards">
                <div class="leave-card card bg-success-subtle text-success">
                    <div class="card-inner">
                        <i class="fa-solid fa-tree"></i>
                        <p>VACATION LEAVE:</p>
                    </div>
                    <h2 id="vacationLeaveCount">Loading...</h2>
                </div>
                <div class="leave-card card bg-success-subtle text-success">
                    <div class="card-inner">
                        <i class="fa-solid fa-bandage"></i>
                        <p>SICK LEAVE:</p>
                    </div>
                    <h2 id="sickLeaveCount">Loading...</h2>
                </div>
                <div class="leave-card card bg-danger-subtle text-danger">
                    <div class="card-inner">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <p>EMERGENCY LEAVE:</p>
                    </div>
                    <h2 id="emergencyLeaveCount">Loading...</h2>
                </div>
                <div class="leave-card card bg-warning-subtle text-warning">
                    <div class="card-inner">
                        <i class="fa-solid fa-hand-holding-hand"></i>
                        <p>COMPASSIONATE LEAVE:</p>
                    </div>
                    <h2 id="compassionateLeaveCount">Loading...</h2>
                </div>
            </div>

            <!-- Leave Request Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="leave-request-table">
                    <thead>
                        <tr>
                            <th>Date Created</th>
                            <th>Date Requested</th>
                            <th>Leave Type</th>
                            <th>Employee</th>
                            <th>Status</th>
                            <th>Checked By</th>
                            <th>Payment Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <!-- Fallback message MUST be OUTSIDE the table -->
                <p id="noLeaveResults" class="text-center text-muted mt-3" style="display:none;">
                    No leave requests found.
                </p>

                <div id="leaveRequestsPagination" class="my-3 d-flex justify-content-center"></div>
            </div>
        </div>

        <!--- COMMENDATION LIST PAGE --->
        <div id="commend-list-page" class="page-content">
            <div class="main-title mb-3">
                <h1 class="fw-bold">COMMENDATIONS LIST</h1>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="text-muted small">
                    Total points:
                    <span id="commendTotalPoints" class="fw-bold">0</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="commend-list-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Nominator</th>
                            <th>Value</th>
                            <th>Type</th>
                            <th>Points</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div id="commendListPagination" class="my-3 d-flex justify-content-center"></div>
        </div>

        <!--- MY COMMENDATION PAGE --->
        <div id="my-commendation-page" class="page-content">
            <div class="main-title mb-3">
                <h1 class="fw-bold">MY COMMENDATION REQUESTS</h1>
            </div>

            <div class="filters mb-3 row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" id="myCommendStartDate" class="form-control" />
                </div>

                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" id="myCommendEndDate" class="form-control" />
                </div>

                <div class="col-md-2">
                    <label class="form-label">Value</label>
                    <select class="form-select" id="myCommendValueFilter">
                        <option value="">Value</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select id="myCommendFilterStatus" class="form-select">
                        <option value="all">All</option>
                        <option value="pending" selected>Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select id="myCommendFilterType" class="form-select">
                        <option value="all">All</option>
                        <option value="commend" selected>Commend</option>
                        <option value="deduct">Deduct</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-success w-50" onclick="filterMyCommendations()">Filter</button>
                    <button class="btn btn-secondary w-50" onclick="resetMyCommendations()">Reset</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="my-commendation-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Nominee Name</th>
                            <th>Values</th>
                            <th>Points</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div id="myCommendationPagination" class="my-3 d-flex justify-content-center"></div>
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
<?php include "../modals/user-amendment-modal.php"; ?>
<?php include "../modals/edit-request-modal.php"; ?>
<?php include "../modals/task-insertion-modal.php"; ?>
<?php include "../modals/view-task-insertion-modal.php"; ?>
<?php include "../modals/ot-requests-modal.php"; ?>
<?php include "../modals/edit-ot-requests-modal.php"; ?>
<?php include "../modals/create-leave-request-modal.php"; ?>
<?php include "../modals/view-leave-request-modal.php"; ?>
<?php include "../modals/commendation-modals/create-commend-modal.php"; ?>
<?php include "../modals/commendation-modals/commend-notif-modal.php"; ?>
<?php include "../modals/commendation-modals/view-commend-modal.php"; ?>
<?php include "../modals/commendation-modals/view-commend-list-modal.php"; ?>

<!---JS LINKS HERE--->
<script src="../js/announcements.js"></script>
<script src="../js/leave-request.js"></script>
<script src="../js/task-insertion-management.js"></script>
<script src="../js/task-insertion.js"></script>
<!---script src="../js/task-insertion-request.js"></script--->
<!---script src="../js/task-insertion-history.js"></script--->
<script src="../js/global-loader.js"></script>
<script src="../js/load-statuses.js"></script>
<script src="../js/start-tag-task.js"></script>
<script src="../js/user-amendments.js"></script>
<script src="../js/create-work-mode.js"></script>
<script src="../js/slider-function.js"></script>
<script src="../js/toggle-department.js"></script>
<script src="../js/load-monthly-summary.js"></script>
<script src="../js/sidebar.js"></script>
<script src="../js/real-time-clock.js"></script>
<script src="../js/toggle-password.js"></script>
<script src="../js/edit-profile.js"></script>
<script src="../js/tracker-edit-task.js"></script>
<script src="../js/revised-user-requests.js"></script>
<!---script src="../js/user-requests.js"></script>
<script src="../js/user-request-render.js"></script--->
<script src="../js/archive.js"></script>
<script src="../js/idle-session-timeout.js"></script>
<script src="../js/ot-requests.js"></script>
<script src="../js/commendations-related.js"></script>
<script src="../js/commendations-notif.js"></script>
<script src="../js/commendations-table.js"></script>
<script src="../js/alertService.js"></script>



<!---script src="../js/insert-task-in-between.js"></script--->


<script>
    function confirmRegistration() {
        return confirm("Register the account?");
    }
</script>


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
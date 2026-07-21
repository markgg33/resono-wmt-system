<?php

require_once "../backend/session_config.php"; // Load lifetime settings first
session_start();
require_once "../backend/connection_db.php"; // Adjust path if needed


// Check if user is logged in and has an authorized role
$allowedRoles = ['admin', 'hr', 'executive', 'supervisor'];

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
$userId = $_SESSION['user_id'];

// If supervisor, fetch their department assignment
if ($loggedInUserRole === 'supervisor') {
    $supervisorDepartments = [];
    $stmt = $conn->prepare("SELECT department_id FROM user_departments WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $supervisorDepartments[] = $row['department_id'];
    }
    $stmt->close();
}

include "../modals/wmt-success-modal.php";

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
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <!----AOS LIBRARY---->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!---SWEET ALERT--->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!---SESSION STORAGE--->
    <script>
        const supervisorDepartments = <?= json_encode($supervisorDepartments ?? []); ?>;
        const userRole = "<?php echo $loggedInUserRole; ?>"; // make it accessible as a JS variable
        const USER_ROLE = "<?= strtolower($_SESSION['role']); ?>";
        const USER_ID = <?= intval($_SESSION['user_id']); ?>; //NEW ADDITIONAL CODE FOR TESTING
        sessionStorage.setItem("userRole", "<?php echo $_SESSION['role']; ?>");
        sessionStorage.setItem("user_id", "<?php echo $_SESSION['user_id']; ?>");
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
            <!-- 🕐 TIMEZONE INDICATOR: Shows PH time to prevent timezone confusion -->
            <!---small id="phServerTimeDisplay" style="font-size: 0.75rem; color: #6c757d;"></small--->
            <div class="time-container text-center">
                <h5 id="live-date" class="fw-bold"></h5>
                <h6 id="live-time" class="text-muted"></h6>
            </div>
            <a href="../backend/logout.php" onclick="return confirm('Are you sure you want to log out?')"><button class="btn-logout" data-bs-toggle="tooltip" data-bs-placement="top" title="Logout button"><i class="fa-solid fa-right-from-bracket"></i></button></a>
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
                    <a class=" sidebar-list-item sidebar-dropdown d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#statusSubmenu" role="button" aria-expanded="false" aria-controls="statusSubmenu" data-page="dashboard" onclick="changePage('dashboard')">
                        <span><i class="fa-solid fa-gauge"></i>DASHBOARD</span>
                        <i class="fa-solid fa-caret-down"></i>
                    </a>

                    <ul class="collapse sidebar-submenu list-unstyled ps-3" id="statusSubmenu">
                        <li class="sidebar-list-item" data-page="status-dashboard" onclick="changePage('status-dashboard')">Status Dashboard</li>
                        <!?php if ($loggedInUserRole==='admin' || $loggedInUserRole==='executive' || $loggedInUserRole==='supervisor' ): ?>
                            <li class="sidebar-list-item" data-page="data-visualization" onclick="changePage('data-visualization')">
                                Analytics
                            </li>
                            <!?php endif; ?>

                    </ul>
                </li>

                <li>
                    <a class="sidebar-dropdown d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#generalSubmenu" role="button" aria-expanded="false" aria-controls="generalSubmenu">
                        <span><i class="fa-solid fa-house"></i>GENERAL</span>
                        <i class="fa-solid fa-caret-down"></i>
                    </a>

                    <ul class="collapse sidebar-submenu list-unstyled ps-3" id="generalSubmenu">
                        <li class="sidebar-list-item" data-page="my-tracker" onclick="changePage('my-tracker')">Daily Tracker</li>
                        <li class="sidebar-list-item" data-page="ot-request" onclick="changePage('ot-request')">Overtime Request</li>
                        <li class="sidebar-list-item" data-page="leave-request" onclick="changePage('leave-request')">Leave Request</li>
                        <li class="sidebar-list-item" data-page="monthly-summary" onclick="changePage('monthly-summary')">Tracker Summary</li>
                    </ul>

                </li>

                <!----DTR AMENDMENT---->

                <li>
                    <a class="sidebar-dropdown d-flex justify-content-between align-items-center"
                        data-bs-toggle="collapse"
                        href="#dtrAmendmentSubmenu"
                        role="button"
                        aria-expanded="false"
                        aria-controls="dtrAmendmentSubmenu">
                        <span><i class="fa-solid fa-pen-to-square"></i>DTR AMENDMENT</span>
                        <i class="fa-solid fa-caret-down"></i>
                    </a>

                    <ul class="collapse sidebar-submenu list-unstyled ps-3" id="dtrAmendmentSubmenu">
                        <li class="sidebar-list-item" data-page="dtr-amendments" onclick="changePage('dtr-amendments')">DTR Requests</li>
                        <!---li class="sidebar-list-item" data-page="dtr-amendment-archive" onclick="changePage('dtr-amendment-archive')">DTR Archives</li--->
                        <li class="sidebar-list-item" data-page="task-insertion" onclick="changePage('task-insertion')">Task Insertion</li>
                        <!---li class="sidebar-list-item" data-page="task-insertion-requests" onclick="changePage('task-insertion-requests')">Task Insertion Requests</li>
                        <li class="sidebar-list-item" data-page="task-insertion-history" onclick="changePage('task-insertion-history')">Task Insertion Requests History</li--->
                    </ul>
                </li>

                <!----SCHEDULER---->
                <li>
                    <a class="sidebar-dropdown d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#schedulerSettingsmenu" role="button" aria-expanded="false" aria-controls="schedulerSettingsmenu" onclick="changePage('scheduler')">
                        <span><i class="fa-solid fa-calendar"></i>SCHEDULER</span>
                        <i class="fa-solid fa-caret-down"></i>
                    </a>
                </li>
                <!----SCHEDULER END---->

                <!----COMMENDATIONS---->

                <li>
                    <a class="sidebar-dropdown d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#commendationSubmenu" role="button" aria-expanded="false" aria-controls="commendationSubmenu">
                        <span><i class="fa-solid fa-handshake"></i>COMMENDATIONS</span>
                        <i class="fa-solid fa-caret-down"></i>
                    </a>

                    <ul class="collapse sidebar-submenu list-unstyled ps-3" id="commendationSubmenu">
                        <li class="sidebar-list-item" data-page="commendation" onclick="changePage('commendation')">Commendations</li>
                        <li class="sidebar-list-item" data-page="commend-list" onclick="changePage('commend-list')">Commendations List</li>
                    </ul>
                </li>

                <li>
                    <a class="sidebar-dropdown d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#consolidateSubmenu" role="button" aria-expanded="false" aria-controls="consolidateSubmenu">
                        <span><i class="fa-solid fa-layer-group"></i></i>CONSOLIDATIONS</span>
                        <i class="fa-solid fa-caret-down"></i>
                    </a>

                    <ul class="collapse sidebar-submenu list-unstyled ps-3" id="consolidateSubmenu">
                        <li class="sidebar-list-item" data-page="individual-con" onclick="changePage('individual-con')">Individual Consolidation</li>
                        <li class="sidebar-list-item" data-page="team-con" onclick="changePage('team-con')">Team Consolidation</li>
                    </ul>
                </li>

                <!----USER MANAGEMENT---->
                <li>
                    <?php if ($loggedInUserRole === 'admin' || $loggedInUserRole === 'executive' || $loggedInUserRole === 'hr'): ?>
                        <a class="sidebar-dropdown d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#teamUserManagementmenu" role="button" aria-expanded="false" aria-controls="teamUserManagementmenu">
                            <span><i class="fa-solid fa-people-group"></i>TEAM & USER MANAGEMENT</span>
                            <i class="fa-solid fa-caret-down"></i>
                        </a>
                    <?php endif; ?>

                    <ul class="collapse sidebar-submenu list-unstyled ps-3" id="teamUserManagementmenu">
                        <li class="sidebar-list-item" data-page="users-list" onclick="changePage('users-list')">User Accounts</li>
                        <li class="sidebar-list-item" data-page="team-management" onclick="changePage('team-management')">Team Management</li>
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
                        <?php if ($loggedInUserRole === 'admin' || $loggedInUserRole === 'executive' || $loggedInUserRole === 'hr'): ?>
                            <li class="sidebar-list-item" data-page="create-work-mode" onclick="changePage('create-work-mode')">
                                Workmode
                            </li>
                            <li class="sidebar-list-item" data-page="billing-category" onclick="changePage('billing-category')">
                                Billing Category
                            </li>
                            <li class="sidebar-list-item" data-page="departments" onclick="changePage('departments')">
                                Department
                            </li>
                            <li class="sidebar-list-item" data-page="announcements" onclick="changePage('announcements')">Announcements</li>
                        <?php endif; ?>
                        <!---li class="sidebar-list-item" data-page="archive" onclick="changePage('archive')">Archives</li--->
                        <li class="sidebar-list-item" data-page="edit-profile" onclick="changePage('edit-profile')">Profile</li>
                        <li class="sidebar-list-item" data-page="values-setup" onclick="changePage('values-setup')">Values Setup</li>
                    </ul>
                </li>
                <!----SYSTEM SETTINGS END---->
            </ul>
        </aside>

        <div class="rsn-main-container">

            <!-- STATUS-DASHBOARD PAGE -->
            <div id="status-dashboard-page" class="page-content container-fluid">
                <div class="main-title mb-4 d-flex justify-content-between align-items-center">
                    <h1 class="fw-bold">STATUS DASHBOARD</h1>

                    <?php if (in_array($loggedInUserRole, ['admin', 'executive', 'hr', 'supervisor'])): ?>
                        <!-- Department Filter (admins, hr, executives, supervisors) -->
                        <div>
                            <select id="dashDepartmentFilter" class="form-select shadow-sm">
                                <option value="">All Departments</option>
                            </select>
                        </div>
                    <?php endif; ?>

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

                    </div>
                    <div id="paginationControls" class="my-3 d-flex justify-content-center"></div>
                </div>
            </div>

            <?php if ($loggedInUserRole === 'admin' || $loggedInUserRole === 'executive' || $loggedInUserRole === 'supervisor' || $loggedInUserRole === 'hr'): ?>
                <!-- DATA VISUALIZATION (admins , executives and supervisors) -->
                <div id="data-visualization-page" class="page-content container-fluid py-4">
                    <div class="main-title mb-4 text-center">
                        <h1 class="fw-bold">ANALYTICS</h1>
                    </div>

                    <div class="row align-items-start mb-4">

                        <div class="card shadow-sm p-3 mb-4">
                            <div class="row g-2 align-items-end analytics-filter-row">

                                <div class="col-xl col-lg-3 col-md-4">
                                    <label class="fw-bold">Start Date</label>
                                    <input type="date" id="bar_Start_Date" class="form-control">
                                </div>

                                <div class="col-xl col-lg-3 col-md-4">
                                    <label class="fw-bold">End Date</label>
                                    <input type="date" id="bar_End_Date" class="form-control">
                                </div>


                                <div class="col-xl-2 col-lg-3 col-md-4">
                                    <label class="fw-bold">Chart Type</label>
                                    <select id="chartTypeDropdown" class="form-select">
                                        <option value="">Select</option>
                                        <option value="bar">Production Hours</option>
                                        <option value="pie">Task Distribution</option>
                                        <option value="aht">Average Handling Time (AHT)</option>
                                        <!--option value="billing">Billing Category</option-->
                                    </select>
                                </div>


                                <div class="col-xl col-lg-3 col-md-4">
                                    <label id="departmentFilterLabel" class="fw-bold">
                                        Departments
                                    </label>
                                    <div class="dropdown">
                                        <button id="departmentDropdownLabel"
                                            class="btn btn-outline-secondary dropdown-toggle w-100 text-truncate"
                                            type="button"
                                            data-bs-toggle="dropdown">
                                            Select Departments
                                        </button>

                                        <ul class="dropdown-menu w-100" id="departmentDropdownMenu"
                                            style="max-height: 250px; overflow-y: auto;">
                                        </ul>
                                    </div>
                                </div>

                                <div class="col-xl-2 col-lg-3 col-md-4" id="billingFilterContainer">
                                    <label id="billingFilterLabel" class="fw-bold">
                                        Billing Category
                                    </label>
                                    <div class="dropdown">
                                        <button id="billingDropdownLabel"
                                            class="btn btn-outline-secondary dropdown-toggle w-100 text-truncate"
                                            type="button"
                                            data-bs-toggle="dropdown">
                                            Select Billing Category
                                        </button>

                                        <ul class="dropdown-menu w-100" id="billingDropdownMenu"
                                            style="max-height: 250px; overflow-y: auto;">
                                        </ul>
                                    </div>
                                </div>

                                <div class="col-xl-2 col-lg-3 col-md-4" id="employeeFilterContainer" style="display:none;">
                                    <label class="fw-bold">Employee</label>

                                    <div class="dropdown">
                                        <button
                                            id="employeeDropdownLabel"
                                            class="btn btn-outline-secondary dropdown-toggle w-100 text-truncate"
                                            type="button"
                                            data-bs-toggle="dropdown">

                                            All Employees

                                        </button>

                                        <ul
                                            class="dropdown-menu w-100"
                                            id="employeeDropdownMenu"
                                            style="max-height:250px; overflow-y:auto;">
                                        </ul>
                                    </div>
                                </div>

                                <div class="col-xl-2 col-lg-3 col-md-4" id="workModeFilterContainer" style="display:none;">
                                    <label class="fw-bold">Work Mode</label>

                                    <div class="dropdown">
                                        <button
                                            id="workModeDropdownLabel"
                                            class="btn btn-outline-secondary dropdown-toggle w-100 text-truncate"
                                            type="button"
                                            data-bs-toggle="dropdown">

                                            Select Work Mode

                                        </button>

                                        <ul
                                            id="workModeDropdownMenu"
                                            class="dropdown-menu w-100"
                                            style="max-height:250px;overflow-y:auto;">
                                        </ul>
                                    </div>
                                </div>

                                <div class="col-xl-3 col-lg-4 col-md-6" id="taskFilterContainer" style="display:none;">
                                    <label class="fw-bold">Task Description</label>

                                    <div class="dropdown">
                                        <button
                                            id="taskDropdownLabel"
                                            class="btn btn-outline-secondary dropdown-toggle w-100 text-truncate"
                                            type="button"
                                            data-bs-toggle="dropdown">

                                            Select Tasks

                                        </button>

                                        <ul
                                            id="taskDropdownMenu"
                                            class="dropdown-menu w-100"
                                            style="max-height:250px;overflow-y:auto;">
                                        </ul>
                                    </div>
                                </div>

                                <!-- ✅ RESTORED VIEW FILTER -->
                                <div class="col-xl-1 col-lg-3 col-md-4" id="viewFilterContainer">
                                    <label class="fw-bold">View</label>
                                    <select id="barMode" class="form-select">
                                        <option value="daily">Daily</option>
                                        <option value="monthly">Monthly (FTE)</option>
                                    </select>
                                </div>

                                <!--div class="col-xl-1 col-lg-2 col-md-3">

                                    <button
                                        class="btn btn-outline-secondary w-100"
                                        id="resetFilters">

                                        Reset

                                    </button>

                                </div>

                                <div class="col-xl-1 col-lg-2 col-md-3">
                                    <button class="btn btn-success w-100" id="applyFilters">Apply</button>
                                </div-->

                                <div class="col-xl-1 col-lg-2 col-md-3 ms-auto">

                                    <button
                                        class="btn btn-outline-secondary w-100"
                                        id="resetFilters">

                                        Reset

                                    </button>

                                </div>

                                <div class="col-xl-1 col-lg-2 col-md-3">

                                    <button
                                        class="btn btn-success w-100"
                                        id="applyFilters">

                                        Apply

                                    </button>

                                </div>

                                <div
                                    class="col-xl-1 col-lg-2 col-md-3"
                                    id="ahtExportContainer"
                                    style="display:none;">

                                    <button
                                        class="btn btn-success w-100"
                                        id="exportAHTBtn">

                                        <i class="fas fa-file-excel me-1"></i>

                                        Export

                                    </button>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== Chart Type & Month Selector ===== -->
                    <div class="row align-items-center mb-4">
                        <div class="col-md-6" id="month-filter" style="display:none;">
                            <h5 class="fw-bold mb-2">Select Month</h5>
                            <select id="monthSelector" class="form-select"></select>
                        </div>
                    </div>

                    <!-- ===== Chart + Task List ===== -->
                    <div class="row">
                        <!-- Chart -->
                        <div class="col-md-7 mb-4" id="analyticsChartColumn">
                            <!--div class="card shadow p-3" style="height: 500px;">
                                    <canvas id="visualizationChart"></canvas>
                                    <AHT TABLE>
                                    <div id="ahtTableContainer" style="display:none;"></div>
                                    <div id="chartFallback"
                                        class="text-center text-muted fst-italic"
                                        style="display:none; padding:20px;">
                                        No chart data available for this selection.
                                    </div>
                                </div-->
                            <div class="card shadow p-3 position-relative" style="height:500px;">

                                <canvas id="visualizationChart"></canvas>

                                <div id="ahtTableContainer"
                                    style="display:none;height:100%;overflow:auto;">
                                </div>

                                <div id="chartFallback"
                                    class="position-absolute top-50 start-50 translate-middle text-center text-muted fst-italic"
                                    style="display:none;">
                                    No chart data available for this selection.
                                </div>

                            </div>
                        </div>

                        <!-- Task List -->
                        <div class="col-md-5 mb-4" id="analyticsDetailsColumn">
                            <!--div id="taskList" class="card shadow p-3 h-100">
                                    <h5 class="fw-bold text-center mb-3">Task List</h5>
                                    <div id="taskListContent" class="d-flex flex-column gap-2"></div>
                                </div-->
                            <div class="card shadow p-3" style="height: 500px;">
                                <h5 class="fw-bold text-center mb-3">Details</h5>

                                <!-- Tabs -->
                                <ul class="nav nav-tabs mb-2">
                                    <li class="nav-item">
                                        <button id="fte-tab" class="nav-link active">Production</button>
                                    </li>
                                    <li class="nav-item">
                                        <button id="billing-tab" class="nav-link">Billing</button>
                                    </li>
                                </ul>

                                <!-- Scrollable content wrapper -->
                                <div id="tabContentWrapper" style="overflow-y: auto; flex: 1;">
                                    <div id="fteContent"></div>
                                    <div id="billingContent" style="display:none;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

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

                    <button class="btn btn-warning w-100"
                        onclick="openCommendationModal('deduct')">
                        <i class="fa-solid fa-thumbs-down"></i> Deduct Employee
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

            <!---MONTHLY SUMMARY PAGE--->
            <div id="monthly-summary-page" class="page-content">
                <div class="main-title">
                    <h1 class="fw-bold">TRACKER SUMMARY</h1>
                </div>

                <div class="filters mb-3 row g-2">
                    <div class="col-md-3">
                        <label for="summaryDepartmentFilter">Select Department</label>
                        <select class="form-select" id="summaryDepartmentFilter">
                            <option value="">All Departments</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="userDropdown">Select User</label>
                        <select id="userDropdown" class="form-select">
                            <option value="">-- Select User --</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="startDate">Start Date</label>
                        <input type="date" id="startDate" class="form-control" />
                    </div>
                    <div class="col-md-2">
                        <label for="endDate">End Date</label>
                        <input type="date" id="endDate" class="form-control" />
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-success w-100" onclick="loadMonthlySummary()">Search</button>
                    </div>
                </div>

                <!---Search Result List--->
                <div id="searchResults" class="my-3"></div>

                <!---NEW PART OF THE SECTION FOR TABS--->
                <!-- TABS -->
                <ul class="nav nav-tabs mb-3" id="trackerSummaryTabs">
                    <li class="nav-item">
                        <button class="nav-link active"
                            data-bs-toggle="tab"
                            data-bs-target="#summaryTabContent" style="color: #198754; font-weight: bold;">
                            Tracker Summary View
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link"
                            data-bs-toggle="tab"
                            data-bs-target="#detailedTabContent" style="color: #198754; font-weight: bold;">
                            User Task Logs View
                        </button>
                    </li>
                </ul>

                <!-- TAB CONTENT -->
                <div class="tab-content">

                    <!-- ================= SUMMARY TAB ================= -->
                    <div class="tab-pane fade show active" id="summaryTabContent">
                        <!---Table--->
                        <div id="summaryTableWrapper" class="table-responsive" style="display:none;">
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
                                        <th>Resono Function</th>
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
                                <tbody></tbody>
                            </table>
                        </div>

                        <!---Export Button--->
                        <div class="text-end mt-3">
                            <div class="d-flex align-items-center gap-2 justify-content-end" id="exportButtonGroup" style="display:none;">
                                <select id="exportTypeSelect" class="form-select" style="width: auto; max-width: 250px;">
                                    <option value="monthly_summary">Activity Logs</option>
                                    <option value="tracker_summary">Individual Summary</option>
                                    <option value="department">Consolidated Activity Logs</option>
                                    <option value="department_tracker">Consolidated Individual Summary</option>
                                </select>
                                <button id="exportBtn" class="btn btn-success">Export</button>
                            </div>
                        </div>
                    </div>

                    <!-- ================= DETAILED LOGS TAB ================= -->
                    <div class="tab-pane fade" id="detailedTabContent">

                        <!-- DETAIL TABLE -->
                        <div class="table-responsive">
                            <table class="table table-bordered text-center" id="adminTaskLogTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Work Mode</th>
                                        <th>Task Description</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Total Time Spent</th>
                                        <th>Remarks</th>
                                        <th>Volume</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                </div><!-- TAB CONTENT ENDS-->
            </div>

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

            <!---DEPARTMENTS PAGE--->
            <div id="departments-page" class="page-content">
                <div class="main-title">
                    <h1 class="fw-bold">DEPARTMENT</h1>
                </div>

                <!-- Add Department Form -->
                <div class="card mb-4 shadow-sm p-3">
                    <h5 class="mb-3">Add Department</h5>
                    <form id="addDeptForm" class="d-flex gap-2">
                        <input
                            type="text"
                            id="deptName"
                            class="form-control"
                            placeholder="Enter department name"
                            required />
                        <button type="submit" class="btn btn-success">Add</button>
                    </form>
                </div>

                <!-- Departments Table -->
                <div class="table-responsive">
                    <table id="departmentsTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Filled by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- USER ACCOUNTS PAGE -->
            <?php if ($loggedInUserRole === 'admin' || $loggedInUserRole === 'executive' || $loggedInUserRole === 'hr'): ?>
                <div id="users-list-page" class="page-content">
                    <div class="main-title">
                        <h1 class="fw-bold">USER ACCOUNTS</h1>
                    </div>

                    <!-- Department Filter -->
                    <div class="row mb-3 align-items-end">
                        <div class="col-md-4">
                            <label for="adminDepartmentFilter" class="form-label">Filter by Department:</label>
                            <select id="adminDepartmentFilter" class="form-select">
                                <option value="0">All Departments</option>
                            </select>
                        </div>
                        <div class="col text-end">
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fa fa-user-plus"></i> Create User
                            </button>
                        </div>
                    </div>


                    <!-- Users Table -->
                    <div class="table-responsive">
                        <table id="usersTable" class="table table-striped table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Image</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Department</th>
                                    <th scope="col">Role</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamic rows will be injected here by JS -->
                            </tbody>
                        </table>
                        <!---PAGINATION FOR USERS LIST-->
                        <div class="d-flex justify-content-center mt-3">
                            <nav>
                                <ul class="pagination" id="pagination"></ul>
                            </nav>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TEAM MANAGEMENT PAGE -->
            <div id="team-management-page" class="page-content">
                <div class="main-title">
                    <h1 class="fw-bold">TEAM MANAGEMENT</h1>
                </div>

                <!-- Department Filter -->
                <div class="row mb-3 align-items-end">
                    <div class="col text-end">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createTeamModal">
                            <i class="fa fa-user-plus"></i> Create team
                        </button>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="accordion" id="teamsAccordion"></div>
                </div>
            </div>

            <!-- VALUES SETUP PAGE -->
            <div id="values-setup-page" class="page-content">
                <div class="main-title">
                    <h1 class="fw-bold">VALUES SETUP</h1>
                </div>

                <!-- Department Filter -->
                <div class="row mb-3 align-items-end">
                    <div class="col text-end">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createValueModal">
                            <i class="fa fa-user-plus"></i> Create Values
                        </button>
                    </div>
                </div>

                <div class="card mt-4">
                    <table class="table table-bordered align-middle" id="valuesTable">
                        <thead class="table-light">
                            <tr>
                                <th>Value Name</th>
                                <th>Description</th>
                                <th width="220">Value Status</th>
                                <th width="110" text>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!--- COMMENDATION PAGE --->
            <div id="commendation-page" class="page-content">
                <div class="main-title mb-3">
                    <h1 class="fw-bold">COMMENDATIONS</h1>
                </div>

                <!-- 🔍 Filter Section -->
                <div class="filters mb-3 row g-2 align-items-end">
                    <div class="col-md-2">
                        <label for="commendStartDate" class="form-label">From Date</label>
                        <input type="date" id="commendStartDate" class="form-control" />
                    </div>

                    <div class="col-md-2">
                        <label for="commendEndDate" class="form-label">To Date</label>
                        <input type="date" id="commendEndDate" class="form-control" />
                    </div>

                    <!-- 👥 Department Filter (Admins / Executives / HR Only) -->
                    <?php if ($loggedInUserRole === 'admin' || $loggedInUserRole === 'executive' || $loggedInUserRole === 'hr'): ?>
                        <div class="col-md-2">
                            <label for="commendValueFilter" class="form-label">Value</label>
                            <select class="form-select" id="commendValueFilter">
                                <option value="">Value</option>
                            </select>
                        </div>


                        <div class="col-md-2">
                            <label for="commendFilterUser" class="form-label">Employee</label>
                            <select id="commendFilterUser" class="form-select">
                                <option value="">All Employees</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-2">
                        <label for="commendFilterStatus" class="form-label">Status</label>
                        <select id="commendFilterStatus" class="form-select">
                            <option value="all">All</option>
                            <option value="pending" selected>Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="commendFilterType" class="form-label">Type</label>
                        <select id="commendFilterType" class="form-select">
                            <option value="all">All</option>
                            <option value="commend" selected>Commend</option>
                            <option value="deduct">Deduct</option>
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-success w-50" onclick="filterCommendations()">Filter</button>
                        <button class="btn btn-secondary w-50" onclick="resetCommendations()">Reset</button>
                    </div>

                </div>

                <!-- Commendation Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="commendation-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Nominator</th>
                                <th>Email</th>
                                <th>Nominee Name</th>
                                <th>Values</th>
                                <th>Points Awarded</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div id="commendationPagination" class="my-3 d-flex justify-content-center"></div>
                </table>
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

            <!-- TEAM CONSOLIDATION PAGE -->
            <div id="team-con-page" class="page-content">

                <div class="main-title mb-3">
                    <h1 class="fw-bold">TEAM CONSOLIDATION</h1>
                </div>

                <ul class="nav nav-tabs mb-3">

                    <li class="nav-item">
                        <button class="nav-link active"
                            data-bs-toggle="tab"
                            data-bs-target="#teamValueTab" style="color: #198754; font-weight: bold;">
                            Consolidated by Value
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link"
                            data-bs-toggle="tab"
                            data-bs-target="#teamTotalTab" style="color: #198754; font-weight: bold;">
                            Total Commendations (Team)
                        </button>
                    </li>

                </ul>

                <div class="tab-content">

                    <!-- VALUE TAB -->
                    <div class="tab-pane fade show active" id="teamValueTab">

                        <div class="filters mb-3 row g-2 align-items-end">

                            <div class="col-md-3">
                                <label>Start Date</label>
                                <input type="date" id="teamStartDate" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <label>End Date</label>
                                <input type="date" id="teamEndDate" class="form-control">
                            </div>

                            <div class="col-md-2">
                                <label></label>
                                <button class="btn btn-success w-100"
                                    onclick="loadTeamValue()">Search</button>
                            </div>

                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="teamConTable">
                                <thead>
                                    <tr id="teamConHeader"></tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                    </div>

                    <!-- TOTAL TAB -->
                    <div class="tab-pane fade" id="teamTotalTab">

                        <div class="filters mb-3 row g-2 align-items-end">

                            <div class="col-md-3">
                                <label>Start Date</label>
                                <input type="date" id="teamTotalStart" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <label>End Date</label>
                                <input type="date" id="teamTotalEnd" class="form-control">
                            </div>

                            <div class="col-md-2">
                                <label></label>
                                <button class="btn btn-success w-100"
                                    onclick="loadTeamTotal()">Search</button>
                            </div>

                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="teamTotalTable">
                                <thead>
                                    <tr>
                                        <th>Team</th>
                                        <th>Total Points</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                    </div>

                </div>
            </div>

            <!-- INDIVIDUAL CONSOLIDATION PAGE -->
            <div id="individual-con-page" class="page-content">

                <div class="main-title mb-3">
                    <h1 class="fw-bold">INDIVIDUAL CONSOLIDATION</h1>
                </div>

                <ul class="nav nav-tabs mb-3">

                    <li class="nav-item">
                        <button class="nav-link active"
                            data-bs-toggle="tab"
                            data-bs-target="#indValueTab" style="color: #198754; font-weight: bold;">
                            Consolidated by Value
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link"
                            data-bs-toggle="tab"
                            data-bs-target="#indTotalTab" style="color: #198754; font-weight: bold;">
                            Total Commendations (Individual)
                        </button>
                    </li>

                </ul>

                <div class="tab-content">

                    <!-- TAB 1 -->
                    <div class="tab-pane fade show active" id="indValueTab">

                        <div class="filters mb-3 row g-2 align-items-end">

                            <div class="col-md-2">
                                <label>Start Date</label>
                                <input type="date" id="indStartDate" class="form-control">
                            </div>

                            <div class="col-md-2">
                                <label>End Date</label>
                                <input type="date" id="indEndDate" class="form-control">
                            </div>

                            <div class="col-md-2">
                                <label>Level</label>
                                <select id="indValueLevelFilter" class="form-select">
                                    <option value="">All</option>
                                    <option value="employee">Employee</option>
                                    <option value="supervisor">Supervisor</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label>Value</label>
                                <select id="indValueFilter" class="form-select">
                                    <option value="">All Values</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label></label>
                                <button class="btn btn-success w-100"
                                    onclick="loadIndividualValue()">Search</button>
                            </div>

                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="individualConTable">
                                <thead>
                                    <tr id="individualConHeader"></tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                    </div>


                    <!-- TAB 2 -->
                    <div class="tab-pane fade" id="indTotalTab">

                        <div class="filters mb-3 row g-2 align-items-end">

                            <div class="col-md-3">
                                <label>Start Date</label>
                                <input type="date" id="indTotalStart" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <label>End Date</label>
                                <input type="date" id="indTotalEnd" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <label>Level</label>
                                <select id="indLevelFilter" class="form-select">
                                    <option value="employee">Employee</option>
                                    <option value="supervisor">Supervisor</option>
                                </select>
                            </div>

                            <div class="col-md-3 d-flex align-items-end">
                                <button class="btn btn-success w-100"
                                    onclick="loadIndividualTotal()">Search</button>
                            </div>

                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="individualTotalTable">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Total Points</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                    </div>

                </div>
            </div>

            <!---CREATE WORK MODE PAGE--->
            <div id="create-work-mode-page" class="page-content">
                <div class="main-title">
                    <h1 class="fw-bold">WORKMODE</h1>
                </div>

                <div class="row">
                    <!-- Add Work Mode -->
                    <div class="col-md-4 mb-4">
                        <div class="card p-4">
                            <h4 class="mb-3">Add Work Mode</h4>
                            <form id="addWorkModeForm">
                                <div class="mb-3">
                                    <label for="work_mode_name" class="form-label">Work Mode Name</label>
                                    <input type="text" class="form-control" id="work_mode_name" name="work_mode_name" required placeholder="e.g. Web Development">
                                </div>
                                <button type="submit" class="btn btn-success w-100">Add Work Mode</button>
                            </form>
                        </div>
                    </div>

                    <!-- Add Task Description -->
                    <div class="col-md-8 mb-4">
                        <div class="card p-4">
                            <h4 class="mb-3">Add Task Description</h4>
                            <form id="addTaskDescriptionForm">
                                <div class="mb-3">
                                    <label for="work_mode_id" class="form-label">Select Work Mode</label>
                                    <select class="form-select" id="work_mode_id" name="work_mode_id" required>
                                        <option value="">-- Choose Work Mode --</option>
                                        <!-- Dynamically populated -->
                                    </select>
                                </div>
                                <div id="taskInputs">
                                    <!---div class="mb-3 task-desc-group">
                                        <input type="text" class="form-control" name="task_description[]" required placeholder="e.g. Debug API endpoint">
                                    </div--->
                                    <div class="d-flex gap-2 task-desc-group mb-3">

                                        <select class="form-select billing-category" name="billing_category_id[]">
                                            <option value="">Billing Category</option>
                                        </select>

                                        <input type="text"
                                            class="form-control"
                                            name="task_description[]"
                                            placeholder="Additional Task">

                                        <!-- ADDED STANDARD AHT -->
                                        <input type="number"
                                            class="form-control"
                                            name="standard_aht[]"
                                            step="0.01"
                                            min="0"
                                            placeholder="Standard AHT">

                                        <button type="button" class="btn btn-danger btn-sm remove-task-btn">
                                            <i class="fa fa-trash"></i>
                                        </button>

                                    </div>
                                </div>

                                <button type="button" class="btn btn-outline-secondary mb-2" id="addMoreTask">+ Add More</button>
                                <button type="submit" class="btn btn-success w-100">Add Task Descriptions</button>
                            </form>
                        </div>
                    </div>

                </div>

                <hr class="my-4">

                <div class="card p-4">
                    <h4 class="mb-3">Edit Work Mode & Descriptions</h4>

                    <!-- Work Mode Selector -->
                    <div class="mb-3">
                        <label for="edit_work_mode" class="form-label">Select Work Mode</label>
                        <select class="form-select" id="edit_work_mode">
                            <option value="">-- Choose Work Mode --</option>
                        </select>
                    </div>

                    <!-- Work Mode Name Editor -->
                    <div class="mb-3" id="workModeEditor" style="display: none;">
                        <label class="form-label">Work Mode Name</label>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary toggle-edit" title="Toggle Edit Work Mode">
                                <i class="fa fa-eye"></i>
                            </button>
                            <input type="text" class="form-control mx-2" id="edit_work_mode_field" disabled>
                            <button class="btn btn-primary d-none" id="saveWorkModeNameBtnDynamic">Save</button>
                            <button class="btn btn-danger ms-2 d-none" id="deleteWorkModeBtn">Delete Work Mode</button>
                        </div>
                    </div>


                    <!-- Task Descriptions -->
                    <div class="mb-3">
                        <label class="form-label">Task Descriptions</label>
                        <div id="editDescriptionsContainer" class="d-flex flex-column gap-2">
                            <!-- Tasks will load here -->
                        </div>
                        <button class="btn btn-success btn-md mt-2" id="saveOrderBtn" disabled>
                            Save Order
                        </button>
                    </div>
                </div>
            </div>

            <!-- BILLING CATEGORY PAGE -->
            <div id="billing-category-page" class="page-content">
                <div class="main-title">
                    <h1 class="fw-bold">BILLING CATEGORY</h1>
                </div>

                <div class="row">
                    <!-- CREATE CATEGORIES -->
                    <div class="col-md-5">
                        <div class="card p-4">
                            <h5 class="mb-3">Create Billing Categories</h5>
                            <div id="billingInputs">
                                <div class="input-group mb-2 billing-input-group">
                                    <input type="text" class="form-control billing-category-new"
                                        placeholder="Billing Category">
                                    <button class="btn btn-danger remove-billing-input">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>

                            <button class="btn btn-outline-secondary mb-2 w-100" id="addBillingInput">
                                + Add More
                            </button>

                            <button class="btn btn-success w-100" id="addBillingCategoryBtn">
                                Add Billing Categories
                            </button>

                        </div>

                    </div>
                </div>

                <!-- CATEGORY TABLE -->

                <table class="table table-bordered align-middle mt-3" id="billingCategoryTable">
                    <thead class="table-light">
                        <tr>
                            <th>Category Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

            </div>

            <!---EDIT PROFILE USER--->
            <div id="edit-profile-page" class="page-content">
                <div class="main-title">
                    <h1 class="fw-bold">PROFILE</h1>
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

            <!---REVISED REQUESTS PAGE-->
            <div id="dtr-amendments-page" class="page-content">
                <div class="main-title d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h1 class="fw-bold">DTR AMENDMENTS MANAGEMENT</h1>
                    <!---div class="filters d-flex align-items-center gap-2">
                        <select id="filterRequestor" class="form-select w-auto">
                            <option value="">All Requestors</option>
                        </select>
                        <input type="text" id="searchRequestor" class="form-control" placeholder="Search requestor or task...">
                        <select id="amendmentFilterStatus" class="form-select w-auto">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                        <button id="filterBtn" class="btn btn-success">Filter</button>
                    </div--->
                </div>

                <!-- Filters -->
                <div class="row g-2 align-items-center mt-3 mb-3">
                    <div class="col-md-6 col-lg-5">
                        <input type="text" id="searchRequestor" class="form-control"
                            placeholder="Search by requestor or task">
                    </div>
                    <div class="col-md-3 col-lg-3">
                        <select id="amendmentFilterStatus" class="form-select form-control">
                            <option value="">All Status</option>
                            <option value="Pending" selected>Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-lg-2 d-flex justify-content-start">
                        <button id="filterBtn" class="btn btn-success w-100">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Request UID</th>
                                <th>Requestor</th>
                                <th>Task</th>
                                <!---th>Field</th--->
                                <th>Old Value</th>
                                <th>New Value</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Processed By</th>
                                <th>Requested At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="dtr-requests-table">
                            <!-- JS Populated -->
                        </tbody>
                    </table>
                </div>

                <div id="dtr-pagination" class="text-center mt-3"></div>
            </div>

            <!--- 🕒 OVERTIME REQUEST PAGE --->
            <div id="ot-request-page" class="page-content">
                <div class="main-title mb-3">
                    <h1 class="fw-bold">OVERTIME REQUESTS</h1>
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

                    <!-- 👥 Department Filter (Admins / Executives / HR Only) -->
                    <?php if ($loggedInUserRole === 'admin' || $loggedInUserRole === 'executive' || $loggedInUserRole === 'hr'): ?>
                        <div class="col-md-2">
                            <label for="otSummaryDepartmentFilter" class="form-label">Department</label>
                            <select class="form-select" id="otSummaryDepartmentFilter">
                                <option value="">All Departments</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-2">
                        <label for="otUserDropdown" class="form-label">Employee</label>
                        <select id="otUserDropdown" class="form-select">
                            <option value="">All Employees</option>
                        </select>
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

                    <!-- Buttons -->
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-success w-50" onclick="filterOTRequests()">Filter</button>
                        <button class="btn btn-secondary w-50" onclick="resetOTRequests()">Reset</button>
                    </div>

                </div>

                <!-- Place Create button on a separate row below filters, left-aligned -->
                <div class="row mb-3">
                    <div class="col-md-2 d-flex justify-content-start">
                        <button class="btn btn-success" onclick="openCreateOvertimeModal()">
                            <i class="bi bi-plus-circle"></i> Create Overtime Request
                        </button>
                    </div>
                </div>

                <!-- 📋 Overtime Request Table -->
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
                </div>
                <div id="otRequestsPagination" class="my-3 d-flex justify-content-center"></div>
                </table>
            </div>

            <!---LEAVE REQUEST PAGE --->
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

                    <!-- 👥 Department Filter (Admins / Executives / HR Only) -->
                    <?php if ($loggedInUserRole === 'admin' || $loggedInUserRole === 'executive' || $loggedInUserRole === 'hr'): ?>
                        <div class="col-md-2">
                            <label for="leaveSummaryDepartmentFilter" class="form-label">Department</label>
                            <select class="form-select" id="leaveSummaryDepartmentFilter">
                                <option value="">All Departments</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-2">
                        <label for="leaveUserDropdown" class="form-label">Employee</label>
                        <select id="leaveUserDropdown" class="form-select">
                            <option value="">All Employees</option>
                        </select>
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
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-success w-50" onclick="filterLeaveRequests()">Filter</button>
                        <button class="btn btn-secondary w-50" onclick="resetLeaveRequests()">Reset</button>
                    </div>
                </div>

                <!-- Place Create button on a separate row below filters, left-aligned -->
                <div class="row mb-3">
                    <div class="col-md-2 d-flex justify-content-start">
                        <button class="btn btn-success" onclick="openCreateLeaveModal()">
                            <i class="bi bi-plus-circle"></i>Leave Request
                        </button>
                    </div>
                </div>

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

            <!---SCHEDULER PAGE --->
            <div id="scheduler-page" class="page-content">
                <div class="main-title mb-3">
                    <h1 class="fw-bold">SCHEDULER</h1>
                </div>

                <div class="filters mb-3 row g-2 align-items-end">
                    <div class="col-md-3">
                        <label for="schedulerDepartmentFilter">Select Department</label>
                        <select class="form-select" id="schedulerDepartmentFilter">
                            <option value="">All Departments</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="schedulerMonth">Select Month</label>
                        <input type="month" id="schedulerMonth" class="form-control" />
                    </div>

                    <div class="col-md-2">
                        <button class="btn btn-success w-100" onclick="loadSchedulerMatrix()">Load</button>
                    </div>
                </div>

                <!-- LEGEND -->
                <div class="mb-2 small text-muted">
                    <span class="me-2"><b>W</b>=Work</span>
                    <span class="me-2"><b>OFF</b>=Rest</span>
                    <span class="me-2"><b>RH</b>=Regular Holiday</span>
                    <span class="me-2"><b>SH</b>=Special Holiday</span>
                    <span class="me-2"><b>RHL</b>=Regular Holiday Leave</span>
                    <span class="me-2"><b>Leave</b>=Paid Leave</span>
                    <span class="me-2"><b>Sick-PL</b>=Paid Sick Leave</span>
                    <span class="me-2"><b>Unpaid</b>=Unpaid Leave</span>
                    <span class="me-2"><b>Absent</b>=No show</span>
                    <span class="me-2"><b>CB</b>=Callback</span>
                    <span class="me-2"><b>SBL</b>=Special Benefit Leave</span>
                    <span class="me-2"><b>LWOP</b>=Leave without Pay</span>
                    <span class="me-2"><b>SPND</b>=Suspended</span>
                </div>

                <!-- MATRIX TABLE -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="scheduler-matrix-wrap">
                            <table class="table table-bordered table-sm align-middle text-center mb-0" id="schedulerMatrixTable">
                                <thead id="schedulerMatrixHead"></thead>
                                <tbody id="schedulerMatrixBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ACTION TOOLBAR -->
                <div class="card mb-3">
                    <div class="card-body d-flex flex-wrap gap-2 align-items-end">

                        <div>
                            <label class="form-label mb-1">Set Schedule Code</label>
                            <div class="btn-group">
                                <button id="btnW" class="btn btn-outline-primary" onclick="setSelectedCode('W', this)">W</button>
                                <button id="btnOff" class="btn btn-outline-secondary" onclick="setSelectedCode('OFF', this)">OFF</button>
                                <button id="btnRH" class="btn btn-outline-success" onclick="setSelectedCode('RH', this)">RH</button>
                                <button id="btnSH" class="btn btn-outline-success" onclick="setSelectedCode('SH', this)">SH</button>
                                <button id="btnCB" class="btn btn-outline-info" onclick="setSelectedCode('CB', this)">CB</button>
                                <button id="btnAbsent" class="btn btn-outline-danger" onclick="setSelectedCode('ABSENT', this)">Absent</button>
                                <button id="btnSBL" class="btn btn-outline-warning" onclick="setSelectedCode('SBL', this)">SBL</button>
                                <button id="btnLWOP" class="btn btn-outline-danger" onclick="setSelectedCode('LWOP', this)">LWOP</button>
                                <button id="btnSPND" class="btn btn-outline-danger" onclick="setSelectedCode('SPND', this)">SPND</button>
                            </div>
                        </div>

                        <!---div>
                            <label class="form-label mb-1">Call Time</label>
                            <select id="schedulerCallTimeSelect" class="form-select w-auto">
                                <ul id="schedulerCallTimeMenu" class="dropdown-menu" style="max-height: 220px; overflow-y: auto;">
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
                                </ul>
                            </select>
                        </div--->

                        <div class="dropdown">
                            <label class="form-label mb-1 d-block"></label>

                            <button
                                class="btn btn-outline-secondary dropdown-toggle"
                                type="button"
                                id="schedulerCallTimeDropdownBtn"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">
                                Call Time
                            </button>

                            <ul
                                id="schedulerCallTimeMenu"
                                class="dropdown-menu"
                                aria-labelledby="schedulerCallTimeDropdownBtn"
                                style="max-height: 220px; overflow-y: auto;">
                                <!-- JS will fill <li><button class="dropdown-item"...> -->
                            </ul>

                            <!-- hidden field to store the selected value -->
                            <input type="hidden" id="schedulerCallTimeSelect" value="">
                        </div>

                        <div class="ms-auto d-flex gap-2">
                            <button class="btn btn-success" onclick="confirmApply()">
                                <i class="fa-solid fa-check me-1"></i> Apply Tag
                            </button>

                            <button class="btn btn-danger" onclick="confirmDeleteSelected()">
                                <i class="fa-solid fa-trash me-1"></i> Delete Selected
                            </button>

                            <button class="btn btn-primary" onclick="saveSchedulerChanges()">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                            </button>

                            <button class="btn btn-outline-dark" onclick="clearSelection()">
                                <i class="fa-solid fa-xmark me-1"></i> Clear Selection
                            </button>
                        </div>

                    </div>
                </div>

                <!-- CONFIRM MODAL -->
                <div class="modal fade" id="schedulerConfirmModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirm Apply</h5>
                                <button class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div id="schedulerConfirmText">-</div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-success" onclick="applyToSelected()">Yes, Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ANNOUNCEMENTS PAGE -->
            <div id="announcements-page" class="page-content">
                <div class="main-title">
                    <h1 class="fw-bold">ANNOUNCEMENTS</h1>
                </div>

                <!-- 🔍 Filter Section -->
                <div class="filters mb-3 row g-2 align-items-end">
                    <div class="col-md-2">
                        <label for="announcementStartDate" class="form-label">From Date</label>
                        <input type="date" id="announcementStartDate" class="form-control" />
                    </div>

                    <div class="col-md-2">
                        <label for="announcementendDate" class="form-label">To Date</label>
                        <input type="date" id="announcementendDate" class="form-control" />
                    </div>

                    <div class="col-md-2">
                        <label for=""></label>
                        <button class="btn btn-success w-100" onclick="filterAnnouncements()">Search</button>
                    </div>

                    <div class="col-md-2">
                        <label for=""></label>
                        <button class="btn btn-success w-100" onclick="createAnnouncements()">Create</button>
                    </div>

                    <table class="table table-bordered align-middle" id="announcementTable">
                        <thead class="table-light">
                            <tr>
                                <th>Published Date</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <?php include "../modals/create-announcement-modal.php"; ?>
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
    <?php include "../modals/add-user-modal.php"; ?>
    <?php include "../modals/success-modal.php"; ?>
    <?php include "../modals/task-insertion-modal.php"; ?>
    <?php include "../modals/view-task-insertion-modal.php"; ?>
    <?php include "../modals/assign-work-mode-modal.php"; ?>
    <?php include "../modals/ot-requests-modal.php"; ?>
    <?php include "../modals/edit-ot-requests-modal.php"; ?>
    <?php include "../modals/create-leave-request-modal.php"; ?>
    <?php include "../modals/view-leave-request-modal.php"; ?>
    <?php include "../modals/commendation-modals/create-teams-modal.php"; ?>
    <?php include "../modals/commendation-modals/view-teams-modal.php"; ?>
    <?php include "../modals/commendation-modals/edit-teams-modal.php"; ?>
    <?php include "../modals/commendation-modals/edit-values-modal.php"; ?>
    <?php include "../modals/commendation-modals/create-values-modal.php"; ?>
    <?php include "../modals/commendation-modals/create-commend-modal.php"; ?>
    <?php include "../modals/commendation-modals/view-commend-modal.php"; ?>
    <?php include "../modals/commendation-modals/commend-notif-modal.php"; ?>
    <?php include "../modals/commendation-modals/view-commend-list-modal.php"; ?>

    <!---JS LINKS HERE--->
    <!---script src="../js/admin-amendments-archive.js"></script--->
    <!---script src="../js/admin-request-render.js"></script>
    <script src="../js/admin-amendments.js"></script--->
    <script src="../js/idle-session-timeout.js"></script>
    <script src="../js/announcements.js"></script>
    <script src="../js/consolidation.js"></script>
    <script src="../js/task-insertion-request.js"></script>
    <script src="../js/task-insertion-history.js"></script>
    <script src="../js/start-tag-task.js"></script>
    <script src="../js/slider-function.js"></script>
    <script src="../js/load-monthly-summary.js"></script>
    <script src="../js/commendations-notif.js"></script>
    <script src="../js/commendations-table.js"></script>
    <script src="../js/leave-request.js"></script>
    <script src="../js/ot-requests.js"></script>
    <script src="../js/task-insertion-management.js"></script>
    <script src="../js/unified-amendments.js"></script>
    <script src="../js/task-insertion.js"></script>
    <script src="../js/global-loader.js"></script>
    <script src="../js/load-statuses.js"></script>
    <script src="../js/edit-profile.js"></script>
    <script src="../js/create-work-mode.js"></script>
    <script src="../js/toggle-department.js"></script>
    <script src="../js/sidebar.js"></script>
    <script src="../js/real-time-clock.js"></script>
    <script src="../js/toggle-password.js"></script>
    <script src="../js/js-modals/user-added-modal.js"></script>
    <script src="../js/tracker-edit-task.js"></script>
    <script src="../js/user-amendments.js"></script>
    <script src="../js/archive.js"></script>
    <script src="../js/user-requests.js"></script>
    <script src="../js/users-list.js"></script>
    <script src="../js/departments.js"></script>
    <script src="../js/scheduler.js"></script>
    <script src="../js/commendations-related.js"></script>
    <script src="../js/data-visualization.js"></script>
    <script src="../js/alertService.js"></script>
    <script src="../js/aht-export.js"></script>
    <script src="../js/midnight-refresh-test.js"></script>



    <script>
        function confirmRegistration() {
            // Collect selected departments
            const checkboxes = document.querySelectorAll("#addUserDepartmentDropdown input[type=checkbox]:checked");
            const primaryRadio = document.querySelector("#addUserDepartmentDropdown input[type=radio]:checked");

            const departments = [];

            checkboxes.forEach(cb => {
                departments.push({
                    id: parseInt(cb.value),
                    primary: primaryRadio && parseInt(primaryRadio.value) === parseInt(cb.value) ? 1 : 0
                });
            });

            if (departments.length === 0) {
                alert("Please select at least one department.");
                return false;
            }

            // Remove previous hidden input if exists
            const existingInput = document.querySelector("#addUserModal form input[name=departments]");
            if (existingInput) existingInput.remove();

            // Create hidden input with JSON value
            const hiddenInput = document.createElement("input");
            hiddenInput.type = "hidden";
            hiddenInput.name = "departments";
            hiddenInput.value = JSON.stringify(departments);
            document.querySelector("#addUserModal form").appendChild(hiddenInput);
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
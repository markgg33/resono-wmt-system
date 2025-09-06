<?php
session_start();
require_once "../backend/connection_db.php"; // Adjust path if needed


// Check if user is logged in and has an authorized role
$allowedRoles = ['admin', 'hr', 'executive', 'supervisor'];

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
        sessionStorage.setItem("userRole", "<?php echo $_SESSION['role']; ?>");
        const userRole = "<?php echo $loggedInUserRole; ?>"; // make it accessible as a JS variable
    </script>

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
                $userImage = !empty($_SESSION['profile_image'])
                    ? "../" . $_SESSION['profile_image']
                    : "../assets/default-avatar.png";
                ?>
                <img src="<?php echo htmlspecialchars($userImage); ?>"
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
                    <a class="sidebar-dropdown d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#generalSubmenu" role="button" aria-expanded="false" aria-controls="generalSubmenu">
                        <span><i class="fa-solid fa-house"></i>GENERAL</span>
                        <i class="fa-solid fa-caret-down"></i>
                    </a>

                    <ul class="collapse sidebar-submenu list-unstyled ps-3" id="generalSubmenu">
                        <li class="sidebar-list-item" data-page="my-tracker" onclick="changePage('my-tracker')">My Tracker</li>
                        <li class="sidebar-list-item" data-page="monthly-summary" onclick="changePage('monthly-summary')">Monthly Summary</li>
                    </ul>
                </li>

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
                        <li class="sidebar-list-item" data-page="admin-request" onclick="changePage('admin-request')">Admin Requests</li>
                        <li class="sidebar-list-item" data-page="dtr-amendment" onclick="changePage('dtr-amendment')">DTR Requests</li>
                        <li class="sidebar-list-item" data-page="dtr-amendment-archive" onclick="changePage('dtr-amendment-archive')">DTR Archives</li>
                    </ul>
                </li>

                <!----USER MANAGEMENT---->
                <li>
                    <a class="sidebar-dropdown d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#userManagementmenu" role="button" aria-expanded="false" aria-controls="userManagementmenu">
                        <span><i class="fa-solid fa-user"></i>USER MANAGEMENT</span>
                        <i class="fa-solid fa-caret-down"></i>
                    </a>

                    <ul class="collapse sidebar-submenu list-unstyled ps-3" id="userManagementmenu">

                        <li class="sidebar-list-item" data-page="add-users" onclick="changePage('add-users')">Add Users</li>
                        <li class="sidebar-list-item" data-page="users-list" onclick="changePage('users-list')">Users list</li>
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
                        <li class="sidebar-list-item" data-page="create-work-mode" onclick="changePage('create-work-mode')">Create Work Mode</li>
                        <li class="sidebar-list-item" data-page="departments" onclick="changePage('departments')">Departments</li>
                        <li class="sidebar-list-item" data-page="archive" onclick="changePage('archive')">Archives</li>
                        <li class="sidebar-list-item" data-page="edit-profile" onclick="changePage('edit-profile')">Edit Profile</li>
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

                            <!---button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#insertTaskModal">
                                <i class="fa-solid fa-plus"></i> Insert Missed Task
                            </button--->
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

            <!---ADD USERS PAGE--->
            <div id="add-users-page" class="page-content">
                <div class="main-title">
                    <h1>ADD USERS</h1>
                </div>

                <div class="card p-4">
                    <form action="../backend/add_user.php" method="POST" enctype="multipart/form-data" onsubmit="return confirmRegistration()">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="employee_id" class="form-label">Employee ID (Optional)</label>
                                <input type="text" class="form-control" id="employee_id" name="employee_id" placeholder="e.g. 2024-0012">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="first_name" class="form-label">First Name <span style="color:red;">*</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-4">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middle_name" name="middle_name">
                            </div>
                            <div class="col-md-4">
                                <label for="last_name" class="form-label">Last Name <span style="color:red;">*</span></label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                            <div class="col-md-4 mt-3">
                                <label for="email" class="form-label">Email <span style="color:red;">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" required>
                            </div>
                            <div class="col-md-4 mt-3">
                                <label for="password" class="form-label">Password <span style="color:red;">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="••••••" required>
                                    <span class="input-group-text toggle-password" onclick="togglePassword('password')">
                                        <i class="fa-solid fa-eye-slash"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-4 mt-3">
                                <label for="role" class="form-label">Role <span style="color:red;">*</span></label>
                                <select class="form-select" id="role" name="role" required onchange="toggleDepartmentField()">
                                    <option value="">-- Select Role --</option>
                                    <option value="admin">Admin</option>
                                    <option value="executive">Executive</option>
                                    <option value="hr">HR</option>
                                    <option value="user">User</option>
                                    <option value="client">Client</option>
                                    <option value="supervisor">Supervisor</option>
                                </select>
                            </div>
                            <div class="col-md-4 mt-3">
                                <label for="profile_image" class="form-label">Profile Image</label>
                                <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                            </div>
                            <div class="col-md-4 mt-3" id="departmentField" style="display: none;">
                                <label for="department_id" class="form-label">Department <span style="color:red;">*</span></label>
                                <select class="form-select" id="department_id" name="department_id">
                                    <option value="">-- Select Department --</option>
                                </select>
                            </div>

                        </div>
                        <button type="submit" class="btn-register">Register</button>
                    </form>
                </div>

                <?php include "../modals/success-modal.php"; ?>

            </div>

            <!---DEPARTMENTS PAGE--->
            <div id="departments-page" class="page-content">
                <div class="main-title">
                    <h1>DEPARTMENTS</h1>
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
                <!-- Work Mode Assignment Section -->
                <div class="card mt-4 shadow-sm p-3">
                    <h5 class="mb-3">Assign Work Modes to Department</h5>
                    <form id="assignWorkModeForm" class="d-flex gap-2">
                        <select id="assignDeptSelect" class="form-control" required></select>
                        <select id="assignWorkModeSelect" class="form-control" required></select>
                        <button type="submit" class="btn btn-success">Assign</button>
                    </form>
                </div>

                <!-- Assigned Work Modes Table -->
                <div class="table-responsive mt-4">
                    <table id="deptWorkModesTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Work Mode</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Filled by JS -->
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- USERS LIST PAGE -->
            <div id="users-list-page" class="page-content">
                <div class="main-title">
                    <h1>USERS LIST</h1>
                </div>

                <!-- Department Filter -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="departmentFilter" class="form-label">Filter by Department:</label>
                        <select id="departmentFilter" class="form-select">
                            <option value="0">All Departments</option>
                        </select>
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
                </div>
            </div>

            <!---CREATE WORK MODE PAGE--->
            <div id="create-work-mode-page" class="page-content">
                <div class="main-title">
                    <h1>CREATE WORK MODE & TASK</h1>
                </div>

                <div class="row">
                    <!-- Add Work Mode -->
                    <div class="col-md-6 mb-4">
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
                    <div class="col-md-6 mb-4">
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
                                    <div class="mb-3 task-desc-group">
                                        <input type="text" class="form-control" name="task_description[]" required placeholder="e.g. Debug API endpoint">
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
                        </div>
                    </div>

                    <!-- Task Descriptions -->
                    <div class="mb-3">
                        <label class="form-label">Task Descriptions</label>
                        <div id="editDescriptionsContainer" class="d-flex flex-column gap-2">
                            <!-- Tasks will load here -->
                        </div>
                    </div>
                </div>


                <?php include "../modals/wmt-success-modal.php"; ?>
            </div>

            <!---MONTHLY SUMMARY PAGE--->
            <div id="monthly-summary-page" class="page-content">
                <div class="main-title">
                    <h1>MONTHLY SUMMARY</h1>
                </div>

                <div class="filters mb-3 row g-2">
                    <div class="col-md-3">
                        <label for="searchUser">Search User</label>
                        <input type="text" id="searchUser" placeholder="Search User" class="form-control" />
                    </div>
                    <div class="col-md-2">
                        <label for="startDate">Start Date</label>
                        <input type="date" id="startDate" class="form-control" />
                    </div>
                    <div class="col-md-2">
                        <label for="endDate">End Date</label>
                        <input type="date" id="endDate" class="form-control" />
                    </div>
                    <div class="col-md-3">
                        <label for="summaryDepartmentFilter">Select Department</label>
                        <select class="form-select" id="summaryDepartmentFilter">
                            <option value="">All Departments</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for=""></label>
                        <button class="btn btn-success w-100" onclick="searchUsers()">Search</button>
                    </div>
                </div>



                <!-- Search Result List -->
                <div id="searchResults" class="my-3"></div>

                <!-- Table -->
                <div id="summaryTableWrapper" class="table-responsive" style="display:none;">
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
                                <th>Resono Function</th>
                                <th>Paid Break</th>
                                <th>Unpaid Break</th>
                                <th>Personal Time</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <!-- Export Button -->
                <div class="text-end mt-3">
                    <button id="exportPDFBtn" class="btn btn-danger" style="display:none;">
                        📄 Export to PDF
                    </button>
                    <button id="exportCSVBtn" class="btn btn-primary" style="display:none;">📊 Export to CSV</button>
                    <button id="exportDeptCSVBtn" class="btn btn-success">Export Department (ZIP)</button>
                </div>
            </div>

            <!---EDIT PROFILE USER--->
            <div id="edit-profile-page" class="page-content">
                <div class="main-title">
                    <h1>EDIT PROFILE</h1>
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
                                    <input type="text" id="edit_department" class="form-control-modern" disabled>
                                </div>

                                <!-- ✅ Profile Image (preview + upload) -->
                                <div class="form-group">
                                    <label>Profile Image</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <img id="profilePreview" src="../assets/default-avatar.png" class="rounded-circle border" width="96" height="96" style="object-fit:cover;">
                                        <div class="w-100">
                                            <input type="file" id="edit_profile_image" class="form-control-modern" accept="image/*">
                                            <small class="text-muted">JPEG/PNG/GIF, up to 5MB.</small>
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

            <!---ADMIN REQUEST SECTION--->
            <div id="admin-request-page" class="page-content">
                <div class="main-title">
                    <h1>ADMIN REQUESTS</h1>
                </div>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Request UID</th>
                            <!--th>Request #</th-->
                            <th>Date</th>
                            <th>Task</th>
                            <th>Field</th>
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
                    <tbody id="admin-request-table">
                        <!-- Filled by JS -->
                    </tbody>
                </table>
            </div>

            <!---ADMIN DTR AMENDMENT USER--->
            <div id="dtr-amendment-page" class="page-content">
                <div class="main-title">
                    <h1>DTR AMENDMENT REQUESTS</h1>
                </div>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Request UID</th>
                            <th>Requestor</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="admin-amendments-table"> <!-- Filled by JS --> </tbody>
                </table>
            </div>

            <!-- ADMIN DTR AMENDMENT ARCHIVE -->
            <div id="dtr-amendment-archive-page" class="page-content">
                <div class="main-title">
                    <h1>APPROVED & REJECTED DTR REQUESTS</h1>
                </div>
                <table class="table table-striped table-hover">
                    <tr>
                        <th>Request ID</th>
                        <th>Requester</th>
                        <th>Status</th>
                        <th>Processed At</th>
                        <th>Processed By</th>
                        <th>Actions</th>
                    </tr>
                    <tbody id="admin-amendments-archive-table">
                        <tr>
                            <td colspan="6" class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
                <div id="archive-pagination" class="mt-3 text-center"></div>
            </div>

            <!---ARCHIVE PAGE--->
            <div id="archive-page" class="page-content">
                <div class="main-title d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h1>THE ARCHIVE</h1>
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
                            </tr>
                        </thead>
                        <tbody>
                            <!-- rows go here -->
                        </tbody>
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

    <!---PHP MODAL--->
    <?php include "../modals/edit-task-modal.php"; ?>
    <?php include "../modals/admin-amendment-modal.php"; ?>
    <?php include "../modals/user-amendment-modal.php"; ?>
    <?php include "../modals/admin-user-profile-modal.php"; ?>
    <?php include "../modals/admin-edit-request-modal.php"; ?>
    <?php include "../modals/edit-department-modal.php"; ?>

    <!---JS LINKS HERE--->
    <script src="../js/admin-request-render.js"></script>
    <script src="../js/admin-amendments.js"></script>
    <script src="../js/edit-profile.js"></script>
    <script src="../js/start-tag-task.js"></script>
    <script src="../js/slider-function.js"></script>
    <script src="../js/create-work-mode.js"></script>
    <script src="../js/load-monthly-summary.js"></script>
    <script src="../js/toggle-department.js"></script>
    <script src="../js/sidebar.js"></script>
    <script src="../js/real-time-clock.js"></script>
    <script src="../js/toggle-password.js"></script>
    <script src="../js/js-modals/user-added-modal.js"></script>
    <script src="../js/tracker-edit-task.js"></script>
    <script src="../js/user-amendments.js"></script>
    <script src="../js/archive.js"></script>
    <script src="../js/user-requests.js"></script>
    <script src="../js/admin-amendments-archive.js"></script>
    <script src="../js/users-list.js"></script>
    <script src="../js/departments.js"></script>
    <script src="../js/insert-task-in-between.js"></script>
    <script src="../js/assign-dept-wmt.js"></script>




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
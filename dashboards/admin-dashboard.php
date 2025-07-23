<?php
session_start();
require_once "../backend/connection_db.php"; // Adjust path if needed

// Check if user is logged in and has an authorized role
$allowedRoles = ['admin', 'hr', 'executive'];

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

                        <li class="sidebar-list-item" data-page="add-users" onclick="changePage('add-users')">Add Users</li>
                        <li class="sidebar-list-item" data-page="edit-profile" onclick="changePage('edit-profile')">Edit Profile</li>
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

            <!---ADD USERS PAGE--->
            <div id="add-users-page" class="page-content">
                <div class="main-title">
                    <h1>ADD USERS</h1>
                </div>

                <div class="card p-4">
                    <form action="../backend/add_user.php" method="POST" onsubmit="return confirmRegistration()">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="employee_id" class="form-label">Employee ID (Optional)</label>
                                <input type="text" class="form-control" id="employee_id" name="employee_id" placeholder="e.g. 2024-0012">
                            </div>
                            <div class="col-md-4">
                                <label for="first_name" class="form-label">First Name <span style="color:red;">*</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-4">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middle_name" name="middle_name">
                            </div>
                            <div class="col-md-4 mt-3">
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
                                        <i class="fa-solid fa-eye"></i>
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
                                </select>
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
                                <button type="submit" class="btn btn-primary w-100">Add Task Descriptions</button>
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
                    <div class="col-md-5">
                        <input type="text" id="searchUser" placeholder="Search User" class="form-control" />
                    </div>
                    <div class="col-md-4">
                        <input type="month" id="monthFilter" class="form-control" />
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" onclick="searchUsers()">Search</button>
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
    <script src="../js/create-work-mode.js"></script>
    <script src="../js/load-monthly-summary.js"></script>
    <script src="../js/toggle-department.js"></script>
    <script src="../js/sidebar.js"></script>
    <script src="../js/real-time-clock.js"></script>
    <script src="../js/js-modals/user-added-modal.js"></script>
    <script src="../js/toggle-password.js"></script>


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
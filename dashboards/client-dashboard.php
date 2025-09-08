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
    <title>Client Data Dashboard</title>
    <!---CSS--->
    <link rel="stylesheet" href="../css/client.css">
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>
</head>

<body>

    <div class="grid-container">

        <!-- Navbar -->
        <nav class="client-navbar">
            <a href="#"><img src="../assets/RESONO_logo_edited.png" alt="Resono Logo" width="50"></a>
            <ul class="client-nav-items">
                <?php
                $deptQuery = $conn->query("SELECT * FROM departments");
                while ($dept = $deptQuery->fetch_assoc()) {
                    echo '<li><a href="#" class="dept-link" data-dept-id="' . $dept['id'] . '">' . $dept['name'] . '</a></li>';
                }
                ?>
            </ul>

            <a href="../backend/logout.php"><i class="fa-solid fa-arrow-right-from-bracket btn-logout"></i></a>
        </nav>

        <!-- Main Content -->
        <div class="client-main-container">


            <h1 id="department-title">Web Department</h1>

            <!-- Month Dropdown -->
            <div>
                <label for="monthSelect" class="form-label me-2">Month:</label>
                <select id="monthSelect" class="form-select form-select-sm d-inline-block" style="width: auto;">
                    <!-- Options will be populated by JS -->
                </select>
            </div>
            <br>

            <!-- Dashboard Cards -->
            <div class="main-cards">
                <div class="card">
                    <div class="card-inner">
                        <h2>Department Summary</h2>
                    </div>
                    <p>Total Production Hours: </p>
                </div>
                <div class="card">
                    <div class="card-inner">
                        <h2>Members</h2>
                    </div>
                </div>
                <div class="card">
                    <div class="card-inner">
                        <h2>Task Breakdown</h2>
                    </div>
                    <div id="taskBreakdown" class="task-breakdown"></div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-row">
            <!-- Tasks per Department -->
            <div class="card shadow-sm rounded chart-card">
                <div class="card-body p-4">
                    <div class="main-title">
                        <h1>Tasks per Department</h1>
                    </div>
                    <div style="position: relative; height: 350px; width: 100%;">
                        <canvas id="tasksChart"></canvas>
                    </div>
                    <div id="chartLegend" class="mt-3 text-center small"></div>
                </div>
            </div>

            <!-- Tasks Over Time -->
            <div class="card shadow-sm rounded chart-card">
                <div class="card-body p-4">
                    <div class="main-title">
                        <h1>Tasks Over Time</h1>
                    </div>
                    <div style="position: relative; height: 350px; width: 100%;">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>
    </div>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Scripts -->
    <script src="../js/client-dashboard.js"></script>

</body>

</html>
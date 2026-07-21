<?php
require_once "connection_db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $employee_id   = $_POST["employee_id"] ?? "";
    $first_name    = $_POST["first_name"] ?? "";
    $middle_name   = $_POST["middle_name"] ?? "";
    $last_name     = $_POST["last_name"] ?? "";
    $email         = $_POST["email"] ?? "";
    $password      = $_POST["password"] ?? "";
    $role          = $_POST["role"] ?? "";
    $profile_image = $_POST["profile_image"] ?? "";
    //FOR LEAVE REQUESTS
    $vacation = intval($_POST["vacationLeave"] ?? 0);
    $sick = intval($_POST["sickLeave"] ?? 0);
    $comp = intval($_POST["compLeave"] ?? 0);
    $emergency = intval($_POST["emergencyLeave"] ?? 0);

    // 🔹 Parse departments (from JSON string sent by JS)
    $departments = [];
    if (!empty($_POST["departments"])) {
        $decoded = json_decode($_POST["departments"], true);
        if (is_array($decoded)) {
            $departments = $decoded;
        }
    }

    // 🔹 Only require departments for specific roles
    $rolesRequiringDept = ["user", "supervisor", "client"];
    if (in_array($role, $rolesRequiringDept) && empty($departments)) {
        echo json_encode(["success" => false, "message" => "No department selected for this role."]);
        exit;
    }


    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    //WITHOUT LEAVE REQUESTS
    /*
    $stmt = $conn->prepare("
        INSERT INTO users 
        (employee_id, first_name, middle_name, last_name, email, password, role, profile_image)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    */
    //WITH LEAVE REQUESTS
    $stmt = $conn->prepare("
        INSERT INTO users 
(employee_id, first_name, middle_name, last_name, email, password, role, profile_image,
 vacation_leave, sick_leave, compassionate_leave, emergency_leave)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    //WITHOUT LEAVE REQUESTS
    /*
    $stmt->bind_param(
        "ssssssss",
        $employee_id,
        $first_name,
        $middle_name,
        $last_name,
        $email,
        $hashedPassword,
        $role,
        $profile_image
    );
    */
    //WITH LEAVE REQUESTS
    $stmt->bind_param(
        "ssssssssiiii",
        $employee_id,
        $first_name,
        $middle_name,
        $last_name,
        $email,
        $hashedPassword,
        $role,
        $profile_image,
        $vacation,
        $sick,
        $comp,
        $emergency
    );


    if ($stmt->execute()) {
        $new_user_id = $stmt->insert_id;

        // 🔹 Insert departments with is_primary
        $stmt2 = $conn->prepare("INSERT INTO user_departments (user_id, department_id, is_primary) VALUES (?, ?, ?)");
        foreach ($departments as $dept) {
            $deptId = intval($dept["id"]);
            $isPrimary = !empty($dept["primary"]) ? 1 : 0;
            $stmt2->bind_param("iii", $new_user_id, $deptId, $isPrimary);
            $stmt2->execute();
        }
        $stmt2->close();

        echo json_encode(["success" => true, "message" => "User added successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => $stmt->error]);
    }
    $stmt->close();
}
$conn->close();

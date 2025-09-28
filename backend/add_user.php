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

    // 🔹 Parse departments (support JSON or normal array)
    $departmentsRaw = $_POST["department_ids"] ?? [];
    if (is_string($departmentsRaw)) {
        $departments = json_decode($departmentsRaw, true);
        if (!is_array($departments)) $departments = [];
    } elseif (is_array($departmentsRaw)) {
        $departments = [];
        foreach ($departmentsRaw as $id) {
            $departments[] = ["id" => intval($id), "primary" => 0];
        }
    } else {
        $departments = [];
    }

    if (empty($departments)) {
        echo json_encode(["success" => false, "message" => "No department selected"]);
        exit;
    }


    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        INSERT INTO users 
        (employee_id, first_name, middle_name, last_name, email, password, role, profile_image)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
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

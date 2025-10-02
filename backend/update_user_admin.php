<?php
require_once "connection_db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id          = intval($_POST["id"] ?? 0);
    $employee_id = $_POST["employee_id"] ?? "";
    $first_name  = $_POST["first_name"] ?? "";
    $middle_name = $_POST["middle_name"] ?? "";
    $last_name   = $_POST["last_name"] ?? "";
    $email       = trim($_POST["email"] ?? "");
    $role        = $_POST["role"] ?? "";
    $password    = $_POST["password"] ?? "";
    $profile_image = $_POST["current_photo"] ?? "";

    // ===== Handle image upload safely =====
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = time() . '_' . basename($_FILES['profile_image']['name']);
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
            $profile_image = 'uploads/' . $fileName; // relative path for DB
        } else {
            echo json_encode(["success" => false, "message" => "Failed to upload image."]);
            exit;
        }
    } elseif (!empty($_POST['keep_existing_image'])) {
        $profile_image = $_POST['keep_existing_image']; // keep old image
    } else {
        $profile_image = 'assets/default-avatar.jpg'; // fallback default
    }

    $departments = json_decode($_POST["departments"] ?? "[]", true);

    if (empty($email)) {
        echo json_encode(["success" => false, "message" => "Email cannot be empty."]);
        exit;
    }

    // ===== Check duplicate email but exclude current user =====
    $stmtCheck = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmtCheck->bind_param("si", $email, $id);
    $stmtCheck->execute();
    $stmtCheck->store_result();
    if ($stmtCheck->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Email already exists for another user."]);
        exit;
    }
    $stmtCheck->close();

    // ===== Update user (with or without password) =====
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            UPDATE users 
            SET employee_id=?, first_name=?, middle_name=?, last_name=?, email=?, password=?, role=?, profile_image=?
            WHERE id=?
        ");
        $stmt->bind_param("ssssssssi", $employee_id, $first_name, $middle_name, $last_name, $email, $hashedPassword, $role, $profile_image, $id);
    } else {
        $stmt = $conn->prepare("
            UPDATE users 
            SET employee_id=?, first_name=?, middle_name=?, last_name=?, email=?, role=?, profile_image=?
            WHERE id=?
        ");
        $stmt->bind_param("sssssssi", $employee_id, $first_name, $middle_name, $last_name, $email, $role, $profile_image, $id);
    }

    if ($stmt->execute()) {

        // ===== Clear old departments =====
        $conn->query("DELETE FROM user_departments WHERE user_id = $id");

        // ===== Insert new departments if any =====
        if (!empty($departments)) {
            $stmt2 = $conn->prepare("INSERT INTO user_departments (user_id, department_id, is_primary) VALUES (?, ?, ?)");
            foreach ($departments as $dept) {
                $deptId = intval($dept["id"]);
                $isPrimary = !empty($dept["primary"]) ? 1 : 0;
                $stmt2->bind_param("iii", $id, $deptId, $isPrimary);
                $stmt2->execute();
            }
            $stmt2->close();
        }

        echo json_encode(["success" => true, "message" => "User updated successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => $stmt->error]);
    }

    $stmt->close();
}
$conn->close();

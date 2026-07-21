<?php

require_once "../connection_db.php";

$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';

$imagePath = NULL;

/* -------------------------
CREATE UPLOAD FOLDER IF NOT EXISTS
--------------------------*/

$uploadDir = "../../uploads/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* -------------------------
IMAGE UPLOAD VALIDATION
--------------------------*/

if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    $originalName = $_FILES['image']['name'];
    $tmpName = $_FILES['image']['tmp_name'];

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowed)) {

        echo json_encode([
            "success" => false,
            "message" => "Only JPG, JPEG, PNG, WEBP images are allowed."
        ]);
        exit;
    }

    /* sanitize filename */
    $safeName = preg_replace("/[^a-zA-Z0-9\.\-_]/", "", $originalName);

    $fileName = time() . '_' . $safeName;

    if (move_uploaded_file($tmpName, $uploadDir . $fileName)) {
        $imagePath = $fileName;
    }
}

/* -------------------------
INSERT ANNOUNCEMENT
--------------------------*/

$stmt = $conn->prepare("
INSERT INTO announcements
(title, description, image)
VALUES (?,?,?)
");

$stmt->bind_param("sss", $title, $description, $imagePath);
$stmt->execute();

echo json_encode([
    "success" => true,
    "message" => "Announcement posted successfully."
]);

<?php

require_once "../connection_db.php";

$title = $_POST['title'];
$description = $_POST['description'];
$id = $_POST['id'] ?? null;

$imagePath = null;

if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {

    $uploadDir = "../../uploads/";

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid image type"
        ]);
        exit;
    }

    $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.\-_]/", "", $_FILES['image']['name']);

    move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName);

    $imagePath = $fileName;
}

if ($id) {

    if ($imagePath) {

        $stmt = $conn->prepare("
        UPDATE announcements
        SET title=?,description=?,image=?
        WHERE id=?
        ");

        $stmt->bind_param("sssi", $title, $description, $imagePath, $id);
    } else {

        $stmt = $conn->prepare("
        UPDATE announcements
        SET title=?,description=?
        WHERE id=?
        ");

        $stmt->bind_param("ssi", $title, $description, $id);
    }

    $stmt->execute();

    echo json_encode([
        "success" => true,
        "message" => "Announcement updated."
    ]);
} else {

    $stmt = $conn->prepare("
    INSERT INTO announcements(title,description,image)
    VALUES(?,?,?)
    ");

    $stmt->bind_param("sss", $title, $description, $imagePath);

    $stmt->execute();

    echo json_encode([
        "success" => true,
        "message" => "Announcement posted."
    ]);
}

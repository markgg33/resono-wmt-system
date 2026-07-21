<?php
require 'connection_db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$categories = $data['categories'] ?? [];

if (!is_array($categories) || count($categories) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No categories received'
    ]);
    exit;
}

$duplicates = [];
$inserted = [];

$stmtCheck = $conn->prepare("
SELECT id FROM billing_categories 
WHERE LOWER(category_name) = LOWER(?)
");

$stmtInsert = $conn->prepare("
INSERT INTO billing_categories (category_name)
VALUES (?)
");

foreach ($categories as $cat) {

    $name = trim($cat);

    if ($name === '') continue;

    $stmtCheck->bind_param("s", $name);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows > 0) {

        $duplicates[] = $name;
    } else {

        $stmtInsert->bind_param("s", $name);
        $stmtInsert->execute();

        $inserted[] = $name;
    }
}

echo json_encode([
    'success' => true,
    'inserted' => $inserted,
    'duplicates' => $duplicates
]);

$stmtCheck->close();
$stmtInsert->close();
$conn->close();

<?php

require 'connection_db.php';

//$result = $conn->query("SELECT id, category_name FROM billing_categories WHERE is_active = 1 ORDER BY category_name ASC");
$result = $conn->query("SELECT id, category_name, is_active FROM billing_categories ORDER BY category_name ASC");
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);

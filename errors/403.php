<?php
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>403 Forbidden</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-box {
            text-align: center;
            padding: 40px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .error-code {
            font-size: 5rem;
            font-weight: 700;
            color: #fd7e14;
        }
    </style>
</head>

<body>
    <div class="error-box">
        <div class="error-code">403</div>
        <h2>Forbidden</h2>
        <p>You are logged in but don’t have permission to access this page.</p>
        <a href="../backend/login.php" class="btn btn-success mt-3">Back to Login</a>
    </div>
</body>

</html>
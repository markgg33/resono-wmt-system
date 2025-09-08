<?php
http_response_code(401);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>401 Unauthorized</title>
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
            color: #00920cff;
        }
    </style>
</head>

<body>
    <div class="error-box">
        <div class="error-code">401</div>
        <h2>Unauthorized</h2>
        <p>Your session has expired or you are not logged in.</p>
        <a href="../index.php" class="btn btn-success mt-3">Go to Login</a>
    </div>
</body>

</html>
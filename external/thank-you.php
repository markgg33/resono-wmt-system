<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Recognition Submitted</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="thank-you.css">

    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>

    <script src="thank-you.js" defer></script>

</head>

<body>

    <div class="background"></div>

    <div class="success-wrapper">

        <div class="success-card">

            <img src="../assets/RESONO_logo_edited.png"
                class="success-logo">

            <div class="checkmark">

                ✓

            </div>

            <h1>

                You've Made Someone's Day!

            </h1>

            <p class="subtitle">

                Your recognition has been successfully delivered.

            </p>

            <div id="senderName" class="sender"></div>

            <hr>

            <h5 class="section-title">

                Employees Recognized

            </h5>

            <div id="recipientList"
                class="recipient-list">

            </div>

            <div class="details">

                <div>

                    <small>Recognition</small>

                    <div id="points"></div>

                </div>

                <div>

                    <small>Core Value</small>

                    <div id="value"></div>

                </div>

            </div>

            <blockquote>

                "Recognition inspires excellence."

            </blockquote>

            <button
                class="btn btn-success btn-lg w-100 mt-4"
                onclick="window.location.href='index.html'">

                Submit Another Recognition

            </button>

            <!--button
                class="btn btn-link w-100 mt-2"
                onclick="closePage()">
                Close Window
            </button--      >

        </div>

    </div>

</body>

</html>
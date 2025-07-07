<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resono WMT System</title>
    <!---ICON--->
    <script src="https://kit.fontawesome.com/92cde7fc6f.js" crossorigin="anonymous"></script>
    <link rel="icon" type="image/x-icon" href="assets/RESONO_logo.ico">
    <!---BOOTSTRAP--->
    <link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css" />
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!---FONT--->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <!----AOS LIBRARY---->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!---CSS--->
    <link rel="stylesheet" href="css/index.css">

</head>

<body>

    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card p-4 shadow" data-aos="fade-up">
            <div class="image-center">
                <img src="assets/RESONO_logo_edited.png" alt="" width="100px">
            </div>
            <br>
            <form id="loginForm">
                <!-- FOR EMAIL INPUT -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        placeholder="you@example.com"
                        required />
                </div>

                <!-- FOR PASSWORD INPUT -->
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required />
                        <span class="input-group-text toggle-password" onclick="togglePassword('password')">
                            <i class="fa-solid fa-eye"></i>
                        </span>
                    </div>

                </div>
                <!-- Submit Button -->
                <button type="submit" class="btn-login w-100">Login</button>
            </form>

            <a href="#" class="forgot-password-link mt-3 text-center">Forgot Password?</a>

            <div id="loginError" class="text-danger text-center mt-2" style="display: none;"></div>
        </div>
    </div>

    <!---JS LINKS HERE--->
    <script src="js/js-session/login.js"></script>
    <script src="js/toggle-password.js"></script>


    <!-- AOS JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            offset: 100, // Start animation 100px before the section is in view
            duration: 800, // Animation duration in milliseconds
            easing: 'ease-in-out', // Smooth transition effect
        });
    </script>

</body>

</html>
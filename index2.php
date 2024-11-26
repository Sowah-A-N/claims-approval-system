<!DOCTYPE html>
<html lang="en">
<head>
    <title>Claims System-Login</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--===============================================================================================-->    
    <link rel="icon" type="image/png" href="./login/images/icons/rmu.ico"/>
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="./login/vendor/bootstrap/css/bootstrap.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="./login/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="./login/vendor/animate/animate.css">
    <!--===============================================================================================-->    
    <link rel="stylesheet" type="text/css" href="./login/vendor/css-hamburgers/hamburgers.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="./login/vendor/select2/select2.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="./login/css/util.css">
    <link rel="stylesheet" type="text/css" href="./login/css/main.css">
    <!--===============================================================================================-->
    <style>
        /* Additional CSS for styling */
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }

        .container-login100 {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-wrap {
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0px 10px 50px 0px rgba(0, 0, 0, 0.1);
        }

        .login100-pic {
            margin-bottom: 30px;
            overflow: hidden;
            border-radius: 10px;
        }

        .login100-form-title {
            font-size: 24px;
            color: #333;
            text-align: center;
            margin-bottom: 40px;
        }

        .input-wrap {
            position: relative;
        }

        .input100 {
            width: 100%;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 20px;
            box-sizing: border-box;
            font-size: 16px;
        }

        .input100:focus {
            outline: none;
        }

        .symbol-input {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 15px;
            color: #ccc;
        }

        .input100::-webkit-input-placeholder {
            color: #999;
        }

        .input100::-moz-placeholder {
            color: #999;
        }

        .input100:-ms-input-placeholder {
            color: #999;
        }

        .input100::placeholder {
            color: #999;
        }

        .login100-form-btn {
            width: 100%;
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .login100-form-btn:hover {
            background-color: #0056b3;
        }

        .txt1 {
            color: #333;
        }

        .txt2 {
            color: #007bff;
        }
    </style>
</head>
<body>
    
    <div class="container-login100">
        <div class="login-wrap">
            <div class="login100-pic js-tilt" data-tilt>
                <img src="./login/images/rmu.jpg" alt="IMG">
            </div>

            <form class="login100-form validate-form" method="POST" name="login" action="index.inc.php">
                <span class="login100-form-title">
                    RMU Claims System
                </span>

                <div class="input-wrap">
                    <span class="symbol-input">
                        <i class="fa fa-user" aria-hidden="true"></i>
                    </span>
                    <input class="input100" type="email" name="email" required placeholder="Enter E-Mail...">
                </div>

                <div class="input-wrap">
                    <span class="symbol-input">
                        <i class="fa fa-lock" aria-hidden="true"></i>
                    </span>
                    <input class="input100" type="password" name="pw" required placeholder="Enter Password...">
                </div>                    
                    
                <div class="container-login100-form-btn">
                    <button class="login100-form-btn" name="Login" type="submit">
                        Login
                    </button>
                </div>
                
                <div class="text-center p-t-12">
                    <span class="txt1">
                        Not registered yet?
                    </span>
                    <a class="txt2" href="registration.php">
                        Register here
                    </a>
                </div>
            </form>
        </div>
    </div>    
    
    <!--===============================================================================================-->    
    <script src="./login/vendor/jquery/jquery-3.2.1.min.js"></script>
    <!--===============================================================================================-->
    <script src="./login/vendor/bootstrap/js/popper.js"></script>
    <script src="./login/vendor/bootstrap/js/bootstrap.min.js"></script>
    <!--===============================================================================================-->
    <script src="./login/vendor/select2/select2.min.js"></script>
    <!--===============================================================================================-->
    <script src="./login/vendor/tilt/tilt.jquery.min.js"></script>
    <script>
        $('.js-tilt').tilt({
            scale: 1.1
        })
    </script>
    <!--===============================================================================================-->
    <script src="js/main.js"></script>

</body>
</html>

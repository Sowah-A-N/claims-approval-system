<?php
	session_start();
?> 
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
</head>
<body>
	
	<div class="limiter">
	
		<div class="container-login100">
		<div><?php
                // Display session message if it exists
                if (isset($_SESSION['message'])) {
                    echo '<div class="alert alert-warning">' . $_SESSION['message'] . '</div>';
                    // Unset the message after displaying it
                    unset($_SESSION['message']);
                }
                ?></div>
		
			<div class="wrap-login100">			

				<div class="login100-pic js-tilt" data-tilt>
					<img src="./login/images/rmu.jpg" alt="IMG">
				</div>
				

				<form class="login100-form validate-form" method="POST" name="login" action="index.inc.php">
					<span class="login100-form-title">
						RMU Claims System
					</span>


				

					<div class="wrap-input100 validate-input" data-validate = "Valid email is required">
						<input class="input100" type="email" name="email" required placeholder="Enter E-Mail..." >
						<span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-user" aria-hidden="true"></i>
						</span>
					</div>

					<div class="wrap-input100 validate-input" data-validate="Password is required">
						<input class="input100" type="password" name="pw" required placeholder="Enter Password..." >
						<span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-lock" aria-hidden="true"></i>
						</span>
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
						<a class="txt2" href="register.php">
							Register here
						</a>
					</div>
				</form>
			</div>
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
	<script >
		$('.js-tilt').tilt({
			scale: 1.1
		})
	</script>
<!--===============================================================================================-->
	<script src="js/main.js"></script>

</body>
</html>

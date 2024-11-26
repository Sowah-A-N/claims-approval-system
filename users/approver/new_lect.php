<?php 
    //Session Include goes here
    $pageTitle = "Add New Lecturer";

    include "./assets/partials/head.php";

    include_once '../../includes/conn.inc.php';

	// Check if the form was submitted via POST
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		// Sanitize and validate user input
		$username = trim($_POST['username']);
		$email = trim($_POST['email']);
		$password = trim($_POST['password']);
		$role = trim($_POST['role']);

		// Perform basic validation
		if (empty($username) || empty($email) || empty($password) || empty($role)) {
			echo "All fields are required.";
			exit();
		}

		// Check if the email already exists in the database
		$query = "SELECT * FROM users WHERE email = ?";
		if ($stmt = mysqli_prepare($connection, $query)) {
			// Bind the email parameter
			mysqli_stmt_bind_param($stmt, "s", $email);

			// Execute the query
			mysqli_stmt_execute($stmt);
			mysqli_stmt_store_result($stmt);

			// If the email already exists, return an error message
			if (mysqli_stmt_num_rows($stmt) > 0) {
				echo "The email is already taken. Please choose another email.";
				mysqli_stmt_close($stmt);
				exit();
			}

			// Close the statement
			mysqli_stmt_close($stmt);
		}

		// Hash the password for security
		$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

		// Insert the new user into the database
		$query = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";

		// Prepare the SQL statement
		if ($stmt = mysqli_prepare($connection, $query)) {
			// Bind parameters to the SQL query
			mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hashedPassword, $role);

			// Execute the query
			if (mysqli_stmt_execute($stmt)) {
				echo "User added successfully!";
				mysqli_stmt_close($stmt);
				exit();
			} else {
				echo "Error executing query: " . mysqli_error($connection);
				mysqli_stmt_close($stmt);
				exit();
			}
		} else {
			echo "Error preparing the query: " . mysqli_error($connection);
			exit();
		}
	}
?>

<body>
    <!--Body Wrapper -->
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed">
        
        <?php include './assets/partials/sidebar.php' ?>
        
        <div class="body-wrapper">
            <?php include './assets/partials/header.html'; ?>
			
			<div class="container-fluid">
				<div class="container mt-5">
					<h2>Add New User</h2>

					<!-- Add User Form -->
					<form id="addUserForm" method="POST">
						<div class="form-group">
							<label for="username">Username</label>
							<input type="text" class="form-control" id="username" name="username" required>
						</div>
						<div class="form-group">
							<label for="email">Email address</label>
							<input type="email" class="form-control" id="email" name="email" required>
						</div>
						<div class="form-group">
							<label for="password">Password</label>
							<input type="password" class="form-control" id="password" name="password" required>
						</div>
						<div class="form-group">
							<label for="role">Role</label>
							<select class="form-control" id="role" name="role" required>
								<option value="admin">Admin</option>
								<option value="user">User</option>
							</select>
						</div>
						<button type="submit" class="btn btn-primary">Add User</button>
					</form>

					<!-- Response Message Label -->
					<label id="responseMessage" class="mt-3" style="font-weight: bold;"></label>
				</div>

				<script>
				$(document).ready(function() {
					// Handle form submission via AJAX
					$('#addUserForm').submit(function(event) {
						event.preventDefault(); // Prevent normal form submission

						// Clear previous response message
						$('#responseMessage').text('');

						// Serialize the form data
						var formData = $(this).serialize();

						// Send the data via AJAX to the PHP handler
						$.ajax({
							type: 'POST',
							url: 'add_user_backend.php', // PHP script that handles form submission
							data: formData,
							success: function(response) {
								// If the user is added successfully, show success message
								$('#responseMessage').text(response).css('color', 'green');
								$('#addUserForm')[0].reset(); // Reset the form fields
							},
							error: function(xhr, status, error) {
								// Show error message if something goes wrong
								$('#responseMessage').text('There was an error. Please try again.').css('color', 'red');
							}
						});
					});
				});
				</script>
			</div>
		</div>
	</div>
</body>
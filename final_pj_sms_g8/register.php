<?php
// register.php

session_start();
include 'db.php';

//error msg
$error_message = '';

// user click register
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // get data
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // validate data
    if ($password !== $confirm_password) {
        $error_message = 'Passwords do not match!';
    } else {
        // check if exist
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = 'Username or Email already exists!';
        } else {
            // create new
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("sss", $username, $email, $password_hash);

            if ($stmt_insert->execute()) {
                // register succesfully, direct into login page
                header('Location: login.php?success=registered');
                exit;
            } else {
                $error_message = 'An error occurred. Please try again.';
            }
            $stmt_insert->close();
        }
        $stmt->close();
    }
    $conn->close();
}

// html
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="style_simple.css">
</head>
<body>
    <div class="form-box">
        <h2>Register</h2>

        <?php
        // error msg
        if (!empty($error_message)) {
            echo '<p class="error">' . $error_message . '</p>';
        }
        ?>

        <form action="" method="POST">
            <div class="input-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="input-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="input-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
             
            <div class="input-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn">Register</button>
            
            <div class="form-link">
                <a href="login.php">Already have an account? Login</a>
            </div>
        </form>
    </div>
</body>
</html>
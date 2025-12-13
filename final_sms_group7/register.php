<?php
// register.php (Basic English Version - No Secret Key)
session_start();
include 'db.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validation
    if ($password !== $confirm_password) {
        $error_message = 'Passwords do not match!';
    } else {
        // 2. Check if user exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = 'Username or Email already exists!';
        } else {
            // 3. Create User (Default role is handled by Database or we can verify it here)
            // Note: Database "role" column default is 'user', so we don't strictly need to insert it
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("sss", $username, $email, $password_hash);

            if ($stmt_insert->execute()) {
                // Success
                header('Location: login.php?success=registered');
                exit;
            } else {
                $error_message = 'Error: ' . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
        $stmt->close();
    }
    $conn->close();
}
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

        <?php if (!empty($error_message)) echo '<p class="error">' . $error_message . '</p>'; ?>

        <form action="" method="POST">
            <div class="input-group">
                <label>Username:</label>
                <input type="text" name="username" required>
            </div>
            
            <div class="input-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="input-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>
             
            <div class="input-group">
                <label>Confirm Password:</label>
                <input type="password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn">Register</button>
            
            <div class="form-link">
                <a href="login.php">Already have an account? Login</a>
            </div>
        </form>
    </div>
</body>
</html>
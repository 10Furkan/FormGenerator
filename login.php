<?php
session_start();

    include_once("connection.php");
    include_once("function.php");

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $username = $_POST['username'];
        $password = $_POST['password'];

        if (!empty($username) && !empty($password) && !is_numeric($username)) {
            $stmt = mysqli_stmt_init($con);
            $query = "select * from users where username = ?";
            mysqli_stmt_prepare($stmt, $query);
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result && mysqli_num_rows($result) > 0) {
                $user_data = mysqli_fetch_assoc($result);
                if (password_verify($password, $user_data['password'])) {
                    $_SESSION['user_id'] = $user_data['user_id'];
                    header("Location: index.php");
                    die;
                }
            }
            $error = "Wrong username or password!";
        } else {
            $error = "Please enter valid information!";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>  
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        <?php include 'assets/css/login_view.css'; ?>
    </style>
</head>
<body>
    <div id="box">
        <form method="post">
            <div class="title">Login</div>
            <?php if(isset($error)): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>
            <input class="input-field" type="text" name="username" placeholder="Username">
            <input class="input-field" type="password" name="password" placeholder="Password">
            <input id="button" type="submit" value="Login">
            <p style="margin-top: 20px; color: #777; font-size: 14px;">
                Don't have an account? <a href="signup.php">Signup</a>
            </p>
        </form>
    </div>
</body>
</html>
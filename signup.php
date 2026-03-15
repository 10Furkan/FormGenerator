<?php
session_start();
    include("connection.php");
    include("function.php");

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $username = $_POST['username'];
        $password = $_POST['password'];

        if (!empty($username) && !empty($password) && !is_numeric($username)) {
            $stmt = mysqli_stmt_init($con);
            $isAnyUserNameExists = "select * from users where username = ?";
            mysqli_stmt_prepare($stmt, $isAnyUserNameExists);  
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result && mysqli_num_rows($result) > 0) {
                $error = "Username already exists!";
            } else {
                $user_id = random_num(20);
                $password = password_hash($password, PASSWORD_DEFAULT);
                $query = "insert into users (user_id, username, password) values (?, ?, ?)";
                $stmt = mysqli_prepare($con, $query);
                mysqli_stmt_bind_param($stmt, "sss", $user_id, $username, $password);
                mysqli_stmt_execute($stmt);
                header("Location: login.php");
                die;
            }
        } else {
            $error = "Please enter some valid information!";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>  
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
    <style type="text/css">
        <?php include 'assets/css/signup_view.css'; ?>
    </style>
</head>
<body>
    <div id="box">
        <form method="post">
            <div class="title">Signup</div>
            <?php if(isset($error)): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>
            <input class="input-field" type="text" name="username" placeholder="Choose a Username" required>
            <input class="input-field" type="password" name="password" placeholder="Choose a Password" required>
            <input id="button" type="submit" value="Create Account">
            <p style="margin-top: 20px; color: #777; font-size: 14px;">
                Already a member? <a href="login.php">Login here</a>
            </p>
        </form>
    </div>
</body>
</html>
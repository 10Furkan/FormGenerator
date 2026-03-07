<?php
session_start();

    include("connection.php");
    include("function.php");

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
    <style type="text/css">
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #74ebd5 0%, #9face6 100%);
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #box {
            background-color: #ffffff;
            width: 350px;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            text-align: center;
        }
        .title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 25px;
        }
        .input-field {
            height: 40px;
            border-radius: 8px;
            padding: 5px 15px;
            border: 1px solid #ddd;
            width: 100%;
            margin-bottom: 20px;
            box-sizing: border-box;
            outline: none;
            transition: border 0.3s;
        }
        .input-field:focus {
            border: 1px solid #9face6;
        }
        #button {
            padding: 12px;
            width: 100%;
            color: white;
            background-color: #6c5ce7;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        #button:hover {
            background-color: #5b4cc4;
        }
        a {
            text-decoration: none;
            color: #6c5ce7;
            font-size: 14px;
        }
        .error-msg {
            color: #d63031;
            font-size: 14px;
            margin-bottom: 15px;
        }
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
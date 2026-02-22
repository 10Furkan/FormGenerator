<?php
session_start();
    include("connection.php");
    include("function.php");

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $username = $_POST['username'];
        $password = $_POST['password'];

        if (!empty($username) && !empty($password) && !is_numeric($username)) {
            $user_id = random_num(20);
            $query = "insert into users (user_id, username, password) values ('$user_id', '$username', '$password')";
            mysqli_query($con, $query);
            header("Location: login.php");
            die;
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
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            text-align: center;
        }
        .title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 25px;
        }
        .input-field {
            height: 45px;
            border-radius: 8px;
            padding: 5px 15px;
            border: 1px solid #ddd;
            width: 100%;
            margin-bottom: 20px;
            box-sizing: border-box;
            outline: none;
            transition: all 0.3s;
        }
        .input-field:focus {
            border: 1px solid #764ba2;
        }
        #button {
            padding: 12px;
            width: 100%;
            color: white;
            background-color: #764ba2;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        #button:hover {
            background-color: #5a3a7e;
        }
        a {
            text-decoration: none;
            color: #764ba2;
            font-size: 14px;
            font-weight: bold;
        }
        .error-msg {
            color: #e74c3c;
            background-color: #fceae9;
            padding: 10px;
            border-radius: 5px;
            font-size: 13px;
            margin-bottom: 15px;
        }
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
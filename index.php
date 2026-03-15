<?php
session_start();

    include("connection.php");
    include("function.php");

    $user_data = check_login($con);
?>

<!DOCTYPE html>
<html lang="en">
<head>  
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Generator</title>
    <style>
        <?php include 'assets/css/index_view.css'; ?>
    </style>
</head>

<body>
    <header class="navbar"> <a href="created_forms.php" class="btn-history">View Created Forms</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </header>

    <div class="container">
        <h1>Welcome Back!</h1>
        <p class="welcome-text">Hello, <span class="user-highlight"><?php echo $user_data['username']; ?></span>!</p>
        
        <hr>

        <form method="post" action="generate_form.php">
            <label for="user_input">Form Request</label>
            <input type="text" id="user_input" name="user_data_input" placeholder="Describe the form you want to create..." required>
            
            <input type="submit" value="Submit Request">
        </form>

    </div>


</body>
</html>
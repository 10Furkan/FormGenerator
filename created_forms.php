<?php
session_start();

include("connection.php");
include("function.php");

$user_data = check_login($con);
// Fetching all forms created by the logged-in user
$user_id = $user_data['user_id'];  
$query = "SELECT * FROM surveys WHERE user_id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$forms = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $forms[] = $row;
    }
} else {
    die("Database query error: " . mysqli_error($con));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Created Forms</title>
    <style>
        <?php include 'assets/css/created_forms_view.css'; ?>
    </style>
</head>
<body>
    <div class="navbar">
        <a href="index.php" class="home-btn">Home</a> 
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="container">
        <h1>Your Created Forms</h1>
        <?php if (empty($forms)): ?>
            <p>You haven't created any forms yet. <a href="index.php">Create your first form now!</a></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Created At</th>
                        <th>Link</th>
                        <th>Responses</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form): 
                        $form_data = json_decode($form['form_data'], true);
                    ?>
                        <tr>
                            <td>
                                <a href="generate_form.php?survey_id=<?php echo $form['id']; ?>" target="_blank" class="title-link">
                                    <?php echo htmlspecialchars($form_data['title'] ?? 'Untitled Form'); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($form_data['description'] ?? 'No description'); ?></td>
                            <td><?php echo date("Y-m-d H:i", strtotime($form['created_at'])); ?></td>
                            <td><a href="public_form.php?hash=<?php echo $form['survey_hash']; ?>" target="_blank">View Form</a></td>
                            <td><a href="responses.php?survey_id=<?php echo $form['id']; ?>">View Responses</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
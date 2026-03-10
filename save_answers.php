<?php
include("connection.php");

$status_message = "";
$status_type = ""; // Will be "success" or "error"

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    
    // Check if the hidden survey_id field was sent
    if (isset($_POST['survey_id']) && !empty($_POST['survey_id'])) {
        $survey_id = (int)$_POST['survey_id'];
        
        // Copy the POST array and remove the survey_id so we are left with ONLY the form answers
        $answers = $_POST;
        unset($answers['survey_id']);
        
        // Convert the answers array to JSON string to save in the database
        $answers_json = json_encode($answers, JSON_UNESCAPED_UNICODE);
        
        // Insert the data into the survey_responses table
        $query = "INSERT INTO survey_responses (survey_id, answers_data) VALUES (?, ?)";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "is", $survey_id, $answers_json);
        
        if (mysqli_stmt_execute($stmt)) {
            $status_message = "Thank you! Your answers have been successfully submitted.";
            $status_type = "success";
        } else {
            $status_message = "Database Error: Could not save your answers. Please try again.";
            $status_type = "error";
        }
    } else {
        $status_message = "Invalid submission: Survey ID is missing.";
        $status_type = "error";
    }
} else {
    $status_message = "Direct access to this page is not allowed.";
    $status_type = "error";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submission Status</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; padding: 40px 20px; display: flex; justify-content: center; align-items: center; height: 80vh;}
        .message-box { max-width: 500px; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; width: 100%;}
        h1 { margin-bottom: 15px; font-size: 24px;}
        p { color: #555; line-height: 1.6; margin-bottom: 25px;}
        .success-text { color: #27ae60; }
        .error-text { color: #e74c3c; }
        .icon { font-size: 50px; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="message-box">
    <?php if ($status_type === 'success'): ?>
        <div class="icon success-text">✓</div>
        <h1 class="success-text">Submission Successful</h1>
    <?php else: ?>
        <div class="icon error-text">⚠</div>
        <h1 class="error-text">Submission Failed</h1>
    <?php endif; ?>
    
    <p><?php echo htmlspecialchars($status_message); ?></p>
    
    <a href="javascript:window.history.back();" style="display:inline-block; padding: 10px 20px; background: #34495e; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;">Go Back</a>
</div>

</body>
</html>
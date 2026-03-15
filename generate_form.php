<?php
session_start();
include("connection.php");
include("function.php");

// Check if the user is logged in
$user_data = check_login($con);

$response_php = [
    'title' => 'Awaiting Survey',
    'description' => 'Please enter data to generate a form.',
    'fields' => []
];

$share_link = ""; // Empty variable for the share link

// 1. FORM GENERATION PROCESS WITH AI
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['user_data_input'])) {
    
    $request_text = mysqli_real_escape_string($con, $_POST['user_data_input']);

    if (!empty($request_text)) {
        $ai_response_raw = ai_response($request_text, $api_key);
        $api_data = json_decode($ai_response_raw, true);

        if (isset($api_data['candidates'][0]['content']['parts'][0]['text'])) {
            $form_json_text = $api_data['candidates'][0]['content']['parts'][0]['text'];
            $clean_json = trim($form_json_text);
            $decoded_data = json_decode($clean_json, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_data)) {
                $form_fields = $decoded_data['fields'] ?? [];
                
                if (!empty($form_fields)) {
                    $_SESSION['current_survey'] = [
                        'title' => $decoded_data['title'] ?? 'Untitled Form',
                        'description' => $decoded_data['description'] ?? '',
                        'fields' => $form_fields 
                    ];
                    $response_php = $_SESSION['current_survey'];
                } else {
                    $response_php['title'] = 'No Questions Found';
                    $response_php['description'] = 'AI generated the form but did not add any questions.';
                }
            } else {
                $response_php['title'] = 'JSON Parsing Error';
                $response_php['description'] = 'Corrupted content: ' . json_last_error_msg();
            }
        } else {
            $response_php['title'] = 'Connection Error';
            $response_php['description'] = 'Google Gemini API returned an unexpected structure.';
        }
    }
} 
// 2. SAVING FORM TO DATABASE PROCESS
elseif ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['save_to_db'])) {
    if (isset($_SESSION['current_survey'])) {
        $user_id = $user_data['user_id']; // Logged-in user's ID
        $form_data_json = json_encode($_SESSION['current_survey'], JSON_UNESCAPED_UNICODE);
        $survey_hash = random_num(10); // Using the random number generator from function.php

        // Save to database
        $query = "INSERT INTO surveys (user_id, survey_hash, form_data) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $survey_hash, $form_data_json);
        
        if (mysqli_stmt_execute($stmt)) {
            // Generate link if saving is successful
            $share_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/public_form.php?hash=" . $survey_hash;
            $response_php = $_SESSION['current_survey']; // Keep showing the form
        }
    }
}
// 3. SHOW THE FORM IF IT ALREADY EXISTS IN SESSION
elseif (isset($_SESSION['current_survey']) && is_array($_SESSION['current_survey'])) {
    $response_php = $_SESSION['current_survey'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($response_php['title'] ?? 'Form'); ?></title>
    <style>
        <?php include 'assets/css/generate_form_view.css'; ?>
    </style>
</head>
<body>
    <div class="navbar">
        <a href="index.php" class="home-btn">Home</a> 
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

<div class="top-bar">
    <div>
        <?php if (!empty($response_php['fields']) && empty($share_link)): ?>
            <form method="POST" style="display:inline;">
                <button type="submit" name="save_to_db" class="btn-save-db">Publish & Generate Link</button>
            </form>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($share_link)): ?>
        <div class="share-box">
            Published! Share the Link: <a href="<?php echo $share_link; ?>" target="_blank">Go to Form &rarr;</a>
        </div>
    <?php endif; ?>
</div>

<div class="container">
    <h1><?php echo htmlspecialchars($response_php['title'] ?? ''); ?></h1>
    <p class="desc"><?php echo htmlspecialchars($response_php['description'] ?? ''); ?></p>

    <?php if (!empty($response_php['fields'])): ?>
        <form action="save_answers.php" method="POST">
            <?php foreach ($response_php['fields'] as $field): 
                $input_name = htmlspecialchars($field['name'] ?? 'untitled_field');
            ?>
                <div class="form-group">
                    <label>
                        <?php echo htmlspecialchars($field['label'] ?? 'Question'); ?> 
                        <span style="color:red">*</span>
                    </label>
                    
                    <?php if (isset($field['type']) && $field['type'] === 'radio'): 
                        if (isset($field['options']) && is_array($field['options'])):
                            foreach ($field['options'] as $option): ?>
                                <label class="radio-option">
                                    <input type="radio" name="<?php echo $input_name; ?>" value="<?php echo htmlspecialchars($option); ?>" required>
                                    <?php echo htmlspecialchars($option); ?>
                                </label>
                            <?php endforeach;
                        endif;
                    elseif (isset($field['type']) && $field['type'] === 'textarea'): ?>
                        <textarea name="<?php echo $input_name; ?>" rows="4" placeholder="Write your thoughts here..." required></textarea>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="button" class="btn-submit" onclick="alert('This page is just a preview. Share the link above to collect answers externally.');">Preview Form (Cannot be submitted)</button>
        </form>
    <?php else: ?>
        <div class="empty-state">There are no survey fields to display yet. Please generate a form.</div>
    <?php endif; ?>
</div>

</body>
</html>
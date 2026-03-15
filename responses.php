<?php
session_start();

include("connection.php");
include("function.php");

$user_data = check_login($con);

$survey_id = $_GET['survey_id'] ?? null;
if (!$survey_id) {
    die("Survey ID is required.");
}
// Fetch the survey to ensure it belongs to the logged-in user
$query_survey = "SELECT * FROM surveys WHERE id = ? AND user_id = ?";
$stmt_survey = mysqli_prepare($con, $query_survey);
mysqli_stmt_bind_param($stmt_survey, "ii", $survey_id, $user_data['user_id']);
mysqli_stmt_execute($stmt_survey);
$result_survey = mysqli_stmt_get_result($stmt_survey);
$survey = mysqli_fetch_assoc($result_survey);

if (!$survey) {
    die("Survey not found or you don't have permission to view it.");
}

$form_data = json_decode($survey['form_data'], true);

$query_responses = "SELECT * FROM survey_responses WHERE survey_id = ?";
$stmt_responses = mysqli_prepare($con, $query_responses);
mysqli_stmt_bind_param($stmt_responses, "i", $survey_id);
mysqli_stmt_execute($stmt_responses);
$result_responses = mysqli_stmt_get_result($stmt_responses);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Responses</title>
    <style>
        <?php include 'assets/css/responses_view.css'; ?>
    </style>
</head>
<body>
    <div class="navbar">
        <div class="nav-links">
            <a href="index.php" class="home-btn">Home</a>
            <a href="created_forms.php" class="home-btn" style="color:#4b5563; border-color:#e5e7eb;">Back to Forms</a>
        </div>
        
        <div class="nav-center">
            <a href="form_analysis.php?survey_id=<?php echo $survey_id; ?>" class="analysis-btn">
                📊 View Form Analysis
            </a>
        </div>

        <div class="logout-container">
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <h1>Responses for: <?php echo htmlspecialchars($form_data['title'] ?? 'Untitled Form'); ?></h1>
        <p class="description"><?php echo htmlspecialchars($form_data['description'] ?? 'No description provided for this survey.'); ?></p>
        
        <div class="responses-area">
            <?php
            if (mysqli_num_rows($result_responses) > 0) {
                echo "<p><strong>" . mysqli_num_rows($result_responses) . " responses</strong> received so far.</p>";
                
                echo '<div class="response-list">';
                
                $counter = 1;
                while ($row = mysqli_fetch_assoc($result_responses)) {
                    $answers = json_decode($row['answers_data'], true);
                    
                    $date = isset($row['created_at']) ? date("d M Y, H:i", strtotime($row['created_at'])) : '';

                    echo '<div class="response-card">';
                    echo '  <div class="response-header">';
                    echo '      <span>Response #' . $counter . '</span>';
                    echo '      <span class="response-date">' . $date . '</span>';
                    echo '  </div>';
                    echo '  <div class="response-body">';

                    if (is_array($answers) && !empty($answers)) {
                        // Her bir soru-cevap ikilisi için döngü
                        foreach ($answers as $question => $answer) {
                            
                            // Eğer cevap bir dizi ise (örn: çoklu seçim/checkbox), virgülle ayırarak yazdır
                            if (is_array($answer)) {
                                $answer_text = implode(", ", $answer);
                            } else {
                                $answer_text = $answer;
                            }

                            echo '<div class="qa-pair">';
                            echo '  <div class="question">' . htmlspecialchars($question) . '</div>';
                            echo '  <div class="answer">' . htmlspecialchars($answer_text) . '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p style="color:#ef4444; font-size:14px;">No valid data found for this response.</p>';
                    }

                    echo '  </div>';
                    echo '</div>';
                    
                    $counter++;
                }
                echo '</div>'; // response-list div kapatması

            } else {
                echo "<p style='color: #ef4444;'>No responses yet. Share your form link to collect data!</p>";
            }
            ?>
        </div>
    </div>
</body>
</html>
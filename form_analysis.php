<?php
session_start();

include("connection.php");
include("function.php");

$user_data = check_login($con);

$survey_id = $_GET['survey_id'] ?? null;
if (!$survey_id) {
    die("Survey ID is required.");
}
//Form information
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

// Responses information
$query_responses = "SELECT * FROM survey_responses WHERE survey_id = ?";
$stmt_responses = mysqli_prepare($con, $query_responses);
mysqli_stmt_bind_param($stmt_responses, "i", $survey_id);
mysqli_stmt_execute($stmt_responses);
$result_responses = mysqli_stmt_get_result($stmt_responses);

$all_answers = [];
$total_responses = mysqli_num_rows($result_responses);

// Verileri sorulara göre grupluyoruz ki AI daha iyi anlasın
while ($row = mysqli_fetch_assoc($result_responses)) {
    $answers = json_decode($row['answers_data'], true);
    if (is_array($answers)) {
        foreach ($answers as $question => $answer) {
            if (!isset($all_answers[$question])) {
                $all_answers[$question] = [];
            }
            $all_answers[$question][] = is_array($answer) ? implode(", ", $answer) : $answer;
        }
    }
}

$ai_analysis_result = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['analyze'])) {
        
    $prompt = "You are an expert data analyst. Review the following survey analysis. Provide me with suggestions: 1. A general summary (what do people think?), 2. Key positive aspects, 3. Negative aspects or complaints that need improvement. The data is in JSON format:\n\n" . json_encode($all_answers, JSON_UNESCAPED_UNICODE);

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $api_key;
    $data = [
        "contents" => [
            ["parts" => [["text" => $prompt]]]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Localhost sorunu yaşamamak için
    
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $result_data = json_decode($response, true);
        if (isset($result_data['candidates'][0]['content']['parts'][0]['text'])) {
            $ai_analysis_result = $result_data['candidates'][0]['content']['parts'][0]['text'];
        } else {
            $ai_analysis_result = "Do not have a valid response.";
        }
    } else {
        $ai_analysis_result = "API connection error.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Form Analysis</title>
    <style>
        <?php include 'assets/css/form_analysis.css'; ?>
    </style>
</head>
<body>
    <div class="navbar">
        <div class="nav-links">
            <a href="index.php" class="home-btn">Home</a>
        </div>
        <div class="nav-center">
            <a href="responses.php?survey_id=<?php echo $survey_id; ?>" class="responses-btn">⬅ Back to Responses</a>
        </div>
        <div class="logout-container">
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <h1>✨ AI Form Analysis</h1>
        
        <div class="info-box">
            <strong>Survey:</strong> <?php echo htmlspecialchars($form_data['title'] ?? 'Untitled Form'); ?><br>
            <strong>Total Responses:</strong> <?php echo $total_responses; ?>
        </div>

        <?php if ($total_responses > 0): ?>
            <form method="POST" action="">
                <button type="submit" name="analyze" class="ai-btn">
                    🧠 Generate AI Insights
                </button>
            </form>

            <?php if (!empty($ai_analysis_result)): ?>
                <div class="result-box">
                    <h3>📊 Analysis Result</h3>
                    <div class="ai-content">
                        <?php 
                        // AI'dan gelen markdown formatındaki (**kalın** vb.) metni temel HTML'e çevirelim
                        $formatted_text = htmlspecialchars($ai_analysis_result);
                        $formatted_text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $formatted_text); // Bold
                        $formatted_text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $formatted_text); // Italic
                        $formatted_text = nl2br($formatted_text); // Satır atlamaları
                        echo $formatted_text; 
                        ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <p style="color: #ef4444; text-align: center;">There is not enough data to analyze yet. Please collect some responses first.</p>
        <?php endif; ?>
    </div>
</body>
</html>
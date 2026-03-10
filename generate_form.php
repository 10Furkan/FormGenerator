<?php
session_start();

include("connection.php");
include("function.php");

// check login
$user_data = check_login($con);

// default response for the form page
$response_php = [
    'title' => 'Survey Awaiting',
    'description' => 'Please enter data to create a form.',
    'fields' => []
];

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['user_data_input'])) {
    
    $request_text = mysqli_real_escape_string($con, $_POST['user_data_input']);

    if (!empty($request_text)) {
        
        // 1.Get the answer from Google Gemini API
        $ai_response_raw = ai_response($request_text, $google_ai_key);

        // 2. Check the returned data
        $api_data = json_decode($ai_response_raw, true);

        if (isset($api_data['candidates'][0]['content']['parts'][0]['text'])) {
            
            $form_json_text = $api_data['candidates'][0]['content']['parts'][0]['text'];
            
            // Because Gemini uses the JSON MimeType, it usually comes clean, but just to be safe.:
            $clean_json = trim($form_json_text);
            $decoded_data = json_decode($clean_json, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_data)) {
                
                $form_fields = $decoded_data['fields'] ?? [];
                $form_title = $decoded_data['title'] ?? 'Unnamed Form';
                $form_desc = $decoded_data['description'] ?? '';

                if (!empty($form_fields)) {
                    $response_php = [
                        'title' => $form_title,
                        'description' => $form_desc,
                        'fields' => $form_fields 
                    ];
                    $_SESSION['current_survey'] = $response_php;
                } else {
                    $response_php['title'] = 'Question Not Found';
                    $response_php['description'] = 'The AI generated a form but didn\'t include any questions.';
                }
            } else {
                $response_php['title'] = 'JSON Parsing Error';
                $response_php['description'] = 'Internal text is corrupted: ' . json_last_error_msg();
            }
        } else {
            $response_php['title'] = 'Connection Error';
            $response_php['description'] = 'Google Gemini API returned an unexpected structure.';
        }
    }
} 
elseif (isset($_SESSION['current_survey']) && is_array($_SESSION['current_survey'])) {
    $response_php = $_SESSION['current_survey'];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($response_php['title'] ?? 'Form'); ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; padding: 20px; }
        .container { max-width: 650px; background: #fff; padding: 30px; margin: auto; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1 { color: #2c3e50; margin-bottom: 10px; font-size: 24px;}
        p.desc { color: #7f8c8d; margin-bottom: 25px; line-height: 1.6; }
        .form-group { margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 10px; color: #34495e;}
        .radio-option { display: block; margin-bottom: 8px; cursor: pointer; font-weight: 400;}
        textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-family:inherit;}
        button { background: #27ae60; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; width: 100%; font-weight:bold;}
        button:hover { background: #219150; }
        .empty-state { text-align: center; color: #888; margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 8px;}
    </style>
</head>
<body>

<div class="container">
    <h1><?php echo htmlspecialchars($response_php['title'] ?? ''); ?></h1>
    <p class="desc"><?php echo htmlspecialchars($response_php['description'] ?? ''); ?></p>

    <?php if (!empty($response_php['fields'])): ?>
        <form action="save.php" method="POST">
            <?php foreach ($response_php['fields'] as $field): 
                $input_name = htmlspecialchars($field['name'] ?? 'unnamed_field');
            ?>
                <div class="form-group">
                    <label>
                        <?php echo htmlspecialchars($field['label'] ?? 'Question'); ?> 
                        <span style="color:red">*</span>
                    </label>
                    
                    <?php 
                    /* --- ONLY RADIO AND TEXTAREA CONTROL --- */
                    if (isset($field['type']) && $field['type'] === 'radio'): 
                        
                        if (isset($field['options']) && is_array($field['options'])):
                            foreach ($field['options'] as $option): ?>
                                <label class="radio-option">
                                    <input type="radio" name="<?php echo $input_name; ?>" value="<?php echo htmlspecialchars($option); ?>" required>
                                    <?php echo htmlspecialchars($option); ?>
                                </label>
                            <?php endforeach;
                        endif;

                    elseif (isset($field['type']) && $field['type'] === 'textarea'): ?>
                        <textarea name="<?php echo $input_name; ?>" 
                                  rows="4" 
                                  placeholder="Enter your feedback here..."
                                  required></textarea>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit">Send</button>
        </form>
    <?php else: ?>
        <div class="empty-state">
            There is no survey area to display yet. Please create a form.
        </div>
    <?php endif; ?>
</div>

</body>
</html>
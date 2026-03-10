<?php

function check_login($con)
{
    if (isset($_SESSION['user_id'])) {
        $id = $_SESSION['user_id'];
        $query = "select * from users where user_id = ?";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user_data = mysqli_fetch_assoc($result);
            return $user_data;
        }
    }

    //redirect to login
    header("Location: login.php");
    die;
}

function random_num($length)
{
    $text = "";
    if ($length < 5) {
        $length = 5;
    }

    $len = rand(4, $length);

    for ($i = 0; $i < $len; $i++) {
        $text .= rand(0, 9);
    }

    return $text;
}

function ai_response($request_text, $api_key) {
    // 1. Define system and user prompts 
    $system_prompt = "You are a form generator. You must return ONLY a JSON object. The JSON MUST have exactly three keys: 'title' (string), 'description' (string), and 'fields' (array). Every object in the 'fields' array MUST have a 'type' (strictly ONLY 'radio' or 'textarea'), a 'label' (string), and a unique 'name' (string). If the type is 'radio', it MUST include an 'options' array containing simple strings (e.g., ['Option 1', 'Option 2']). Do NOT create any other input types like text, number, email, or rating.";
    
    $user_prompt = "Create a form based on this request: " . $request_text;

    // 2. Call the Google Gemini API
    $api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;

    $data = [
        "systemInstruction" => [
            "parts" => [
                ["text" => $system_prompt]
            ]
        ],
        "contents" => [
            [
                "parts" => [
                    ["text" => $user_prompt]
                ]
            ]
        ],
        "generationConfig" => [
            "responseMimeType" => "application/json" //It forces Gemini to convert to JSON format.
        ]
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
    // To avoid getting an SSL error on local servers like XAMPP/MAMP
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

function json_to_php_array($json_response) {
    $response_data = json_decode($json_response, true);
    
    if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
        return $response_data['candidates'][0]['content']['parts'][0]['text'];
    }
    
    return null;
}
?>
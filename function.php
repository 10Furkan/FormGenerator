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
    // 1. PROMPT OLUŞTUR
    // Prompt'a inputlar için 'name' parametresi üretmesini de ekledim ki formlar düzgün post edilsin.
    $system_prompt = "You are a helpful assistant that creates HTML forms based on user requests. Always respond with only the JSON that can be used to fill the \$response_php variable in the above code, without any extra text or explanation. The JSON should include a 'title', 'description', and an array of 'fields'. Each field must have a 'label', a unique 'name' attribute, 'type' (which can be 'text', 'radio', 'number', 'email', or 'textarea'), and other relevant properties based on the type (e.g., 'options' array with 'value' and 'text' keys for radio, 'min'/'max' for number). Make sure the JSON is properly formatted and can be directly used in PHP.";
    
    $user_prompt = "Create a form based on this request: " . $request_text;

    // 2. API AYARLARINI YAP
    $api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;

    // 3. EKSİK OLAN KISIM BURASIYDI: systemInstruction eklendi
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
            "responseMimeType" => "application/json"
        ]
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
    // XAMPP/MAMP gibi yerel sunucularda SSL hatası almamak için
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

function json_to_php_array($json_response) {
    // 1. JSON'ı PHP dizisine çevir
    $response_data = json_decode($json_response, true);
    
    // 2. GEMINI'nin veri yolunu kontrol et (OpenAI değil!)
    if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
        
        // 3. Gemini'nin ürettiği metni (bizim form JSON'ını) döndür
        return $response_data['candidates'][0]['content']['parts'][0]['text'];
        
    }
    
    // Eğer o yol yoksa null döndür (Hata durumu)
    return null;
}


<?php
session_start();

include("connection.php");
include("function.php");

// Kullanıcı giriş yapmış mı kontrol et
$user_data = check_login($con);

// Varsayılan boş yapı
$response_php = [
    'title' => 'Anket Bekleniyor',
    'description' => 'Lütfen bir form oluşturmak için veri giriniz.',
    'fields' => []
];

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['user_data_input'])) {
    
    $request_text = mysqli_real_escape_string($con, $_POST['user_data_input']);

    if (!empty($request_text)) {
        // 1. Google Gemini'den ham cevabı al
        $ai_response_raw = ai_response($request_text, $google_ai_key);

        // 2. Önce Gemini'nin devasa dış API JSON'unu PHP dizisine çevir
        $api_data = json_decode($ai_response_raw, true);

        // 3. Formun ASIL metninin (text) yerinde olup olmadığını kontrol et
        if (isset($api_data['candidates'][0]['content']['parts'][0]['text'])) {
            
            // Asıl form JSON metnini çıkarıyoruz
            $form_json_text = $api_data['candidates'][0]['content']['parts'][0]['text'];

            // Başındaki ve sonundaki markdown (` ```json `) taglerini temizle
            $clean_json = preg_replace('/^```json\s*|```\s*$/m', '', $form_json_text);
            $clean_json = trim($clean_json);

            // Şimdi bu asıl form metnini diziye çevir
            $decoded_data = json_decode($clean_json, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_data)) {
                
                // Yapay Zeka isimleri kafasına göre değiştirmiş olabilir, toparlayalım:
                $form_fields = $decoded_data['fields'] ?? $decoded_data['questions'] ?? $decoded_data['sorular'] ?? [];
                $form_title = $decoded_data['form_title'] ?? $decoded_data['title'] ?? 'İsimsiz Form';
                $form_desc = $decoded_data['form_description'] ?? $decoded_data['description'] ?? '';

                if (!empty($form_fields)) {
                    $response_php = [
                        'title' => $form_title,
                        'description' => $form_desc,
                        'fields' => $form_fields 
                    ];
                    $_SESSION['current_survey'] = $response_php;
                } else {
                    $response_php['title'] = 'Soru Bulunamadı';
                    $response_php['description'] = 'Yapay zeka formu oluşturdu ama içine soru eklemedi.';
                }
            } else {
                $response_php['title'] = 'JSON Ayrıştırma Hatası';
                $response_php['description'] = 'İç metin bozuk: ' . json_last_error_msg();
            }
        } else {
            $response_php['title'] = 'Bağlantı Hatası';
            $response_php['description'] = 'Google Gemini API beklenmedik bir yapı döndürdü.';
            //echo "<pre>API Cevabı:\n" . htmlspecialchars($ai_response_raw) . "</pre>"; // Hata ayıklama için ham cevabı göster
            echo "<pre>Retry Delay : " . htmlspecialchars($api_data['error']['details'][2]['retryDelay'] ?? 'Yok') . "</pre>"; // Hata ayıklama için retryDelay'ı göster
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
        .radio-option { display: block; margin-bottom: 8px; cursor: pointer; }
        .radio-inline { display: inline-block; margin-right: 15px; cursor: pointer; }
        input[type="number"], input[type="email"], textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-family:inherit;}
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
                // Yapay Zeka bazen name, bazen id kullanır. İkisini de destekleyelim.
                $input_name = htmlspecialchars($field['name'] ?? $field['id'] ?? 'isimsiz_alan');
            ?>
                <div class="form-group">
                    <label>
                        <?php echo htmlspecialchars($field['label'] ?? 'Soru'); ?> 
                        <?php echo (!empty($field['required']) ? '<span style="color:red">*</span>' : ''); ?>
                    </label>
                    
                    <?php 
                    /* --- RADIO veya RATING TİPİ KONTROLÜ --- */
                    if (isset($field['type']) && ($field['type'] === 'radio' || $field['type'] === 'rating')): 
                        
                        // 1. Senaryo: Eski usül 'options' dizisi geldiyse
                        if (isset($field['options']) && is_array($field['options'])):
                            foreach ($field['options'] as $option): ?>
                                <label class="radio-option">
                                    <input type="radio" name="<?php echo $input_name; ?>" value="<?php echo htmlspecialchars($option['value'] ?? ''); ?>" <?php echo (!empty($field['required']) ? 'required' : ''); ?>>
                                    <?php echo htmlspecialchars($option['text'] ?? ''); ?>
                                </label>
                            <?php endforeach;
                        
                        // 2. Senaryo: Senin çıktındaki gibi 'labels' objesi geldiyse (1: Kötü, 5: İyi gibi)
                        elseif (isset($field['labels']) && is_array($field['labels'])):
                            foreach ($field['labels'] as $val => $text): ?>
                                <label class="radio-option">
                                    <input type="radio" name="<?php echo $input_name; ?>" value="<?php echo htmlspecialchars($val); ?>" <?php echo (!empty($field['required']) ? 'required' : ''); ?>>
                                    <strong><?php echo htmlspecialchars($val); ?>:</strong> <?php echo htmlspecialchars($text); ?>
                                </label>
                            <?php endforeach;
                            
                        // 3. Senaryo: Sadece min-max verdiyse (1, 2, 3, 4, 5 gibi yanyana dizelim)
                        elseif (isset($field['min']) && isset($field['max'])):
                            for ($i = $field['min']; $i <= $field['max']; $i++): ?>
                                <label class="radio-inline">
                                    <input type="radio" name="<?php echo $input_name; ?>" value="<?php echo $i; ?>" <?php echo (!empty($field['required']) ? 'required' : ''); ?>>
                                    <?php echo $i; ?>
                                </label>
                            <?php endfor;
                        endif;

                    /* --- NUMBER TİPİ KONTROLÜ --- */
                    elseif (isset($field['type']) && $field['type'] === 'number'): ?>
                        <input type="number" 
                               name="<?php echo $input_name; ?>" 
                               min="<?php echo htmlspecialchars($field['min'] ?? ''); ?>" 
                               max="<?php echo htmlspecialchars($field['max'] ?? ''); ?>" 
                               placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                               <?php echo (!empty($field['required']) ? 'required' : ''); ?>>

                    /* --- EMAIL TİPİ KONTROLÜ (Yapay zeka bunu da üretmiş) --- */
                    elseif (isset($field['type']) && $field['type'] === 'email'): ?>
                        <input type="email" 
                               name="<?php echo $input_name; ?>" 
                               placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                               <?php echo (!empty($field['required']) ? 'required' : ''); ?>>

                    /* --- TEXTAREA TİPİ KONTROLÜ --- */
                    elseif (isset($field['type']) && $field['type'] === 'textarea'): ?>
                        <textarea name="<?php echo $input_name; ?>" 
                                  rows="4" 
                                  placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                                  <?php echo (!empty($field['required']) ? 'required' : ''); ?>></textarea>
                    
                    /* --- DEFAULT TEXT INPUT KONTROLÜ --- */
                    else: ?>
                        <input type="text" 
                               name="<?php echo $input_name; ?>" 
                               placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                               <?php echo (!empty($field['required']) ? 'required' : ''); ?>>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit"><?php echo htmlspecialchars($response_php['submit_button_text'] ?? 'Gönder'); ?></button>
        </form>
    <?php else: ?>
        <div class="empty-state">
            Henüz görüntülenecek bir anket alanı yok. Lütfen bir form oluşturun.
        </div>
    <?php endif; ?>
</div>

</body>
</html>
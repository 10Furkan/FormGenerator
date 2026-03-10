<?php
include("connection.php");

$form_found = false;
$response_php = [];
$survey_id = 0;

if (isset($_GET['hash'])) {
    $hash = mysqli_real_escape_string($con, $_GET['hash']);
    
    // Find the form from the database
    $query = "SELECT * FROM surveys WHERE survey_hash = ? LIMIT 1";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "s", $hash);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $survey_data = mysqli_fetch_assoc($result);
        $response_php = json_decode($survey_data['form_data'], true);
        $survey_id = $survey_data['id']; // This ID will be needed when saving answers
        $form_found = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $form_found ? htmlspecialchars($response_php['title']) : 'Form Not Found'; ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; padding: 40px 20px; }
        .container { max-width: 650px; background: #fff; padding: 40px; margin: auto; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1 { color: #2c3e50; margin-bottom: 10px; font-size: 26px;}
        p.desc { color: #7f8c8d; margin-bottom: 30px; line-height: 1.6; }
        .form-group { margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 10px; color: #34495e;}
        .radio-option { display: block; margin-bottom: 8px; cursor: pointer; font-weight: 400;}
        textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-family:inherit;}
        .btn-submit { background: #27ae60; color: white; padding: 15px 25px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; width: 100%; font-weight:bold;}
        .btn-submit:hover { background: #219150; }
        .error { text-align: center; color: #e74c3c; padding: 20px; font-size: 18px; }
    </style>
</head>
<body>

<div class="container">
    <?php if ($form_found): ?>
        <h1><?php echo htmlspecialchars($response_php['title']); ?></h1>
        <p class="desc"><?php echo htmlspecialchars($response_php['description']); ?></p>

        <form action="save_answers.php" method="POST">
            <input type="hidden" name="survey_id" value="<?php echo $survey_id; ?>">
            
            <?php foreach ($response_php['fields'] as $field): 
                $input_name = htmlspecialchars($field['name']);
            ?>
                <div class="form-group">
                    <label>
                        <?php echo htmlspecialchars($field['label']); ?> 
                        <span style="color:red">*</span>
                    </label>
                    
                    <?php if ($field['type'] === 'radio'): 
                        foreach ($field['options'] as $option): ?>
                            <label class="radio-option">
                                <input type="radio" name="<?php echo $input_name; ?>" value="<?php echo htmlspecialchars($option); ?>" required>
                                <?php echo htmlspecialchars($option); ?>
                            </label>
                        <?php endforeach;
                    elseif ($field['type'] === 'textarea'): ?>
                        <textarea name="<?php echo $input_name; ?>" rows="4" placeholder="Write your answer here..." required></textarea>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn-submit">Submit Answers</button>
        </form>
    <?php else: ?>
        <div class="error">
            <h2>Error: Form Not Found!</h2>
            <p>The survey you are looking for might have been deleted, or the link is incorrect.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
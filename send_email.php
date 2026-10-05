<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Configure RECAPTCHA_SECRET_KEY privately on the server; never commit it.
    $recaptcha_secret = getenv('RECAPTCHA_SECRET_KEY');
    if (!is_string($recaptcha_secret) || trim($recaptcha_secret) === '') {
        http_response_code(503);
        echo 'The form is temporarily unavailable. Please contact the website owner.';
        exit;
    }
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    if (!is_string($recaptcha_response) || trim($recaptcha_response) === '') {
        http_response_code(400);
        echo 'reCAPTCHA verification failed. Please reload the form and try again.';
        exit;
    }
    $recaptcha_url = "https://www.google.com/recaptcha/api/siteverify";
    
    // Validate reCAPTCHA
    // Use POST so the secret and token are not included in the request URL.
    $verification_context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query([
                'secret' => $recaptcha_secret,
                'response' => $recaptcha_response,
            ]),
            'timeout' => 10,
        ],
    ]);
    $verification_result = @file_get_contents($recaptcha_url, false, $verification_context);
    $recaptcha = is_string($verification_result) ? json_decode($verification_result, true) : null;
    
    if (!is_array($recaptcha)
        || ($recaptcha['success'] ?? false) !== true
        || !isset($recaptcha['score'])
        || !is_numeric($recaptcha['score'])
        || $recaptcha['score'] < 0.5
        || ($recaptcha['action'] ?? '') !== 'submit') {
        echo "<script>alert('reCAPTCHA verification failed. Please try again.'); window.history.back();</script>";
        exit;
    }
    
    $to = "tanya@webhoney.digital"; // Updated email address
    $subject = "New Logo Design Inquiry";
    
    // Sanitize and collect form data
    $business_name = htmlspecialchars($_POST['business_name']);
    $tagline = htmlspecialchars($_POST['tagline']);
    $business_description = htmlspecialchars($_POST['business_description']);
    $brand_values = htmlspecialchars($_POST['brand_values']);
    $target_audience = htmlspecialchars($_POST['target_audience']);
    $color_preferences = htmlspecialchars($_POST['color_preferences']);
    $color_avoid = htmlspecialchars($_POST['color_avoid']);
    $font_preferences = htmlspecialchars($_POST['font_preferences']);
    $competitor_logos = htmlspecialchars($_POST['competitor_logos']);
    $specific_elements = htmlspecialchars($_POST['specific_elements']);
    $deadline = htmlspecialchars($_POST['deadline']);
    $budget = htmlspecialchars($_POST['budget']);
    
    $logo_styles = isset($_POST['logo_style']) ? implode(", ", $_POST['logo_style']) : "Not specified";
    $logo_usage = isset($_POST['logo_usage']) ? implode(", ", $_POST['logo_usage']) : "Not specified";
    
    // Email message
    $message = "A new logo design request has been submitted.\n\n";
    $message .= "Business Name: $business_name\n";
    $message .= "Tagline: $tagline\n";
    $message .= "Business Description: $business_description\n";
    $message .= "Brand Values: $brand_values\n";
    $message .= "Target Audience: $target_audience\n";
    $message .= "Preferred Logo Style: $logo_styles\n";
    $message .= "Color Preferences: $color_preferences\n";
    $message .= "Colors to Avoid: $color_avoid\n";
    $message .= "Font Preferences: $font_preferences\n";
    $message .= "Logo Usage: $logo_usage\n";
    $message .= "Competitor Logos/Inspiration: $competitor_logos\n";
    $message .= "Specific Elements: $specific_elements\n";
    $message .= "Deadline: $deadline\n";
    $message .= "Budget: $budget\n";
    
    $headers = "From: noreply@webhoney.digital\r\n";
    $headers .= "Reply-To: noreply@webhoney.digital\r\n";
    
    // Send email
    if (mail($to, $subject, $message, $headers)) {
        echo "<script>alert('Your request has been submitted successfully!'); window.location.href = 'thank-you.html';</script>";
    } else {
        echo "<script>alert('There was an issue submitting your request. Please try again.'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Invalid request.'); window.history.back();</script>";
}
?>

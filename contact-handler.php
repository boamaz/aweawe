<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Resend API configuration
define('RESEND_API_KEY', 're_YfbXRXJh_KjR95HAhjZ3NWPrkVWTDS18A');
define('RECIPIENT_EMAIL', 'realgamers654@gmail.com');

function validate_input($data) {
    $errors = [];
    
    if (empty(trim($data['name']))) {
        $errors[] = 'Name is required';
    }
    
    if (empty(trim($data['email'])) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty(trim($data['subject']))) {
        $errors[] = 'Subject is required';
    }
    
    if (empty(trim($data['message'])) || strlen(trim($data['message'])) < 10) {
        $errors[] = 'Message must be at least 10 characters long';
    }
    
    return $errors;
}

function send_email_via_resend($data) {
    $url = 'https://api.resend.com/emails';
    
    $email_html = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <h2 style="color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px;">
            New Contact Form Submission
        </h2>
        
        <div style="margin: 20px 0;">
            <p><strong>Name:</strong> ' . htmlspecialchars($data['name']) . '</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($data['email']) . '</p>
            <p><strong>Subject:</strong> ' . htmlspecialchars($data['subject']) . '</p>
        </div>
        
        <div style="margin: 20px 0;">
            <h3 style="color: #333;">Message:</h3>
            <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; white-space: pre-wrap;">
                ' . nl2br(htmlspecialchars($data['message'])) . '
            </div>
        </div>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
        <p style="color: #666; font-size: 12px;">
            This email was sent from your contact form. Reply to respond to ' . htmlspecialchars($data['email']) . '.
        </p>
    </div>';
    
    $post_data = json_encode([
        'from' => 'onboarding@resend.dev',
        'to' => [RECIPIENT_EMAIL],
        'subject' => 'New Contact Form: ' . $data['subject'],
        'html' => $email_html,
        'reply_to' => $data['email']
    ]);
    
    $headers = [
        'Authorization: Bearer ' . RESEND_API_KEY,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($post_data)
    ];
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $post_data,
            'timeout' => 30
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        error_log('Failed to connect to Resend API');
        return false;
    }
    
    $response_data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Invalid JSON response from Resend API: ' . $response);
        return false;
    }
    
    // Check if the response contains an error
    if (isset($response_data['message']) && strpos(strtolower($response_data['message']), 'error') !== false) {
        error_log('Resend API error: ' . $response_data['message']);
        return false;
    }
    
    // Check for specific error indicators
    if (isset($response_data['error']) || (isset($response_data['name']) && $response_data['name'] === 'validation_error')) {
        error_log('Resend API validation error: ' . json_encode($response_data));
        return false;
    }
    
    return $response_data;
}

try {
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $form_data = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'subject' => $_POST['subject'] ?? '',
            'message' => $_POST['message'] ?? ''
        ];
        
        // Validate input
        $errors = validate_input($form_data);
        
        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'error' => implode(', ', $errors),
                'details' => $errors
            ]);
            exit;
        }
        
        // Send email
        $result = send_email_via_resend($form_data);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Thank you for your message! We\'ll get back to you soon.',
                'id' => $result['id'] ?? null
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to send email. Please try again later.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request method'
        ]);
    }
} catch (Exception $e) {
    error_log('Contact form exception: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An unexpected error occurred. Please try again later.'
    ]);
}
?>

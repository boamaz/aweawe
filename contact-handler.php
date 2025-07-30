<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resend_api_key = 're_YfbXRXJh_KjR95HAhjZ3NWPrkVWTDS18A';
    $recipient_email = 'realgamers654@gmail.com';

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $errors = [];
    if (!$name) $errors[] = 'Name is required';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (!$subject) $errors[] = 'Subject is required';
    if (!$message || strlen($message) < 10) $errors[] = 'Message must be at least 10 characters long';

    if ($errors) {
        $error_message = implode(', ', $errors);
        echo '<script>window.location.href = "?status=error&message=' . urlencode($error_message) . '";</script>';
        exit;
    }

    $email_html = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2>New Contact Form Submission</h2>
            <p><strong>Name:</strong> ' . htmlspecialchars($name) . '</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>
            <p><strong>Subject:</strong> ' . htmlspecialchars($subject) . '</p>
            <p><strong>Message:</strong><br>' . nl2br(htmlspecialchars($message)) . '</p>
        </div>';

    $payload = json_encode([
        'from' => 'onboarding@resend.dev',
        'to' => [$recipient_email],
        'subject' => 'New Contact Form: ' . $subject,
        'html' => $email_html,
        'reply_to' => $email
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Authorization: Bearer $resend_api_key\r\nContent-Type: application/json\r\n",
            'content' => $payload,
            'ignore_errors' => true
        ]
    ]);

    $res = @file_get_contents('https://api.resend.com/emails', false, $context);

    if ($res) {
        echo '<script>window.location.href = "?status=success";</script>';
    } else {
        echo '<script>window.location.href = "?status=error&message=' . urlencode("Failed to send email. Try again later.") . '";</script>';
    }

    exit;
}
?>

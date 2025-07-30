<?php
// PHP handler (başta olmalı)
$status = null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resend_api_key = 're_YfbXRXJh_KjR95HAhjZ3NWPrkVWTDS18A';
    $recipient_email = 'realgamers654@gmail.com';

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $msg = trim($_POST['message'] ?? '');

    $errors = [];
    if (!$name) $errors[] = 'Name is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (!$subject) $errors[] = 'Subject is required.';
    if (!$msg || strlen($msg) < 10) $errors[] = 'Message must be at least 10 characters.';

    if (empty($errors)) {
        $email_html = '
        <h2>New Contact Submission</h2>
        <p><strong>Name:</strong> ' . htmlspecialchars($name) . '</p>
        <p><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>
        <p><strong>Subject:</strong> ' . htmlspecialchars($subject) . '</p>
        <p><strong>Message:</strong><br>' . nl2br(htmlspecialchars($msg)) . '</p>';

        $payload = json_encode([
            'from' => 'onboarding@resend.dev',
            'to' => [$recipient_email],
            'subject' => 'New Contact Form: ' . $subject,
            'html' => $email_html,
            'reply_to' => $email
        ]);

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer $resend_api_key\r\nContent-Type: application/json\r\n",
                'content' => $payload,
                'ignore_errors' => true
            ]
        ];
        $context = stream_context_create($opts);
        $response = file_get_contents('https://api.resend.com/emails', false, $context);

        if ($response) {
            $status = 'success';
            $message = 'Thank you! Your message has been sent.';
        } else {
            $status = 'error';
            $message = 'Failed to send email. Try again later.';
        }
    } else {
        $status = 'error';
        $message = implode('<br>', $errors);
    }
}
?>

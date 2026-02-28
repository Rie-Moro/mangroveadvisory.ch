<?php
/**
 * send_mail.php
 * Place this file in the same directory as index.html on your Hostpoint server.
 * Set $recipient to the email address where you want to receive enquiries.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// ── CONFIGURE THIS ──────────────────────────────────────────────
$recipient    = 'dario@mangrove-advisory.com'; // ← change to real address
$sender_name  = 'Mangrove Advisory Website';
$site_name    = 'Mangrove Advisory';
// ────────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

// Sanitise
function clean($val) {
    return htmlspecialchars(strip_tags(trim((string)$val)), ENT_QUOTES, 'UTF-8');
}

$name    = clean($data['name']    ?? '');
$company = clean($data['company'] ?? '');
$email   = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone   = clean($data['phone']   ?? '');
$subject = clean($data['subject'] ?? 'General Enquiry');
$message = clean($data['message'] ?? '');

if (!$name || !$email || !$message) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address']);
    exit;
}

// Build email
$mail_subject = "[{$site_name}] New enquiry: {$subject}";

$mail_body = "You have received a new enquiry via the {$site_name} website.\n\n";
$mail_body .= "──────────────────────────────\n";
$mail_body .= "Name:    {$name}\n";
if ($company) $mail_body .= "Company: {$company}\n";
$mail_body .= "Email:   {$email}\n";
if ($phone) $mail_body .= "Phone:   {$phone}\n";
$mail_body .= "Subject: {$subject}\n";
$mail_body .= "──────────────────────────────\n\n";
$mail_body .= "Message:\n{$message}\n\n";
$mail_body .= "──────────────────────────────\n";
$mail_body .= "Sent from mangrove-advisory.com\n";

$headers  = "From: {$sender_name} <noreply@mangrove-advisory.com>\r\n";
$headers .= "Reply-To: {$name} <{$email}>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$sent = mail($recipient, $mail_subject, $mail_body, $headers);

if ($sent) {
    http_response_code(200);
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Mail could not be sent. Please check server mail configuration.']);
}

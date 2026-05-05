<?php

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Happy Tails Dog Care — Reservation Email Handler
 *
 * SETUP (two options):
 *
 * Option A — PHPMailer via Composer (recommended for maildev):
 *   composer require phpmailer/phpmailer
 *   Then make sure maildev is running: npx maildev
 *   maildev SMTP listens on localhost:1025
 *
 * Option B — PHP built-in mail() (requires a local sendmail/postfix):
 *   Set $USE_PHPMAILER = false below.
 *
 * Place this file in your project root and run with any PHP server:
 *   php -S localhost:8080
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// ── Config ────────────────────────────────────
$USE_PHPMAILER   = true;           // set false to use PHP mail() instead
$MAILDEV_HOST    = 'localhost';
$MAILDEV_PORT    = 1025;           // maildev SMTP port
$STAFF_EMAIL     = 'staff@happytailscare.ca';
$FROM_EMAIL      = 'bookings@happytailscare.ca';
$FROM_NAME       = 'Happy Tails Booking';

// ── Read JSON body ────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    // Fall back to POST form data
    $data = $_POST;
}

// ── Sanitize helper ───────────────────────────
function clean(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

// ── Extract fields ────────────────────────────
$firstName   = clean($data['firstName']   ?? '');
$lastName    = clean($data['lastName']    ?? '');
$email       = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone       = clean($data['phone']       ?? '');
$date        = clean($data['date']        ?? '');
$time        = clean($data['time']        ?? '');
$service     = clean($data['service']     ?? '');
$petName     = clean($data['petName']     ?? '');
$breed       = clean($data['breed']       ?? '');
$age         = clean($data['age']         ?? '');
$size        = clean($data['size']        ?? '');
$healthNotes = clean($data['healthNotes'] ?? '');
$personality = clean($data['personality'] ?? '');

$ownerName = trim("$firstName $lastName");

// ── Validate required fields ──────────────────
if (!$firstName || !$email || !$service || !$petName) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// ── Build email HTML ──────────────────────────
function buildStaffHtml(array $d): string {
    $extra = '';
    if ($d['healthNotes']) {
        $extra .= "<h3 style='color:#C96B2C;margin-top:20px;'>🏥 Health Notes</h3>
                   <p style='background:#fff;padding:12px;border-radius:8px;color:#3D2B1F;'>{$d['healthNotes']}</p>";
    }
    if ($d['personality']) {
        $extra .= "<h3 style='color:#C96B2C;margin-top:20px;'>💬 Personality</h3>
                   <p style='background:#fff;padding:12px;border-radius:8px;color:#3D2B1F;'>{$d['personality']}</p>";
    }

    return "
    <div style='font-family:Georgia,serif;max-width:600px;margin:0 auto;border:1px solid #F0DCC3;border-radius:12px;overflow:hidden;'>
      <div style='background:#C96B2C;padding:28px 32px;text-align:center;'>
        <h1 style='color:#fff;margin:0;font-size:1.5rem;'>🐾 New Reservation — Happy Tails</h1>
      </div>
      <div style='padding:28px 32px;background:#FEF3E8;'>
        <h2 style='color:#3D1F0F;border-bottom:2px solid #F0DCC3;padding-bottom:8px;'>
          Registration: {$d['ownerName']} &amp; {$d['petName']}
        </h2>

        <h3 style='color:#C96B2C;margin-top:20px;'>👤 Owner Information</h3>
        <table style='width:100%;border-collapse:collapse;'>
          <tr><td style='padding:6px 0;color:#6B4F3A;font-weight:bold;width:140px;'>Name</td><td style='color:#3D2B1F;'>{$d['ownerName']}</td></tr>
          <tr><td style='padding:6px 0;color:#6B4F3A;font-weight:bold;'>Email</td><td><a href='mailto:{$d['email']}' style='color:#C96B2C;'>{$d['email']}</a></td></tr>
          <tr><td style='padding:6px 0;color:#6B4F3A;font-weight:bold;'>Phone</td><td style='color:#3D2B1F;'>{$d['phone']}</td></tr>
        </table>

        <h3 style='color:#C96B2C;margin-top:20px;'>📅 Booking Details</h3>
        <table style='width:100%;border-collapse:collapse;'>
          <tr><td style='padding:6px 0;color:#6B4F3A;font-weight:bold;width:140px;'>Service</td><td style='color:#3D2B1F;font-weight:bold;'>{$d['service']}</td></tr>
          <tr><td style='padding:6px 0;color:#6B4F3A;font-weight:bold;'>Date</td><td style='color:#3D2B1F;'>{$d['date']}</td></tr>
          <tr><td style='padding:6px 0;color:#6B4F3A;font-weight:bold;'>Time</td><td style='color:#3D2B1F;'>{$d['time']}</td></tr>
        </table>

        <h3 style='color:#C96B2C;margin-top:20px;'>🐶 Pet Information</h3>
        <table style='width:100%;border-collapse:collapse;'>
          <tr><td style='padding:6px 0;color:#6B4F3A;font-weight:bold;width:140px;'>Dog's Name</td><td style='color:#3D2B1F;'>{$d['petName']}</td></tr>
          <tr><td style='padding:6px 0;color:#6B4F3A;font-weight:bold;'>Breed</td><td style='color:#3D2B1F;'>{$d['breed']}</td></tr>
          <tr><td style='padding:6px 0;color:#6B4F3A;font-weight:bold;'>Age</td><td style='color:#3D2B1F;'>{$d['age']}</td></tr>
          <tr><td style='padding:6px 0;color:#6B4F3A;font-weight:bold;'>Size</td><td style='color:#3D2B1F;'>{$d['size']}</td></tr>
        </table>

        $extra

        <div style='margin-top:28px;padding:16px;background:#C96B2C;border-radius:8px;text-align:center;'>
          <p style='color:#fff;margin:0;'>
            Please confirm this booking by contacting <strong>{$d['ownerName']}</strong>
            at <a href='mailto:{$d['email']}' style='color:#FEF3E8;'>{$d['email']}</a>
          </p>
        </div>
      </div>
      <div style='padding:14px 32px;background:#3D1F0F;text-align:center;'>
        <p style='color:rgba(255,255,255,.5);margin:0;font-size:.8rem;'>
          Happy Tails Dog Care · 123 Bow Trail SW, Calgary · (403) 555-0147
        </p>
      </div>
    </div>";
}

function buildOwnerHtml(array $d): string {
    return "
    <div style='font-family:Georgia,serif;max-width:600px;margin:0 auto;border:1px solid #F0DCC3;border-radius:12px;overflow:hidden;'>
      <div style='background:#C96B2C;padding:28px 32px;text-align:center;'>
        <h1 style='color:#fff;margin:0;'>🎉 Reservation Received!</h1>
      </div>
      <div style='padding:28px 32px;background:#FEF3E8;'>
        <p style='color:#3D2B1F;font-size:1.05rem;'>Hi <strong>{$d['firstName']}</strong>,</p>
        <p style='color:#6B4F3A;'>Thank you for choosing Happy Tails! We've received your booking request for
        <strong>{$d['petName']}</strong> and our team will be in touch within 2 hours to confirm.</p>
        <div style='background:#fff;border-radius:8px;padding:16px;margin:20px 0;'>
          <p style='margin:5px 0;color:#6B4F3A;'><strong>Service:</strong> {$d['service']}</p>
          <p style='margin:5px 0;color:#6B4F3A;'><strong>Date:</strong> {$d['date']}</p>
          <p style='margin:5px 0;color:#6B4F3A;'><strong>Time:</strong> {$d['time']}</p>
        </div>
        <p style='color:#6B4F3A;'>Questions? Call us at <strong>(403) 555-0147</strong></p>
        <p style='color:#6B4F3A;margin-top:20px;'>Can't wait to meet {$d['petName']}! 🐾</p>
        <p style='color:#C96B2C;font-weight:bold;'>— The Happy Tails Team</p>
      </div>
    </div>";
}

$templateData = compact(
    'ownerName','firstName','lastName','email','phone',
    'date','time','service',
    'petName','breed','age','size',
    'healthNotes','personality'
);

$staffHtml = buildStaffHtml($templateData);
$ownerHtml = buildOwnerHtml($templateData);
$staffSubject = "🐾 New Booking: $ownerName & $petName — $service";
$ownerSubject = "Your reservation is confirmed! 🐾 $petName at Happy Tails";

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  SEND EMAILS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
if ($USE_PHPMAILER) {

    // ── PHPMailer via SMTP → maildev ──────────
    function sendWithMailer(
        string $toEmail, string $toName,
        string $subject, string $html,
        string $fromEmail, string $fromName,
        string $host, int $port
    ): void {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->Port       = $port;
        $mail->SMTPAuth   = false;
        $mail->SMTPSecure = '';
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->send();
    }

    try {
        // Email to staff
        sendWithMailer(
            $STAFF_EMAIL, 'Happy Tails Staff',
            $staffSubject, $staffHtml,
            $FROM_EMAIL, $FROM_NAME,
            $MAILDEV_HOST, $MAILDEV_PORT
        );

        // Confirmation to owner
        sendWithMailer(
            $email, $ownerName,
            $ownerSubject, $ownerHtml,
            'hello@happytailscare.ca', 'Happy Tails Dog Care',
            $MAILDEV_HOST, $MAILDEV_PORT
        );

        echo json_encode(['success' => true, 'message' => 'Emails sent via PHPMailer']);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Mailer error: ' . $e->getMessage()]);
    }

} else {

    // ── Option B: PHP built-in mail() ─────────
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $FROM_NAME <$FROM_EMAIL>\r\n";

    $ok1 = mail($STAFF_EMAIL, $staffSubject, $staffHtml, $headers);

    $ownerHeaders  = "MIME-Version: 1.0\r\n";
    $ownerHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
    $ownerHeaders .= "From: Happy Tails Dog Care <hello@happytailscare.ca>\r\n";

    $ok2 = mail($email, $ownerSubject, $ownerHtml, $ownerHeaders);

    if ($ok1 && $ok2) {
        echo json_encode(['success' => true, 'message' => 'Emails sent via mail()']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'mail() failed — check server sendmail config']);
    }
}

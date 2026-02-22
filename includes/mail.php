<?php
/**
 * Mail helper using PHPMailer with SMTP from config.
 * Use send_portal_email() for all portal emails (templated HTML + plain).
 */

if (!defined('STAFF_PORTAL')) {
    die('Direct access not permitted');
}

require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';
require_once __DIR__ . '/email_template.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Send a templated portal email (HTML + plain) matching site design.
 * $title = heading shown in the email (e.g. "Your login code").
 * $content_plain = body text (plain, no HTML).
 * $options: 'code' => 6-digit string, 'cta_url' => URL, 'cta_text' => button label.
 * Returns true on success, false on failure.
 */
function send_portal_email(string $to, string $subject, string $title, string $content_plain, array $options = []): bool
{
    $rendered = render_email_template($title, $content_plain, $options);
    return send_mail($to, $subject, $rendered['plain'], $rendered['html']);
}

/**
 * Send an email via SMTP (config).
 * Prefer send_portal_email() for portal emails.
 * Returns true on success, false on failure.
 */
function send_mail(string $to, string $subject, string $body_plain, ?string $body_html = null): bool
{
    if (!defined('MAIL_ENABLED') || !MAIL_ENABLED) {
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = !empty(SMTP_USERNAME);
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION ?: false;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body    = $body_html ?? $body_plain;
        $mail->AltBody = $body_plain;
        if ($body_html) {
            $mail->isHTML(true);
        }

        $mail->send();
        return true;
    } catch (MailException $e) {
        return false;
    }
}

<?php
/**
 * Staff Portal email template – matches portal design (navy primary, lemon accent).
 * Use via send_portal_email() in includes/mail.php.
 */

if (!defined('STAFF_PORTAL')) {
    die('Direct access not permitted');
}

/**
 * Render email as HTML and plain text for a single layout.
 * Options:
 *   - code: 6-digit OTP/code to show in a highlighted block
 *   - cta_url: URL for button (e.g. password reset link)
 *   - cta_text: button label (e.g. "Reset password")
 * Returns ['html' => string, 'plain' => string].
 */
function render_email_template(string $title, string $content_plain, array $options = []): array {
    $brand_name = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Staff Portal';
    $code = $options['code'] ?? null;
    $cta_url = $options['cta_url'] ?? null;
    $cta_text = $options['cta_text'] ?? null;

    // Plain text version
    $plain = $title . "\n\n" . $content_plain;
    if ($code !== null) {
        $plain .= "\n\nYour code: " . $code;
    }
    if ($cta_url && $cta_text) {
        $plain .= "\n\n" . $cta_text . ": " . $cta_url;
    }
    $plain .= "\n\n— " . $brand_name;

    // HTML version – inline styles for email clients
    $content_html = nl2br(htmlspecialchars($content_plain, ENT_QUOTES, 'UTF-8'));

    $code_block = '';
    if ($code !== null) {
        $code_safe = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        $code_block = '
        <tr><td style="padding: 16px 0 8px 0;">
            <div style="background: #F4F6F9; border-radius: 8px; padding: 16px; text-align: center;">
                <span style="font-size: 28px; font-weight: 700; letter-spacing: 6px; color: #0A1F44;">' . $code_safe . '</span>
            </div>
        </td></tr>';
    }

    $cta_block = '';
    if ($cta_url && $cta_text) {
        $cta_url_safe = htmlspecialchars($cta_url, ENT_QUOTES, 'UTF-8');
        $cta_text_safe = htmlspecialchars($cta_text, ENT_QUOTES, 'UTF-8');
        $cta_block = '
        <tr><td style="padding: 16px 0 8px 0;">
            <a href="' . $cta_url_safe . '" style="display: inline-block; background: #86c93e; color: #0A1F44; font-weight: 600; text-decoration: none; padding: 12px 24px; border-radius: 8px;">' . $cta_text_safe . '</a>
        </td></tr>';
    }

    $title_safe = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $brand_safe = htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8');

    $html = '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background: #F4F6F9; color: #0A1F44;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #F4F6F9;">
<tr><td style="padding: 24px;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 480px; margin: 0 auto; background: #FFFFFF; border-radius: 8px; box-shadow: 0 2px 8px rgba(10, 31, 68, 0.08);">
<tr>
    <td style="background: #0A1F44; color: #FFFFFF; padding: 16px 24px; border-radius: 8px 8px 0 0; font-weight: 600; font-size: 18px;">' . $brand_safe . '</td>
</tr>
<tr>
    <td style="padding: 24px; font-size: 15px; line-height: 1.6;">
        <p style="margin: 0 0 12px 0; font-size: 18px; font-weight: 600; color: #0A1F44;">' . $title_safe . '</p>
        ' . $content_html . '
        ' . $code_block . '
        ' . $cta_block . '
    </td>
</tr>
<tr>
    <td style="padding: 16px 24px; border-top: 1px solid #F4F6F9; font-size: 12px; color: #6B7280;">' . $brand_safe . '</td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>';

    return ['html' => $html, 'plain' => $plain];
}

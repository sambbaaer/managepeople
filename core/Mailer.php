<?php

/**
 * Simple Mailer for ManagePeople V3
 * Uses PHP's built-in mail() function (works on Apache with sendmail/postfix).
 */
class Mailer
{
    private $fromName = 'ManagePeople';

    public function send($toEmail, $subject, $htmlBody)
    {
        $boundary = md5(uniqid(time()));

        // Plain text version
        $plainText = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));

        // Headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: " . $this->encodeHeader($this->fromName) . " <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
        $headers .= "Reply-To: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $headers .= "X-Mailer: ManagePeople/3.0\r\n";

        // Body (plain + HTML)
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($plainText)) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $body .= "--{$boundary}--\r\n";

        $encodedSubject = $this->encodeHeader($subject);

        $result = @mail($toEmail, $encodedSubject, $body, $headers);

        if (!$result) {
            throw new Exception('E-Mail konnte nicht gesendet werden.');
        }

        return true;
    }

    private function encodeHeader($text)
    {
        if (preg_match('/[^\x20-\x7E]/', $text)) {
            return '=?UTF-8?B?' . base64_encode($text) . '?=';
        }
        return $text;
    }
}

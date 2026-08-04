<?php

namespace GardenSensors\Services;

/**
 * Simple SMTP mailer (AUTH LOGIN) with optional PHP mail() fallback.
 * Configure via env: MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD,
 * MAIL_ENCRYPTION (tls|ssl|none), MAIL_FROM / MAIL_FROM_NAME.
 */
class MailService
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $encryption;
    private string $from;
    private string $fromName;

    public function __construct(?array $config = null)
    {
        $config = $config ?? [];
        $this->host = (string)($config['host'] ?? getenv('MAIL_HOST') ?: '');
        $this->port = (int)($config['port'] ?? getenv('MAIL_PORT') ?: 587);
        $this->username = (string)($config['username'] ?? getenv('MAIL_USERNAME') ?: '');
        $this->password = (string)($config['password'] ?? getenv('MAIL_PASSWORD') ?: '');
        $this->encryption = strtolower((string)($config['encryption'] ?? getenv('MAIL_ENCRYPTION') ?: 'tls'));
        $this->from = (string)($config['from'] ?? getenv('MAIL_FROM') ?: ($this->username ?: 'noreply@localhost'));
        $this->fromName = (string)($config['from_name'] ?? getenv('MAIL_FROM_NAME') ?: 'Garden Sensors');
    }

    public function isConfigured(): bool
    {
        return $this->host !== '' && $this->username !== '' && $this->password !== '';
    }

    /**
     * @return array{ok:bool,method:string,error:?string}
     */
    public function send(string $to, string $subject, string $body): array
    {
        if ($this->isConfigured()) {
            try {
                $this->sendSmtp($to, $subject, $body);
                return ['ok' => true, 'method' => 'smtp', 'error' => null];
            } catch (\Throwable $e) {
                return ['ok' => false, 'method' => 'smtp', 'error' => $e->getMessage()];
            }
        }

        $headers = 'From: ' . $this->formatAddress($this->from, $this->fromName) . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $ok = @mail($to, $subject, $body, $headers);
        return [
            'ok' => (bool)$ok,
            'method' => 'mail',
            'error' => $ok ? null : 'PHP mail() returned false (configure MAIL_* SMTP for reliable delivery)',
        ];
    }

    private function sendSmtp(string $to, string $subject, string $body): void
    {
        $remote = ($this->encryption === 'ssl' ? 'ssl://' : '') . $this->host;
        $fp = @stream_socket_client(
            "{$remote}:{$this->port}",
            $errno,
            $errstr,
            20,
            STREAM_CLIENT_CONNECT
        );
        if (!$fp) {
            throw new \RuntimeException("SMTP connect failed: {$errstr} ({$errno})");
        }
        stream_set_timeout($fp, 20);

        $this->expect($fp, 220);
        $this->command($fp, 'EHLO gardensensors.local', 250);

        if ($this->encryption === 'tls') {
            $this->command($fp, 'STARTTLS', 220);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new \RuntimeException('STARTTLS failed');
            }
            $this->command($fp, 'EHLO gardensensors.local', 250);
        }

        $this->command($fp, 'AUTH LOGIN', 334);
        $this->command($fp, base64_encode($this->username), 334);
        $this->command($fp, base64_encode($this->password), 235);

        $this->command($fp, 'MAIL FROM:<' . $this->from . '>', 250);
        $this->command($fp, 'RCPT TO:<' . $to . '>', 250);
        $this->command($fp, 'DATA', 354);

        $payload = 'From: ' . $this->formatAddress($this->from, $this->fromName) . "\r\n";
        $payload .= 'To: <' . $to . ">\r\n";
        $payload .= 'Subject: ' . $this->encodeHeader($subject) . "\r\n";
        $payload .= "MIME-Version: 1.0\r\n";
        $payload .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $payload .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $payload .= str_replace("\n.", "\n..", $body);
        $payload .= "\r\n.";
        $this->command($fp, $payload, 250);
        $this->command($fp, 'QUIT', 221);
        fclose($fp);
    }

    private function command($fp, string $cmd, int $expectCode): void
    {
        fwrite($fp, $cmd . "\r\n");
        $this->expect($fp, $expectCode);
    }

    private function expect($fp, int $code): void
    {
        $response = '';
        while (($line = fgets($fp, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        if ((int)substr($response, 0, 3) !== $code) {
            throw new \RuntimeException("SMTP unexpected reply (wanted {$code}): " . trim($response));
        }
    }

    private function formatAddress(string $email, string $name): string
    {
        if ($name === '') {
            return '<' . $email . '>';
        }
        return '"' . addcslashes($name, '"\\') . '" <' . $email . '>';
    }

    private function encodeHeader(string $value): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }
}

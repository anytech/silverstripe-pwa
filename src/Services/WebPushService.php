<?php

namespace SilverStripePWA\Services;

use SilverStripe\SiteConfig\SiteConfig;

/**
 * Native PHP Web Push implementation
 * No external dependencies - uses PHP's built-in OpenSSL extension
 */
class WebPushService
{
    private string $publicKey;
    private string $privateKey;
    private string $subject;
    private bool $debug = false;

    public function __construct(string $publicKey, string $privateKey, string $subject)
    {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->subject = $subject;

        // Check debug mode from SiteConfig
        $config = SiteConfig::current_site_config();
        $this->debug = (bool)$config->ServiceWorkerDebug;
    }

    /**
     * Log debug message if debug mode is enabled
     * Writes directly to pwa-debug.log in project root
     */
    private function log(string $message, array $context = []): void
    {
        if (!$this->debug) {
            return;
        }

        $logFile = BASE_PATH . '/pwa-debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logLine = "[$timestamp] [WebPush] $message$contextStr\n";

        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Send a push notification
     *
     * @param array $subscription Subscription data (endpoint, publicKey, authToken)
     * @param string $payload JSON payload
     * @param int $ttl Time to live in seconds
     * @return array Response with success status and message
     */
    public function send(array $subscription, string $payload, int $ttl = 86400): array
    {
        $endpoint = $subscription['endpoint'];
        $userPublicKey = $subscription['publicKey'];
        $userAuthToken = $subscription['authToken'];

        $this->log('Sending push notification', [
            'endpoint' => substr($endpoint, 0, 80) . '...',
            'payload_length' => strlen($payload),
            'ttl' => $ttl
        ]);

        // Generate VAPID headers
        $this->log('Generating VAPID headers');
        $vapidHeaders = $this->generateVapidHeaders($endpoint);

        // Encrypt the payload
        $this->log('Encrypting payload');
        $encrypted = $this->encryptPayload($payload, $userPublicKey, $userAuthToken);

        if (!$encrypted) {
            $this->log('Encryption failed');
            return ['success' => false, 'message' => 'Encryption failed', 'expired' => false];
        }

        $this->log('Payload encrypted successfully', ['body_length' => strlen($encrypted['body'])]);

        // Send the request
        return $this->sendRequest($endpoint, $encrypted, $vapidHeaders, $ttl);
    }

    /**
     * Generate VAPID Authorization headers
     */
    private function generateVapidHeaders(string $endpoint): array
    {
        $audience = parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST);

        $header = $this->base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => 'ES256'
        ]));

        $payload = $this->base64UrlEncode(json_encode([
            'aud' => $audience,
            'exp' => time() + 43200, // 12 hours
            'sub' => $this->subject
        ]));

        $signature = $this->signEs256("$header.$payload");

        $jwt = "$header.$payload.$signature";

        return [
            'Authorization' => 'vapid t=' . $jwt . ', k=' . $this->publicKey,
            'Crypto-Key' => 'p256ecdsa=' . $this->publicKey
        ];
    }

    /**
     * Sign data using ES256 (ECDSA with P-256 and SHA-256)
     */
    private function signEs256(string $data): string
    {
        $privateKeyPem = $this->convertPrivateKeyToPem($this->privateKey);

        $key = openssl_pkey_get_private($privateKeyPem);
        if (!$key) {
            throw new \Exception('Invalid private key');
        }

        $signature = '';
        if (!openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new \Exception('Signing failed');
        }

        // Convert DER signature to raw R||S format (64 bytes)
        $signature = $this->derToRaw($signature);

        return $this->base64UrlEncode($signature);
    }

    /**
     * Convert base64url-encoded private key to PEM format
     */
    private function convertPrivateKeyToPem(string $privateKey): string
    {
        $privateKeyData = $this->base64UrlDecode($privateKey);

        // Create the ASN.1 structure for EC private key
        $der = "\x30\x77" . // SEQUENCE
            "\x02\x01\x01" . // INTEGER 1 (version)
            "\x04\x20" . $privateKeyData . // OCTET STRING (32 bytes private key)
            "\xa0\x0a" . // [0] OID
            "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07" . // OID for P-256
            "\xa1\x44" . // [1] BIT STRING
            "\x03\x42\x00" . $this->base64UrlDecode($this->publicKey); // public key

        return "-----BEGIN EC PRIVATE KEY-----\n" .
            chunk_split(base64_encode($der), 64, "\n") .
            "-----END EC PRIVATE KEY-----\n";
    }

    /**
     * Convert DER signature to raw R||S format
     */
    private function derToRaw(string $der): string
    {
        $pos = 0;
        if (ord($der[$pos++]) !== 0x30) {
            throw new \Exception('Invalid DER signature');
        }

        $pos++; // Skip length byte

        // Extract R
        if (ord($der[$pos++]) !== 0x02) {
            throw new \Exception('Invalid DER signature');
        }
        $rLen = ord($der[$pos++]);
        $r = substr($der, $pos, $rLen);
        $pos += $rLen;

        // Extract S
        if (ord($der[$pos++]) !== 0x02) {
            throw new \Exception('Invalid DER signature');
        }
        $sLen = ord($der[$pos++]);
        $s = substr($der, $pos, $sLen);

        // Remove leading zeros and pad to 32 bytes
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");
        $r = str_pad($r, 32, "\x00", STR_PAD_LEFT);
        $s = str_pad($s, 32, "\x00", STR_PAD_LEFT);

        return $r . $s;
    }

    /**
     * Encrypt payload using ECDH and AES-128-GCM (aes128gcm encoding)
     */
    private function encryptPayload(string $payload, string $userPublicKey, string $userAuthToken): ?array
    {
        $userPublicKeyData = $this->base64UrlDecode($userPublicKey);
        $userAuthTokenData = $this->base64UrlDecode($userAuthToken);

        // Generate local key pair
        $localKeyPair = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC
        ]);

        if (!$localKeyPair) {
            return null;
        }

        $localKeyDetails = openssl_pkey_get_details($localKeyPair);
        $localPublicKey = $this->extractPublicKeyBytes($localKeyDetails);

        // Export private key for ECDH
        openssl_pkey_export($localKeyPair, $localPrivateKeyPem);

        // Compute shared secret using ECDH
        $sharedSecret = $this->computeEcdh($localPrivateKeyPem, $userPublicKeyData);

        if (!$sharedSecret) {
            return null;
        }

        // Generate salt
        $salt = random_bytes(16);

        // Derive encryption key using HKDF
        $ikm = $this->hkdf(
            $userAuthTokenData,
            $sharedSecret,
            "WebPush: info\x00" . $userPublicKeyData . $localPublicKey,
            32
        );

        $prk = hash_hmac('sha256', $ikm, $salt, true);
        $cek = $this->hkdf($prk, '', "Content-Encoding: aes128gcm\x00", 16);
        $nonce = $this->hkdf($prk, '', "Content-Encoding: nonce\x00", 12);

        // Pad the payload (minimum 1 byte padding with 0x02 delimiter)
        $paddedPayload = $payload . "\x02";

        // Encrypt using AES-128-GCM
        $tag = '';
        $encrypted = openssl_encrypt(
            $paddedPayload,
            'aes-128-gcm',
            $cek,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            16
        );

        if ($encrypted === false) {
            return null;
        }

        // Build aes128gcm payload
        $body = $salt .
            pack('N', 4096) . // Record size
            chr(strlen($localPublicKey)) .
            $localPublicKey .
            $encrypted . $tag;

        return [
            'body' => $body,
            'encoding' => 'aes128gcm'
        ];
    }

    /**
     * Extract raw public key bytes (uncompressed point)
     */
    private function extractPublicKeyBytes(array $keyDetails): string
    {
        $x = str_pad($keyDetails['ec']['x'], 32, "\x00", STR_PAD_LEFT);
        $y = str_pad($keyDetails['ec']['y'], 32, "\x00", STR_PAD_LEFT);
        return "\x04" . $x . $y;
    }

    /**
     * Compute ECDH shared secret
     */
    private function computeEcdh(string $localPrivateKeyPem, string $remotePublicKeyBytes): ?string
    {
        // Convert raw public key to PEM
        $remotePem = $this->publicKeyBytesToPem($remotePublicKeyBytes);

        $remoteKey = openssl_pkey_get_public($remotePem);
        $localKey = openssl_pkey_get_private($localPrivateKeyPem);

        if (!$remoteKey || !$localKey) {
            return null;
        }

        $sharedSecret = openssl_pkey_derive($remoteKey, $localKey);

        return $sharedSecret ?: null;
    }

    /**
     * Convert raw public key bytes to PEM format
     */
    private function publicKeyBytesToPem(string $publicKeyBytes): string
    {
        // Ensure uncompressed format
        if (strlen($publicKeyBytes) === 65 && $publicKeyBytes[0] === "\x04") {
            $keyData = $publicKeyBytes;
        } else {
            $keyData = "\x04" . $publicKeyBytes;
        }

        // ASN.1 structure for EC public key
        $der = "\x30\x59" . // SEQUENCE
            "\x30\x13" . // SEQUENCE
            "\x06\x07\x2a\x86\x48\xce\x3d\x02\x01" . // OID ecPublicKey
            "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07" . // OID P-256
            "\x03\x42\x00" . $keyData; // BIT STRING

        return "-----BEGIN PUBLIC KEY-----\n" .
            chunk_split(base64_encode($der), 64, "\n") .
            "-----END PUBLIC KEY-----\n";
    }

    /**
     * HKDF extract and expand
     */
    private function hkdf(string $ikm, string $salt, string $info, int $length): string
    {
        if (empty($salt)) {
            $salt = str_repeat("\x00", 32);
        }

        $prk = hash_hmac('sha256', $ikm, $salt, true);
        $output = '';
        $counter = 1;
        $previous = '';

        while (strlen($output) < $length) {
            $previous = hash_hmac('sha256', $previous . $info . chr($counter), $prk, true);
            $output .= $previous;
            $counter++;
        }

        return substr($output, 0, $length);
    }

    /**
     * Send the encrypted push request
     */
    private function sendRequest(string $endpoint, array $encrypted, array $vapidHeaders, int $ttl): array
    {
        $headers = [
            'Content-Type: application/octet-stream',
            'Content-Encoding: ' . $encrypted['encoding'],
            'Content-Length: ' . strlen($encrypted['body']),
            'TTL: ' . $ttl,
            'Urgency: normal'
        ];

        foreach ($vapidHeaders as $name => $value) {
            $headers[] = "$name: $value";
        }

        $this->log('Sending HTTP request to push service', [
            'endpoint' => $endpoint,
            'headers' => $headers
        ]);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $encrypted['body'],
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->log('cURL error', ['error' => $error]);
            return ['success' => false, 'message' => 'cURL error: ' . $error, 'expired' => false];
        }

        $this->log('Push service response', [
            'http_code' => $httpCode,
            'response' => $response
        ]);

        // 201 = created (success)
        // 410 = gone (subscription expired)
        // 404 = not found (subscription expired)
        if ($httpCode === 201) {
            $this->log('Push notification delivered successfully');
            return ['success' => true, 'message' => 'Delivered', 'expired' => false];
        }

        if ($httpCode === 410 || $httpCode === 404) {
            $this->log('Subscription expired', ['http_code' => $httpCode]);
            return ['success' => false, 'message' => 'Subscription expired', 'expired' => true];
        }

        $this->log('Push notification failed', ['http_code' => $httpCode, 'response' => $response]);
        return ['success' => false, 'message' => "HTTP $httpCode: $response", 'expired' => false];
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }

    /**
     * Generate a new VAPID key pair
     *
     * @return array ['publicKey' => string, 'privateKey' => string] Base64URL encoded keys
     */
    public static function generateVapidKeys(): array
    {
        $keyPair = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC
        ]);

        if (!$keyPair) {
            throw new \Exception('Failed to generate key pair: ' . openssl_error_string());
        }

        $details = openssl_pkey_get_details($keyPair);

        // Extract raw private key (32 bytes)
        $privateKey = str_pad($details['ec']['d'], 32, "\x00", STR_PAD_LEFT);

        // Extract raw public key (uncompressed point, 65 bytes: 0x04 || X || Y)
        $x = str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT);
        $y = str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);
        $publicKey = "\x04" . $x . $y;

        return [
            'publicKey' => self::staticBase64UrlEncode($publicKey),
            'privateKey' => self::staticBase64UrlEncode($privateKey)
        ];
    }

    /**
     * Static base64 URL encode for key generation
     */
    private static function staticBase64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

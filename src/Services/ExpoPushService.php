<?php

namespace SilverStripePWA\Services;

use SilverStripe\SiteConfig\SiteConfig;

class ExpoPushService
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    private bool $debug = false;

    public function __construct() {
        $config = SiteConfig::current_site_config();
        $this->debug = (bool)$config->ServiceWorkerDebug;
    }

    public function send(array $subscription, string $payload, int $ttl = 86400): array {
        $token = $subscription['endpoint'];
        $data = json_decode($payload, true) ?: [];

        $message = [
            'to' => $token,
            'title' => $data['title'] ?? '',
            'body' => $data['message'] ?? '',
            'sound' => 'default',
            'ttl' => $ttl,
        ];

        $extra = [];
        if (!empty($data['url'])) {
            $extra['url'] = $data['url'];
        }
        if (!empty($data['data']) && is_array($data['data'])) {
            $extra = array_merge($extra, $data['data']);
        }
        if (!empty($extra)) {
            $message['data'] = $extra;
        }

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($message),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Accept-encoding: gzip, deflate',
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $this->log('Expo response', ['http' => $httpCode, 'body' => $response]);

        if ($error) {
            return ['success' => false, 'message' => 'cURL error: ' . $error, 'expired' => false];
        }

        if ($httpCode !== 200) {
            return ['success' => false, 'message' => "HTTP $httpCode: $response", 'expired' => false];
        }

        $body = json_decode($response, true);
        $ticket = $body['data'] ?? null;

        if (is_array($ticket) && ($ticket['status'] ?? '') === 'error') {
            $code = $ticket['details']['error'] ?? '';
            $expired = in_array($code, ['DeviceNotRegistered', 'InvalidCredentials'], true);
            return ['success' => false, 'message' => $ticket['message'] ?? 'Unknown error', 'expired' => $expired];
        }

        return ['success' => true, 'message' => 'Delivered', 'expired' => false];
    }

    private function log(string $message, array $context = []): void {
        if (!$this->debug) {
            return;
        }
        $logFile = BASE_PATH . '/pwa-debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        file_put_contents($logFile, "[$timestamp] [ExpoPushService] $message$contextStr\n", FILE_APPEND | LOCK_EX);
    }
}

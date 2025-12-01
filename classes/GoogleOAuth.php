<?php
require_once __DIR__ . '/../config/env.php';

class GoogleOAuth {
    private $clientId;
    private $clientSecret;
    private $tokenFile = __DIR__ . '/../config/google_oauth.json';

    public function __construct() {
        $this->clientId = getenv('GOOGLE_OAUTH_CLIENT_ID');
        $this->clientSecret = getenv('GOOGLE_OAUTH_CLIENT_SECRET');
    }

    public function getAuthUrl($redirectUri) {
        $scope = urlencode('https://www.googleapis.com/auth/calendar');
        $params = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar',
            'access_type' => 'offline',
            'prompt' => 'consent'
        ]);
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    public function handleCallback($code, $redirectUri) {
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $post = http_build_query([
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        $resp = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($resp, true);
        if (isset($data['access_token'])) {
            $data['created_at'] = time();
            // compute expires_at
            $expiry = isset($data['expires_in']) ? time() + (int)$data['expires_in'] : time() + 3600;
            $data['expires_at'] = $expiry;
            file_put_contents($this->tokenFile, json_encode($data));
            return $data;
        }
        return false;
    }

    public function getAccessToken() {
        if (!file_exists($this->tokenFile)) return null;
        $data = json_decode(file_get_contents($this->tokenFile), true);
        if (!$data) return null;

        if (isset($data['expires_at']) && $data['expires_at'] > time() + 60) {
            return $data['access_token'];
        }

        // try refresh
        if (isset($data['refresh_token'])) {
            $refreshed = $this->refreshAccessToken($data['refresh_token']);
            if ($refreshed && isset($refreshed['access_token'])) {
                $refreshed['refresh_token'] = $refreshed['refresh_token'] ?? $data['refresh_token'];
                $refreshed['created_at'] = time();
                $refreshed['expires_at'] = time() + (isset($refreshed['expires_in']) ? (int)$refreshed['expires_in'] : 3600);
                file_put_contents($this->tokenFile, json_encode($refreshed));
                return $refreshed['access_token'];
            }
        }

        return null;
    }

    private function refreshAccessToken($refreshToken) {
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $post = http_build_query([
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token'
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        $resp = curl_exec($ch);
        curl_close($ch);

        return json_decode($resp, true);
    }
}

?>

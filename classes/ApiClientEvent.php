<?php
require_once __DIR__ . '/../config/Database.php';

class ApiClientEvent {
    private $apiKey;
    private $cacheDir = __DIR__ . '/../cache/';
    private $cacheTtl = 3600; // default 1 hour cache

    public function __construct() {
        $this->apiKey = getenv('GOOGLE_CALENDAR_API_KEY');

        // Create cache directory if not exists
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function fetchEventsFromGoogle(
        $calendarId = 'id.indonesian#holiday@group.v.calendar.google.com',
        $maxResults = 50,
        $forceRefresh = false
    ) {
        // Override cache TTL using env
        $envTtl = getenv('GOOGLE_CACHE_TTL');
        if ($envTtl && is_numeric($envTtl)) {
            $this->cacheTtl = (int)$envTtl;
        }

        $cacheFile = $this->cacheDir . 'google_events_' . md5($calendarId) . '.json';

        // Check cache first
        if (!$forceRefresh && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $this->cacheTtl) {
            return json_decode(file_get_contents($cacheFile), true);
        }

        // Fetch from Google API
        $url = "https://www.googleapis.com/calendar/v3/calendars/" . urlencode($calendarId) . "/events";
        $params = [
            'key' => $this->apiKey,
            'maxResults' => $maxResults,
            'orderBy' => 'startTime',
            'singleEvents' => 'true',
            'timeMin' => date('c')
        ];

        $fullUrl = $url . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            // Save to cache
            file_put_contents($cacheFile, $response);
            return json_decode($response, true);
        }

        // Fallback dummy data
        return $this->getDummyEvents();
    }

    public function syncGoogleEventsToDatabase($forceRefresh = false) {
        $googleEvents = $this->fetchEventsFromGoogle(null, 50, $forceRefresh);

        if (!$googleEvents || !isset($googleEvents['items'])) {
            return false;
        }

        $db = new Database();
        $syncedCount = 0;

        foreach ($googleEvents['items'] as $googleEvent) {
            // Check if exists
            $checkSql = "SELECT id FROM events WHERE google_event_id = ?";
            $checkStmt = $db->conn->prepare($checkSql);
            $checkStmt->bind_param("s", $googleEvent['id']);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows === 0) {
                // Insert new event
                $title = $googleEvent['summary'] ?? 'No Title';
                $description = $googleEvent['description'] ?? 'Event from Google Calendar';
                $location = $googleEvent['location'] ?? 'Online';

                // Parse event date/time
                $start = $googleEvent['start']['dateTime'] ?? $googleEvent['start']['date'];
                $eventDate = date('Y-m-d', strtotime($start));
                $eventTime = isset($googleEvent['start']['dateTime'])
                    ? date('H:i:s', strtotime($start))
                    : '00:00:00';

                $insertSql = "INSERT INTO events (title, description, event_date, event_time, location, google_event_id, created_by)
                              VALUES (?, ?, ?, ?, ?, ?, 1)";
                $insertStmt = $db->conn->prepare($insertSql);
                $insertStmt->bind_param("ssssss", $title, $description, $eventDate, $eventTime, $location, $googleEvent['id']);

                if ($insertStmt->execute()) {
                    $syncedCount++;
                }
            }
        }

        return $syncedCount;
    }

    private function getDummyEvents() {
        return [
            'items' => [
                [
                    'id' => 'dummy1',
                    'summary' => 'Tech Workshop: Web Development',
                    'description' => 'Learn modern web development technologies',
                    'location' => 'Lab Komputer A',
                    'start' => [
                        'dateTime' => date('Y-m-d', strtotime('+3 days')) . 'T14:00:00+07:00'
                    ]
                ],
                [
                    'id' => 'dummy2',
                    'summary' => 'Career Fair 2024',
                    'description' => 'Meet top companies and explore opportunities',
                    'location' => 'Auditorium Utama',
                    'start' => [
                        'dateTime' => date('Y-m-d', strtotime('+7 days')) . 'T09:00:00+07:00'
                    ]
                ]
            ]
        ];
    }
}
?>

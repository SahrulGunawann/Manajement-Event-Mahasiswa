<?php
// Test Google Calendar API - Public Version
echo "<h2>🔧 Testing Google Calendar API</h2>";

// Load environment variables manually
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die("❌ .env file not found at: " . $envFile);
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$envVars = [];
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    list($name, $value) = explode('=', $line, 2);
    $envVars[trim($name)] = trim($value);
}

$apiKey = $envVars['GOOGLE_CALENDAR_API_KEY'] ?? '';
echo "<strong>API Key:</strong> " . (empty($apiKey) ? "❌ NOT SET" : "✅ " . substr($apiKey, 0, 20) . "...") . "<br>";

if (empty($apiKey) || $apiKey === 'your_google_calendar_api_key_here') {
    echo "
    ❌ <strong>API Key belum diatur!</strong><br>
    Edit file <code>.env</code> dan ganti:<br>
    <code>GOOGLE_CALENDAR_API_KEY=your_actual_api_key_here</code><br><br>
    
    🎯 <strong>Alternatif:</strong> Kita pakai data dummy dulu ya!<br>
    <a href='index.php' class='btn btn-primary mt-3'>Lanjut ke Dashboard dengan Data Dummy</a>
    ";
    exit;
}

// Test dengan public calendar
$calendarId = 'id.indonesian#holiday@group.v.calendar.google.com'; // Public holiday calendar
$url = "https://www.googleapis.com/calendar/v3/calendars/" . urlencode($calendarId) . "/events";
$params = [
    'key' => $apiKey,
    'maxResults' => 5,
    'orderBy' => 'startTime',
    'singleEvents' => 'true',
    'timeMin' => date('c')
];

$fullUrl = $url . '?' . http_build_query($params);
echo "<strong>Testing Calendar:</strong> Indonesian Holidays<br>";
echo "<strong>Request URL:</strong> " . htmlspecialchars($fullUrl) . "<br><br>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fullUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<strong>HTTP Response Code:</strong> " . $httpCode . "<br>";

if ($httpCode === 200) {
    $events = json_decode($response, true);
    echo "✅ <strong style='color: green;'>SUCCESS! API Key BERHASIL!</strong><br>";
    echo "<strong>Total events found:</strong> " . count($events['items'] ?? []) . "<br><br>";
    
    if (!empty($events['items'])) {
        foreach ($events['items'] as $event) {
            $title = $event['summary'] ?? 'No Title';
            $date = $event['start']['dateTime'] ?? $event['start']['date'];
            echo "🎯 <strong>$title</strong> - " . date('d M Y', strtotime($date)) . "<br>";
        }
    }
    
    echo "<br><a href='index.php' class='btn btn-success'>🎉 Lanjut ke Dashboard</a>";
    
} else {
    echo "❌ <strong style='color: red;'>API Key GAGAL! Error: " . $httpCode . "</strong><br>";
    echo "<strong>Error Message:</strong> " . $error . "<br>";
    
    if ($response) {
        $errorData = json_decode($response, true);
        echo "<strong>Error Details:</strong> " . ($errorData['error']['message'] ?? 'Unknown error') . "<br>";
    }
    
    echo "<br>🎯 <strong>Alternatif:</strong> Kita pakai data dummy dulu!<br>";
    echo "<a href='index.php' class='btn btn-primary mt-2'>Lanjut ke Dashboard dengan Data Dummy</a>";
}
?>
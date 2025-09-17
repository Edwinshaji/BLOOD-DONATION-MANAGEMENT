<?php
// ---------------- Determine the target URL ----------------
if (!isset($_GET['url'])) {
    // Fallback for legacy maps that use "?q=searchterm"
    if (isset($_GET['q'])) {
        $url = "https://nominatim.openstreetmap.org/search?" . http_build_query($_GET);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'No URL provided']);
        exit;
    }
} else {
    // Use the fully passed URL (encoded in JS)
    $url = $_GET['url'];
}

// ---------------- Security check ----------------
// Only allow Nominatim URLs
if (strpos($url, 'nominatim.openstreetmap.org') === false) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: Only Nominatim URLs allowed']);
    exit;
}

// ---------------- Initialize cURL ----------------
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, "BloodDonationApp/1.0"); // Required by Nominatim
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// ---------------- Execute request ----------------
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// ---------------- Return response ----------------
http_response_code($httpcode);
header("Content-Type: application/json");
echo $response;

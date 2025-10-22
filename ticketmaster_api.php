<?php

include 'config.php';

function getTicketmasterEvents($keyword = '', $city = '') {
    global $TICKETMASTER_API_KEY;

    $url = "https://app.ticketmaster.com/discovery/v2/events.json?";
    if ($keyword !== '') $url .= "keyword=" . urlencode($keyword) . "&";
    if ($city !== '') $url .= "city=" . urlencode($city) . "&";
    $url .= "apikey=" . $TICKETMASTER_API_KEY;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code != 200) {
        return ["error" => "HTTP $http_code", "raw" => $response];
    }

    $data = json_decode($response, true);
    return $data['_embedded']['events'] ?? [];
}

<?php
// proxy.php

if (isset($_GET['url'])) {
    $url = filter_var($_GET['url'], FILTER_VALIDATE_URL);

    if ($url === false) {
        die('Invalid URL.');
    }

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    // Include headers in the response
    curl_setopt($ch, CURLOPT_HEADER, true);

    // Execute the request
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        die('Error fetching the URL: ' . curl_error($ch));
    }

    // Get the HTTP status code
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close cURL
    curl_close($ch);

    // Set the appropriate content type for the response
    header('Content-Type: text/html');
    http_response_code($statusCode);

    // Output the response
    echo $response;
} else {
    echo 'No URL provided.';
}
?>

<?php
if (isset($_GET['url'])) {
    $url = filter_var($_GET['url'], FILTER_VALIDATE_URL);

    if ($url === false) {
        die('Invalid URL.');
    }

    // Fetch the page content using cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        die('Error fetching the URL: ' . curl_error($ch));
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Rewrite the links in the HTML content
    $baseUrl = parse_url($url, PHP_URL_SCHEME) . "://" . parse_url($url, PHP_URL_HOST);
    $response = preg_replace_callback(
        '/<a\s+[^>]*href=["\']([^"\']+)["\']/i',
        function ($matches) use ($baseUrl) {
            $link = $matches[1];
            // Convert relative URLs to absolute URLs
            if (!preg_match('/^(http|https):\/\//i', $link)) {
                $link = rtrim($baseUrl, '/') . '/' . ltrim($link, '/');
            }
            // Route the link back through the proxy
            return '<a href="proxy.php?url=' . urlencode($link) . '"';
        },
        $response
    );

    // Set the appropriate content type
    header('Content-Type: text/html');
    http_response_code($statusCode);

    // Output the modified HTML
    echo $response;
} else {
    echo 'No URL provided.';
}
?>

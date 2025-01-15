<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Helper function to make URLs absolute
function makeAbsoluteUrl($url, $baseUrl)
{
    // If the URL is already absolute, return it
    if (preg_match('/^(http|https):\/\//i', $url)) {
        return $url;
    }
    // Convert relative URLs to absolute
    return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
}

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

    // Rewrite all resource links in the HTML content
    $baseUrl = parse_url($url, PHP_URL_SCHEME) . "://" . parse_url($url, PHP_URL_HOST);

    // Rewrite links for various elements
    $response = preg_replace_callback_array(
        [
            // Rewrite <a href="...">
            '/<a\s+[^>]*href=["\']([^"\']+)["\']/i' => function ($matches) use ($baseUrl) {
                return '<a href="proxy.php?url=' . urlencode(makeAbsoluteUrl($matches[1], $baseUrl)) . '"';
            },
            // Rewrite <img src="...">
            '/<img\s+[^>]*src=["\']([^"\']+)["\']/i' => function ($matches) use ($baseUrl) {
                return '<img src="' . makeAbsoluteUrl($matches[1], $baseUrl) . '"';
            },
            // Rewrite <link href="...">
            '/<link\s+[^>]*href=["\']([^"\']+)["\']/i' => function ($matches) use ($baseUrl) {
                return '<link href="' . makeAbsoluteUrl($matches[1], $baseUrl) . '"';
            },
            // Rewrite <script src="...">
            '/<script\s+[^>]*src=["\']([^"\']+)["\']/i' => function ($matches) use ($baseUrl) {
                return '<script src="' . makeAbsoluteUrl($matches[1], $baseUrl) . '"';
            },
        ],
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

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
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    // Get the base URL for resource rewriting
    $baseUrl = parse_url($url, PHP_URL_SCHEME) . "://" . parse_url($url, PHP_URL_HOST);

    // Rewrite links, images, scripts, and CSS
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
            // Rewrite CSS background URLs in <style> tags
            '/url\(["\']?([^"\')]+)["\']?\)/i' => function ($matches) use ($baseUrl) {
                return 'url(' . makeAbsoluteUrl($matches[1], $baseUrl) . ')';
            },
        ],
        $response
    );

    // Set the appropriate content type
    if (strpos($contentType, 'text/html') !== false) {
        header('Content-Type: text/html');
    } elseif (strpos($contentType, 'text/css') !== false) {
        header('Content-Type: text/css');
    } elseif (strpos($contentType, 'application/javascript') !== false) {
        header('Content-Type: application/javascript');
    } else {
        header('Content-Type: ' . $contentType);
    }

    http_response_code($statusCode);

    // Output the modified content
    echo $response;
} else {
    echo 'No URL provided.';
}
?>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function makeAbsoluteUrl($url, $baseUrl)
{
    if (preg_match('/^(http|https):\/\//i', $url)) {
        return $url;
    }
    return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
}

if (isset($_GET['url'])) {
    $url = filter_var($_GET['url'], FILTER_VALIDATE_URL);
    if ($url === false) {
        die('Invalid URL.');
    }

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

    $baseUrl = parse_url($url, PHP_URL_SCHEME) . "://" . parse_url($url, PHP_URL_HOST);

    // Inject <base> tag for relative URLs
    $baseTag = '<base href="' . $baseUrl . '">';
    $response = preg_replace('/<head>/i', '<head>' . $baseTag, $response);

    // Rewrite all resource URLs
    $response = preg_replace_callback_array(
        [
            '/<a\s+[^>]*href=["\']([^"\']+)["\']/i' => function ($matches) use ($baseUrl) {
                return '<a href="proxy.php?url=' . urlencode(makeAbsoluteUrl($matches[1], $baseUrl)) . '"';
            },
            '/<img\s+[^>]*src=["\']([^"\']+)["\']/i' => function ($matches) use ($baseUrl) {
                return '<img src="' . makeAbsoluteUrl($matches[1], $baseUrl) . '"';
            },
            '/<link\s+[^>]*href=["\']([^"\']+)["\']/i' => function ($matches) use ($baseUrl) {
                return '<link href="' . makeAbsoluteUrl($matches[1], $baseUrl) . '"';
            },
            '/<script\s+[^>]*src=["\']([^"\']+)["\']/i' => function ($matches) use ($baseUrl) {
                return '<script src="' . makeAbsoluteUrl($matches[1], $baseUrl) . '"';
            },
            '/url\(["\']?([^"\')]+)["\']?\)/i' => function ($matches) use ($baseUrl) {
                return 'url(' . makeAbsoluteUrl($matches[1], $baseUrl) . ')';
            },
        ],
        $response
    );

    // Set content type header
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

    echo $response;
} else {
    echo 'No URL provided.';
}
?>

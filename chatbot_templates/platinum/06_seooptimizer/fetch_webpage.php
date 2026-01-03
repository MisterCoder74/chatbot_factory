<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

function errorResponse($message, $httpStatus = 400) {
    http_response_code((int)$httpStatus);
    echo json_encode(['status' => 'error', 'message' => (string)$message]);
    exit;
}

function isPublicIp($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

function validateUrlForFetch($url) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        errorResponse('Invalid URL');
    }

    $parts = parse_url($url);
    if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
        errorResponse('Invalid URL');
    }

    $scheme = strtolower($parts['scheme']);
    if (!in_array($scheme, ['http', 'https'], true)) {
        errorResponse('Only http/https URLs are allowed');
    }

    if (isset($parts['user']) || isset($parts['pass'])) {
        errorResponse('URL credentials are not allowed');
    }

    if (isset($parts['port']) && !in_array((int)$parts['port'], [80, 443], true)) {
        errorResponse('Only standard ports 80/443 are allowed');
    }

    $host = strtolower($parts['host']);
    if ($host === 'localhost') {
        errorResponse('Localhost is not allowed');
    }

    // Resolve host and block private/reserved ranges (SSRF protection)
    $ips = @gethostbynamel($host);
    if (!$ips || !is_array($ips) || empty($ips)) {
        errorResponse('Unable to resolve host');
    }

    foreach ($ips as $ip) {
        if (!isPublicIp($ip)) {
            errorResponse('Blocked host');
        }
    }

    return [$parts, $ips];
}

function fetchHtml($url, $maxRedirects = 3) {
    $currentUrl = $url;
    $redirects = 0;

    if (!function_exists('curl_init')) {
        $opts = [
            'http' => [
                'method' => 'GET',
                'timeout' => 12,
                'header' => "User-Agent: ChatbotFactorySEOFetcher/1.0\r\n"
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ];

        $context = stream_context_create($opts);
        $body = @file_get_contents($currentUrl, false, $context);
        if ($body === false) {
            errorResponse('Fetch failed', 502);
        }

        $statusLine = $http_response_header[0] ?? '';
        if (!preg_match('/\s(\d{3})\s/', $statusLine, $m)) {
            errorResponse('Upstream returned an invalid response', 502);
        }

        $status = (int)$m[1];
        if ($status < 200 || $status >= 300) {
            errorResponse('Upstream returned HTTP ' . $status, 502);
        }

        $maxBytes = 700000;
        if (strlen($body) > $maxBytes) {
            $body = substr($body, 0, $maxBytes);
        }

        return [$currentUrl, $body];
    }

    while (true) {
        $ch = curl_init($currentUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'ChatbotFactorySEOFetcher/1.0'
        ]);

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            errorResponse('Fetch failed: ' . $err, 502);
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '';
        curl_close($ch);

        $headers = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);

        // Handle redirects manually so we can validate each hop
        if ($status >= 300 && $status < 400) {
            if ($redirects >= $maxRedirects) {
                errorResponse('Too many redirects', 502);
            }

            if (!preg_match('/^Location:\s*(.+)$/im', $headers, $m)) {
                errorResponse('Redirect without Location header', 502);
            }

            $location = trim($m[1]);
            $nextUrl = $location;

            // Relative redirect
            if (strpos($location, 'http://') !== 0 && strpos($location, 'https://') !== 0) {
                $base = parse_url($currentUrl);
                if (!$base || empty($base['scheme']) || empty($base['host'])) {
                    errorResponse('Invalid redirect', 502);
                }
                $prefix = $base['scheme'] . '://' . $base['host'];
                if (!empty($base['port'])) {
                    $prefix .= ':' . $base['port'];
                }

                if (substr($location, 0, 1) === '/') {
                    $nextUrl = $prefix . $location;
                } else {
                    $path = $base['path'] ?? '/';
                    $dirPos = strrpos($path, '/');
                    $dir = $dirPos !== false ? rtrim(substr($path, 0, $dirPos), '/') : '';
                    $nextUrl = $prefix . ($dir ? $dir . '/' : '/') . $location;
                }
            }

            validateUrlForFetch($nextUrl);
            $currentUrl = $nextUrl;
            $redirects++;
            continue;
        }

        if ($status < 200 || $status >= 300) {
            errorResponse('Upstream returned HTTP ' . $status, 502);
        }

        if ($contentType && stripos($contentType, 'text/html') === false) {
            // Some pages send text/plain but still HTML; allow if it contains "<html".
            if (stripos($body, '<html') === false) {
                errorResponse('Unsupported content type: ' . $contentType, 415);
            }
        }

        // Truncate overly large responses
        $maxBytes = 700000; // ~700KB
        if (strlen($body) > $maxBytes) {
            $body = substr($body, 0, $maxBytes);
        }

        return [$currentUrl, $body];
    }
}

$url = trim($_GET['url'] ?? '');
if ($url === '') {
    errorResponse('Missing url parameter');
}

validateUrlForFetch($url);
[$finalUrl, $html] = fetchHtml($url);

libxml_use_internal_errors(true);
$dom = new DOMDocument();
@$dom->loadHTML($html);

$title = '';
$titleNodes = $dom->getElementsByTagName('title');
if ($titleNodes && $titleNodes->length > 0) {
    $title = trim($titleNodes->item(0)->textContent);
}

$description = '';
$xpath = new DOMXPath($dom);
$metaDesc = $xpath->query("//meta[translate(@name,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='description']/@content");
if ($metaDesc && $metaDesc->length > 0) {
    $description = trim($metaDesc->item(0)->textContent);
}

$h1 = $dom->getElementsByTagName('h1')->length;
$h2 = $dom->getElementsByTagName('h2')->length;
$h3 = $dom->getElementsByTagName('h3')->length;
$images = $dom->getElementsByTagName('img')->length;

$bodyText = '';
$body = $dom->getElementsByTagName('body');
if ($body && $body->length > 0) {
    $bodyText = $body->item(0)->textContent;
}
$bodyText = preg_replace('/\s+/', ' ', trim($bodyText));
$contentSnippet = substr($bodyText, 0, 3000);

echo json_encode([
    'status' => 'success',
    'url' => $finalUrl,
    'title' => $title,
    'description' => $description,
    'headings' => ['h1' => $h1, 'h2' => $h2, 'h3' => $h3],
    'images' => $images,
    'content_length' => strlen($bodyText),
    'content_snippet' => $contentSnippet,
    'html' => $html
]);

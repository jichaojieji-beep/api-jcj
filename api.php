<?php
require __DIR__ . '/config.php';

function getParam(string $key, $default = null) {
    if (isset($_GET[$key])) return $_GET[$key];
    if (isset($_POST[$key])) return $_POST[$key];
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $json = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json) && array_key_exists($key, $json)) {
            return $json[$key];
        }
    }
    return $default;
}

function checkRateLimit(string $ip): void {
    $dir = sys_get_temp_dir() . '/api_rate';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $file = $dir . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $ip) . '.txt';
    $now = time();
    $window = 60;
    $max = 20;
    $records = [];
    if (file_exists($file)) {
        $data = file_get_contents($file);
        if ($data !== false && $data !== '') {
            $lines = explode("\n", $data);
            foreach ($lines as $line) {
                if ($line === '') continue;
                $t = is_numeric($line) ? (int)$line : 0;
                if ($now - $t < $window) $records[] = $t;
            }
        }
    }
    if (count($records) >= $max) {
        http_response_code(429);
        jsonReturn(false, 'Rate limit exceeded. Max 20 requests per 60s.', null, 429);
        exit;
    }
    $records[] = $now;
    file_put_contents($file, implode("\n", $records) . "\n", LOCK_EX);
}

function filterRequestHeaders(array $headers): array {
    $blocked = ['x-forwarded', 'cf-', 'x-real-ip', 'authorization', 'cookie', 'host', 'content-length'];
    $safe = [];
    foreach ($headers as $name => $value) {
        $lower = strtolower((string)$name);
        $skip = false;
        foreach ($blocked as $b) {
            if ($lower === $b || strpos($lower, $b . '-') === 0) {
                $skip = true;
                break;
            }
        }
        if (!$skip) $safe[$name] = $value;
    }
    return $safe;
}

function formatHeaders(array $headers): array {
    $result = [];
    foreach ($headers as $name => $value) {
        if (is_array($value)) $value = implode(', ', $value);
        $result[] = $name . ': ' . $value;
    }
    return $result;
}

setCORS();
$clientIP = getRealIP();
checkRateLimit($clientIP);

$authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
$paramKey = getParam('key');
$globalKeyRaw = getGlobalKey(false);

$providedKey = '';
if (strpos($authHeader, 'Bearer ') === 0) {
    $providedKey = trim(substr($authHeader, 7));
} elseif ($paramKey !== null) {
    $providedKey = (string)$paramKey;
}

if ($providedKey === '') {
    jsonReturn(false, 'Missing authentication: provide ?key= parameter or Authorization: Bearer header.', null, 401);
}

if (empty($globalKeyRaw) || !hash_equals((string)$globalKeyRaw, $providedKey)) {
    jsonReturn(false, 'Invalid authentication key.', null, 403);
}

$target = getParam('target');
if (empty($target) || !is_string($target)) {
    jsonReturn(false, 'Missing target parameter.', null, 400);
}
$target = trim($target);

if (isDangerousUrl($target)) {
    jsonReturn(false, 'Target URL is forbidden (localhost, local network or dangerous pattern).', null, 400);
}

$urlParts = parse_url($target);
if (!$urlParts || !isset($urlParts['scheme']) || !in_array(strtolower($urlParts['scheme']), ['http', 'https'])) {
    jsonReturn(false, 'Invalid or unsupported target URL.', null, 400);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$reqHeaders = [];
if (function_exists('getallheaders')) {
    $reqHeaders = getallheaders() ?: [];
} else {
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) === 'HTTP_') {
            $headerName = str_replace('_', '-', substr($name, 5));
            $reqHeaders[$headerName] = $value;
        }
    }
}

$forwardHeaders = filterRequestHeaders($reqHeaders);

$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (!empty($contentType) && !isset($forwardHeaders['Content-Type'])) {
    $forwardHeaders['Content-Type'] = $contentType;
}

$inputBody = file_get_contents('php://input');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $target);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; ApiRelay/1.0)');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

$curlHeaders = formatHeaders($forwardHeaders);
if (!empty($curlHeaders)) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
}

if (!in_array(strtoupper($method), ['GET', 'HEAD']) && !empty($inputBody)) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $inputBody);
}

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$totalTime = (float)curl_getinfo($ch, CURLINFO_TOTAL_TIME);
curl_close($ch);

if ($response === false) {
    jsonReturn(false, 'Forward request failed: ' . $curlError, null, 500);
}

$elapsedMs = round($totalTime * 1000, 2);
writeLog($clientIP, $target, $method, $httpCode, $elapsedMs);

$output = [
    'relay' => true,
    'target' => $target,
    'statusCode' => $httpCode,
    'elapsedMs' => $elapsedMs
];

$jsonBody = json_decode((string)$response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    $output['data'] = $jsonBody;
} else {
    $output['data'] = $response;
    $output['contentType'] = 'text/plain';
}

jsonReturn(true, 'success', $output, $httpCode);
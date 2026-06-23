<?php
/**
 * api.php - API 中转接口
 *
 * 使用方式：
 * https://你的域名/api.php?target=https%3A%2F%2Fhttpbin.org%2Fget&key=你的密钥
 *
 * 注意：
 * 1. 本文件不要添加 PHP 结束标签。
 * 2. 保存为 UTF-8 无 BOM。
 * 3. 需要 config.php 中能提供数据库配置、getDB()、getGlobalKey() 等函数。
 */

ob_start();

require __DIR__ . '/config.php';

if (ob_get_length() > 0) {
    ob_clean();
}

/**
 * 设置 CORS
 */
if (!function_exists('setCORS')) {
    function setCORS(): void {
        $origin = '*';

        if (defined('CORS_ALLOW_ORIGIN')) {
            $origin = CORS_ALLOW_ORIGIN;
        } elseif (getenv('CORS_ALLOW_ORIGIN')) {
            $origin = getenv('CORS_ALLOW_ORIGIN');
        }

        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
    }
}

/**
 * JSON 输出
 */
if (!function_exists('jsonReturn')) {
    function jsonReturn(bool $success, string $message = '', $data = null, int $statusCode = 200): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if ($statusCode < 100 || $statusCode > 599) {
            $statusCode = 200;
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'time' => date('Y-m-d H:i:s'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }
}

/**
 * 读取原始请求体
 */
if (!function_exists('getRawInputBody')) {
    function getRawInputBody(): string {
        static $body = null;

        if ($body === null) {
            $body = file_get_contents('php://input');
            if ($body === false) {
                $body = '';
            }
        }

        return $body;
    }
}

/**
 * 获取请求参数：GET / POST / JSON body
 */
if (!function_exists('getParam')) {
    function getParam(string $key, $default = null) {
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }

        if (isset($_POST[$key])) {
            return $_POST[$key];
        }

        $input = getRawInputBody();

        if ($input !== '') {
            $json = json_decode($input, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($json) && array_key_exists($key, $json)) {
                return $json[$key];
            }
        }

        return $default;
    }
}

/**
 * 获取真实 IP
 */
if (!function_exists('getRealIP')) {
    function getRealIP(): string {
        $keys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = (string)$_SERVER[$key];

                if ($key === 'HTTP_X_FORWARDED_FOR') {
                    $parts = explode(',', $ip);
                    $ip = trim($parts[0]);
                }

                return $ip;
            }
        }

        return 'unknown';
    }
}

/**
 * IP 是否为内网、保留地址或本地地址
 */
if (!function_exists('isPrivateOrReservedIP')) {
    function isPrivateOrReservedIP(string $ip): bool {
        $ip = trim($ip);

        if ($ip === '') {
            return true;
        }

        if (strtolower($ip) === 'localhost') {
            return true;
        }

        if ($ip === '0.0.0.0' || $ip === '127.0.0.1' || $ip === '::1') {
            return true;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $long = ip2long($ip);

            if ($long === false) {
                return true;
            }

            $ranges = [
                ['0.0.0.0', '0.255.255.255'],
                ['10.0.0.0', '10.255.255.255'],
                ['100.64.0.0', '100.127.255.255'],
                ['127.0.0.0', '127.255.255.255'],
                ['169.254.0.0', '169.254.255.255'],
                ['172.16.0.0', '172.31.255.255'],
                ['192.0.0.0', '192.0.0.255'],
                ['192.168.0.0', '192.168.255.255'],
                ['198.18.0.0', '198.19.255.255'],
                ['224.0.0.0', '239.255.255.255'],
                ['240.0.0.0', '255.255.255.255'],
            ];

            foreach ($ranges as $range) {
                $start = ip2long($range[0]);
                $end = ip2long($range[1]);

                if ($start !== false && $end !== false && $long >= $start && $long <= $end) {
                    return true;
                }
            }

            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $lower = strtolower($ip);

            if ($lower === '::1') {
                return true;
            }

            if (strpos($lower, 'fc') === 0 || strpos($lower, 'fd') === 0) {
                return true;
            }

            if (strpos($lower, 'fe80') === 0) {
                return true;
            }

            return false;
        }

        return true;
    }
}

/**
 * 判断目标 URL 是否危险
 */
if (!function_exists('isDangerousUrl')) {
    function isDangerousUrl(string $url): bool {
        $url = trim($url);

        if ($url === '') {
            return true;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return true;
        }

        $parts = parse_url($url);

        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return true;
        }

        $scheme = strtolower((string)$parts['scheme']);

        if (!in_array($scheme, ['http', 'https'], true)) {
            return true;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return true;
        }

        $host = trim((string)$parts['host'], "[] \t\n\r\0\x0B");
        $lowerHost = strtolower($host);

        $blockedHosts = [
            'localhost',
            'localhost.localdomain',
            'metadata.google.internal',
        ];

        if (in_array($lowerHost, $blockedHosts, true)) {
            return true;
        }

        if (str_ends_with($lowerHost, '.local') || str_ends_with($lowerHost, '.internal')) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return isPrivateOrReservedIP($host);
        }

        $ips = [];

        $aRecords = @dns_get_record($host, DNS_A);
        if (is_array($aRecords)) {
            foreach ($aRecords as $record) {
                if (!empty($record['ip'])) {
                    $ips[] = $record['ip'];
                }
            }
        }

        $aaaaRecords = @dns_get_record($host, DNS_AAAA);
        if (is_array($aaaaRecords)) {
            foreach ($aaaaRecords as $record) {
                if (!empty($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        if (empty($ips)) {
            $fallback = @gethostbynamel($host);
            if (is_array($fallback)) {
                $ips = array_merge($ips, $fallback);
            }
        }

        if (empty($ips)) {
            return true;
        }

        foreach ($ips as $ip) {
            if (isPrivateOrReservedIP($ip)) {
                return true;
            }
        }

        return false;
    }
}

/**
 * 简单限流：每个 IP 每 60 秒最多 20 次
 */
if (!function_exists('checkRateLimit')) {
    function checkRateLimit(string $ip): void {
        $dir = sys_get_temp_dir() . '/api_rate';

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $ip);
        if ($safeName === '') {
            $safeName = 'unknown';
        }

        $file = $dir . '/' . $safeName . '.txt';
        $now = time();
        $window = 60;
        $max = 20;
        $records = [];

        if (file_exists($file)) {
            $data = file_get_contents($file);

            if ($data !== false && $data !== '') {
                $lines = explode("\n", $data);

                foreach ($lines as $line) {
                    $line = trim($line);

                    if ($line === '') {
                        continue;
                    }

                    $t = is_numeric($line) ? (int)$line : 0;

                    if ($now - $t < $window) {
                        $records[] = $t;
                    }
                }
            }
        }

        if (count($records) >= $max) {
            jsonReturn(false, 'Rate limit exceeded. Max 20 requests per 60 seconds.', null, 429);
        }

        $records[] = $now;

        @file_put_contents($file, implode("\n", $records) . "\n", LOCK_EX);
    }
}

/**
 * 过滤转发请求头
 */
if (!function_exists('filterRequestHeaders')) {
    function filterRequestHeaders(array $headers): array {
        $blockedExact = [
            'authorization',
            'cookie',
            'host',
            'content-length',
            'connection',
            'upgrade',
            'proxy-authorization',
            'proxy-authenticate',
        ];

        $blockedPrefix = [
            'x-forwarded',
            'cf-',
            'x-real-ip',
            'railway-',
        ];

        $safe = [];

        foreach ($headers as $name => $value) {
            $lower = strtolower((string)$name);
            $skip = false;

            if (in_array($lower, $blockedExact, true)) {
                $skip = true;
            }

            foreach ($blockedPrefix as $prefix) {
                if ($lower === rtrim($prefix, '-') || strpos($lower, $prefix) === 0) {
                    $skip = true;
                    break;
                }
            }

            if (!$skip) {
                $safe[$name] = $value;
            }
        }

        return $safe;
    }
}

/**
 * 格式化请求头
 */
if (!function_exists('formatHeaders')) {
    function formatHeaders(array $headers): array {
        $result = [];

        foreach ($headers as $name => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $name = trim((string)$name);
            $value = trim((string)$value);

            if ($name === '' || preg_match('/[\r\n:]/', $name)) {
                continue;
            }

            if (preg_match('/[\r\n]/', $value)) {
                continue;
            }

            $result[] = $name . ': ' . $value;
        }

        return $result;
    }
}

/**
 * 写入请求日志
 */
if (!function_exists('writeLog')) {
    function writeLog(string $ip, string $target, string $method, int $statusCode, float $elapsedMs): void {
        try {
            if (!function_exists('getDB')) {
                return;
            }

            $db = getDB();

            $stmt = $db->prepare(
                "INSERT INTO api_log (ip, target, method, status_code, elapsed_ms, created_at)
                 VALUES (:ip, :target, :method, :status_code, :elapsed_ms, NOW())"
            );

            $stmt->execute([
                ':ip' => $ip,
                ':target' => $target,
                ':method' => $method,
                ':status_code' => $statusCode,
                ':elapsed_ms' => $elapsedMs,
            ]);
        } catch (Throwable $e) {
            // 日志写入失败不影响主请求
        }
    }
}

/**
 * 获取所有请求头
 */
if (!function_exists('getRequestHeadersSafe')) {
    function getRequestHeadersSafe(): array {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();

            if (is_array($headers)) {
                return $headers;
            }
        }

        $headers = [];

        foreach ($_SERVER as $name => $value) {
            if (strpos($name, 'HTTP_') === 0) {
                $headerName = str_replace('_', '-', substr($name, 5));
                $headers[$headerName] = $value;
            }
        }

        return $headers;
    }
}

/**
 * 获取全局密钥兜底
 */
if (!function_exists('getApiGlobalKey')) {
    function getApiGlobalKey(): string {
        if (function_exists('getGlobalKey')) {
            $key = getGlobalKey(false);

            if ($key !== '') {
                return (string)$key;
            }
        }

        if (defined('GLOBAL_KEY') && GLOBAL_KEY !== '') {
            return (string)GLOBAL_KEY;
        }

        if (defined('ADMIN_KEY') && ADMIN_KEY !== '') {
            return (string)ADMIN_KEY;
        }

        return '';
    }
}

/**
 * 主流程开始
 */

setCORS();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    jsonReturn(true, 'OK', null, 200);
}

if (!function_exists('curl_init')) {
    jsonReturn(false, 'Server error: PHP cURL extension is not installed.', null, 500);
}

$clientIP = getRealIP();
checkRateLimit($clientIP);

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$paramKey = getParam('key');

$globalKeyRaw = getApiGlobalKey();

$providedKey = '';

if (strpos($authHeader, 'Bearer ') === 0) {
    $providedKey = trim(substr($authHeader, 7));
} elseif ($paramKey !== null) {
    $providedKey = trim((string)$paramKey);
}

if ($providedKey === '') {
    jsonReturn(false, 'Missing authentication. Provide ?key=YOUR_KEY or Authorization: Bearer YOUR_KEY.', null, 401);
}

if ($globalKeyRaw === '' || !hash_equals((string)$globalKeyRaw, (string)$providedKey)) {
    jsonReturn(false, 'Invalid authentication key.', null, 403);
}

$target = getParam('target');

if (empty($target) || !is_string($target)) {
    jsonReturn(false, 'Missing target parameter.', null, 400);
}

$target = trim($target);

$urlParts = parse_url($target);

if (!$urlParts || empty($urlParts['scheme']) || empty($urlParts['host'])) {
    jsonReturn(false, 'Invalid target URL.', null, 400);
}

$scheme = strtolower((string)$urlParts['scheme']);

if (!in_array($scheme, ['http', 'https'], true)) {
    jsonReturn(false, 'Only http and https target URLs are supported.', null, 400);
}

if (isDangerousUrl($target)) {
    jsonReturn(false, 'Target URL is forbidden.', null, 400);
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

$allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD'];

if (!in_array($method, $allowedMethods, true)) {
    jsonReturn(false, 'Unsupported request method.', null, 405);
}

$reqHeaders = getRequestHeadersSafe();
$forwardHeaders = filterRequestHeaders($reqHeaders);

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if ($contentType !== '' && !isset($forwardHeaders['Content-Type'])) {
    $forwardHeaders['Content-Type'] = $contentType;
}

$inputBody = getRawInputBody();

$startTime = microtime(true);

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $target);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; ApiRelay/1.0)');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_HEADER, true);

$curlHeaders = formatHeaders($forwardHeaders);

if (!empty($curlHeaders)) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
}

if (!in_array($method, ['GET', 'HEAD'], true) && $inputBody !== '') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $inputBody);
}

$responseRaw = curl_exec($ch);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$totalTime = (float)curl_getinfo($ch, CURLINFO_TOTAL_TIME);

curl_close($ch);

if ($responseRaw === false) {
    $elapsedMs = round((microtime(true) - $startTime) * 1000, 2);
    writeLog($clientIP, $target, $method, 0, $elapsedMs);

    jsonReturn(false, 'Forward request failed: ' . $curlError, [
        'errno' => $curlErrno,
    ], 500);
}

$responseHeadersRaw = substr((string)$responseRaw, 0, $headerSize);
$responseBody = substr((string)$responseRaw, $headerSize);

if ($httpCode <= 0) {
    $httpCode = 502;
}

$elapsedMs = round($totalTime > 0 ? $totalTime * 1000 : (microtime(true) - $startTime) * 1000, 2);

writeLog($clientIP, $target, $method, $httpCode, $elapsedMs);

$responseContentType = '';

if (preg_match('/content-type:\s*([^\r\n]+)/i', $responseHeadersRaw, $matches)) {
    $responseContentType = trim($matches[1]);
}

$output = [
    'relay' => true,
    'target' => $target,
    'statusCode' => $httpCode,
    'elapsedMs' => $elapsedMs,
];

$jsonBody = json_decode((string)$responseBody, true);

if (json_last_error() === JSON_ERROR_NONE) {
    $output['data'] = $jsonBody;
} else {
    $output['data'] = $responseBody;
    $output['contentType'] = $responseContentType !== '' ? $responseContentType : 'text/plain';
}

jsonReturn(true, 'success', $output, $httpCode);

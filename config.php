<?php
/**
 * config.php - 全局配置与公共函数
 *
 * 注意：
 * 1. 本文件不要添加 PHP 结束标签。
 * 2. 本文件必须保存为 UTF-8 无 BOM。
 * 3. <?php 前面不能有空格、空行或任何不可见字符。
 */

// ============================================================
// 环境变量读取函数
// ============================================================

if (!function_exists('env_value')) {
    function env_value(string $key, $default = '') {
        $value = getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return $value;
    }
}

// ============================================================
// 数据库连接配置
// Railway MySQL 常见环境变量：
// MYSQLHOST / MYSQLUSER / MYSQLPASSWORD / MYSQLDATABASE / MYSQLPORT
// ============================================================

if (!defined('DB_HOST')) {
    define('DB_HOST', env_value('MYSQLHOST', 'localhost'));
}

if (!defined('DB_USER')) {
    define('DB_USER', env_value('MYSQLUSER', 'root'));
}

if (!defined('DB_PASS')) {
    define('DB_PASS', env_value('MYSQLPASSWORD', ''));
}

if (!defined('DB_NAME')) {
    define('DB_NAME', env_value('MYSQLDATABASE', 'nav_community'));
}

if (!defined('DB_PORT')) {
    define('DB_PORT', (int)env_value('MYSQLPORT', 3306));
}

if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

if (!defined('DB_COLLATE')) {
    define('DB_COLLATE', 'utf8mb4_unicode_ci');
}

if (!defined('DB_DRIVER')) {
    define('DB_DRIVER', 'mysql');
}

if (!defined('DB_PREFIX')) {
    define('DB_PREFIX', '');
}

if (!defined('DB_DEBUG')) {
    define('DB_DEBUG', false);
}

if (!defined('DB_DSN')) {
    define(
        'DB_DSN',
        DB_DRIVER . ':host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET
    );
}

// ============================================================
// 网站基础参数
// ============================================================

if (!defined('SITE_NAME')) {
    define('SITE_NAME', '导航社区一体站');
}

if (!defined('SITE_URL')) {
    define('SITE_URL', env_value('SITE_URL', 'http://localhost'));
}

if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', __DIR__ . '/');
}

if (!defined('ADMIN_EMAIL')) {
    define('ADMIN_EMAIL', env_value('ADMIN_EMAIL', 'admin@example.com'));
}

if (!defined('TIMEZONE')) {
    define('TIMEZONE', 'Asia/Shanghai');
}

if (!defined('DATE_FORMAT')) {
    define('DATE_FORMAT', 'Y-m-d H:i:s');
}

if (!defined('PAGE_SIZE')) {
    define('PAGE_SIZE', 20);
}

if (!defined('CACHE_TTL')) {
    define('CACHE_TTL', 3600);
}

date_default_timezone_set(TIMEZONE);

// ============================================================
// 图片上传配置
// ============================================================

if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', 'uploads/images/');
}

if (!defined('UPLOAD_MAX_SIZE')) {
    define('UPLOAD_MAX_SIZE', 2097152);
}

if (!defined('UPLOAD_ALLOWED_EXT')) {
    define('UPLOAD_ALLOWED_EXT', 'jpg,jpeg,png,gif,webp');
}

if (!defined('UPLOAD_MAX_WIDTH')) {
    define('UPLOAD_MAX_WIDTH', 1920);
}

if (!defined('UPLOAD_MAX_HEIGHT')) {
    define('UPLOAD_MAX_HEIGHT', 1080);
}

if (!defined('UPLOAD_THUMB_WIDTH')) {
    define('UPLOAD_THUMB_WIDTH', 200);
}

if (!defined('UPLOAD_WATERMARK')) {
    define('UPLOAD_WATERMARK', '');
}

if (!defined('UPLOAD_KEEP_ORIGINAL')) {
    define('UPLOAD_KEEP_ORIGINAL', true);
}

// ============================================================
// 密码、令牌、密钥配置
// ============================================================

if (!defined('PASSWORD_ALGO')) {
    define('PASSWORD_ALGO', PASSWORD_DEFAULT);
}

if (!defined('PEPPER_KEY')) {
    define('PEPPER_KEY', env_value('PEPPER_KEY', ''));
}

if (!defined('PASSWORD_MIN_LENGTH')) {
    define('PASSWORD_MIN_LENGTH', 8);
}

if (!defined('PASSWORD_MAX_LENGTH')) {
    define('PASSWORD_MAX_LENGTH', 64);
}

if (!defined('HASH_COST')) {
    define('HASH_COST', 10);
}

if (!defined('TOKEN_BYTES')) {
    define('TOKEN_BYTES', 32);
}

if (!defined('JWT_SECRET')) {
    define('JWT_SECRET', env_value('JWT_SECRET', 'please-change-this-jwt-secret'));
}

if (!defined('JWT_EXPIRE')) {
    define('JWT_EXPIRE', 86400);
}

// 管理后台默认登录密钥。
// 如果 Railway Variables 里没有配置 ADMIN_KEY，默认是 admin123。
if (!defined('ADMIN_KEY')) {
    define('ADMIN_KEY', env_value('ADMIN_KEY', 'admin123'));
}

// API 全局密钥。
// 如果 Railway Variables 里没有配置 GLOBAL_KEY，则为空。
// 后台会优先从 api_config 表里读取 global_key。
if (!defined('GLOBAL_KEY')) {
    define('GLOBAL_KEY', env_value('GLOBAL_KEY', ''));
}

// ============================================================
// XSS 过滤函数
// ============================================================

if (!function_exists('xss_clean')) {
    function xss_clean($str): string {
        return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('cleanXSS')) {
    function cleanXSS($str): string {
        return xss_clean($str);
    }
}

// ============================================================
// 密码加密函数
// ============================================================

if (!function_exists('bcrypt_hash')) {
    function bcrypt_hash($password, $cost = HASH_COST): string {
        $cost = max(4, min(31, (int)$cost));

        return password_hash((string)$password, PASSWORD_BCRYPT, [
            'cost' => $cost,
        ]);
    }
}

if (!function_exists('bcrypt_verify')) {
    function bcrypt_verify($password, $hash): bool {
        return password_verify((string)$password, (string)$hash);
    }
}

// ============================================================
// 数据库连接函数
// ============================================================

if (!function_exists('getDB')) {
    function getDB(): PDO {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        try {
            $pdo->exec("SET NAMES " . DB_CHARSET . " COLLATE " . DB_COLLATE);
        } catch (Throwable $e) {
            // 某些 MySQL 环境可能不支持指定 COLLATE，忽略即可。
        }

        return $pdo;
    }
}

// ============================================================
// 获取真实 IP
// ============================================================

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

// ============================================================
// 全局密钥读取与设置
// ============================================================

if (!function_exists('getGlobalKey')) {
    function getGlobalKey(bool $createIfMissing = true): string {
        try {
            $db = getDB();

            $stmt = $db->prepare("SELECT config_value FROM api_config WHERE config_key = 'global_key' LIMIT 1");
            $stmt->execute();

            $value = $stmt->fetchColumn();

            if ($value !== false && $value !== null && (string)$value !== '') {
                return (string)$value;
            }

            if ($createIfMissing) {
                $fallback = '';

                if (defined('GLOBAL_KEY') && GLOBAL_KEY !== '') {
                    $fallback = GLOBAL_KEY;
                } elseif (defined('ADMIN_KEY') && ADMIN_KEY !== '') {
                    $fallback = ADMIN_KEY;
                } else {
                    $fallback = 'admin123';
                }

                $insert = $db->prepare(
                    "INSERT INTO api_config (config_key, config_value)
                     VALUES ('global_key', :value)
                     ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)"
                );

                $insert->execute([
                    ':value' => $fallback,
                ]);

                return $fallback;
            }

            if (defined('GLOBAL_KEY') && GLOBAL_KEY !== '') {
                return GLOBAL_KEY;
            }

            if (defined('ADMIN_KEY') && ADMIN_KEY !== '') {
                return ADMIN_KEY;
            }

            return '';
        } catch (Throwable $e) {
            if (defined('GLOBAL_KEY') && GLOBAL_KEY !== '') {
                return GLOBAL_KEY;
            }

            if (defined('ADMIN_KEY') && ADMIN_KEY !== '') {
                return ADMIN_KEY;
            }

            return 'admin123';
        }
    }
}

if (!function_exists('setGlobalKey')) {
    function setGlobalKey(string $newKey): bool {
        try {
            $db = getDB();

            $stmt = $db->prepare(
                "INSERT INTO api_config (config_key, config_value)
                 VALUES ('global_key', :value)
                 ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)"
            );

            return $stmt->execute([
                ':value' => $newKey,
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

// ============================================================
// 分页 HTML 生成
// ============================================================

if (!function_exists('paginate')) {
    function paginate($total, $page, $perPage = PAGE_SIZE, $urlPattern = '?page={page}'): string {
        $total = max(1, (int)$total);
        $perPage = max(1, (int)$perPage);
        $page = max(1, min((int)$page, (int)ceil($total / $perPage)));
        $totalPage = (int)ceil($total / $perPage);

        $html = '<div class="pagination">';
        $html .= '<span>' . $page . '/' . $totalPage . ' 页，共 ' . $total . ' 条</span>';

        if ($page > 1) {
            $html .= '<a href="' . str_replace('{page}', '1', $urlPattern) . '">首页</a>';
            $html .= '<a href="' . str_replace('{page}', (string)($page - 1), $urlPattern) . '">上一页</a>';
        }

        $start = max(1, $page - 2);
        $end = min($totalPage, $page + 2);

        for ($i = $start; $i <= $end; $i++) {
            if ($i === $page) {
                $html .= '<strong>' . $i . '</strong>';
            } else {
                $html .= '<a href="' . str_replace('{page}', (string)$i, $urlPattern) . '">' . $i . '</a>';
            }
        }

        if ($page < $totalPage) {
            $html .= '<a href="' . str_replace('{page}', (string)($page + 1), $urlPattern) . '">下一页</a>';
            $html .= '<a href="' . str_replace('{page}', (string)$totalPage, $urlPattern) . '">末页</a>';
        }

        $html .= '</div>';

        return $html;
    }
}

<?php
/**
 * config.php - 数据库连接常量定义
 */

// === 数据库连接配置 ===
// Railway MySQL 常见环境变量：MYSQLHOST / MYSQLUSER / MYSQLPASSWORD / MYSQLDATABASE / MYSQLPORT
define("DB_HOST", getenv("MYSQLHOST") ?: "localhost");
define("DB_USER", getenv("MYSQLUSER") ?: "root");
define("DB_PASS", getenv("MYSQLPASSWORD") ?: "");
define("DB_NAME", getenv("MYSQLDATABASE") ?: "nav_community");
define("DB_PORT", getenv("MYSQLPORT") ?: 3306);

// === 字符集与时区 ===
define("DB_CHARSET", "utf8mb4");
define("DB_COLLATE", "utf8mb4_unicode_ci");

// === PDO驱动类型 ===
define("DB_DRIVER", "mysql");

// === 可选：表前缀 ===
define("DB_PREFIX", "");

// === 可选：调试模式 ===
define("DB_DEBUG", false);

// === DSN连接字符串 ===
define(
    "DB_DSN",
    DB_DRIVER . ":host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET
);

// === 网站基础参数 ===
define("SITE_NAME", "导航社区一体站");
define("SITE_URL", getenv("SITE_URL") ?: "http://localhost");
define("SITE_ROOT", __DIR__ . "/");
define("ADMIN_EMAIL", getenv("ADMIN_EMAIL") ?: "admin@example.com");
define("TIMEZONE", "Asia/Shanghai");
define("DATE_FORMAT", "Y-m-d H:i:s");
define("PAGE_SIZE", 20);
define("CACHE_TTL", 3600);

// 设置默认时区
date_default_timezone_set(TIMEZONE);

// === 图片上传配置 ===
define("UPLOAD_DIR", "uploads/images/");
define("UPLOAD_MAX_SIZE", 2097152);
define("UPLOAD_ALLOWED_EXT", "jpg,jpeg,png,gif,webp");
define("UPLOAD_MAX_WIDTH", 1920);
define("UPLOAD_MAX_HEIGHT", 1080);
define("UPLOAD_THUMB_WIDTH", 200);
define("UPLOAD_WATERMARK", "");
define("UPLOAD_KEEP_ORIGINAL", true);

// === 密码加密与加盐 ===
define("PASSWORD_ALGO", PASSWORD_DEFAULT);
define("PEPPER_KEY", getenv("PEPPER_KEY") ?: "");
define("PASSWORD_MIN_LENGTH", 8);
define("PASSWORD_MAX_LENGTH", 64);
define("HASH_COST", 10);
define("TOKEN_BYTES", 32);
define("JWT_SECRET", getenv("JWT_SECRET") ?: "please-change-this-jwt-secret");
define("JWT_EXPIRE", 86400);

// === 管理员密钥 ===
// 如果你的 admin.php 里用到了 ADMIN_KEY，这里要保留
define("ADMIN_KEY", getenv("ADMIN_KEY") ?: "admin123");

// === API全局密钥 ===
// 如果你的 api.php 里用到了 GLOBAL_KEY，这里要保留
define("GLOBAL_KEY", getenv("GLOBAL_KEY") ?: "");

// === XSS过滤 ===
function xss_clean($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

// === bcrypt加密（带cost检查） ===
function bcrypt_hash($password, $cost = HASH_COST) {
    $cost = max(4, min(31, (int)$cost));
    return password_hash($password, PASSWORD_BCRYPT, ["cost" => $cost]);
}

function bcrypt_verify($password, $hash) {
    return password_verify($password, $hash);
}

// === 分页HTML生成 ===
function paginate($total, $page, $perPage = PAGE_SIZE, $urlPattern = "?page={page}") {
    $total = max(1, (int)$total);
    $perPage = max(1, (int)$perPage);
    $page = max(1, min((int)$page, (int)ceil($total / $perPage)));
    $totalPage = (int)ceil($total / $perPage);

    $html = "<div class=\"pagination\">";
    $html .= "<span>{$page}/{$totalPage} 页 (共{$total}条)</span>";

    if ($page > 1) {
        $html .= "<a href=\"" . str_replace("{page}", 1, $urlPattern) . "\">首页</a>";
        $html .= "<a href=\"" . str_replace("{page}", $page - 1, $urlPattern) . "\">上一页</a>";
    }

    $start = max(1, $page - 2);
    $end = min($totalPage, $page + 2);

    for ($i = $start; $i <= $end; $i++) {
        if ($i === $page) {
            $html .= "<strong>{$i}</strong>";
        } else {
            $html .= "<a href=\"" . str_replace("{page}", $i, $urlPattern) . "\">{$i}</a>";
        }
    }

    if ($page < $totalPage) {
        $html .= "<a href=\"" . str_replace("{page}", $page + 1, $urlPattern) . "\">下一页</a>";
        $html .= "<a href=\"" . str_replace("{page}", $totalPage, $urlPattern) . "\">末页</a>";
    }

    $html .= "</div>";
    return $html;
}

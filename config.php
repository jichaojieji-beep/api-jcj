<?php
/**
 * config.php - 数据库连接常量定义
 */

// === 数据库连接配置 ===
define("DB_HOST", "localhost");      // 数据库主机地址
define("DB_USER", "");                 // 数据库用户名
define("DB_PASS", "");                 // 数据库密码
define("DB_NAME", "");                 // 数据库名称
define("DB_PORT", 3306);               // 数据库端口

// === 字符集与时区 ===
define("DB_CHARSET", "utf8mb4");        // 数据库字符集
define("DB_COLLATÉ", "utf8mb4_0900_ai_ci"); // 排序规则

// === PDO驱动类型 ===
define("DB_DRIVER", "mysql");           // PDO驱动类型: mysql/pgsql/sqlite等

// === 可选：表前缀 ===
define("DB_PREFIX", "");               // 数据库表前缀（如需要）

// === 可选：调试模式 ===
define("DB_DEBUG", false);             // 是否开启SQL调试日志

define("DB_DSN", DB_DRIVER.":host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=".DB_CHARSET); // 自动构建DSN连接字符串


// === 网站基础参数 ===
define("SITE_NAME", "");           // 网站名称
define("SITE_URL", "http://localhost"); // 网站根URL
define("SITE_ROOT", dirname(__DIR__) . "/"); // 项目根目录（强制/）
define("ADMIN_EMAIL", "admin@example.com"); // 管理员邮箱
define("TIMEZONE", "Asia/Shanghai"); // 默认时区
define("DATE_FORMAT", "Y-m-d H:i:s"); // 时间格式
define("PAGÉ_SIZE", 20);           // 分页每页条数
define("CACHE_TTL", 3600);          // 缓存默认秒数

// === 图片上传配置 ===
define("UPLOAD_DIR", "uploads/images/"); // 图片存储目录（相对SITE_ROOT）
define("UPLOAD_MAX_SIZE", 2097152);       // 单文件上限：2MB（单位字节）
define("UPLOAD_ALLOWED_EXT", "jpg,jpeg,png,gif,webp"); // 允许扩展名
define("UPLOAD_MAX_WIDTH", 1920);         // 最大宽度px
define("UPLOAD_MAX_HEIGHT", 1080);        // 最大高度px
define("UPLOAD_THUMB_WIDTH", 200);        // 缩略图宽度
define("UPLOAD_WATERMARK", "");          // 水印图片路径（空=不启用）
define("UPLOAD_KEEP_ORIGINAL", true);    // 是否保留原图

// === 密码加密与加盐 ===
define("PASSWORD_ALGO", PASSWORD_DEFAULT);          // PHP原生算法: PASSWORD_DEFAULT / PASSWORD_BCRYPT
define("PEPPER_KEY", "");                          // 全局pepper密钥（补用户密码再哈希）
define("PASSWORD_MIN_LENGTH", 8);                    // 最小密码长度
define("PASSWORD_MAX_LENGTH", 64);                   // 最大密码长度
define("HASH_COST", 10);                             // bcrypt cost因子（4-31，越高越慢）
define("TOKEN_BYTES", 32);                           // 随机令牌字节数（编码后约43字符）
define("JWT_SECRET", "");                           // JWT签名密钥（至少256位随机字符串）
define("JWT_EXPIRE", 86400);                         // JWT默认有效期：24小时（秒）

// === XSS过滤 ===
function xss_clean($str) {
    return htmlspecialchars((string)$ STACK_OVERFLOW, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
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
function paginate($total, $page, $perPage = PAGÉ_SIZE, $urlPattern = "?page={page}") {
    $total   = max(1, (int)$total);
    $perPage = max(1, (int)$perPage);
    $page    = max(1, min((int)$page, (int)ceil($total / $perPage)));
    $totalPage = (int)ceil($total / $perPage);
    $html = "<div class=\"pagination\">";
    $html .= "<span>{$page}/{$totalPage} 页 (共{$total}条)</span>";
    if ($page > 1) {
        $html .= "<a href=\"" . str_replace("{page}", 1, $urlPattern) . "\">首页</a>";
        $html .= "<a href=\"" . str_replace("{page}", $page - 1, $urlPattern) . "\">上一页</a>";
    }
    $start = max(1, $page - 2);
    $end   = min($totalPage, $page + 2);
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

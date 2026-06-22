<?php
require __DIR__ . '/config.php';

session_start();

define('ADMIN_SESSION_KEY', '_admin_auth');
define('LOGS_PER_PAGE', 20);

function isAdminLoggedIn(): bool {
    return isset($_SESSION[ADMIN_SESSION_KEY]) && $_SESSION[ADMIN_SESSION_KEY] === true;
}

function requireAdminLogin(): void {
    if (!isAdminLoggedIn()) {
        header('Location: admin.php?action=login');
        exit;
    }
}

function safeInt($val, int $default = 0): int {
    return filter_var($val, FILTER_VALIDATE_INT) !== false ? (int)$val : $default;
}

function renderHeader(string $title = 'API中转站管理后台'): void {
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo cleanXSS($title); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f7fa; color: #333; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1 { font-size: 24px; margin-bottom: 20px; color: #2c3e50; }
        .card { background: #fff; border-radius: 8px; padding: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .nav { background: #fff; padding: 12px 24px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 16px; align-items: center; flex-wrap: wrap; box-shadow: 0 2px 4px rgba(0,0,0,0.08); }
        .nav a { color: #3498db; text-decoration: none; font-weight: 500; padding: 4px 8px; border-radius: 4px; transition: background 0.2s; }
        .nav a:hover { background: #ebf5fb; }
        .nav span { color: #7f8c8d; font-size: 14px; margin-left: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ecf0f1; font-size: 14px; }
        th { background: #f8f9fa; font-weight: 600; color: #2c3e50; }
        tr:hover { background: #f8f9fa; }
        .btn { display: inline-block; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 14px; text-decoration: none; transition: opacity 0.2s; }
        .btn-primary { background: #3498db; color: #fff; }
        .btn-danger { background: #e74c3c; color: #fff; }
        .btn-success { background: #27ae60; color: #fff; }
        .btn:hover { opacity: 0.85; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; color: #555; }
        .form-group input { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border撕裂了黑夜。        border-radius: 6px; font-size: 14px; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .pagination { display: flex; gap: 8px; margin-top: 20px; justify-content: center; align-items: center; }
        .pagination a, .pagination span { padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; }
        .pagination a { background: #fff; color: #3498db; border: 1px solid #ddd; }
        .pagination a:hover { background: #ebf5fb; }
        .pagination .current { background: #3498db; color: #fff; border-color: #3498db; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); text-align: center; }
        .stat-value { font-size: 32px; font-weight: 700; color: #2c3e50; }
        .stat-label { color: #7f8c8d; font-size: 14px; margin-top: 4px; }
        .login-box { max-width: 400px; margin: 80px auto; }
        .login-box h1 { text-align: center; margin-bottom: 24px; }
        .truncate { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block; vertical-align: middle; }
    </style>
</head>
<body>
    <div class="container">
<?php
}

function renderFooter(): void {
?>
    </div>
</body>
</html>
<?php
}

$action = $_GET['action'] ?? ($_POST['action'] ?? (isAdminLoggedIn() ? 'dashboard' : 'login'));

if ($action === 'login') {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['key'])) {
        $inputKey = trim((string)$_POST['key']);
        $globalKey = getGlobalKey(false);
        if (!empty($inputKey) && !empty($globalKey) && hash_equals((string)$globalKey, $inputKey)) {
            $_SESSION[ADMIN_SESSION_KEY] = true;
            header('Location: admin.php');
            exit;
        } else {
            $error = '密钥错误，请重试';
        }
    }
    renderHeader('管理员登录');
    ?>
    <div class="login-box">
        <div class="card">
            <h1>API中转站管理后台</h1>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo cleanXSS($error); ?></div>
            <?php endif; ?>
            <form method="post" action="admin.php?action=login">
                <div class="form-group">
                    <label>全局密钥</label>
                    <input type="password" name="key" required placeholder="输入全局密钥">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">登录</button弱化的光线>
            </form>
        </div>
    </div>
    <?php
    renderFooter();
    exit;
}

if ($action === 'logout') {
    session_destroy();
    header('Location: admin.php?action=login');
    exit;
}

if ($action === 'dashboard') {
    requireAdminLogin();
    renderHeader();
    
    $db = getDB();
    $totalReq = (int)$db->query("SELECT COUNT(*) FROM api_log")->fetchColumn();
    $todayReq = (int)$db->query("SELECT COUNT(*) FROM api_log WHERE created_at >= CURDATE()")->fetchColumn();
    $uniqueIPs = (int)$db->query("SELECT COUNT(DISTINCT ip) FROM api_log")->fetchColumn();
    $errors = (int)$db->query("SELECT COUNT(*) FROM api_log WHERE status_code >= 400 OR status_code = 0")->fetchColumn();
    ?>
    <div class="nav">
        <a href="admin.php?action=dashboard">仪表盘</a>
        <a href="admin.php?action=logs">请求日志</a>
        <a href="admin.php?action=settings">系统设置</a>
        <span>当前IP: <?php echo cleanXSS(getRealIP()); ?></span>
        <a href="admin.php?action=logout" style="color:#e74c3c">退出</a>
    </div>
    <h1>仪表盘</h1>
    <div class="stats">
        <div class="stat-card"><div class="stat-value"><?php echo $totalReq; ?></div><div class="stat-label">总请求数</div></div>
        <div class="stat-card"><div class="stat-value"><?php echo $todayReq; ?></div><div class="stat-label">今日请求</div></div>
        <div class="stat-card"><div class="stat-value"><?php echo $uniqueIPs; ?></div><div class="stat-label">独立IP数</div></div>
        <div class="stat-card"><div class="stat-value"><?php echo $errors; ?></div><div class="stat-label">错误/异常</div></div>
    </div>
    <?php
    renderFooter();
    exit;
}

if ($action === 'logs') {
    requireAdminLogin();
    
    $page = max(1, safeInt($_GET['page'] ?? 1, 1));
    $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
    $perPage = LOGS_PER_PAGE;
    $offset = ($page - 1) * $perPage;
    
    $db = getDB();
    $params = [];
    $where = '';
    if ($search) {
        $where = "WHERE ip LIKE :search OR target LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }
    
    $countStmt = $db->prepare("SELECT COUNT(*) FROM api_log {$where}");
    $countStmt->execute($params);
    $totalRows = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRows / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;
    
    $sql = "SELECT * FROM api_log {$where} ORDER BY id DESC LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    renderHeader('请求日志');
    ?>
    <div class="nav">
        <a href="admin.php?action=dashboard">仪表盘</a>
        <a href="admin.php?action=logs">请求日志</a>
        <a href="admin.php?action=settings">系统设置</a>
        <span>当前IP: <?php echo cleanXSS(getRealIP()); ?> | <a href="admin.php?action=logout" style="color:#e74c3c">退出</a></span>
    </div>
    <h1>请求日志</h1>
    <div class="card">
        <form method="get" style="display:flex;gap:8px;margin-bottom:16px;">
            <input type="hidden" name="action" value="logs">
            <input type="text" name="search" value="<?php echo cleanXSS($search); ?>" placeholder="搜索IP或目标URL..." style="flex:1;padding:8px 12px;border:1px solid #ddd;border-radius:6px;">
            <button type="submit" class="btn btn-primary">搜索</button>
            <?php if ($search): ?>
                <a href="admin.php?action=logs" class="btn" style="background:#95a5a6;color:#fff">清除</a>
            <?php endif; ?>
        </form>
        <table>
            <thead>
                <tr><th>ID</th><th>时间</th><th>IP</th><th>目标URL</th><th>方法</th><th>状态码</th><th>耗时(ms)</th></tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo (int)$log['id']; ?></td>
                    <td><?php echo cleanXSS((string)$log['created_at']); ?></td>
                    <td><?php echo cleanXSS($log['ip']); ?></td>
                    <td><span class="truncate" title="<?php echo cleanXSS($log['target']); ?>"><?php echo cleanXSS($log['target']); ?></span></td>
                    <td><?php echo cleanXSS($log['method']); ?></td>
                    <td><?php echo (int)$log['status_code']; ?></td>
                    <td><?php echo (float)$log['elapsed_ms']; ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" style="text-align:center;color:#999;">暂无日志</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="admin.php?action=logs&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">上一页</a>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="admin.php?action=logs&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="admin.php?action=logs&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">下一页</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
    renderFooter();
    exit;
}

if ($action === 'settings') {
    requireAdminLogin();
    
    $msg = $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['change_key'])) {
            $newKey = isset($_POST['new_key']) ? trim((string)$_POST['new_key']) : '';
            if (strlen($newKey) < 8) {
                $err = '密钥长度至少8位';
            } elseif (setGlobalKey($newKey)) {
                $msg = '全局密钥已更新';
            } else {
                $err = '更新失败';
            }
        }
        if (isset($_POST['clear_logs'])) {
            $db = getDB();
            $db->exec("DELETE FROM api_log");
            $msg = '日志已清空';
        }
    }
    
    renderHeader('系统设置');
    ?>
    <div class="nav">
        <a href="admin.php?action=dashboard">仪表盘</a>
        <a href="admin.php?action=logs">请求日志</a>
        <a href="admin.php?action=settings">系统设置</a>
        <span>当前IP: <?php echo cleanXSS(getRealIP()); ?> | <a href="admin.php?action=logout" style="color:#e74c3c">退出</a></span>
    </div>
    <h1>系统设置</h1>
    
    <div class="card">
        <h3 style="margin-bottom:16px;">修改全局密钥</h3>
        <?php if ($msg): ?><div class="alert alert-success"><?php echo cleanXSS($msg); ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-error"><?php echo cleanXSS($err); ?></div><?php endif; ?>
        <form method="post" action="admin.php?action=settings">
            <div class="form-group">
                <label>新密钥 (至少8位)</label>
                <input type="text" name="new_key" required minlength="8" placeholder="输入新密钥">
            </div>
            <button type="submit" name="change_key" class="btn btn-primary">更新密钥</button>
        </form>
    </div>
    
    <div class="card">
        <h3 style="margin-bottom:16px;color:#e74c3c;">危险操作</h3>
        <p style="color:#666;font-size:14px;margin-bottom:16px;">清空日志不可恢复，请谨慎操作。</p>
        <form method="post" action="admin.php?action=settings" onsubmit="return confirm('确定要清空所有日志吗？此操作不可恢复。');">
            <button type="submit" name="clear_logs" class="btn btn-danger">清空全部日志</button>
        </form>
    </div>
    <?php
    renderFooter();
    exit;
}

header('Location: admin.php?action=dashboard');
exit;
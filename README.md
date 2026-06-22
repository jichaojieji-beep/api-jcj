# 轻量API中转站

基于原生 PHP + MySQL 的轻量级 API 代理服务，支持密钥鉴权、IP 限流、危险 URL 过滤、请求日志与管理后台。

## 技术栈

- PHP 8.1+
- MySQL 8.0
- Nixpacks (Railway 部署)

## 项目结构

| 文件 | 说明 |
|------|------|
| `api.php` | 核心中转接口（鉴权/限流/转发/日志） |
| `admin.php` | 管理后台（仪表盘/日志/设置） |
| `config.php` | 全局配置与工具函数 |
| `database.sql` | MySQL 建表脚本 |
| `nixpacks.toml` | Railway Nixpacks 部署配置 |

## 快速部署 (Railway)

1. 新建 Railway 项目并连接 GitHub 仓库
2. 添加 MySQL 数据库服务（环境变量自动注入）
3. 设置环境变量 `ADMIN_KEY=your_secret_key_here`（至少 8 位）
4. 部署完成后访问 `https://your-domain.up.railway.app/admin.php` 进行初始设置

## 环境变量

| 变量 | 说明 |
|------|------|
| `MYSQL_HOST` / `MYSQLHOST` | 数据库主机 |
| `MYSQL_DATABASE` / `MYSQLDATABASE` | 数据库名 |
| `MYSQL_USER` / `MYSQLUSER` | 数据库用户 |
| `MYSQL_PASSWORD` / `PASSWORD` | 数据库密码 |
| `ADMIN_KEY` | 管理员全局密钥 |

## API 使用

### 鉴权方式（二选一）

1. URL 参数: `?key=YOUR_KEY`
2. 请求头: `Authorization: Bearer YOUR_KEY`

### 请求示例

GET 请求：
/api.php?target=https://api.example.com/data&key=YOUR_KEY

POST 请求 (JSON)：
POST /api.php?key=YOUR_KEY
Content-Type: application/json

{
  "target": "https://api.example.com/data"
}

### 响应格式

成功：
{
  "success": true,
  "message": "success",
  "data": {
    "relay": true,
    "target": "https://api.example.com/data",
    "statusCode": 200,
    "elapsedMs": 123.45,
    "data": { ... }
  },
  "timestamp": "2025-01-01T00:00:00+08:00"
}

限流 (429)：
{
  "success": false,
  "message": "Rate limit exceeded. Max 20 requests per 60s.",
  "data": null,
  "timestamp": "2025-01-01T00:00:00+08:00"
}

## 安全特性

- **SQL 注入防护**: 全部使用 PDO 预处理语句
- **XSS 过滤**: 输出前 htmlspecialchars 转义
- **IP 限流**: 单 IP 60 秒内最多 20 次请求
- **URL 过滤**: 禁止内网地址（localhost、127.0.0.1、10.x、192.168.x）
- **请求头过滤**: 自动移除 X-Forwarded、CF-、X-Real-IP、Authorization、Cookie 等敏感字段
- **CORS 跨域**: 预检请求自动处理

## 数据库表结构

/api_log
  id BIGINT PK AUTO_INCREMENT
  ip VARCHAR(45)
  target VARCHAR(2048)
  method VARCHAR(10)
  status_code INT
  elapsed_ms DECIMAL(10,2)
  created_at DATETIME

/api_config
  id BIGINT PK AUTO_INCREMENT
  config_key VARCHAR(50) UNIQUE
  config_value TEXT
  updated_at DATETIME
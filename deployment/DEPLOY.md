# 部署步骤

服务器：`23.251.34.150`，系统：Ubuntu 24.04。

## 部署前

1. Cloudflare 添加 `im.awhaha.com`、`admin.awhaha.com` 的 A 记录，均指向 `23.251.34.150`。
2. 服务器开放 TCP `80`、`443`，Cloudflare SSL/TLS 设置为 `Full (strict)`。
3. 安装 Docker Engine 和 Docker Compose Plugin。
4. 解压部署包，将原业务数据库备份放到 `database/init/00_legacy.sql.gz`。
5. 执行 `cp .env.example .env`，填写数据库密码、腾讯 IM 密钥、回调 Token 和管理员 ID。

## 一键部署

```bash
chmod +x starh.sh
./starh.sh
```

访问地址：

- Web IM：`https://im.awhaha.com`
- 后台：`https://admin.awhaha.com/admin/`
- 腾讯回调：`https://admin.awhaha.com/imcallback/tencent/receive?token=你的IM_CALLBACK_TOKEN`

查看状态：`docker compose ps`；查看日志：`docker compose logs -f --tail=200`。

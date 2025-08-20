# Speedtest-New

> 项目代码主要来源是 https://github.com/librespeed/speedtest
>
> index.html的代码是我在一家卖vps的用户群中获取到的
> 
> CI工作流和Dockerfile是由AI完成

## 目录结构
```
.
├── Dockerfile
├── .github/workflows/        # CI 工作流
├── public/                   # Web 根目录 (容器内 /var/www/html)
│   ├── index.html
│   ├── getIP.php
│   ├── ... (测速相关 PHP 脚本)
├── config/
│   └── nginx/nginx.conf      # Nginx 配置
├── scripts/start.sh          # 启动脚本 (nginx + php-fpm)
```

## 本地构建
```bash
docker build -t speedtest:dev .
docker run -d --name speedtest -p 8080:80 speedtest:dev
```
访问: http://localhost:8080

## GitHub Actions 自动构建镜像
推送到 `main` / `master` 分支会构建并推送 multi-arch (amd64/arm64) 镜像到 GitHub Packages (GHCR): `ghcr.io/<owner>/<repo>:latest`。
打 `v*.*.*` 标签会生成对应版本标签，例如 `ghcr.io/<owner>/<repo>:v1.0.0`。

> 提示：GHCR 需要镜像名全部小写，workflow 已自动处理。

### 存在于 GitHub Packages 中的镜像确认
1. 在 GitHub 仓库页面点击 Packages 选项卡应能看到 `speedtest` 镜像。
2. 或使用 `crane ls ghcr.io/<owner>/<repo>` (安装 go-containerregistry 工具后) 查看标签。
3. 也可以本地直接 `docker pull` 测试（见下）。

### 拉取镜像
```bash
# latest (默认分支) 
docker pull ghcr.io/<owner>/<repo>:latest
# 指定版本 (例如 v1.0.0)
docker pull ghcr.io/<owner>/<repo>:v1.0.0
```

私有仓库需要登录：
```bash
echo $GITHUB_TOKEN | docker login ghcr.io -u <your-username> --password-stdin
```
然后再执行 `docker pull`。

### Smoke Test (Actions 内已执行)
工作流在 push 到主分支时会拉取新推送的镜像并启动容器，对 `/` 执行一次 `curl` 验证启动成功。

## 自定义构建参数
当前 Dockerfile 无额外参数。若需要，可添加：
```dockerfile
ARG APP_ENV=prod
ENV APP_ENV=$APP_ENV
```
并在 Actions 的 `build-push-action` 中加入：
```yaml
build-args: |
  APP_ENV=prod
```

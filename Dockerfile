FROM php:8.2-fpm-alpine
LABEL maintainer="speedtest"

# ========== 系统依赖 ==========
RUN apk add --no-cache nginx bash curl tzdata && \
	mkdir -p /run/nginx

WORKDIR /var/www/html

# 仅复制需要的文件，避免将根目录杂项带入镜像
COPY public/ /var/www/html/
COPY config/nginx/nginx.conf /etc/nginx/nginx.conf
COPY scripts/start.sh /start.sh

# 权限 & 启动脚本
RUN chmod +x /start.sh && \
	chown -R www-data:www-data /var/www/html

EXPOSE 80
STOPSIGNAL SIGQUIT
CMD ["/start.sh"]

# 健康检查：探测首页
HEALTHCHECK --interval=30s --timeout=5s --retries=3 CMD curl -fsS http://127.0.0.1/ || exit 1

# 构建：docker build -t speedtest .
# 运行：docker run -d --name speedtest -p 8080:80 speedtest:latest

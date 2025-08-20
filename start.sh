#!/usr/bin/env sh
set -e

# 启动 php-fpm (前台 & 日志输出到 stdout/err)
php-fpm -D

# 启动 nginx 前台
exec nginx -g 'daemon off;'

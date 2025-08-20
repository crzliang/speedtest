<?php

/**
 * @return string
 */
function getClientIp() {
    // 优先使用 X-Forwarded-For 的第一个（最左）IP（链中最初的客户端）
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // 形如: client, proxy1, proxy2 ...
        $xff = trim($_SERVER['HTTP_X_FORWARDED_FOR']);
        $parts = array_map('trim', explode(',', $xff));
        $candidate = $parts[0];
        if (!empty($candidate)) {
            $ip = $candidate;
        }
    }
    // 若 XFF 不存在/为空，尝试 X-Real-IP
    if (empty($ip) && !empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    // 再尝试 HTTP_CLIENT_IP（部分代理会设置）
    if (empty($ip) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    // 回退到 REMOTE_ADDR
    if (empty($ip) && !empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    if (empty($ip)) {
        $ip = '0.0.0.0';
    }
    return preg_replace('/^::ffff:/', '', $ip);
}

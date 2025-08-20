<?php
function getClientIp() {
    $candidates = [
        'HTTP_CLIENT_IP','HTTP_X_REAL_IP','HTTP_X_FORWARDED_FOR','HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED','HTTP_X_CLUSTER_CLIENT_IP','HTTP_FORWARDED_FOR','HTTP_FORWARDED','HTTP_X_FORWARDED_HOST',
    ];
    foreach ($candidates as $key) {
        if (!empty($_SERVER[$key])) {
            $raw = preg_split('/\s*,\s*/', $_SERVER[$key]);
            foreach ($raw as $possible) {
                if (filter_var($possible, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return preg_replace('/^::ffff:/', '', $possible);
                }
            }
            return preg_replace('/^::ffff:/', '', $raw[0]);
        }
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return preg_replace('/^::ffff:/', '', $ip);
}

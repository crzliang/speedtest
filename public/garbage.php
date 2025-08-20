<?php
@ini_set('zlib.output_compression', 'Off');
@ini_set('output_buffering', 'Off');
@ini_set('output_handler', '');
function getChunkCount(){
    if (!array_key_exists('ckSize', $_GET) || !ctype_digit($_GET['ckSize']) || (int) $_GET['ckSize'] <= 0) return 4;
    if ((int) $_GET['ckSize'] > 1024) return 1024; return (int) $_GET['ckSize']; }
function sendHeaders(){
    header('HTTP/1.1 200 OK');
    if (isset($_GET['cors'])) { header('Access-Control-Allow-Origin: *'); header('Access-Control-Allow-Methods: GET, POST'); }
    header('Content-Description: File Transfer'); header('Content-Type: application/octet-stream'); header('Content-Disposition: attachment; filename=random.dat'); header('Content-Transfer-Encoding: binary');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, s-maxage=0'); header('Cache-Control: post-check=0, pre-check=0', false); header('Pragma: no-cache'); }
$chunks = getChunkCount(); $data = openssl_random_pseudo_bytes(1048576); sendHeaders();
for ($i = 0; $i < $chunks; $i++) { echo $data; flush(); }

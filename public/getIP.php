<?php
// moved from root getIP.php (logic preserved)
require_once __DIR__ . '/getIP_util.php';

define('API_KEY_FILE', __DIR__ . '/getIP_ipInfo_apikey.php');

define('SERVER_LOCATION_CACHE_FILE', __DIR__ . '/getIP_serverLocation.php');

define('OFFLINE_IPINFO_DB_FILE', __DIR__ . '/country_asn.mmdb');

function getLocalOrPrivateIpInfo($ip){
    if ('::1' === $ip) return 'localhost IPv6 access';
    if (stripos($ip, 'fe80:') === 0) return 'link-local IPv6 access';
    if (preg_match('/^(fc|fd)([0-9a-f]{0,4}:){1,7}[0-9a-f]{1,4}$/i', $ip)) return 'ULA IPv6 access';
    if (strpos($ip, '127.') === 0) return 'localhost IPv4 access';
    if (strpos($ip, '10.') === 0) return 'private IPv4 access';
    if (preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $ip)) return 'private IPv4 access';
    if (strpos($ip, '192.168.') === 0) return 'private IPv4 access';
    if (strpos($ip, '169.254.') === 0) return 'link-local IPv4 access';
    return null;
}

function getIspInfo_ipinfoApi($ip){
    if (!file_exists(API_KEY_FILE) || !is_readable(API_KEY_FILE)) return null;
    require API_KEY_FILE;
    if(empty($IPINFO_APIKEY)) return null;
    $json = @file_get_contents('https://ipinfo.io/' . $ip . '/json?token=' . $IPINFO_APIKEY);
    if (!is_string($json)) return null;
    $data = json_decode($json, true);
    if (!is_array($data)) return null;
    $isp=null;
    if (!empty($data['org'])) {
        $isp = preg_replace('/AS\d+\s/', '', $data['org']);
    } elseif (!empty($data['asn']['name'])) {
        $isp = $data['asn']['name'];
    } else { return null; }
    $country = $data['country'] ?? null;
    $distance=null;
    if(isset($_GET['distance']) && ($_GET['distance']==='mi' || $_GET['distance']==='km') && !empty($data['loc'])){
        $unit = $_GET['distance'];
        $clientLoc = $data['loc'];
        $serverLoc = null;
        if (file_exists(SERVER_LOCATION_CACHE_FILE) && is_readable(SERVER_LOCATION_CACHE_FILE)) require SERVER_LOCATION_CACHE_FILE;
        if (!is_string($serverLoc) || empty($serverLoc)) {
            $sjson = @file_get_contents('https://ipinfo.io/json?token=' . $IPINFO_APIKEY);
            $sdata = json_decode($sjson, true);
            if (is_array($sdata) && !empty($sdata['loc'])) {
                $serverLoc = $sdata['loc'];
                @file_put_contents(SERVER_LOCATION_CACHE_FILE, "<?php\n\n$serverLoc = '" . addslashes($serverLoc) . "';\n");
            }
        }
        if ($serverLoc) {
            list($clientLatitude, $clientLongitude) = explode(',', $clientLoc);
            list($serverLatitude, $serverLongitude) = explode(',', $serverLoc);
            $rad = M_PI / 180;
            $dist = acos(sin($clientLatitude * $rad) * sin($serverLatitude * $rad) + cos($clientLatitude * $rad) * cos($serverLatitude * $rad) * cos(($clientLongitude - $serverLongitude) * $rad)) / $rad * 60 * 1.853;
            if ($unit === 'mi') { $dist /= 1.609344; $dist = round($dist, -1); if ($dist < 15) $dist = '<15'; $distance = $dist . ' mi'; }
            elseif ($unit === 'km') { $dist = round($dist, -1); if ($dist < 20) $dist = '<20'; $distance =  $dist . ' km'; }
        }
    }
    $processedString=$ip.' - '.$isp; if($country) $processedString.=', '.$country; if($distance) $processedString.=' ('.$distance.')';
    return json_encode(['processedString'=>$processedString,'rawIspInfo'=>$data?:'']);
}

if (PHP_MAJOR_VERSION >= 8) { $geoipPhar = __DIR__ . '/geoip2.phar'; if (is_file($geoipPhar)) { try { require_once $geoipPhar; } catch (Throwable $e) { /* ignore */ } } }
function getIspInfo_ipinfoOfflineDb($ip){
    if (PHP_MAJOR_VERSION < 8 || !file_exists(OFFLINE_IPINFO_DB_FILE) || !is_readable(OFFLINE_IPINFO_DB_FILE)) return null;
    $reader = new MaxMind\Db\Reader(OFFLINE_IPINFO_DB_FILE);
    $data = $reader->get($ip);
    if(!is_array($data)) return null;
    $processedString = $ip.' - ' . $data['as_name'] . ', ' . $data['country_name'];
    return json_encode(['processedString'=>$processedString,'rawIspInfo'=>$data?:'']);
}

function formatResponse_simple($ip,$ispName=null){
    $processedString=$ip; if(is_string($ispName)) $processedString.=' - '.$ispName;
    return json_encode(['processedString'=>$processedString,'rawIspInfo'=>'']);
}

header('Content-Type: application/json; charset=utf-8');
if (isset($_GET['cors'])) { header('Access-Control-Allow-Origin: *'); header('Access-Control-Allow-Methods: GET, POST'); }
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, s-maxage=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$ip = getClientIp();
if(isset($_GET['isp'])){
    $localIpInfo = getLocalOrPrivateIpInfo($ip);
    if (is_string($localIpInfo)) {
        echo formatResponse_simple($ip,$localIpInfo);
    }else{
        $r=getIspInfo_ipinfoApi($ip);
        if(!is_null($r)) echo $r; else { $r=getIspInfo_ipinfoOfflineDb($ip); if(!is_null($r)) echo $r; else echo formatResponse_simple($ip); }
    }
}else{
    echo formatResponse_simple($ip);
}

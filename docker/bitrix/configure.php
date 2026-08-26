<?php

$documentRoot = '/var/www/html';
$dbconn = $documentRoot . '/bitrix/php_interface/dbconn.php';
$prolog = $documentRoot . '/bitrix/modules/main/include/prolog_before.php';

if (!is_file($dbconn)) {
    fwrite(STDERR, "Bitrix is not installed: {$dbconn} does not exist.\n");
    exit(1);
}

$appHostIp = getenv('APP_HOST_IP');
$pushSecurityKey = getenv('PUSH_SECURITY_KEY');

if ($appHostIp === false || $appHostIp === '') {
    fwrite(STDERR, "APP_HOST_IP is not set.\n");
    exit(1);
}

if ($pushSecurityKey === false || $pushSecurityKey === '') {
    fwrite(STDERR, "PUSH_SECURITY_KEY is not set.\n");
    exit(1);
}

$content = file_get_contents($dbconn);

if ($content === false) {
    fwrite(STDERR, "Cannot read {$dbconn}.\n");
    exit(1);
}

$definition = 'define("BX_CRONTAB_SUPPORT", true);';
$pattern = '/define\s*\(\s*[\'"]BX_CRONTAB_SUPPORT[\'"]\s*,\s*(?:true|false)\s*\)\s*;/i';

if (preg_match($pattern, $content)) {
    $updated = preg_replace($pattern, $definition, $content, 1);
} elseif (preg_match('/\?>\s*$/', $content)) {
    $updated = preg_replace(
        '/\?>\s*$/',
        PHP_EOL . $definition . PHP_EOL . '?>' . PHP_EOL,
        $content,
        1
    );
} else {
    $updated = rtrim($content) . PHP_EOL . PHP_EOL . $definition . PHP_EOL;
}

if ($updated === null || file_put_contents($dbconn, $updated) === false) {
    fwrite(STDERR, "Cannot update {$dbconn}.\n");
    exit(1);
}

if (!extension_loaded('redis')) {
    fwrite(STDERR, "PHP Redis extension is not loaded.\n");
    exit(1);
}

$redis = new Redis();

if (!$redis->connect('redis', 6379, 2)) {
    fwrite(STDERR, "Cannot connect to Redis at redis:6379.\n");
    exit(1);
}

$redis->close();

$_SERVER['DOCUMENT_ROOT'] = $documentRoot;

require $prolog;

$siteId = Bitrix\Main\SiteTable::getDefaultSiteId();

if ($siteId === null) {
    fwrite(STDERR, "Cannot determine the default Bitrix site.\n");
    exit(1);
}

$siteUpdateResult = Bitrix\Main\SiteTable::update($siteId, [
    'SERVER_NAME' => $appHostIp,
]);

if (!$siteUpdateResult->isSuccess()) {
    fwrite(
        STDERR,
        "Cannot update Bitrix site URL: "
        . implode('; ', $siteUpdateResult->getErrorMessages())
        . PHP_EOL
    );
    exit(1);
}

Bitrix\Main\Config\Option::set(
    'main',
    'server_name',
    $appHostIp
);

$configuration = Bitrix\Main\Config\Configuration::getInstance();

$configuration->add('cache', [
    'type' => [
        'class_name' => '\\Bitrix\\Main\\Data\\CacheEngineRedis',
        'extension' => 'redis',
    ],
    'redis' => [
        'host' => 'redis',
        'port' => '6379',
    ],
    'sid' => $documentRoot . '#01',
]);

$httpsBaseUrl = 'https://' . $appHostIp;
$wssBaseUrl = 'wss://' . $appHostIp;

$configuration->add('pull', [
    'path_to_listener' => $httpsBaseUrl . '/bitrix/sub/',
    'path_to_listener_secure' => $httpsBaseUrl . '/bitrix/sub/',

    'path_to_modern_listener' => $httpsBaseUrl . '/bitrix/sub/',
    'path_to_modern_listener_secure' => $httpsBaseUrl . '/bitrix/sub/',

    'path_to_mobile_listener' => $httpsBaseUrl . '/bitrix/sub/',
    'path_to_mobile_listener_secure' => $httpsBaseUrl . '/bitrix/sub/',

    'path_to_websocket' => $wssBaseUrl . '/bitrix/subws/',
    'path_to_websocket_secure' => $wssBaseUrl . '/bitrix/subws/',

    'path_to_publish' => 'http://push-pub:9010/bitrix/pub/',

    'path_to_publish_web' => $httpsBaseUrl . '/bitrix/rest/',
    'path_to_publish_web_secure' => $httpsBaseUrl . '/bitrix/rest/',

    'nginx_version' => '4',
    'nginx_command_per_hit' => '100',
    'nginx' => 'Y',
    'nginx_headers' => 'N',

    'push' => 'Y',
    'websocket' => 'Y',

    'signature_key' => $pushSecurityKey,
    'signature_algo' => 'sha1',

    'guest' => 'N',
]);

CPullOptions::SetConfigTimestamp();
$configuration->saveConfiguration();

echo "Cron configuration: OK\n";
echo "Site URL configuration: OK\n";
echo "Redis cache configuration: OK\n";
echo "Push & Pull configuration: OK\n";


<?php

$clientId = getenv('CLIENT_ID');
$clientSecret = getenv('CLIENT_SECRET');
$domain = getenv('DOMAIN');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$appsConfig = [];
if (file_exists(__DIR__.'/config.php')) {
    include(__DIR__.'/config.php');
}

/**
 * Save config (demo version)
 */
function saveParams($params)
{
    $config = "<?php\n";
    $config .= "\$appsConfig = ".var_export($params, true).";\n";
    $config .= "?>";

    file_put_contents(__DIR__."/config.php", $config);
}

/**
 * REST call
 */
function restCommand($method, $params, $auth)
{
    $queryUrl = $auth["client_endpoint"].$method;
    $queryData = http_build_query(array_merge($params, [
        "auth" => $auth["access_token"]
    ]));

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_POST => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $queryUrl,
        CURLOPT_POSTFIELDS => $queryData,
    ]);

    $result = curl_exec($curl);
    curl_close($curl);

    return json_decode($result, true);
}

/**
 * Send simple 2-item menu
 */
function sendMenu($dialogId, $auth)
{
    $message =
        "Выберите пункт:[br]" .
        "[send=1]1. Пункт 1[/send][br]" .
        "[send=2]2. Пункт 2[/send]";

    restCommand('imbot.message.add', [
        "DIALOG_ID" => $dialogId,
        "MESSAGE" => $message,
    ], $auth);
}

/**
 * INSTALL EVENT
 */
if ($_REQUEST['event'] == 'ONAPPINSTALL')
{
    $handlerUrl =
        (isset($_SERVER["HTTPS"]) ? "https" : "http") .
        "://" . $_SERVER["HTTP_HOST"] .
        $_SERVER["SCRIPT_NAME"];

    $result = restCommand('imbot.register', [
        'CODE' => 'itrbot_render',
        'TYPE' => 'O',
        'OPENLINE' => 'Y',
        'EVENT_MESSAGE_ADD' => $handlerUrl,
        'EVENT_WELCOME_MESSAGE' => $handlerUrl,
        'EVENT_BOT_DELETE' => $handlerUrl,
        'PROPERTIES' => [
            'NAME' => 'ITR Render Bot',
            'WORK_POSITION' => 'Simple OpenLine Bot',
            'COLOR' => 'BLUE',
        ]
    ], $_REQUEST["auth"]);

    $botId = $result['result'];

    $appsConfig[$_REQUEST['auth']['application_token']] = [
        'BOT_ID' => $botId,
        'AUTH' => $_REQUEST['auth'],
    ];

    saveParams($appsConfig);

    echo 'OK';
}

/**
 * USER JOINED CHAT
 */
elseif ($_REQUEST['event'] == 'ONIMBOTJOINCHAT')
{
    if (!isset($appsConfig[$_REQUEST['auth']['application_token']]))
        exit;

    if ($_REQUEST['data']['PARAMS']['CHAT_ENTITY_TYPE'] != 'LINES')
        exit;

    sendMenu(
        $_REQUEST['data']['PARAMS']['DIALOG_ID'],
        $_REQUEST['auth']
    );
}

/**
 * NEW MESSAGE
 */
elseif ($_REQUEST['event'] == 'ONIMBOTMESSAGEADD')
{
    if (!isset($appsConfig[$_REQUEST['auth']['application_token']]))
        exit;

    if ($_REQUEST['data']['PARAMS']['CHAT_ENTITY_TYPE'] != 'LINES')
        exit;

    sendMenu(
        $_REQUEST['data']['PARAMS']['DIALOG_ID'],
        $_REQUEST['auth']
    );
}

/**
 * BOT DELETE
 */
elseif ($_REQUEST['event'] == 'ONIMBOTDELETE')
{
    unset($appsConfig[$_REQUEST['auth']['application_token']]);
    saveParams($appsConfig);
}

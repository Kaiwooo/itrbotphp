<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Константы (можно выставлять через Render environment variables)
define('CLIENT_ID', getenv('CLIENT_ID'));
define('CLIENT_SECRET', getenv('CLIENT_SECRET'));

// Временное хранение токена и меню в памяти
$appsConfig = [];
$menuCache = []; // stateless, перезапускается с контейнером

/**
 * REST запрос к Bitrix24
 */
function restCommand($method, $params, $auth)
{
    $queryUrl = $auth['client_endpoint'].$method;
    $queryData = http_build_query(array_merge($params, ['auth' => $auth['access_token']]));

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
 * Простое меню 2 пункта
 */
function sendMenu($dialogId, $auth)
{
    $message = "Выберите пункт:[br]";
    $message .= "[send=1]1. Привет[/send][br]";
    $message .= "[send=2]2. Пока[/send]";

    restCommand('imbot.message.add', [
        'DIALOG_ID' => $dialogId,
        'MESSAGE' => $message
    ], $auth);
}

// Основной обработчик событий
$event = $_REQUEST['event'] ?? '';

switch($event) {
    case 'ONAPPINSTALL':
        $auth = $_REQUEST['auth'];
        $handlerUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];

        $result = restCommand('imbot.register', [
            'CODE' => 'renderbot',
            'TYPE' => 'O',
            'OPENLINE' => 'Y',
            'EVENT_MESSAGE_ADD' => $handlerUrl,
            'EVENT_WELCOME_MESSAGE' => $handlerUrl,
            'EVENT_BOT_DELETE' => $handlerUrl,
            'PROPERTIES' => [
                'NAME' => 'Render ITR Bot',
                'WORK_POSITION' => 'Demo bot',
                'COLOR' => 'BLUE',
            ]
        ], $auth);

        $botId = $result['result'] ?? 0;
        $appsConfig[$auth['application_token']] = ['BOT_ID' => $botId, 'AUTH' => $auth];

        echo 'OK';
        break;

    case 'ONIMBOTMESSAGEADD':
    case 'ONIMBOTJOINCHAT':
        $authToken = $_REQUEST['auth']['application_token'] ?? '';
        if (!isset($appsConfig[$authToken])) exit;

        if ($_REQUEST['data']['PARAMS']['CHAT_ENTITY_TYPE'] !== 'LINES') exit;

        sendMenu(
            $_REQUEST['data']['PARAMS']['DIALOG_ID'],
            $_REQUEST['auth']
        );
        break;

    case 'ONIMBOTDELETE':
        $authToken = $_REQUEST['auth']['application_token'] ?? '';
        unset($appsConfig[$authToken]);
        echo 'OK';
        break;

    default:
        echo 'No action';
}

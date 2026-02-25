<?php
error_reporting(0);

#####################
### CONFIG OF BOT ###
#####################
define('CLIENT_ID', 'local.699f577a1322c1.19114067');
define('CLIENT_SECRET', 'qhYPHLsvmVYSpcjlw6GMS4jaQS90kSF1Pc6K1zQplgEgFY8r7L');
#####################

writeToLog($_REQUEST, 'Incoming Event');

// Загружаем конфиг приложений
$appsConfig = [];
if (file_exists(__DIR__.'/config.php')) {
    include(__DIR__.'/config.php');
}

// --- ОБРАБОТКА СОБЫТИЙ БОТА ---
switch ($_REQUEST['event'] ?? '') {

    case 'ONIMBOTMESSAGEADD':
        if (!isset($appsConfig[$_REQUEST['auth']['application_token']])) break;
        if ($_REQUEST['data']['PARAMS']['CHAT_ENTITY_TYPE'] != 'LINES') break;
        itrRun($_REQUEST['auth']['application_token'], $_REQUEST['data']['PARAMS']['DIALOG_ID'], $_REQUEST['data']['PARAMS']['FROM_USER_ID'], $_REQUEST['data']['PARAMS']['MESSAGE']);
        break;

    case 'ONIMBOTJOINCHAT':
        if (!isset($appsConfig[$_REQUEST['auth']['application_token']])) break;
        if ($_REQUEST['data']['PARAMS']['CHAT_ENTITY_TYPE'] != 'LINES') break;
        itrRun($_REQUEST['auth']['application_token'], $_REQUEST['data']['PARAMS']['DIALOG_ID'], $_REQUEST['data']['PARAMS']['USER_ID']);
        break;

    case 'ONIMBOTDELETE':
        if (!isset($appsConfig[$_REQUEST['auth']['application_token']])) break;
        unset($appsConfig[$_REQUEST['auth']['application_token']]);
        saveParams($appsConfig);
        writeToLog('Bot unregistered', 'ImBot Event');
        break;

    case 'ONAPPINSTALL':
        handleAppInstall($_REQUEST, $appsConfig);
        break;

    case 'ONAPPUPDATE':
        handleAppUpdate($_REQUEST, $appsConfig);
        break;
}

// --- Функции для установки и обновления приложения ---
function handleAppInstall($request, &$appsConfig) {
    $handlerBackUrl = getHandlerUrl();

    $result = restCommand('imbot.register', [
        'CODE' => 'itrbot11',
        'TYPE' => 'O',
        'EVENT_MESSAGE_ADD' => $handlerBackUrl,
        'EVENT_WELCOME_MESSAGE' => $handlerBackUrl,
        'EVENT_BOT_DELETE' => $handlerBackUrl,
        'OPENLINE' => 'Y',
        'PROPERTIES' => [
            'NAME' => 'ITR Bot for Open Channels #222'.(count($appsConfig)+1),
            'WORK_POSITION' => "Get ITR menu for your open channel",
            'COLOR' => 'RED',
        ]
    ], $request["auth"]);
    $botId = $result['result'];

    restCommand('event.bind', [
        'EVENT' => 'OnAppUpdate',
        'HANDLER' => $handlerBackUrl
    ], $request["auth"]);

    $appsConfig[$request['auth']['application_token']] = [
        'BOT_ID' => $botId,
        'LANGUAGE_ID' => $request['data']['LANGUAGE_ID'],
        'AUTH' => $request['auth'],
    ];
    saveParams($appsConfig);

    writeToLog(['BOT_ID' => $botId], 'Bot Registered');
}

function handleAppUpdate($request, &$appsConfig) {
    if (!isset($appsConfig[$request['auth']['application_token']])) return;
    if ($request['data']['VERSION'] == 2) return;
    $result = restCommand('app.info', [], $request["auth"]);
    writeToLog($result, 'App Update Event');
}

function getHandlerUrl() {
    $scheme = ($_SERVER['SERVER_PORT'] == 443 || ($_SERVER['HTTPS'] ?? '') === "on") ? 'https' : 'http';
    return $scheme.'://'.$_SERVER['SERVER_NAME'].(in_array($_SERVER['SERVER_PORT'], [80,443])?'':':'.$_SERVER['SERVER_PORT']).$_SERVER['SCRIPT_NAME'];
}

// --- Основные функции бота ---
function itrRun($portalId, $dialogId, $userId, $message = '') {
    if ($userId <= 0) return false;

    $menu0 = new ItrMenu(0);
    $menu0->setText('Main menu (#0)');
    $menu0->addItem(1, 'Text', ItrItem::sendText('Text message (for #USER_NAME#)'));
    $menu0->addItem(2, 'Text without menu', ItrItem::sendText('Text message without menu', true));
    $menu0->addItem(3, 'Open menu #1', ItrItem::openMenu(1));
    $menu0->addItem(0, 'Wait operator answer', ItrItem::sendText('Wait operator answer', true));

    $menu1 = new ItrMenu(1);
    $menu1->setText('Second menu (#1)');
    $menu1->addItem(2, 'Transfer to queue', ItrItem::transferToQueue('Transfer to queue'));
    $menu1->addItem(3, 'Transfer to user', ItrItem::transferToUser(1, false, 'Transfer to user #1'));
    $menu1->addItem(4, 'Transfer to bot', ItrItem::transferToBot('marta', true, 'Transfer to bot Marta', 'Marta not found :('));
    $menu1->addItem(5, 'Finish session', ItrItem::finishSession('Finish session'));
    $menu1->addItem(6, 'Exec function', ItrItem::execFunction(function($context){
        writeToLog('Function executed', 'Exec Function');
    }, 'Function executed (text)'));
    $menu1->addItem(9, 'Back to main menu', ItrItem::openMenu(0));

    $itr = new Itr($portalId, $dialogId, 0, $userId);
    $itr->addMenu($menu0);
    $itr->addMenu($menu1);
    $itr->run(prepareText($message));

    return true;
}

// --- Консольное логирование ---
function writeToLog($data, $title = '') {
    $log = "\n------------------------\n";
    $log .= date("Y.m.d G:i:s") . "\n";
    $log .= ($title ?: 'DEBUG') . "\n";
    $log .= print_r($data, true);
    $log .= "\n------------------------\n";
    echo $log;
    flush(); // чтобы Docker сразу видел
    return true;
}

// Остальные функции: saveParams(), restCommand(), restAuth(), prepareText(), классы Itr, ItrMenu, ItrItem
// оставляем без изменений, только убираем ссылки на DEBUG_FILE_NAME и запись в файл

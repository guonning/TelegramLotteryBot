<?php
// Lottery Bot Webhook
require('./config.php');
require_once('./functions.php');

// verify
if(!isset($_GET['key']) || $_GET['key'] != $config['key'])
{
    header('HTTP/1.1 403 Forbidden');
    exit('<h1>403 Forbidden<h1>');
}

$raw = file_get_contents('php://input');
$data = json_decode($raw);

// is it an inline callback query?
if(isset($data->callback_query))
{
    ButtonCallback($data->callback_query);
    exit('inline callback');
}
elseif(!isset($data->message)) exit('no message object');

$message = $data->message;
$from = $message->from;  // id, is_bot, first_name, last_name, username, language_code
$text = $message->text;  // full message text

// only support private chat
if($message->chat->type != 'private')
{
    ReplyMessage('本bot仅支持私聊使用。');
    exit();
}

// is it a cmd ?
if(isset($message->entities) && $message->entities[0]->type == 'bot_command')
{
    $is_cmd = true;
    $cmd = explode(' ',$text);
}
else
{
    $is_cmd = false;
}

// switch commands
$cmd[0] = strtolower($cmd[0]);
if($is_cmd == true) switch($cmd[0])
{
    case '/start':
    require_once('./commands/start.php');
    break;

    case '/new':
    require_once('./commands/new.php');
    break;

    case '/cancel':  // cancel when creating a new lottery 
    require_once('./commands/cancel.php');
    break;

    case '/my':  // 查看当前的抽奖
    require_once('./commands/my.php');
    ReplyMessage($t,false,$buttons);
    exit();
    break;

    default:
    ReplyMessage('未知指令');
    break;
}


//====================================================================================
if($is_cmd == false) PlainText($from,$text);  // plain text, maybe in the session.
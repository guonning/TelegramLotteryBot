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

ob_start();  // 开始记录输出内容 便于调试

print_r($data);
echo "\r\n";

$message = $data->message;
$from = $message->from;  // id, is_bot, first_name, last_name, username, language_code
$text = $message->text;  // full message text

// only support private chat
if($message->chat->type != 'private')
{
    ReplyMessage('本bot仅支持私聊使用。');
    quit();
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
if($is_cmd == true) 
{
    $cmd[0] = strtolower($cmd[0]);
    switch($cmd[0])
    {
        case '/start':
        echo "Command: /start\r\n";
        require_once('./commands/start.php');
        break;

        case '/new':
        echo "Command: /new\r\n";
        require_once('./commands/new.php');
        break;

        case '/cancel':  // cancel when creating a new lottery 
        echo "Command: /cancel\r\n";
        require_once('./commands/cancel.php');
        break;

        case '/my':  // 查看当前的抽奖
        echo "Command: /my\r\n";
        require_once('./commands/my.php');
        ReplyMessage($t,false,$buttons);
        quit();
        break;

        default:
        echo "Undefind Command\r\n";
        ReplyMessage('未知指令');
        break;
    }
}


//====================================================================================
if($is_cmd == false)
{
    echo "Plain Text\r\n";
    PlainText($from,$text);  // plain text, maybe in the session.
}

$output = ob_get_clean();  // save output

if($output == '') exit();

$ndate = date('Ymd');
$ntime = date('His');

if(!isset($from->username)) $user = $from->id;
else $user = $from->username;

if(file_exists("./log/$ndate/$ntime.log"))
{
    $i = 2;
    while(!file_exists("./log/$ndate/$ntime-$user-$i.log")) $i++;
    $filename = "$ntime-$user-$i.log";
}
else
{
    $filename = "$ntime-$user.log";
}

if(!is_dir("./log/$ndate/"))
{
    mkdir("./log/$ndate/");
    copy('./log/index.php',"./log/$ndate/index.php");
}
file_put_contents("./log/$ndate/$filename",$output);
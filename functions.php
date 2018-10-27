<?php
// Lottery Bot functions

//============================ Telegram APIs ==================================
// any method
function TelegramAPI($method,array $d)
{
    // Send Message
    global $config,$message;
    $token = $config['bot_token'];

    /*
    $d = array(
		'chat_id' => $message->chat->id,
        'text' => $msg,
        'disable_web_page_preview' => true,
        'parse_mode' => 'html'
    );
    */
    $d = json_encode($d);

    CurlSend:
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot$token/$method");
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: '.strlen($d)
	));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $d);
    curl_setopt($ch, CURLOPT_TIMEOUT, $config['curl_timeout']);
	
	$res = curl_exec($ch);
	curl_close($ch);
    $return = json_decode($res);

    if($return->ok === true) return true;
    if(isset($error_report) && $error_report === true) return false;

    $d = json_encode(array(
		'chat_id' => $message->chat->id,
        'text' => "<b>API Error when SendMessage</b>\r\n".print_r($return,true),
        'disable_web_page_preview' => true,
        'parse_mode' => 'html'
    ));
    file_put_contents('./ErrorReport-SendMessage-'.time().'.txt',"API Error\r\n".print_r($return,true));
    $error_report = true;
    goto CurlSend;
    return false;
}

// sendMessage
function ReplyMessage($msg,$by_reply = false,$reply_markup = false,$chat_id = false)
{
    // Send Message
    global $config,$message;
    $token = $config['bot_token'];

    $d = array(
		'chat_id' => $message->chat->id,
        'text' => $msg,
        'disable_web_page_preview' => true,
        'parse_mode' => 'html'
    );

    if($by_reply == true) $d['reply_to_message_id'] = $message->message_id;
    if($reply_markup !== false) $d['reply_markup'] = $reply_markup;
    if($chat_id !== false) $d['chat_id'] = $chat_id;

    $d = json_encode($d);

    CurlSend:
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot$token/sendMessage");
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: '.strlen($d)
	));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $d);
    curl_setopt($ch, CURLOPT_TIMEOUT, $config['curl_timeout']);
	
	$res = curl_exec($ch);
	curl_close($ch);
    $return = json_decode($res);
    
    if($return->ok === true) return true;
    if(isset($error_report) && $error_report === true) return false;

    $d = json_encode(array(
		'chat_id' => $message->chat->id,
        'text' => "<b>API Error when SendMessage</b>\r\n".print_r($return,true),
        'disable_web_page_preview' => true,
        'parse_mode' => 'html'
    ));
    file_put_contents('./ErrorReport-SendMessage-'.time().'.txt',"API Error\r\n".print_r($return,true));
    $error_report = true;
    goto CurlSend;
    return false;
}

// editMessageText
function EditMessage($msg,$msgid,$reply_markup = false,$chat_id = false)
{
    // Send Message
    global $config,$message;
    $token = $config['bot_token'];

    $d = array(
        'chat_id' => $message->chat->id,
        'message_id' => $msgid,
        'text' => $msg,
        'disable_web_page_preview' => true,
        'parse_mode' => 'html'
    );

    if($reply_markup !== false) $d['reply_markup'] = $reply_markup;
    if($chat_id !== false) $d['chat_id'] = $chat_id;

    $d = json_encode($d);

    CurlSend:
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot$token/editMessageText");
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: '.strlen($d)
	));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $d);
    curl_setopt($ch, CURLOPT_TIMEOUT, $config['curl_timeout']);
	
	$res = curl_exec($ch);
	curl_close($ch);
    $return = json_decode($res);
    
    if($return->ok === true) return true;
    if(isset($error_report) && $error_report === true) return false;

    $d = json_encode(array(
		'chat_id' => $message->chat->id,
        'text' => "<b>API Error when editMessageText</b>\r\n".print_r($return,true),
        'disable_web_page_preview' => true,
        'parse_mode' => 'html'
    ));
    file_put_contents('./ErrorReport-editMessageText-'.time().'.txt',"API Error\r\n".print_r($return,true));
    $error_report = true;
    goto CurlSend;
    return false;
}


//============================= Database ==================================
// Get MySQL Connection
function GetDbConn()
{
    global $config;
    $c = new mysqli($config['db_host'],$config['db_user'],$config['db_pass'],$config['db_name']);
    $c->set_charset($config['db_char']);
    return $c;
}


//============================== Logics ===================================

// Deal the plaun text
function PlainText($from,$text)
{
    if(!file_exists("./sessions/$from->id.json"))
    {
        if(file_exists("./sessions/confirm/$from->id.json"))
        {
            goto CheckConfirm;
        }
        else
        {
            ReplyMessage('您当前没有 session 进行中');
            exit();
        }
    }
    
    //========== 创建投票 ==========
    /*
    Step 1: give me the title
    Step 2: give me some details
    Step 3: give me amount of winner
    Step 4: confirm
    */
    $user = json_decode(file_get_contents("./sessions/$from->id.json"));
    switch($user->step)
    {
        case 1:  // Step 1: give me the title
        $user->title = $text;
        $user->step = 2;
        file_put_contents("./sessions/$from->id.json",json_encode($user));
        ReplyMessage("标题设置成功: <code>$user->title</code>\r\n接下来请发送给我抽奖详情，或发送 /cancel 取消抽奖。");
        exit();
        break;
    
        case 2:  // Step 2: give me some details
        $user->details = $text;
        $user->step = 3;
        file_put_contents("./sessions/$from->id.json",json_encode($user));
        ReplyMessage("抽奖详情设置成功: <code>$user->details</code>\r\n接下来请发送给我中奖人数，或发送 /cancel 取消抽奖。");
        exit();
        break;
    
        case 3:  // Step 3: give me amount of winner
        if(!is_numeric($text))
        {
            ReplyMessage('您输入的不是数字！请重新输入，或发送 /cancel 取消抽奖。');
            exit();
        }
        $user->amount = (int)$text;
        $user->step = 4;
        file_put_contents("./sessions/$from->id.json",json_encode($user));
        ReplyMessage("设置完成，详情如下：\r\n抽奖标题: <code>$user->title</code>\r\n抽奖详情：\r\n<code>$user->details</code>\r\n奖品份数: <code>$user->amount</code>\r\n\r\n确认以上信息？(y/n)\r\n发送 <code>n</code> 或 /cancel 取消抽奖。");
        if($user->amount > 25) ReplyMessage('注意：您设定的奖品超过25个，为了防止文字内容超限，开奖时将不会显示所有用户名，通知照常。');
        exit();
        break;
    
        case 4:  // Step 4: confirm
        $text = strtolower($text);
        switch($text)
        {
    
            case 'y':  // Confirm, write to DB
            $c = GetDbConn();
            $id = $c->query("SELECT max(id) FROM `lottery_list`")->fetch_assoc()['max(id)'] + 1;
            $number = 114514000000 + $id;
            $timestamp = time();
            $key = $config['key'];
    
            $token = hash('SHA256',"$number$timestamp-$user->title$key$from->id");
    
            $nrsp[1] = $c->query("INSERT INTO `lottery_list` (`id`, `number`, `token`, `title`, `details`, `prize`, `req_uid`, `req_username`, `req_firstname`, `timestamp`) VALUES ('$id', '$number', '$token', '$user->title', '$user->details', '$user->amount', '$from->id', '$from->username', '$from->first_name','$timestamp')");
            if($nrsp[1] == false)
            {
                ReplyMessage("内部错误，Bot Error 01: $c->error");
                exit();
            }
            $nrsp[2] = $c->query("CREATE TABLE `$number` LIKE `lottery_tpl`");
            if($nrsp[2] == false)
            {
                ReplyMessage("内部错误，Bot Error 02: $c->error");
                exit();
            }
            ReplyMessage("欢迎加入 $from->first_name 创建的抽奖：\r\n唯一抽奖ID: $number\r\n抽奖标题: $user->title\r\n抽奖详情: $user->details\r\n抽奖份数: $user->amount\r\n\r\n<a href=\"https://t.me/tgLotteryBot?start=$token\">点击加入</a>");
            unlink("./sessions/$from->id.json");
            exit();
            break;
    
    
            case 'n':  // remove session
            unlink("./sessions/$from->id.json");
            ReplyMessage('Canceled.');
            break;
    
    
    
            default:
            ReplyMessage('无法识别您的输入，请回复 y/n 或 /cancel');
            exit();
            break;
        }
        break;
    }
    exit();


    CheckConfirm:
    $j = json_decode(file_get_contents("./sessions/confirm/$from->id.json"));
    switch($j->type)
    {
        case 'delete':
        $text = strtolower($text);
        switch($text)
        {
            case 'y':
            $c = GetDbConn();
            $rs = $c->query("UPDATE `lottery_list` SET `closed` = '1' WHERE `number` = $j->number");
            if($rs === false)
            {
                ReplyMessage("内部错误，Bot Error 12: $c->error");
                exit();
            }
            unlink("./sessions/confirm/$from->id.json");
            ReplyMessage('投票删除成功');
            break;

            case 'n':
            unlink("./sessions/confirm/$from->id.json");
            ReplyMessage('已取消');
            exit();
            break;

            default:
            ReplyMessage('您的输入有误，请输入 <code>y</code> 确认或输入 <code>n</code> 取消。');
            exit();
            break;
        }
        break;

        case 'lottery':
        $text = strtolower($text);
        switch($text)
        {
            case 'y':
            Lottery($j->number);
            unlink("./sessions/confirm/$from->id.json");
            exit();
            break;

            case 'n':
            unlink("./sessions/confirm/$from->id.json");
            ReplyMessage('已取消开奖');
            exit();
            break;

            default:
            ReplyMessage('您的输入有误，请输入 <code>y</code> 确认或输入 <code>n</code> 取消。');
            exit();
            break;
        }
        break;
    }
}


//============================== Lottery ===================================

function Lottery($number)
{
    ReplyMessage("正在尝试开奖并通知中奖者，请稍候...");

    //Debug('Lottery-1');

    $c = GetDbConn();
    $rs = $c->query("SELECT max(id) FROM `$number`");
    if($rs === false)
    {
        ReplyMessage("内部错误，Bot Error 20: $c->error");
        exit();
    }
    $joined = $rs->fetch_assoc()['max(id)'];

    //Debug('Lottery-2');

    // get lottery's info
    $rs = $c->query("SELECT * FROM `lottery_list` WHERE `number` = '$number'");
    if($rs === false)
    {
        ReplyMessage("内部错误，Bot Error 21: $c->error");
        exit();
    }

    //Debug('Lottery-3');

    while($row = $rs->fetch_assoc())
    {
        $title = $row['title'];
        $details = $row['details'];
        $prize = $row['prize'];
        $req_uid = $row['req_uid'];
        $req_username = $row['req_username'];
        $req_firstname = $row['req_firstname'];
        if($prize > 25) $big_lottery = true;
        else $big_lottery = false;
    }

    if($joined < $prize)
    {
        ReplyMessage("开奖失败，已经加入抽奖的 $joined 人小于您设置的 $prize 份奖品！");
        exit();
    }

    //Debug('Lottery-5');

    // random join id
    $sql = "SELECT * FROM `$number` WHERE";
    for($i = 1; $i <= $prize; $i++)
    {
        RandomAgain:
        //Debug('Lottery-6-random');
        $rand = random_int(1,$joined);
        if(in_array($rand,$winner) == true)
        {
            goto RandomAgain;
        }
        $winner[$i] = $rand;
        $sql .= " `id` = $rand OR";
    }
    $sql .= "DER BY `user_id` ASC";  // lol

    $rs = $c->query($sql);
    if($rs === false)
    {
        ReplyMessage("内部错误，Bot Error 22: $c->error");
        exit();
    }

    //Debug('Lottery-7');

    $t = "开奖成功！\r\n抽奖名称: <b>$title</b>\r\n创建者: <a href=\"tg://user?id=$req_uid\">$req_firstname</a>\r\n抽奖详情:\r\n<b>$details</b>\r\n奖品份数: <b>$prize 份</b>\r\n唯一抽奖ID: <b>$number</b>\r\n恭喜以下参与者中奖:\r\n";
    while($row = $rs->fetch_assoc())
    {
        $uid = $row['user_id'];
        $first_name = $row['first_name'];
        //=================== update ====================
        $c->query("UPDATE `$number` SET `win` = '1' WHERE `user_id` = $uid");
        //=================== message ===================
        if($row['username'] !== '')
        {
            $t .= '@'.$row['username']."\r\n";
        }
        else
        {
            $t .= "<a href=\"tg://user?id=$uid\">$first_name</a>\r\n";
        }

        //=================== notify ====================
        CallWinner(
            $number,
            $title,
            $details,
            $prize,
            $row['user_id'],
            $row['first_name'],
            $req_uid,
            $req_username,
            $req_firstname
        );
    }

    $t .= "\r\n已经PM通知获奖者，请尽快领奖";

    // ad
    $t .= "\r\n\r\nPowered By <a href=\"https://azuki.cloud\">Azuki Cloud</a>";

    // Update Lottery Status
    $rs = $c->query("UPDATE `lottery_list` SET `extracted` = '1' WHERE `number` = $number");
    if($rs === false)
    {
        ReplyMessage("内部错误，更新投票状态失败，Bot Error 23: $c->error");
        exit();
    }

    ReplyMessage($t);
}

// Call Winner
function CallWinner($number,$title,$details,$prize,$uid,$firstname,$req_uid,$req_username,$req_firstname)
{
    $msg = "$firstname, 恭喜你中奖！\r\n中奖详情如下:\r\n抽奖标题: <b>$title</b>\r\n抽奖详情:\r\n<b>$details</b>\r\n唯一抽奖ID: <code>$number</code>\r\n请及时按照约定方式或联系发起者 <a href=\"tg://user?id=$req_uid\">$req_firstname</a> 领奖。";
    // AD
    $msg .= "\r\n\r\nPowered By <a href=\"https://azuki.cloud\">Azuki Cloud</a>";
    ReplyMessage($msg,false,false,$uid);
}


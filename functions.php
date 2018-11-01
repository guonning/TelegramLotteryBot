<?php
// Lottery Bot functions

//=============================== Actions =====================================
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
    return $res;
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


function CloudVisionApi($tg_file_id)
{
    global $config;
    $token = $config['bot_token'];
    $api_key = $config['vision_api_key'];

    // get img file path
    $getfile = file_get_contents("https://api.telegram.org/bot$token/getfile?file_id=$tg_file_id");
    if($getfile === false || json_decode($getfile)->ok != true) return false;
    $tg_file_path = json_decode($getfile)->result->file_path;

    $img_url = "https://tgapi.azuki.cloud/file/bot$token/$tg_file_path";
    //$req = '{"requests":[{"image":{"source":{"imageUri":"'.$img_url.'"}},"features":[{"type":"LABEL_DETECTION"},{"type":"SAFE_SEARCH_DETECTION"}]}]}';

    $base64 = file_get_contents($config['base_url']."/img/ImageToBase64.php?url=$img_url");

    $req = '{"requests":[{"image":{"content": "'.$base64.'"},"features":[{"type":"LABEL_DETECTION"},{"type":"SAFE_SEARCH_DETECTION"}]}]}';
    $return = PostJson($req,"https://vision.googleapis.com/v1/images:annotate?key=$api_key");
    return $return;
}

function PostJson($json,$url)
{
    global $config;
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: '.strlen($json)
	));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_TIMEOUT, $config['curl_timeout']);
	
	$res = curl_exec($ch);
    curl_close($ch);
    
    return $res;
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
            ReplyMessage('不支持的消息类型 或 当前没有 session 进行中');
            exit();
        }
    }
    
    //========== 创建投票 ==========
    /*
    Step 1: give me the title
    Step 2: give me some details
    Step 3: give me amount of winner
    Step 4: smart probability control
    Step 5: confirm
    */
    $user = json_decode(file_get_contents("./sessions/$from->id.json"));
    switch($user->step)
    {
        case 1:  // Step 1: give me the title
        $user->title = $text;
        $user->step = 2;
        if(!file_put_contents("./sessions/$from->id.json",json_encode($user)))
        {
            ReplyMessage("内部错误 Bot Error 101: 无法写入session");
            exit();
        }
        ReplyMessage("标题设置成功: <code>$user->title</code>\r\n接下来请发送给我抽奖详情，或发送 /cancel 取消抽奖。");
        exit();
        break;
    
        case 2:  // Step 2: give me some details
        $user->details = $text;
        $user->step = 3;
        if(!file_put_contents("./sessions/$from->id.json",json_encode($user)))
        {
            ReplyMessage("内部错误 Bot Error 102: 无法写入session");
            exit();
        }
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

        $t = "已设置奖品数: $user->amount 份".PHP_EOL .
        "是否启用智能中奖概率控制？(y/n)".PHP_EOL .
        "该功能测试中！\r\n<a href=\"https://open.azuki.cloud/AzukiLotteryBot/docs/smart-probability-control.html\">了解更多</a>";

        if(!file_put_contents("./sessions/$from->id.json",json_encode($user)))
        {
            ReplyMessage("内部错误 Bot Error 103: 无法写入session");
            exit();
        }
        ReplyMessage($t);
        exit();
        break;
    
        case 4:  // Step 4: smart probability control
        switch($text)
        {
            case 'y':
            $user->smart = true;
            break;

            case 'n':
            $user->smart = false;
            break;

            default:
            ReplyMessage('无法识别您的输入，请回复 y/n 或取消 /cancel');
            exit();
            break;
        }
        $user->step = 5;
        if(!file_put_contents("./sessions/$from->id.json",json_encode($user)))
        {
            ReplyMessage("内部错误 Bot Error 104: 无法写入session");
            exit();
        }

        if($user->smart == true)
        {
            $smart = '开启';
        }
        else
        {
            $smart = '关闭';
        }

        $t = '设置完成，详情如下：' . PHP_EOL .
        "抽奖标题: <code>$user->title</code>" . PHP_EOL .
        "抽奖详情：\r\n<code>$user->details</code>" . PHP_EOL .
        "奖品份数: <code>$user->amount</code>".PHP_EOL .
        "智能概率: $smart\r\n\r\n";

        if($user->amount > 25) $t .= "注意：您设定的奖品超过 25 个，为了防止文字内容超限，开奖时将不会显示所有用户名，通知照常。\r\n\r\n";
        $t .= "确认以上信息？(y/n)\r\n发送 <code>n</code> 或 /cancel 取消抽奖。";

        ReplyMessage($t);
        exit();
        break;

        case 5:  // Step 5: confirm
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
    
            $nrsp[1] = $c->query("INSERT INTO `lottery_list` (`id`, `number`, `token`, `title`, `details`, `prize`, `smart`, `req_uid`, `req_username`, `req_firstname`, `timestamp`) VALUES ('$id', '$number', '$token', '$user->title', '$user->details', '$user->amount', '$user->smart', '$from->id', '$from->username', '$from->first_name','$timestamp')");
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

            $t = "欢迎加入 $from->first_name 创建的抽奖". PHP_EOL .
            "唯一抽奖ID: $number".PHP_EOL.
            "抽奖标题: $user->title".PHP_EOL.
            "抽奖详情: $user->details".PHP_EOL.
            "抽奖份数: $user->amount".PHP_EOL.PHP_EOL.
            "<a href=\"https://t.me/tgLotteryBot?start=$token\">点击加入</a>";

            ReplyMessage($t);
            unlink("./sessions/$from->id.json");
            exit();
            break;
    
    
            case 'n':  // remove session
            unlink("./sessions/$from->id.json");
            ReplyMessage('已取消');
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
    ReplyMessage("正在尝试开奖并通知中奖者，这可能需要一些时间，请稍候...");

    $c = GetDbConn();
    $rs = $c->query("SELECT max(id) FROM `$number`");
    if($rs === false)
    {
        ReplyMessage("内部错误，Bot Error 20: $c->error");
        exit();
    }
    $joined = $rs->fetch_assoc()['max(id)'];

    // get lottery's info
    $rs = $c->query("SELECT * FROM `lottery_list` WHERE `number` = '$number'");
    if($rs === false)
    {
        ReplyMessage("内部错误，Bot Error 21: $c->error");
        exit();
    }

    while($row = $rs->fetch_assoc())
    {
        $title = $row['title'];
        $details = $row['details'];
        $prize = $row['prize'];
        $smart = $row['smart'];
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

    $sql = "SELECT * FROM `$number` WHERE";  // sql perfix

    if($smart == false)
    {
        // random join id
        for($i = 1; $i <= $prize; $i++)
        {
            RandomAgain:
            $rand = random_int(1,$joined);
            if(in_array($rand,$winner) == true)
            {
                goto RandomAgain;
            }
            $winner[$i] = $rand;

            $sql .= " `id` = $rand OR";  // add to sql statement
        }
    }
    elseif($smart == true)
    {
        // 智能概率控制
        $rs = $c->query("SELECT * FROM `$number`");
        if($rs === false)
        {
            ReplyMessage("内部错误，Bot Error 131: $c->error");
            exit();
        }

        $users = array();
        $i = 0;
        while($row = $rs->fetch_assoc())
        {
            $users[$i]['id'] = $row['id'];
            //$users[$i]['user_id'] = $row['user_id'];
            //$users[$i]['username'] = $row['username'];
            //$users[$i]['first_name'] = $row['first_name'];
            //$users[$i]['last_name'] = $row['last_name'];
            $users[$i]['prob'] = $row['probability'];
            //$users[$i]['join_time'] = $row['join_time'];
            //$users[$i]['lang_code'] = $row['lang_code'];
        }

        $winners = array();
        for($i = 1; $i <= $prize; $i++)
        {
            SmartRandomAgain:
            $winner = LotteryWithWeight($users);

            // 如果已经中奖一次则重抽
            if(array_search($winner->id, array_column($winners, 'id')) !== false) goto SmartRandomAgain;
            
            array_push($winners,$winner);

            $sql .= ' `id` = '.$winner['id'].' OR';  // add to sql statement
        }

    }

    $sql .= "DER BY `user_id` ASC";  // sql suffix, lol
    $rs = $c->query($sql);
    if($rs === false)
    {
        ReplyMessage("内部错误，Bot Error 22: $c->error");
        exit();
    }

    $t = "开奖成功！\r\n" .
    "抽奖名称: <b>$title</b>\r\n" .
    "创建者: <a href=\"tg://user?id=$req_uid\">$req_firstname</a>\r\n" .
    "抽奖详情:\r\n<b>$details</b>\r\n" .
    "奖品份数: <b>$prize 份</b>\r\n" .
    "唯一抽奖ID: <b>$number</b>\r\n" .
    "恭喜以下参与者中奖:\r\n";
    
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
    $t .= "\r\n\r\n<i>Powered By Azuki Cloud</i>";

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
    $msg .= "\r\n\r\nPowered By <a href=\"https://azuki.cloud/analytics.php?from=LotteryBot\">Azuki Cloud</a>";
    ReplyMessage($msg,false,false,$uid);
}

function LotteryWithWeight($arr)
{
    // Learn from https://www.jianshu.com/p/70c33bec2077
	$probSum = 0;
	foreach($arr as $value)
	{
		$probSum += $value['prob'];
	}

	if ($probSum <= 0)
	{
		return;
	}

	//初始化对象池， 相等于抽奖箱
	$pool = array();

	foreach ($arr as $v)
	{
		for ($i = 0; $i <= $v['prob']; $i++)
		{
			$pool[] = $v;
		}
	}

	//打乱数组
	shuffle($pool);

	//抽奖
	$randNum = rand(1, $probSum);
	return $pool[$randNum - 1];
}
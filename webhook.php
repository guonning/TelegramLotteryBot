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

    // is it a cmd ? - OK
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
    //====================================================================================

    
    // deving functions

    function ButtonCallback($callback_query)
    {
        // deal InlineKeyboardButton's callback query
        global $config;
        $text = false;  // Text of the notification, can be unset.
        $alert = false;  // show alert, not notification

        $dt = explode(':',$callback_query->data);
        $number = $dt[1];        
        $from = $callback_query->from;
        $msg_id = $callback_query->message->message_id;
        $hash = $dt[2];

        if($hash != md5($number.$from->id.$config['key']))
        {
            $alert = true;
            $text = "鉴权失败: $callback_query->data\r\nFrom User: $from->id";
            goto answerCallbackQuery;
        }

        // stat etc.
        switch($dt[0])
        {

            //========================================
            case 'stat':
            $c = GetDbConn();

            $rs = $c->query("SELECT * FROM `lottery_list` WHERE `number` = '$number'");
            if($rs === false)
            {
                $alert = true;
                $text = "内部错误，Bot Error 07:\r\n$c->error";
                goto answerCallbackQuery;
            }
            elseif($rs->num_rows === 0)
            {
                $alert = true;
                $text = "未找到抽奖？？我也不知道发生了啥反正没找着\r\n内部错误，Bot Error 08";
                goto answerCallbackQuery;
            }

            $t = "$from->first_name 创建的抽奖: ";
            while($row = $rs->fetch_assoc())
            {
                $t .= '<b>'.$row['title']."</b>\r\n抽奖详情：\r\n<b>".$row['details']."</b>\r\n共抽取 <b>".$row['prize']."</b> 份奖品。\r\n唯一抽奖ID: <b>$number</b>\r\n\r\n<a href=\"https://t.me/tgLotteryBot?start=".$row['token']."\">点击加入</a>\r\n";
            }


            $rs = $c->query("SELECT * FROM `$number`");
            if($rs === false)
            {
                $alert = true;
                $text = "内部错误，Bot Error 09:\r\n$c->error";
                goto answerCallbackQuery;
            }
            elseif($rs->num_rows === 0)
            {
                $t .= '当前还没有人参加抽奖哦~';
            }
            else
            {
                $t .= "当前有 $rs->num_rows 人参与了抽奖";

                $i = 0;
                while($row = $rs->fetch_assoc())
                {
                    $json[$i] = array(
                        'id' => $row['id'],
                        'user_id' => $row['user_id'],
                        'username' => $row['username'],
                        'first_name' => $row['first_name'],
                        'last_name' => $row['last_name'],
                        'join_time' => date('Y-m-d H:i:s',$row['join_time']),
                        'lang_code' => $row['lang_code']
                    );
                    $i++;
                }
            
            }

            $json = json_encode($json);

            file_put_contents("./sessions/details/$hash.json",$json);

            $buttons[0] = array(
                'text' => '开奖',
                'callback_data' => 'lottery:'.$number.':'.hash('MD5',$number.$from->id.$config['key'])
            );

            $buttons[1] = array(
                'text' => '取消抽奖',
                'callback_data' => 'delete:'.$number.':'.hash('MD5',$number.$from->id.$config['key'])
            );

            $buttons[2] = array(
                'text' => '查看详情',
                'url' => $config['base_url']."/details/$number/$hash"
            );

            $buttons[3] = array(
                'text' => '返回',
                'callback_data' => 'list:'.$number.':'.hash('MD5',$number.$from->id.$config['key'])
            );

            $btn = json_encode(array('inline_keyboard'=>array($buttons)));
            EditMessage($t,$msg_id,$btn,$from->id);
            break;
            

            //========================================
            case 'lottery':
            $c = GetDbConn();
            $rs = $c->query("SELECT `number`,`title` FROM `lottery_list` WHERE `number` = $number AND `closed` = 0 AND `extracted` = 0");
            if($rs === false)
            {
                ReplyMessage("内部错误，Bot Error 10:\r\n$c->error",false,false,$from->id);
                goto answerCallbackQuery;
            }
            elseif($rs->num_rows === 0)
            {
                $t = '该抽奖不存在 / 已开奖 / 已取消！';
            }
            else
            {   
                while($row = $rs->fetch_assoc())
                {
                    $t = '开奖确认: '.$row['title'].' (ID <code>'.$row['number']."</code>)\r\n";
                }
                $t .= "当前有 $rs->num_rows 人参与了抽奖。\r\n\r\n确定开奖吗？(y/n)";
                $se = array(
                    'type' => 'lottery',
                    'number' => $number
                );
                $se = json_encode($se);
                file_put_contents("./sessions/confirm/$from->id.json",$se);
            }
            EditMessage($t,$msg_id,false,$from->id);
            break;


            //========================================
            case 'delete':
            $c = GetDbConn();
            $rs = $c->query("SELECT `number`,`title` FROM `lottery_list` WHERE `number` = $number AND `extracted` = 0 AND `closed` = 0");
            if($rs === false)
            {
                ReplyMessage("内部错误，Bot Error 11:\r\n$c->error",false,false,$from->id);
                exit();
            }
            elseif($rs->num_rows === 0)
            {
                $t = '该抽奖不存在 / 已开奖 / 已取消！';
            }
            else
            {
                while($row = $rs->fetch_assoc())
                {
                    $t = '删除确认: '.$row['title'].' (ID <code>'.$row['number']."</code>)\r\n";
                }
                $t .= "当前有 $rs->num_rows 人参与了抽奖。\r\n\r\n确定要取消并删除该抽奖？(y/n)";
                $se = array(
                    'type' => 'delete',
                    'number' => $number
                );
                $se = json_encode($se);
                file_put_contents("./sessions/confirm/$from->id.json",$se);
            }
            EditMessage($t,$msg_id,false,$from->id);
            break;


            //========================================
            case 'list':
            require_once('./commands/my.php');
            EditMessage($t,$msg_id,$buttons,$from->id);
            break;



        }  



        //debug
        //$text = 'Success';

        answerCallbackQuery:
        
        $d = array(
            'callback_query_id' => $callback_query->id,
        );
        if($text !== false) $d['text'] = $text;
        if($alert === true) $d['show_alert'] = true;
        TelegramAPI('answerCallbackQuery',$d);
        exit();
    }
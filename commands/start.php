<?php
if(isset($cmd[1]))
{
    //============================= initial ================================
    // find lottery and join
    $c = GetDbConn();
    $token = $cmd[1];
    $prob = 1.00000000;
    $timestamp = time();

    $rs = $c->query("SELECT * FROM `lottery_list` WHERE `token` = '$token'");

    if($rs === false)
    {
        ReplyMessage("内部错误，Bot Error 03: $c->error");
        quit();
    }
    elseif($rs->num_rows === 0)
    {
        ReplyMessage('未找到该抽奖');
        quit();
    }

    while($row = $rs->fetch_assoc())
    {
        if($row['extracted'] == true)
        {
            ReplyMessage('该抽奖已经结束！'); 
            quit();
        }
        elseif($row['closed'] == true)
        {
            ReplyMessage('该抽奖已经关闭！'); 
            quit();
        }
        $number = $row['number'];
        $title = $row['title'];
        //$details = $row['details'];
        //$prize = $row['prize'];
        $smart = $row['smart'];
    }


    //======================================================================
    $rs = $c->query("SELECT `user_id` FROM `$number` WHERE `user_id` = '$from->id'");

    if($rs === false)
    {
        ReplyMessage("内部错误，Bot Error 04: $c->error");
        quit();
    }
    elseif($rs->num_rows !== 0)
    {
        ReplyMessage('你已经参加过这个抽奖了，请等待开奖~');
        quit();
    }


    //======================== Reply Message ==========================
    ReplyMessage("成功参加抽奖 $title , 抽奖ID: $number, 请等待开奖。");


    //==================== Smart Probability Control =======================
    if($smart != true) goto SmartOff;

    if(!is_dir("./spc-log/$number/")) mkdir("./spc-log/$number/");

    $log = "================ Smart Probability Control Log - Start ================\r\n".
    "----------------- User Info -----------------\r\n".
    "User ID: $from->id\r\n".
    "Username: $from->username\r\n".
    "First Name: $from->first_name\r\n".
    "Last Name: $from->last_name\r\n".
    "Language: $from->language_code\r\n".
    "Time: ".date('Y-m-d H:i:s')."\r\n".
    "Timestamp: $timestamp\r\n".
    "Initial Probability: $prob\r\n\r\n".

    "---------- Probability Calculation ----------\r\n";

    // username
    $log .= 'Username: ';
    if($from->username == '')
    {
        $prob = $prob - 0.265;  // 如果未设置用户名
        $log .= "Unset, Prob = $prob\r\n";
    }
    else
    {
        $log .= "Set, Prob = $prob\r\n";
    }

    // user id
    if($from->id < 300000000) $prob = $prob + 0.215;
    if($from->id < 400000000) $prob = $prob + 0.132;
    if($from->id < 500000000) $prob = $prob + 0.096;
    if($from->id < 600000000) $prob = $prob + 0.072;
    $log .= "UID: $from->id, Prob = $prob\r\n";

    // get profile picture
    $pic_info = TelegramAPI('getUserProfilePhotos',array('user_id'=>$from->id));
    $pic_info = json_decode($pic_info);
    if($pic_info->ok !== true)
    {
        ReplyMessage('内部错误，Bot Error 133');
        quit();
    }

    $pic_count = $pic_info->result->total_count;

    if($pic_count >= 2) $prob = $prob + 0.04;
    if($pic_count == 0) $prob = $prob - 0.435;
    $log .= "Profile Photos Count: $pic_count, Prob = $prob\r\n\r\n";

    // Cloud Vision API
    if($config['enable_vision_api'] !== true || $pic_count == 0) goto VisionEnd;

    $photos = $pic_info->result->photos;
    $file_id = $photos[0][2]->file_id;

    $log .= "Uploading user's recent profile photo to Google Cloud Vision API...\r\n";
    $vision = CloudVisionApi($file_id);
    if($vision === false)
    {
        ReplyMessage("内部错误，Vision API Request Failed (Bot Error 121)，请尝试重新点击链接加入抽奖。");
        quit();
    }

    $vision = json_decode($vision);
    $labels = $vision->responses[0]->labelAnnotations;
    $safety = $vision->responses[0]->safeSearchAnnotation;

    if(isset($vision->error))
    {
        ReplyMessage("内部错误，Bot Error 135: Vision API Error\r\n".$vision->error->message);
        quit();
    }

    //------------- preferred label -------------
    $perfer[1] = array_search('anime',array_column($labels,'description')); // personal preference, 不服咬我略略略
    $perfer[2] = array_search('cartoon',array_column($labels,'description'));
    $perfer[3] = array_search('illustration',array_column($labels,'description'));

    // "anime" label
    if($perfer[1] !== false && $labels[$perfer[1]]->score >= 0.7)
    {
        $prob = $prob + (0.29 * $labels[$perfer[1]]->score);
        $log .= "Detected profile photo containing \"Anime\", Prob = $prob\r\n";
    }

    // "cartoon" label
    if($perfer[2] !== false && $labels[$perfer[2]]->score >= 0.72)
    {
        $prob = $prob + (0.22 * $labels[$perfer[2]]->score);
        $log .= "Detected profile photo containing \"Cartoon\", Prob = $prob\r\n";
    }

    // illust
    if($perfer[3] !== false && $labels[$perfer[3]]->score >= 0.63)
    {
        $prob = $prob + (0.1 * $labels[$perfer[3]]->score);
        $log .= "Detected profile photo containing \"Illustration\", Prob = $prob\r\n";
    }


    //--------------- safe search ---------------
    // 色图是第一生产力！
    switch($safety->adult)
    {
        case 'VERY_LIKELY':
        $prob = $prob - 0.05;
        $log .= "\"Adult\" content of \"Very Likely\" detected, Prob = $prob\r\n";
        break;
    }

    // 欺骗
    switch($safety->spoof)
    {
        case 'POSSIBLE':
        $prob = $prob - 0.11;
        $log .= "\"Spoof\" content of \"Possible\" detected, Prob = $prob\r\n";
        break;

        case 'LIKELY':
        $prob = $prob - 0.23;
        $log .= "\"Spoof\" content of \"Likely\" detected, Prob = $prob\r\n";
        break;

        case 'VERY_LIKELY':
        $prob = $prob - 0.46;
        $log .= "\"Spoof\" content of \"Very Likely\" detected, Prob = $prob\r\n";
        break;
    }

    // 药物
    switch($safety->medical)
    {
        case 'POSSIBLE':
        $prob = $prob - 0.13;
        $log .= "\"Medical\" content of \"Possible\" detected, Prob = $prob\r\n";
        break;

        case 'LIKELY':
        $prob = $prob - 0.24;
        $log .= "\"Medical\" content of \"Likely\" detected, Prob = $prob\r\n";
        break;

        case 'VERY_LIKELY':
        $prob = $prob - 0.5;
        $log .= "\"Medical\" content of \"Very Likely\" detected, Prob = $prob\r\n";
        break;
    }

    // 暴力
    switch($safety->violence)
    {
        case 'POSSIBLE':
        $prob = $prob - 0.115;
        $log .= "\"Violence\" content of \"Possible\" detected, Prob = $prob\r\n";
        break;

        case 'LIKELY':
        $prob = $prob - 0.235;
        $log .= "\"Violence\" content of \"Likely\" detected, Prob = $prob\r\n";
        break;

        case 'VERY_LIKELY':
        $prob = $prob - 0.46;
        $log .= "\"Violence\" content of \"Very Likely\" detected, Prob = $prob\r\n";
        break;
    }

    // 不存在的
    switch($safety->racy) 
    {
        case 'POSSIBLE':
        $log .= "\"Racy\" content of \"Possible\" detected, but who cares? lol\r\n";
        break;

        case 'LIKELY':
        $log .= "\"Racy\" content of \"Likely\" detected, but who cares? lol\r\n";
        break;

        case 'VERY_LIKELY':
        $log .= "\"Racy\" content of \"Very Likely\" detected, but who cares? lol\r\n";
        break;
    }  

    $log .= "\r\n";

    VisionEnd:
    $prob = round($prob,4);
    $log .= "Final probability result: $prob\r\n\r\n".
    "================= Smart Probability Control Log - End =================";
    file_put_contents("./spc-log/$number/SPC-$timestamp-$from->first_name.log",$log);
    SmartOff:
    //======================================================================
    $rs = $c->query("INSERT INTO `$number` (`user_id`, `username`, `first_name`, `last_name`, `probability`, `join_time`, `lang_code`) VALUES ('$from->id', '$from->username', '$from->first_name', '$from->last_name', '$prob', '$timestamp', '$from->language_code')");

    if($rs === false)
    {
        ReplyMessage("内部错误，Bot Error 05: $c->error");
        quit();
    }
}
else
{
    ReplyMessage('欢迎使用本bot，输入 /new 发起一个新的抽奖。');
    exit('normal start');
}
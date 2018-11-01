<?php
if(isset($cmd[1]))
{
    //============================= initial ================================
    // find lottery and join
    $c = GetDbConn();
    $token = $cmd[1];
    $prob = 1.00000000;

    //======================================================================
    $rs = $c->query("SELECT * FROM `lottery_list` WHERE `token` = '$token'");

    if($rs === false)
    {
        ReplyMessage("内部错误，Bot Error 03: $c->error");
        exit();
    }
    elseif($rs->num_rows === 0)
    {
        ReplyMessage('未找到该抽奖');
        exit();
    }

    while($row = $rs->fetch_assoc())
    {
        if($row['extracted'] == true)
        {
            ReplyMessage('该抽奖已经结束！'); 
            exit();
        }
        elseif($row['closed'] == true)
        {
            ReplyMessage('该抽奖已经关闭！'); 
            exit();
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
        exit();
    }
    elseif($rs->num_rows !== 0)
    {
        ReplyMessage('你已经参加过这个抽奖了，请等待开奖~');
        exit();
    }


    //==================== Smart Probability Control =======================
    if($smart != true) goto SmartOff;

    // username
    if($from->username == '')
    {
        $prob = $prob - 0.065;  // 如果未设置用户名
    }

    // user id
    if($from->id < 300000000) $prob = $prob + 0.18765345;
    if($from->id < 400000000) $prob = $prob + 0.11215542;
    if($from->id < 500000000) $prob = $prob + 0.06800762;
    if($from->id < 600000000) $prob = $prob + 0.04897614;

    // get profile picture
    $pic_info = TelegramAPI('getUserProfilePhotos',array('user_id'=>$from->id));
    $pic_info = json_decode($pic_info);
    if($pic_info->ok !== true)
    {
        ReplyMessage('内部错误，Bot Error 133');
        exit();
    }

    $pic_count = $pic_info->result->total_count;

    if($pic_count >= 7) $prob = $prob + 0.06534523;
    if($pic_count >= 2) $prob = $prob + 0.03114514;
    if($pic_count == 0) $prob = $prob - 0.28999319;

    // Cloud Vision API
    if($config['enable_vision_api'] !== true || $pic_count == 0) goto VisionEnd;
    $photos = $pic_info->result->photos;
    $file_id = $photos[0][2]->file_id;

    $vision = CloudVisionApi($file_id);
    if($vision === false)
    {
        ReplyMessage("内部错误，Bot Error 121: Vision API Request Failed");
        exit();
    }

    $vision = json_decode($vision);
    $labels = $vision->responses[0]->labelAnnotations;
    $safety = $vision->responses[0]->safeSearchAnnotation;

    if(isset($vision->error))
    {
        ReplyMessage("内部错误，Bot Error 135: Vision API Error\r\n".$vision->error->message);
        exit();
    }

    //------------- preferred label -------------
    $perfer1 = array_search('anime',array_column($labels,'description')); // personal preference, 不服咬我略略略
    $perfer2 = array_search('cartoon',array_column($labels,'description'));
    $perfer3 = array_search('illustration',array_column($labels,'description'));

    // "anime" label
    if($perfer1 !== false && $labels[$perfer1]->score >= 0.7)
    {
        $prob = $prob + (0.29 * $labels[$perfer1]->score);
    }

    // "cartoon" label
    if($perfer2 !== false && $labels[$perfer2]->score >= 0.72)
    {
        $prob = $prob + (0.22 * $labels[$perfer2]->score);
    }

    // illust
    if($perfer3 !== false && $labels[$perfer3]->score >= 0.63)
    {
        $prob = $prob + (0.1 * $labels[$perfer3]->score);
    }


    //--------------- safe search ---------------
    // 色图是第一生产力！
    switch($safety->adult)
    {
        case 'VERY_LIKELY':
        $prob = $prob - 0.05114514;
        break;
    }

    // 欺骗
    switch($safety->spoof)
    {
        case 'POSSIBLE':
        $prob = $prob - 0.11451419;
        break;

        case 'LIKELY':
        $prob = $prob - 0.22498921;
        break;

        case 'VERY_LIKELY':
        $prob = $prob - 0.45673456;
        break;
    }

    // 药物
    switch($safety->medical)
    {
        case 'POSSIBLE':
        $prob = $prob - 0.12599198;
        break;

        case 'LIKELY':
        $prob = $prob - 0.24986921;
        break;

        case 'VERY_LIKELY':
        $prob = $prob - 0.49963742;
        break;
    }

    // 暴力
    switch($safety->violence)
    {
        case 'POSSIBLE':
        $prob = $prob - 0.12341919;
        break;

        case 'LIKELY':
        $prob = $prob - 0.23333810;
        break;

        case 'VERY_LIKELY':
        $prob = $prob - 0.46665678;
        break;
    }

    //switch($safety->racy)  // 不存在的

    VisionEnd:
    $prob = round($prob,8);
    SmartOff:
    //======================================================================
    $timestamp = time();
    $rs = $c->query("INSERT INTO `$number` (`user_id`, `username`, `first_name`, `last_name`, `probability`, `join_time`, `lang_code`) VALUES ('$from->id', '$from->username', '$from->first_name', '$from->last_name', '$prob', '$timestamp', '$from->language_code')");

    if($rs === false)
    {
        ReplyMessage("内部错误，Bot Error 05: $c->error");
        exit();
    }
    else
    {
        ReplyMessage("成功参加抽奖 $title , 唯一抽奖ID: $number, 请等待开奖。");
        exit();
    }
}
else
{
    ReplyMessage('欢迎使用本bot，输入 /new 发起一个新的抽奖。');
    exit('normal start');
}
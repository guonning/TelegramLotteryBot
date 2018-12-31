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
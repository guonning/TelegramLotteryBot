<?php
$c = GetDbConn();
$rs = $c->query("SELECT * FROM `lottery_list` WHERE `req_uid` = $from->id AND `extracted` = 0 AND `closed` = 0");
if($rs === false)
{
    ReplyMessage("内部错误，Bot Error 06: $c->error");
    quit();
}
elseif($rs->num_rows === 0)
{
    ReplyMessage('你还没有进行中的抽奖！');
    quit();
}

$t = "$from->first_name, 您当前有 $rs->num_rows 个抽奖进行中：\r\n\r\n";
$i = 0;
while($row = $rs->fetch_assoc())
{
    $t .= 'ID <code>'.$row['number']."</code>\r\n".$row['title']."\r\n".$row['prize']." 份奖品\r\n\r\n";

    $buttons[$i] = array(
        'text' => $row['title'],
        'callback_data' => 'stat:'.$row['number'].':'.hash('MD5',$row['number'].$from->id.$config['key'])
    );
    $i++;
}
$buttons = json_encode(array('inline_keyboard'=>array($buttons)));
<?php
if(file_exists("./sessions/$from->id.json"))
{
    ReplyMessage('你已经有一个创建中的投票，删除会话 /cancel');
}
else
{
    // create new session
    file_put_contents("./sessions/$from->id.json",json_encode(array(
        'step' => 1,  // step
        'title' => '',  // lottery title
        'details' => '',  // lottery details
        'amount' => 0,   //  amount of winner
        'smart' => false   //  smart probability control 
    )));
    ReplyMessage('开始创建投票，请输入抽奖标题，或发送 /cancel 取消抽奖。');
    quit();
}
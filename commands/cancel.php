<?php
if(file_exists("./sessions/$from->id.json"))
{
    DeleteSession:
    if(unlink("./sessions/$from->id.json") === true) ReplyMessage('Canceled.');
    else ReplyMessage('内部错误，取消失败。');
}
else
{
    ReplyMessage('目前没有创建中的抽奖。');
}
exit();
<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/12/9
 * Time: 16:13
 */

namespace app\api\service\message;

use app\lib\message\MessageTpl as MessageModel;
use app\lib\amazon\Profile;

class QuickReply
{
    /**
     * @param $sendID
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public function faq($sendID)
    {
        // 回复关键词 faq的quick reply
        $msg = "We just get started, more info will be updated later, thanks";
        return MessageModel::sendTextMessage($sendID, $msg);

    }

    /**
     * @param $sendID
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public function freebies($sendID)
    {
        $msg = "We just get started, freebies is just around the corner, info will get updated later, thanks";
        return MessageModel::sendTextMessage($sendID, $msg);
        // TODO 用户想要什么产品的提问 这里需要使用postback的方式了
    }


    /**
     * @param $sendID
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public function booking($sendID)
    {
        $msg = "Your are not book anything yet, thanks.";
        return MessageModel::sendTextMessage($sendID, $msg);
    }
}
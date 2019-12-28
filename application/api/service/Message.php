<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/6/17
 * Time: 17:37
 */

namespace app\api\service;

use app\api\model\Log;
use app\api\model\User as UserModel;

class Message
{

    public static function handleRead($senderID, $readCallback)
    {
        return true;
    }


    public static function handleDelivery($sendPSID, $delivery)
    {
        return true;
    }


    /**
     * @param $event
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function handleMessengerUserInfo($event)
    {
        // 通过用户optin获取用户messenger信息，创建用户帐号
        $senderID = $event['sender']['id'];
        $userTag = explode('_', $event['optin']['ref']);
        $msgInfoUrl = sprintf(config('fb.RETRIEVE_MESSENGER_INFO_API'), $senderID, config('fb.PAGE_ACCESS_TOKEN'));
        $res = curlHttp($msgInfoUrl, [], 'GET',  ['Content-Type: application/json'], false);

        $userMsgInfo = json_decode($res, true);
        if (!array_key_exists('error', $userMsgInfo)) {
            $user = UserModel::getUserByMsgID($userMsgInfo['id']);
            if (!$user) {
                $newUser = UserModel::create([
                    'message_id'    => $userMsgInfo['id'],
                    'name'          => $userMsgInfo['first_name']. ' '. $userMsgInfo['last_name'],
                    'avatar'        => $userMsgInfo['profile_pic'],
                    'tag'           => $userTag[0],
                ]);

                // 增加一条参与活动记录和，推送通知记录
                Activity::connected($newUser->id, $userTag[1], $senderID);
            } else {
                $user->tag = $userTag[0];
                $user->save();
                Activity::connected($user->id, $userTag[1], $senderID);
            }
        } else {
            Log::create([
                'log' => $res,
                'topic' => 'retrieve msg_info error',
            ]);
        }
        return true;
    }





}
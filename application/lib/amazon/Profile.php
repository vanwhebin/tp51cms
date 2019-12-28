<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/12/9
 * Time: 11:03
 */

namespace app\lib\amazon;
use app\api\job\CheckProfile;
use app\api\model\Log;
use app\api\model\ReviewerClub;
use app\lib\enum\ReviewerEnum;
use app\lib\message\MessageTpl as MessageModel;
use think\Queue;

class Profile
{
    /**
     * @param $senderID
     * @param $user
     * @param $profileUrl
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function handleProfileUrl($senderID, $user, $profileUrl)
    {
        // 处理profile链接问题
        $reviewer = ReviewerClub::where(['message_id' => $senderID])->find();
        if ($reviewer && !$reviewer->amazon_profile) {
            $reviewer->amazon_profile = $profileUrl;
            $reviewer->save();
        } else {
            ReviewerClub::create([
                "user_id" => $user->id,
                "message_id" => $senderID,
                "amazon_profile" => $profileUrl,
            ]);
        }
        return self::profileValidate($profileUrl, $user);
    }


    /**
     * @param $product
     * @param $user
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function profileRequest($product, $user)
    {
        // 发送亚马逊profile链接请求消息
        $msgTpl = "Hi %s, we have a %s available! To proceed, please send us your Amzn profile link.";
        $name = explode(" ", $user->name)[0];
        $btnPayload = [
            'template_type' => "button",
            'text' =>  sprintf($msgTpl, $name, $product['name']),
            'buttons'  => [
                [
                    "type"  => 'web_url',
                    "url"   => config('reviewer.amazon_profile_link'),
                    "title" => 'Get link',
                ],
            ]
        ];;

        MessageModel::sendImageMessage($user->message_id, $product['main_img_url']);
        MessageModel::sendButtonMessage($user->message_id, $btnPayload);
        return MessageModel::sendTextMessage($user->message_id, "What's your Amzn profile link?");
    }

    /**
     * * 对用户提交的亚马逊profile进行验证
     * @param $profileUrl
     * @param $user
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function profileValidate($profileUrl, $user)
    {
        // 检查用户提交profile是否合法
        $where = ['user_id' => $user->id, 'message_id' => $user->message_id];
        $reviewer = ReviewerClub::where($where)->find();
        $reviewer->amazon_profile = $profileUrl;
        $reviewer->save();
        // 验证亚马逊profile合法性
        // 对用户发送过来的profile链接进行审查和返回消息
        $data = [
            'url' => $profileUrl,
            'user' => $user
        ];
        $delayTime = config('app_debug') ? 2 : 1;
        Queue::later($delayTime, "app\api\job\CheckProfile", $data, (new CheckProfile())->queueName);
        MessageModel::sendTextMessage($user->message_id, "One moment, please.");
        return MessageModel::sendTypingOn($user->message_id);
    }


    /**
     * 拒绝profile不合格的请求
     * @param $user
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function rejectInvalidProfile($user)
    {
        // 拒绝不合法的用户
        return MessageModel::sendTextMessage($user->message_id, AmazonReviewer::REJECT_MSG);
    }

    /**
     * 接受profile合格的用户
     * @param $user
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function acceptValidProfile($user)
    {
        // 通过合法的用户
        return AmazonReviewer::rebateWayRequest($user);
    }





}
<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/11/16
 * Time: 17:56
 */

namespace app\api\service\message;
use app\api\model\Log;
use app\api\model\RebateUser;
use app\api\model\ReviewerOrder;
use app\api\model\User as UserModel;
use app\api\service\CallForReview;
use app\lib\amazon\AmazonReviewer;
use app\lib\enum\ReviewerEnum;
use app\lib\message\MessageTpl as MessageModel;
use think\Exception;

class Message
{

     const AUTO_REPLY = "Thanks, we'll get back to you shortly.";

    /**
     * 处理文本或者图片等媒体消息
     * @param $user
     * @param $messaging
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function handleMessage($user, $messaging)
    {
        $senderID = $messaging['sender']['id'];
        $message = $messaging['message'];

        $messageText = isset($message['text']) ? $message['text'] : '';
        $messageAttachments = isset($message['attachments']) ? $message['attachments']: [];
        $messageQuickReply = isset($message['quick_reply']) ? json_decode($message['quick_reply']['payload'], true): [];

        if ($messageQuickReply){
            return self::handleQuickReply($messageQuickReply, $user);
        }

        if ($messageText) {
            return self::handleText($senderID, $messageText, $user);
        } else if ($messageAttachments) {
            return self::handleAttachment($senderID, $messageAttachments, $user);
        }
        return true;
    }

    /**
     * @param $messageQuickReply
     * @param $user
     * @return bool
     * @throws \Exception
     */
    public static function handleQuickReply($messageQuickReply, $user)
    {
        //  审核名字 发送要求用户提供亚马逊profile的链接
        $ref = !empty($messageQuickReply['ref']) ? $messageQuickReply['ref'] : false;
        if (!$ref) {
            return false;
        }
        // TODO 快速回复的其他形式
        return MessageModel::sendTextMessage($user->message_id, "what you sent is {$ref}");
        // return AmazonReviewer::usernameValidation($user);
    }


    /**
     *  * 处理发送过来的媒体信息
     * @param $senderID
     * @param $messageAttachments
     * @param $user
     * @return bool
     * @throws \Exception
     */
    public static function handleAttachment($senderID, $messageAttachments, $user)
    {
        try{
            foreach($messageAttachments as $messageAttachment){
                if ($messageAttachment['type'] === 'image') {
                    $imageSize = intval((strlen(file_get_contents($messageAttachment['payload']['url'])) / 1024));
                    $ext = '.'.pathinfo(parse_url($messageAttachment['payload']['url'])['path'])['extension'];
                    $fileName = $senderID.'@'.date('Y-m-d').'-'.uniqid(). $ext;
                    $saveDir = './media/rebate/'.date('Ymd');
                    if ($imageSize > 50) {
                        $image = downloadImage($messageAttachment['payload']['url'], $saveDir, $fileName);
                        if ($image['error'] === 0) {
                            RebateUser::create([
                                'screenshot' => str_replace('./media/','', $image['save_path']),
                                'message_id' => $senderID,
                                'user_id' => $user->id,
                                'name' => $user->name,
                            ]);
                            $order = ReviewerOrder::where([
                                'user_id' => $user->id,
                                'message_id' => $user->message_id,
                                'is_ordered'=> ReviewerEnum::INVALID
                            ])->order('id', 'desc')->find();
                            if ($order) {
                                $order->is_ordered = ReviewerEnum::VALID;
                                $order->save();
                            }
                        }
                    }
                }
            }
            return MessageModel::sendTextMessage($senderID, "Thank you, we'll get back to you soon!");
        }catch(Exception $e){
            Log::create([
                'log' => json_encode([$e->getMessage(), $messageAttachments, $user]),
                'topic' => 'handle attachments'
            ]);
        }
        return true;
    }


    /**
     * 处理文字信息
     * @param $senderID
     * @param $messageText
     * @param $user
     * @return bool
     * @throws Exception
     * @throws \Exception
     * @throws \app\lib\exception\TokenException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function handleText($senderID, $messageText, $user)
    {
        if (preg_match_all("/https:\/\/www.amazon.com\/gp\/profile\/.+/", $messageText, $match)){
            return AmazonReviewer::profileValidation($match[0][0], $user);
        }
        // 处理过滤paypal帐号的情况
        if (preg_match_all("/\w[-\w.+]*@([A-Za-z0-9][-A-Za-z0-9]+\.)+[A-Za-z]{2,14}/", $messageText, $match)){
            return self::handleEmail($match[0][0], $user);
        }

        // 对用户发送过来的信息进行订单号过滤
        if (preg_match_all("/[0-9]{3}-[0-9]{7}-[0-9]{7}/", $messageText, $match)) {
            return self::filterAmazonOrderNumber($match[0][0], $user);
        }

        return self::textFilter($messageText, $senderID);
    }


    /**
     * 对文字信息做前期必备的过滤
     * @param $messageText
     * @param $senderID
     * @return bool
     * @throws Exception
     * @throws \Exception
     * @throws \app\lib\exception\TokenException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function textFilter($messageText, $senderID)
    {
        $lowerStr = strtolower(str_replace([' ', '"', "'"], ['', '', ''], $messageText));
        switch ($lowerStr) {
            case 'stop':
            case 'unsubscribe':
                return MessageModel::handleSubscribeMessage($senderID, 0);
                break;
            case 'confirm':
            case 'entered':
            case 'enter':
            case 'done':
                return MessageModel::handleSubscribeMessage($senderID, 1);
                break;
            case 'freebies':
                return Referral::handleShortLink($senderID, 271);
                break;
            case 'toys':
            case 'toy':
            case 'pets':
            case 'pet':
                return self::handleReviewCallMessage($senderID, $lowerStr);
                break;
            default:
                return MessageModel::sendTextMessage($senderID, self::AUTO_REPLY);
        }
    }

    /**
     * 过滤订单号
     * @param $orderNum
     * @param $user
     * @return bool
     * @throws \Exception
     */
    public static function filterAmazonOrderNumber($orderNum, $user)
    {
        try{
            RebateUser::create([
                'user_id' => $user->id,
                'message_id' => $user->message_id,
                'order_num' => $orderNum,
                'screenshot' => "",
                'name' => $user->name,
            ]);

            return MessageModel::sendTextMessage($user->message_id, 'Got it! Thanks.');
        }catch (Exception $e){
            Log::create([
                'log' => json_encode([$e->getMessage(), $orderNum]),
                'topic' => 'handle rebate order',
            ]);
        }

        return false;
    }


    /**
     * 处理发过来的邮件内容
     * @param $email
     * @param $user
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function handleEmail($email, $user)
    {
        return AmazonReviewer::rebateWayValidation($email, $user);
    }


    /**
     *  * 向用户发送备选产品
     * @param $senderID
     * @param $keyword
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function handleReviewCallMessage($senderID, $keyword)
    {
        if (in_array($keyword, ['toys', 'toy'])) {
            return CallForReview::handleToys($senderID);
        } else if (in_array($keyword, ['pets', 'pet'])) {
            return CallForReview::handlePets($senderID);
        }
        return true;
    }


}
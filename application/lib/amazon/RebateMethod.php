<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/12/9
 * Time: 11:15
 */

namespace app\lib\amazon;


use app\api\model\ReviewerBanned;
use app\api\model\ReviewerClub;
use app\api\model\ReviewerOrder;
use app\api\service\message\Postback;
use app\lib\enum\ReviewerEnum;
use app\lib\message\MessageTpl as MessageModel;

class RebateMethod
{
    public $defaultWay = [
        "name"  =>  "Paypal",
        "abbr"  =>  "PP",
    ];

    const PAYPAL_MSG = " Thank you! This %s is %s. Rebate issued via PP after Amzn comment appears. (PP transaction fee and tax not included).";

    /**
     * 向用户发送退款方式的信息
     * @param $user
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function rebateMethodRequest($user)
    {
        // 发送请求rebate方式的请求
        $where = [
            'message_id' => $user->message_id,
            'user_id'    => $user->id,
        ];
        $product = ReviewerOrder::with(['product'])->where($where)->find()->toArray();

        $msg = sprintf(self::PAYPAL_MSG, $product['product']['name'], $product['product']['amazon_price']);
        $btnPayload = [
            'template_type' => "button",
            'text' =>  $msg,
            'buttons'  => [
                [
                    'type'   =>  "postback",
                    'title'  => "Got it, continue",
                    'payload'=> json_encode(['status' => Postback::POSTBACK_REVIEW_REBATEWAY])
                ],
            ]
        ];


        return MessageModel::sendButtonMessage($user->message_id, $btnPayload);
    }

    /**
     * 检查paypal等支付方式是否被拉黑
     * @param $rebateEmail
     * @param $user
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function checkRebateMethod($rebateEmail, $user)
    {
        // 处理审核paypal等返款的信息
        // 验证返款方式合法性
        // 仅仅在用户的返款帐号为空时进行查询和验证
        $where = [
            'message_id' => $user->message_id,
            'user_id'    => $user->id,
            'identity'   => ReviewerEnum::INVALID
        ];
        $reviewer = ReviewerClub::where($where)->find();

        if ($reviewer) {
            MessageModel::sendTypingOn($user->message_id);
            if ($reviewer->paypal) {
                return false;
            } else {
                $reviewer->paypal = $rebateEmail;
                $reviewer->save();

                $bannedReviewer = ReviewerBanned::where(['email' => $rebateEmail])
                    ->whereOr(['paypal' =>$rebateEmail])->find();
                if (!$bannedReviewer) {
                    return self::acceptRebateInfo($user);
                } else {
                    return self::rejectRebateInfo($user);
                }
            }
        }
        return true;
    }

    /**
     * 审核无误
     * @param $user
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected static function acceptRebateInfo($user)
    {
        $where = [
            'message_id' => $user->message_id,
            'user_id'    => $user->id,
        ];
        ReviewerClub::where($where)->update(['identity' => ReviewerEnum::VALID]);
        $order = ReviewerOrder::with(['product', 'product.screenshot'])->where($where)->find()->toArray();
        return (new FollowUpMsg())->sendCampaignMsg($order['product'], $user);
    }


    /**
     * 拒绝非法用户
     * @param $user
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    protected static function rejectRebateInfo($user)
    {
        return MessageModel::sendTextMessage($user->message_id, AmazonReviewer::REJECT_MSG);
    }










}
<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/12/9
 * Time: 11:03
 */

namespace app\lib\amazon;

use app\api\model\ReviewerBanned;
use app\api\model\ReviewerClub;
use app\lib\message\MessageTpl;


class AmazonReviewer
{
    public $username = "";
    public $userProfile = "";
    const REJECT_MSG = "Unfortunately, products are all taken this time. We'll notify you once we have one available. Thanks for your interest.";
    public static $amazonUrl = "https://www.amazon.com";

    /**
     * 检查用户名是否在黑名单中
     * @param $product
     * @param $user
     * @param $existedReviewer
     * @return bool|mixed
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function usernameValidation($product, $user, $existedReviewer)
    {
        $bannedPeople = ReviewerBanned::where(['name' => $user->name])->value('id');
        if ($bannedPeople) {
            // 用户非法
            return MessageTpl::sendTextMessage($user->message_id, AmazonReviewer::REJECT_MSG);
        } else {
            if (!$existedReviewer) {
                ReviewerClub::create([
                    'user_id' => $user->id,
                    'message_id' => $user->message_id,
                ]);
            }
            return self::profileRequest($product, $user);
        }
    }

    /**
     * 请求用户的amazon profile
     * @param $product
     * @param $user
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function profileRequest($product, $user)
    {
        return Profile::profileRequest($product, $user);
    }

    /**
     *  profile接受验证
     * @param $profileUrl
     * @param $user
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function profileValidation($profileUrl, $user)
    {
        return Profile::profileValidate($profileUrl, $user);
    }

    /**
     * 向用户请求返款信息
     * @param $user
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function rebateWayRequest($user)
    {
        // 向该用户请求返款信息，从已申请的产品review信息中查取
        return RebateMethod::rebateMethodRequest($user);
    }


    /**
     * 验证产品返款方式
     * @param $rebateEmail
     * @param $user
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function rebateWayValidation($rebateEmail, $user)
    {
        return RebateMethod::checkRebateMethod($rebateEmail, $user);
    }

    /**
     * 购买产品的步骤
     * @param $product
     * @param $user
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function stepsToBuy($product, $user)
    {
        return (new FollowUpMsg())->sendCampaignMsg($product, $user);
    }

}
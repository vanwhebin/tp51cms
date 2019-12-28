<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/11/16
 * Time: 17:55
 */

namespace app\api\service\message;
use app\api\job\CampaignOrderConfirm;
use app\api\job\Messenger as MessengerJob;
use app\api\model\Campaign as ActivityModel;
use app\api\model\CampaignUser as ActivityUserModel;
use app\api\model\Product;
use app\api\model\ReviewerClub;
use app\api\model\Log;
use app\lib\amazon\AmazonReviewer;
use app\lib\amazon\FollowUpMsg;
use app\lib\message\MessageTpl as MessageModel;
use app\lib\message\MessageTpl;
use app\api\model\ProductMore as PrizeMoreModel;
use app\api\model\User as UserModel;
use app\api\service\Activity as ActivityService;
use think\Exception;
use think\Queue;

class Postback
{
    const POSTBACK_REVIEW = 'WANNA_REVIEW';
    const POSTBACK_GIVEAWAY = 'WANNA_WIN';

    const POSTBACK_AGREE = "GIVEAWAY_AGREE";

    const POSTBACK_FREEBIE_JOIN = 'POSTBACK_FREEBIE_JOIN';
    const POSTBACK_FREEBIE_JOIN_CONFIRM = 'POSTBACK_FREEBIE_JOIN_CONFIRM';
    const POSTBACK_FREEBIE_JOIN_CONFIRM_CHECKACCOUNT = 'POSTBACK_FREEBIE_JOIN_CONFIRM_CHECKACCOUNT';
    const POSTBACK_FREEBIE_JOIN_CONFIRM_CHECKACCOUNT_YES = 'POSTBACK_FREEBIE_JOIN_CONFIRM_CHECKACCOUNT_YES';
    const POSTBACK_FREEBIE_JOIN_CONFIRM_CHECKACCOUNT_NO = 'POSTBACK_FREEBIE_JOIN_CONFIRM_CHECKACCOUNT_NO';
    const POSTBACK_FREEBIE_JOIN_CONFIRM_PRODUCT_YES = 'POSTBACK_FREEBIE_JOIN_CONFIRM_PRODUCT_YES';
    const POSTBACK_FREEBIE_JOIN_CONFIRM_PRODUCT_NO = 'POSTBACK_FREEBIE_JOIN_CONFIRM_PRODUCT_NO';
    const POSTBACK_FREEBIE_JOIN_CONFIRM_PRODUCT_YES_AGAIN = 'POSTBACK_FREEBIE_JOIN_CONFIRM_PRODUCT_YES_AGAIN';
    const POSTBACK_FREEBIE_JOIN_CONFIRM_PRODUCT_NO_AGAIN = 'POSTBACK_FREEBIE_JOIN_CONFIRM_PRODUCT_NO_AGAIN';

    const POSTBACK_FREEBIE_REBATE = 'POSTBACK_FREEBIE_REBATE';

    const POSTBACK_FREEBIE_REFER = 'POSTBACK_FREEBIE_REFER';
    const POSTBACK_FREEBIE_REFER_NAME = 'POSTBACK_FREEBIE_REFER_NAME';

    const POSTBACK_REVIEW_REBATEWAY = 'POSTBACK_REVIEW_REBATEWAY';


    const TEAMUP_POSTBACK_AGREE_REPLY = "You're in! Wanna increase your chance to win? Get 2 bonus entries when you refer a friend to enter. Share now👇";
    const DEFAULT_POSTBACK_AGREE_REPLY = "Congrats, you're entered!  Result will be drawn via messenger notification.";
    const DEFAULT_POSTBACK_DECLINE_REPLY = "Thanks for your interest.";

    const FIRST_REFERRAL_REBATE_CHOICE = 'FIRST_REFERRAL_REBATE_CHOICE';
    const FIRST_REFERRAL_GIVEAWAY_CHOICE = 'FIRST_REFERRAL_GIVEAWAY_CHOICE';


    /**
     * @param $user
     * @param $postBack
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function handlePostback($user, $postBack)
    {
        if(!empty($postBack['referral'])){
            return Referral::handleReferral($user, $postBack['referral']);
        }

        $payload = json_decode($postBack['payload'], true);
        switch ($payload['status']){
            case self::FIRST_REFERRAL_REBATE_CHOICE:
                return self::postbackFreebies($user, $payload);
                break;
            case self::FIRST_REFERRAL_GIVEAWAY_CHOICE:
                return self::postbackGiveaway($user);
                break;
            case self::POSTBACK_GIVEAWAY:
                return self::postbackGiveawayReply($user, $payload);
                break;
            case self::POSTBACK_FREEBIE_JOIN:
                return self::postbackFreebiesJoin($user, $payload);
                break;
            case self::POSTBACK_FREEBIE_REBATE:
                return self::postbackFreebiesRebate($user, $payload);
                break;
            case self::POSTBACK_FREEBIE_REFER:
                return self::postbackFreebiesRefer($user, $payload);
            case self::POSTBACK_REVIEW_REBATEWAY:
                return self::postbackReviewRebateWay($user);
                break;
        }
        return false;
    }

    /**
     * 向用户询问返款的方式
     * @param $user
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function postbackReviewRebateWay($user)
    {
        return MessageModel::sendTextMessage($user->message_id, "What's your PP ID? (For receiving rebate)");
    }



    /**
     * 向用户发送确认参与按钮
     * @param $user
     * @param $payload
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function postbackFreebiesJoin($user, $payload)
    {
        if (empty($payload['step'])) {
            $buttonPayload = [
                'template_type' => "button",
                'text' =>  "Great, we'd like to invite you to be part of our free product test program! Test & review the product and receive 100% rebate from us! (US only)",
                'buttons'  => [
                    [
                        'type'   =>  "postback",
                        'title'  => "Join now",
                        'payload'=> json_encode([
                            'status' => self::POSTBACK_FREEBIE_JOIN,
                            'step' => self::POSTBACK_FREEBIE_JOIN_CONFIRM_CHECKACCOUNT
                        ])
                    ]
                ]
            ];
            return MessageModel::sendButtonMessage($user->message_id, $buttonPayload);
        } else {
            if ($payload['step'] === self::POSTBACK_FREEBIE_JOIN_CONFIRM_CHECKACCOUNT) {
                $buttonPayload = [
                    'template_type' => "button",
                    'text' =>  "Do you have an active Amazon account and a Paypal account? (Rebate issued via Paypal)",
                    'buttons'  => [
                        [
                            'type'   =>  "postback",
                            'title'  => "Yes",
                            'payload'=> json_encode([
                                'status' => self::POSTBACK_FREEBIE_JOIN,
                                'step' => self::POSTBACK_FREEBIE_JOIN_CONFIRM_CHECKACCOUNT_YES,
                            ])
                        ],
                        [
                            'type'   =>  "postback",
                            'title'  => "No",
                            'payload'=> json_encode([
                                'status' => self::POSTBACK_FREEBIE_JOIN,
                                'step' => self::POSTBACK_FREEBIE_JOIN_CONFIRM_CHECKACCOUNT_NO,
                            ])
                        ]
                    ]
                ];
                return MessageModel::sendButtonMessage($user->message_id, $buttonPayload);
            } else if ($payload['step'] === self::POSTBACK_FREEBIE_JOIN_CONFIRM_CHECKACCOUNT_YES) {
                ReviewerClub::newClubUser($user->id, $user->message_id); // 记录下用户潜力
                $buttonPayload = [
                    'template_type' => "button",
                    'text' =>  "Great, tap the button below to browse our freebie selection! Please do not use gift card to pay.",
                    'buttons'  => [
                        [
                            "type"  => 'web_url',
                            "url"   => config('domain'),
                            "title" => 'View Website',
                        ],
                    ]
                ];
                return MessageModel::sendButtonMessage($user->message_id, $buttonPayload);
            } else if ($payload['step'] === self::POSTBACK_FREEBIE_JOIN_CONFIRM_CHECKACCOUNT_NO) {
                return MessageModel::sendTextMessage($user->message_id, self::DEFAULT_POSTBACK_DECLINE_REPLY);
            } else if ($payload['step'] === self::POSTBACK_FREEBIE_JOIN_CONFIRM_PRODUCT_YES) {
                // 第一次询问下，回复确认
                $confirmAgainYesMsg = "Can you send us a product screenshot to confirm? (Please ignore this message if you've already done so.)";
                return MessageModel::sendTextMessage($user->message_id, $confirmAgainYesMsg);
            } else if ($payload['step'] === self::POSTBACK_FREEBIE_JOIN_CONFIRM_PRODUCT_NO) {
                // 第一次询问下，回复否认

                return self::sendMoreKeywordSearchMsg($user, $payload);
            } else if ($payload['step'] === self::POSTBACK_FREEBIE_JOIN_CONFIRM_PRODUCT_YES_AGAIN) {
                // 第二次询问下，回复确认
                $confirmAgainYesMsg = "Can you send us a product screenshot to confirm? (Please ignore this message if you've already done so.)";
                return MessageModel::sendTextMessage($user->message_id, $confirmAgainYesMsg);
            } else if ($payload['step'] === self::POSTBACK_FREEBIE_JOIN_CONFIRM_PRODUCT_NO_AGAIN) {
                // 第二次询问下，回复否认
                $confirmAgainNoMsg = "Our friendly customer service team will get back to you soon!";
                return MessageModel::sendTextMessage($user->message_id, $confirmAgainNoMsg);
            } else {
                return false;
            }
        }
    }


    /**
     *  * 第一次向用户征询是否找到都应的测评产品
     * @param $user
     * @param $payload
     * @return bool
     * @throws \Exception
     */
    public static function sendMoreKeywordSearchMsg($user, $payload)
    {

        try{
            $product = Product::where(['id' => $payload['product_id']])->find();
            $keywords = json_decode($product['keywords'], true);
            $url = AmazonReviewer::$amazonUrl;
            if (!empty($keywords) && count($keywords) > 1) {
                // $url = $url . "/s?" . http_build_query(['k' =>  $keywords[1], 'ref' => 'nb_sb_noss_2']);
                $text = sprintf((new CampaignOrderConfirm())->postbackMsg, $keywords[1], $product['amazon_price']);
            } else {
                $text = sprintf((new CampaignOrderConfirm())->postbackMsg, $keywords[0], $product['amazon_price']);
            }

            if (!empty($product['color'])) {
                $text .= " and choose color {$product['color']}. ";
            }

            $text .= " Send us a screenshot to confirm before ordering.";

            $btnPayload = [
                'template_type' => "button",
                'text' =>  $text,
                'buttons'  => [
                    [
                        "type"  => 'web_url',
                        "url"   => $url,
                        "title" => 'Search now',
                    ],
                ]
            ];

            $campaignOrderConfirmQueue = new CampaignOrderConfirm();
            $jobData = [
                'user_id' => $user->id,
                'product_id' => $payload['product_id'],
                'message_id' => $user->message_id,
            ];
            Queue::later($campaignOrderConfirmQueue->delayJobIntval, "app\api\job\CampaignOrderConfirm",
                $jobData, $campaignOrderConfirmQueue->delayJobQueueName);
            return MessageTpl::sendButtonMessage($user->message_id, $btnPayload);
        }catch(Exception $ex){
            Log::create([
                'log' => json_encode(['data' => $payload, 'msg' => $ex->getLine().PHP_EOL.$ex->getMessage()]),
                'topic' => __FUNCTION__
            ]);
            return false;
        }
    }


    /**
     * 用户申请发起rebate流程,发送订单截图
     * @param $user
     * @param $payload
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function postbackFreebiesRebate($user, $payload)
    {
        return MessageModel::sendTextMessage($user->message_id, 'Please send us a screenshot to your review.');
    }


    /**
     * 用户refer邀请活动奖励
     * @param $user
     * @param $payload
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function postbackFreebiesRefer($user, $payload)
    {
        if (empty($payload['step'])) {
            $sendPayload = [
                'template_type' => "button",
                'text' =>  'Receive $5 Amazon Gift Card for inviting your friend to participate in the freebies program! Gift card issued after your friend places an order.',
                'buttons'  => [
                    [
                        'type'   =>  "postback",
                        'title'  => "Refer now",
                        'payload'=> json_encode(['status' => self::POSTBACK_FREEBIE_REFER, 'step' => self::POSTBACK_FREEBIE_REFER_NAME])
                    ]
                ]
            ];

            return MessageModel::sendButtonMessage($user->message_id, $sendPayload);
        } else {
           if ($payload['step'] === self::POSTBACK_FREEBIE_REFER_NAME) {
               // 问名
               return MessageModel::sendTextMessage($user->message_id, "What's your friend's full name?");
           }

           return true;
        }
    }


    /**
     * 向意向用户发送freebies选项
     * @param $user
     * @param $payload
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function postbackFreebies($user, $payload)
    {
        $sendPayload = [
            'template_type' => "button",
            'text' =>  'How can we assist you today?',
            'buttons'  => [
                [
                    'type'   =>  "postback",
                    'title'  => "Sign up for free product test program (100% rebate)",
                    'payload'=> json_encode(['status' => self::POSTBACK_FREEBIE_JOIN])
                ],
                [
                    'type'   =>  "postback",
                    'title'  => "Redeem rebate",
                    'payload'=> json_encode(['status' => self::POSTBACK_FREEBIE_REBATE])
                ],
                [
                    'type'   =>  "postback",
                    'title'  => "Refer friend for $",
                    'payload'=> json_encode(['status' => self::POSTBACK_FREEBIE_REFER])
                ],
            ]
        ];

        return MessageModel::sendButtonMessage($user->message_id, $sendPayload);
    }



    /**
     * 对用户参与giveaway进行反馈
     * @param $user
     * @param $payload
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function postbackGiveawayReply($user, $payload)
    {
        // 计入活动
        ActivityService::enroll($payload['activity_id'], $user->id);
        return MessageModel::sendTextMessage($user->message_id, self::DEFAULT_POSTBACK_AGREE_REPLY);
    }




    /**
     * 向用户发送giveaway信息
     * @param $user
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function postbackGiveaway($user)
    {
        $giveaways = ActivityModel::getRecommendations($user->id, [], 2);
        MessageModel::sendTextMessage($user->message_id, 'Below is the giveaways for you');

        foreach($giveaways as $giveaway){
            $payload = [
                'template_type' => "button",
                'text' =>  $giveaway['title'],
                'buttons'  => [
                    [
                        'type'   =>  "postback",
                        'title'  => "Enter Now",
                        'payload'=> json_encode(['status' => self::POSTBACK_GIVEAWAY, 'activity_id' => $giveaway['id']])
                    ],
                ]
            ];
            MessageModel::sendButtonMessage($user->message_id, $payload);
        }
        return true;
    }




    /**
     * @param $user
     * @param $payload
     * @return bool
     * @throws \Exception
     * @throws \app\lib\exception\TokenException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function afterPostBackConfirm($user, $payload)
    {
        // 用户确认参与活动
        if (!$user->confirm) {
            $user->confirm = 1;
            $user->save();
        }
        $activityUser = ActivityUserModel::getOne($payload['activity_id'], $user->id);
        if (!$activityUser->confirm) {
            $activityUser->confirm = 1;
            $activityUser->save();
            return MessengerJob::sendWinnerMsg($payload, $user);
        } else {
            return true;
        }
    }

    /**
     * 处理用户在确认领取活动优惠券发送按钮消息
     * @param $user
     * @param $payload
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function afterPostbackRedeem($user, $payload)
    {
        $codeInfo = PrizeMoreModel::with([
            'code' => function ($query) {
                $query->field(['code', 'desc', 'id'])->where(['status' => 1]);
            },
            'prize',
            'activity.thumb'
        ])->where(['activity_id' => $payload['activity_id']])
            ->visible(['name', 'summary', 'desc', 'url', 'code'])
            ->select()->toArray();

        Log::create([
            'log' => json_encode([$codeInfo, $user, $payload]),
            'topic' => 'test redeem'
        ]);

        $msgTpl = MessageModel::$genericTpl;
        foreach ($codeInfo as $item) {
            MessageModel::sendTextMessage($user->message_id, $item['code']['code']);
            MessageModel::sendTextMessage($user->message_id, $item['code']['desc']);
            $msgPayload = $msgTpl['attachment']['payload'];
            $msgPayload['elements'] = [
                [
                    "title" => $item['prize']['name'],
                    "image_url" => $item['activity']['thumb']['url'],
                    "subtitle" => "",
                    "default_action" => [
                        "type" => "web_url",
                        "url" => $item['url'],
                        "webview_height_ratio" => "tall",
                    ],
                    "buttons" => [
                        [
                            "type" => 'web_url',
                            "url" => $item['url'],
                            "title" => 'Shop now',
                        ],
                    ]
                ]
            ];
            MessageModel::sendGenericTplMessage($user->message_id, $msgPayload);
        }

        return true;
    }

    /**
     * 处理用户测评留言postback
     * @param $user
     * @param $payload
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function afterPostbackReview($user, $payload)
    {
        // return Review::handleReview($user, $payload);
    }
}
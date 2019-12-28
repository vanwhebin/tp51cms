<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/11/16
 * Time: 18:03
 */

namespace app\api\service\message;


use app\api\model\Campaign;
use app\api\model\Campaign as ActivityModel;
use app\api\model\CampaignProduct;
use app\api\model\Log;
use app\api\model\ReviewerClub;
use app\api\model\ReviewerOrder;
use app\api\service\Activity as ActivityService;
use app\lib\amazon\AmazonReviewer;
use app\lib\amazon\FollowUpMsg;
use app\lib\enum\ActivityEnum;
use app\lib\enum\ReviewerEnum;
use app\lib\exception\InvalidParamException;
use app\lib\message\MessageTpl as MessageModel;
use think\Exception;
use think\exception\DbException;

class Referral
{

    /**
     * @param $user
     * @param $refer
     * @return bool
     * @throws \Exception
     */
    public static function handleReferral($user, $refer)
    {
        try{
            if ($refer['source'] === 'SHORTLINK') {
                Log::create([
                    'log' => json_encode([$user, $refer]),
                    'topic' => "handle Referral user"
                ]);
                if (!empty($refer['ref'])) {
                    return self::getFreebies($user, $refer['ref']);
                }
                $msg = sprintf("Hi %s, How can we assist you?", $user->name);
                return MessageModel::sendTextMessage($user->message_id, $msg);
            }
            // 留待其他可能性
        }catch(Exception $e){
            Log::create([
                'log' => $e->getMessage(),
                'topic' => "handle Referral"
            ]);
        }
        return true;
    }


    /**
     * 用户通过m.me短链参与活动，并给予选择反馈
     * @param $user
     * @param $ref
     * @return bool
     * @throws \Exception
     * @throws \app\lib\exception\TokenException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function handleShortLink($user, $ref)
    {
        $ref = htmlspecialchars(strip_tags($ref));
        if (!is_numeric($ref)) {
            return false;
        }
        $actID = ActivityModel::where(['private' => ActivityEnum::PUBLIC, 'status' => ActivityEnum::SHOW])
            ->findOrEmpty(intval($ref));
        if (!$actID) {
            return false;
        } else {
            ActivityService::enroll($actID, $user->message_id);

            $payload = [
                'template_type' => "button",
                'text' =>  sprintf("Hi %s, how can we help you?", $user['first_name']),
                'buttons'  => [
                    [
                        'type'   =>  "postback",
                        'title'  => "Freebies (full rebate)",
                        'payload'=> json_encode(['status'=>Postback::FIRST_REFERRAL_REBATE_CHOICE]),
                    ],
                    [
                        "type" => 'postback',
                        "url" => 'Enter Giveaways',
                        "title" => json_encode(['status'=>Postback::FIRST_REFERRAL_GIVEAWAY_CHOICE]),
                    ],
                ]
            ];
            return MessageModel::sendButtonMessage($user->message_id, $payload);
        }
    }


    public static function quickReply($user, $ref)
    {
        // 带上产品参数 名称和id
        $keywords = ['Booking', 'Freebies', 'FAQ'];
        $payload = ['text' => "Thanks for contacting us. What do you want to know more from us?"];
        foreach($keywords as $key=>$value) {
            $payload['quick_replies'][] =  [
                'content_type' => 'text',
                'title'=> $value,
                'payload'=> json_encode(['ref' => str_replace(' ', '_', strtolower($value))])
            ];
        }

        return MessageModel::sendQuickReply($user->message_id, $payload);
    }

    /**
     * @param $user
     * @param $ref
     * @return bool
     * @throws DbException
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getFreebies($user, $ref)
    {
        Log::create([
            'log' => json_encode([$user, $ref]),
            'topic' => 'referral',
        ]);
        $campaignID = self::handleRef($ref);
        if ($campaignID) {

            $product = self::getCampaignProductByRef($user, $campaignID);
            if (!$product) {
                // 如果对应campaign下没有产品,自动略过
                return false;
            }
            $rec = ReviewerOrder::findRec($user->id, $user->message_id, $product['id']);
            if ($rec) {
                // 如果用户已经参与过这个campaign,自动略过
                return false;
            }
            Log::create([
                'log' => json_encode([$product, $campaignID]),
                'topic' => 'campaign',
            ]);
            $stock = CampaignProduct::where(['campaign_id' =>  $campaignID, 'product_id' => $product['id']])->value('stock');
            if (!$stock) {
                return MessageModel::sendTextMessage($user->message_id, "This product has been sold out.");
            }

            $orderData = [
                'product_id'    => $product['id'],
                'user_id'       => $user->id,
                'message_id'    => $user->message_id,
                'product_price' => $product['amazon_price'],
            ];
            // 写入reviewer_order表作为记录
            ReviewerOrder::createOne($orderData);

            // 判断用户是否已经是reviewer，否则进入流程
            $reviewer = ReviewerClub::where(['message_id' => $user->message_id])->find();

            if ($reviewer && $reviewer->identity) {
                return (new FollowUpMsg())->sendCampaignMsg($product, $user);
            } else {
                // 进入新手流程
                return AmazonReviewer::usernameValidation($product, $user, $reviewer);
            }
        }
        return false;
    }

    public static function handleRef($ref)
    {
        try {
            $decodeRes = easyDecode($ref);
            //  判断campaignID是否合法
            if (!$decodeRes) {
                throw new InvalidParamException();
            }
            $campaignID = intval(addslashes(strip_tags($decodeRes)));
            return $campaignID;
        } catch(InvalidParamException $exception) {
            Log::create([
                'log' => $exception->getMessage(),
                'topic' => 'handleRef error',
            ]);
            return 0;
        } catch(Exception $e){
            Log::create([
                'log' => $e->getMessage(),
                'topic' => 'handleRef error',
            ]);
            return 0;
        }
    }


    /**
     * 对参数进行处理 获取campaign下的产品
     * @param $user
     * @param $campaignID
     * @return array
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function getCampaignProductByRef($user, $campaignID)
    {
        try{
            $campaign = Campaign::with(['product', 'product.screenshot'])
                ->where('id', $campaignID)
                ->where('status', ReviewerEnum::VALID)
                ->findOrFail();
            $campaign = $campaign ? $campaign->toArray() : false;

            // 对ref参数进行解码  对对应参数进行处理 campaignID
            if (!$campaign) {
                throw new InvalidParamException();
            }

            // TODO 兼容多个产品一个campaign的情况
            $product = $campaign['product'][0];

        }catch(InvalidParamException $e){
            Log::create([
                'log' => $e->getMessage(),
                'topic' => __FUNCTION__. ' InvalidParam Error',
            ]);
            MessageModel::sendTextMessage($user->message_id, AmazonReviewer::REJECT_MSG);
            $product = [];
        }catch(DbException $ex){
            Log::create([
                'log' => $ex->getMessage(),
                'topic' => __FUNCTION__. ' DbException Error',
            ]);
            MessageModel::sendTextMessage($user->message_id, AmazonReviewer::REJECT_MSG);
            $product = [];
        } finally {
            return $product;
        }
    }


}
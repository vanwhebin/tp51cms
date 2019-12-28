<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/12/9
 * Time: 11:23
 */

namespace app\lib\amazon;


use app\api\job\CampaignOrder;
use app\api\model\CampaignUser;
use app\api\model\ReviewerOrder;
use app\lib\message\MessageTpl;
use think\Queue;

class FollowUpMsg
{
    public static $maxStepLen = 640;

    /**
     * 向用户发送活动步骤和产品截图进行下单
     * @param $product array
     * @param $user object
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public function sendCampaignMsg($product, $user)
    {
        // $campaign =
        // CampaignUser::create([
        //     ''
        // ]);
        $this->sendMsg($product, $user);
        return $this->checkRightProductJob($product['id'], $user);
    }

    /**
     * 发送活动步骤信息和产品信息
     * @param $product
     * @param $user
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public function sendMsg($product, $user)
    {
        MessageTpl::sendImageMessage($user->message_id, $product['screenshot']['url']);
        $keywords = json_decode($product['keywords'], true);
        $url = AmazonReviewer::$amazonUrl;
        // if (!empty($keywords)) {
            // $url = $url . "/s?" . http_build_query(['k' =>  $keywords[0], 'ref' => 'nb_sb_noss_2']);
        // }

        $payload = [
            'template_type' => "button",
            'text' =>  substr($product['steps'], 0, self::$maxStepLen),
            'buttons'  => [
                [
                    "type"  => 'web_url',
                    "url"   => $url,
                    "title" => 'Search now',
                ],
            ]
        ];

        return MessageTpl::sendButtonMessage($user->message_id, $payload);
    }


    /**
     * 写入延迟推送确认产品的任务
     * @param $productID
     * @param $user
     * @return bool
     */
    public function checkRightProductJob($productID, $user)
    {
        $jobData = [
            'product_id' => $productID,
            'user_id' => $user->id,
            'message_id' => $user->message_id,
        ];
        $campaignOrderQueue = new CampaignOrder();
        Queue::later($campaignOrderQueue->delayJobIntval, "app\api\job\CampaignOrder", $jobData, $campaignOrderQueue->delayJobQueueName);
        return true;
    }



}
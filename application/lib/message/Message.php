<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/7/23
 * Time: 16:29
 */

namespace app\lib\message;
use app\api\model\BaseModel;


use app\api\model\Log;
use app\api\model\User;
use app\api\service\message\Postback;
use app\lib\enum\LogEnum;

class Message extends BaseModel
{
    public static function sendConfirmMsg($senderID, $data)
    {
        $info = $data['info'];
        $definePayload  = json_encode([
            'status' => Postback::POSTBACK_AGREE,
            'activity_id'=> $data['activityID']
        ]);

        $payload = [
            'template_type' => "generic",
            'elements' => [
                [
                    "title" => $info['title'],
                    "image_url" => $info['image_url'],
                    "subtitle" => $info['subtitle'],
                    "default_action" => [
                        "type" => "web_url",
                        "url"  => $info['url'],
                        "webview_height_ratio" => "tall",
                    ],
                    "buttons" => [
                        [
                            "type" => 'postback',
                            "title" => 'OK',
                            "payload" => $definePayload,
                        ],
                    ]
                ],
            ],
        ];

        return self::sendGenericTplMessage($senderID, $payload);
    }


    /**
     * 发送用户组队信息
     * @param $senderID
     * @param $data
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function sendTeamUpMsg($senderID, $data)
    {
        $info = $data['info'];
        $payload = [
            'template_type' => "generic",
            'elements' => [
                [
                    "title" => $info['title'],
                    "image_url" => $info['image_url'],
                    "subtitle" => config('fb.SURPRIZE_SHARE_DESC'),
                    "default_action" => [
                        "type" => "web_url",
                        "url"  => $info['url'],
                        "webview_height_ratio" => "tall",
                    ],
                    // "buttons" => [
                    //     [
                    //         "type" => 'web_url',
                    //         "url" => shareUrl($info['url']),
                    //         "title" => 'Refer Now',
                    //         "webview_height_ratio" => "tall",
                    //     ],
                    // ]
                ],
            ],
        ];

        return self::sendGenericTplMessage($senderID, $payload);
    }


    /**
     * @param $senderID
     * @param $content
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function sendActivityNotification($senderID, $content)
    {
        $message = [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    "template_type"=>"generic",
                    'elements'  => [$content],
                ],
            ],
        ];

        return self::callSendAPI($senderID, $message);
    }


    /**
     * 处理取消推送
     * @param $senderID
     * @param int $subscribe
     * @return bool
     */
    public static function handleSubscribeMessage($senderID, $subscribe=1)
    {
        $user = User::getUserByMsgID($senderID);
        if (!$user) {
            return true;
        } else {
            $user->confirm = $subscribe;
            $user->save();
            return true;
        }
    }


    /**
     * 发送文字
     * @param $senderID
     * @param $msg
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function sendTextMessage($senderID, $msg)
    {
        $message = ['text' =>  $msg, 'metadata' => config('fb.DEVELOPER_DEFINED_METADATA')];
        return self::callSendAPI($senderID, $message);
    }

    /**
     * 发送图片
     * @param $senderID
     * @param $imageUrl
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function sendImageMessage($senderID, $imageUrl)
    {
        $messageData = ['attachment' => [
            'type'  => 'image',
            'payload'  => ['url' => $imageUrl]
        ]];

        return self::callSendAPI($senderID, $messageData);
    }

    public static function sendMediaMessage($type='image', $senderID)
    {
        $msgTemplate = config('fb.DEFAULT_BOT_MSG');
        $messageData = ['attachment' => [
            'type'  => strtolower($type),
            'payload'  => ['url' => $msgTemplate[$type]]
        ]];

        return self::callSendAPI($senderID, $messageData);
    }

    public static function sendTypingOff($senderID, $message="")
    {
        $sender_action = "typing_off";
        return self::callSendAPI($senderID, $message, $sender_action);
    }

    public static function sendTypingOn($senderID, $message="")
    {
        $sender_action = "typing_on";
        return self::callSendAPI($senderID, $message, $sender_action);
    }

    public static function sendReadReceipt($senderID)
    {
        $sender_action = "mark_seen";
        return self::callSendAPI($senderID, '',$sender_action);
    }

    public static function sendQuickReply($senderID, $payload)
    {
        return self::callSendAPI($senderID, $payload);
    }

    public static function sendNotifyMessage($senderID, $info)
    {
        // $url = $info['url'] . "?".http_build_query(['result'=>base64_encode($info['url'])]);
        $url = $info['url'] . "#".base64_encode($info['url']);
        $payload = [
            'template_type' => "generic",
            'elements' => [
                [
                    "title" => $info['title'],
                    "image_url" => $info['image_url'],
                    "subtitle" => $info['subtitle'],
                    "default_action" => [
                        "type" => "web_url",
                        "url"  => $url,
                        "webview_height_ratio" => "tall",
                    ]
                ],
            ],
        ];

        $message = self::$genericTpl;
        $message['attachment']['payload'] = $payload;
        return self::callSendAPI($senderID, $message);
    }

    public static function sendRoutingMessage($senderID, $content)
    {
        $message = [
            'attachment' => [
            'type'=> "template",
            'payload'=> [
                'template_type' => "button",
                'text' =>  $content,
                ],
            ]
        ];
        return self::callSendAPI($senderID, $message);

    }

    /**
     * 发送按钮模板消息
     * @param $senderID
     * @param $payload
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function sendButtonMessage($senderID, $payload)
    {
        $message = self::$buttonMsgTpl;
        $message['attachment']['payload'] = $payload;
        return self::callSendAPI($senderID, $message);
    }

    /**
     * @param $senderID
     * @param $payload
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function sendGenericTplMessage($senderID, $payload)
    {
        $message = self::$genericTpl;
        $message['attachment']['payload'] = $payload;
        return self::callSendAPI($senderID, $message);
    }


    /**
     * @param $sender_psid
     * @param string $message
     * @param string $actionType
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function callSendAPI($sender_psid, $message = '', $actionType = '')
    {
        $requestBody = ['recipient' => ["id" => $sender_psid]];
        if ($message) {
            $requestBody['message'] = $message;
        }

        if ($actionType) {
            $requestBody['sender_action'] = $actionType;
        }

        $url = sprintf(config('fb.SEND_MESSAGE_API'), config('fb.PAGE_ACCESS_TOKEN'));
        $res = curlHttp($url, json_encode($requestBody), 'POST', ['Content-Type: application/json'], true);
        $res = !is_array($res) ? json_decode($res, true): $res;
        if (array_key_exists('error', $res)) {
            Log::create([
                'log'   => json_encode(['senderID' => $sender_psid, 'result' => $res ,'request'=> $requestBody]),
                'type'  => LogEnum::TYPE_FB,
                'topic' => 'claim'
            ]);
            return false;
        } else {
            return true;
        }
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/6/16
 * Time: 15:04
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\Log;
use app\api\model\User as UserModel;
use app\api\service\Message as msgService;
use app\api\service\message\Optin;
use app\api\service\message\Postback;
use app\api\service\message\Referral;
use app\api\service\message\Message as Msg;
use app\lib\enum\LogEnum;
use app\lib\exception\TokenException;
use think\Exception;
use think\facade\Request;

class Message extends BaseController
{
    /**
     * 验证webhook接口
     * @return bool|int
     */
    public function validation()
    {
        $data = Request::get();
        $webhookToken = config('fb.WEBHOOK_TOKEN');
        if ($data['hub_verify_token'] === $webhookToken &&
            $data['hub_mode'] === 'subscribe') {
            return intval($data['hub_challenge']);
        }
        return false;
    }

    /**
     * @return bool|\think\response\Json
     * @throws Exception
     * @throws \Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function message()
    {
        $data = Request::post();
        // $this->_checkMsg($data);

        Log::create([
            'log'  => json_encode($data),
            'type' => LogEnum::TYPE_FB,
            'topic' => 'fb msg webhook'
        ]);
        // return json($data);

        if ($data['object'] == 'page') {
            try{
                foreach ($data['entry'] as $key => $value) {
                    foreach ($value['messaging'] as $index=>$messaging) {
                        $user = UserModel::getUserByMsgID($messaging['sender']['id']);
                        if (!empty($messaging['message'])) {
                            return Msg::handleMessage($user, $messaging);
                        } else if (!empty($messaging['postback'])) {
                            return Postback::handlePostback($user, $messaging['postback']);
                        } else if (!empty($messaging['delivery'])) {
                            return msgService::handleDelivery($user, $messaging['delivery']);
                        } else if (!empty($messaging['read'])) {
                            return msgService::handleRead($user, $messaging['read']);
                        } else if (!empty($messaging['optin'])) {
                            return Optin::handleOptin($user, $messaging);
                        } else if (!empty($messaging['referral'])) {
                            return Referral::handleReferral($user, $messaging['referral']);
                        }

                    }
                }
            }catch(TokenException $e){
                Log::create([
                    'topic' => "Messenger 500",
                    'log' => $e->getCode().'|'. $e->getLine() .'|'.$e->getMessage(),
                ]);
            }

            return json('received', 200);
        } else {
            Log::create([
                'log'  => json_encode($data),
                'type' => LogEnum::TYPE_SYS,
                'topic' => 'user message'
            ]);
            return false;
            // throw new Exception('FB发送信息参数有误');
        }
    }

    private function _checkMsg($data)
    {
        // TODO 验证传递参数sha1
        // $validate= new MessageValidate();
        // $validate->validate();
        // $data1 = file_get_contents("php://input");
        // $data = json_decode($data, true);

    }





}
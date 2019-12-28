<?php

namespace app\api\model;

use app\api\service\Token;
use app\lib\exception\InvalidParamException;
use Laravolt\Avatar\Avatar;
use think\Exception;

class User extends BaseModel
{
    protected $hidden = ['delete_time'];


    public function activity()
    {
        return $this->hasMany('CampaignUser', 'user_id', 'id');
    }


    public static function getUserByUserID($userID)
    {
        return self::where('userid', '=', $userID)->find();
    }


    /**
     * 根据用户msgID获取用户数据
     * @param $msgID
     * @return array|mixed|null|\PDOStatement|string|\think\Model|static
     * @throws Exception
     * @throws \Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getUserByMsgID($msgID)
    {
        $user = self::where('message_id', '=',  $msgID)->find();
        if (!$user) {
            $user = self::retrieveMsgUserInfoByMessageID($msgID);
            $data = [
                'name'          => $user['first_name']. ' '. $user['last_name'],
                'avatar'        => $user['profile_pic'],
                'message_id'    => $msgID,
            ];
            $user = self::newUser($data);
        }
        return $user;
    }

    public static function getUserByEmail($email)
    {
        return self::where('email', '=', $email)->find();
    }

    public static function subscribeCheck($userID)
    {
        return self::where(['id' => $userID])->column('confirm');
    }


    private static function checkPassword($md5Password, $password)
    {
        return $md5Password === md5($password);
    }

    /**
     * 更新个人信息
     * @param $params
     * @return int|string
     * @throws Exception
     * @throws \app\lib\exception\TokenException
     * @throws \think\exception\PDOException
     */

    public static function updateInfo($params)
    {
        $userID = Token::getCurrentUid();
        return self::where('id', '=', $userID)->update([
            'name'          => $params['name'],
            'email'         => $params['email'],
            'amazon_profile'=> $params['profile'],
        ]);
    }


    /**
     * 验证登陆用户信息
     * @param $useremail
     * @param $password
     * @return $this|array
     * @throws InvalidParamException
     */

    public static function verify($useremail, $password)
    {
        try {
            $user = self::where('email', $useremail)->findOrFail();
        } catch (Exception $ex) {
            throw new InvalidParamException(['msg' => 'None exists with this email']);
        }

        if (!self::checkPassword($user->password, $password)) {
            throw new InvalidParamException([
                'code' => 400,
                'msg' => 'Invalid password',
                'error_code' => 10030
            ]);
        }

        return $user->hidden(['password']);
    }

    /**
     * 前台用户注册过程
     * @param $useremail
     * @param $password
     * @return array|null|\PDOStatement|string|\think\Model|static
     * @throws InvalidParamException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function signUp($useremail, $password)
    {

        $user = self::where('email', $useremail)->find();
        if ($user) {
            if (!$user->password) {
                $user->password = $password;
                $user->save();
            } else {
                throw new InvalidParamException([
                    'msg' => 'this email has been registered.'
                ]);
            }
        }

        $user = self::newUser(['email' => $useremail, 'password' => $password]);
        return $user;
    }


    public static function newUser($data)
    {
        $userInfo = [
            'userid' => !empty($data['userID']) ? $data['userID'] : time(),
            'name' => !empty($data['name']) ? $data['name'] : "",
            'password' => !empty($data['password']) ? md5($data['password']) : '',
            'nickname' => !empty($data['nickname']) ? $data['nickname'] : "",
            'email' => !empty($data['email']) ? $data['email'] : "",
            'avatar' => !empty($data['avatar']) ? $data['avatar'] : self::createAvatar($data),
        ];
        if (!empty($data['message_id'])) {
            $userInfo['message_id'] = $data['message_id'];
        }

        $user = self::create($userInfo);
        return $user;
    }


    public static function createAvatar($data)
    {
        $name = !empty($data['name']) ? $data['name'] : (!empty($data['nickname'])? $data['nickname'] : substr(uniqid(), 0,2));
        $config = config('avatar.');
        $avatar = new Avatar($config);
        $filePath = 'avatar/'.md5(slugify($name)).'.png';
        $avatar->create($name)->save('./media/'.$filePath, $quality = 100);
        return config('img_prefix').$filePath;

    }

    /**
     * 通过某一个app下的用户ID获取所有APP下的信息
     * @param $senderID
     * @return mixed
     * @throws Exception
     * @throws \Exception
     */
    public static function retrievePSIDUserInfo($senderID)
    {
        // 获取用户的message_id更新对应的数据信息
        $appsecretProof= hash_hmac('sha256', config('fb.PAGE_ACCESS_TOKEN'), config('fb.APP_SECRET'));
        $retrieveUrl = sprintf(config('fb.RETRIEVE_ID_API'), $senderID);

        $params = [
            'app' => config('fb.APP_ID'),
            'access_token' => config('fb.PAGE_ACCESS_TOKEN'),
            'appsecret_proof' => $appsecretProof,
        ];
        $res = curlHttp($retrieveUrl, $params, 'GET',  ['Content-Type: application/json'], false);

        return $res = json_decode($res, true);
    }

    /**
     * 在messenger中获取messenger用户profile
     * @param $senderID
     * @return mixed
     * @throws Exception
     * @throws \Exception
     */
    public static function retrieveMsgUserInfoByMessageID($senderID)
    {
        $retrieveUrl = sprintf(config('fb.RETRIEVE_MESSENGER_INFO_API'), $senderID, config('fb.PAGE_ACCESS_TOKEN'));
        $res = curlHttp($retrieveUrl, [], 'GET',  ['Content-Type: application/json'], false);
        return json_decode($res, true);
    }





}

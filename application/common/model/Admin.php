<?php
/**
 * Created by PhpStorm.
 * User: 沁塵
 * Date: 2019/2/19
 * Time: 11:22
 */

namespace app\common\model;

use think\facade\Config;
use think\facade\Request;
use think\Model;
use app\lib\exception\user\UserException;
use think\Exception;
use think\model\concern\SoftDelete;

class Admin extends Model
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
    protected $autoWriteTimestamp = 'datetime';
    protected $hidden = ['delete_time', 'update_time'];

    /**
     * @param $params
     * @throws UserException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function createUser($params)
    {
        $user = self::where('username', $params['username'])->find();
        if ($user) {
            throw new UserException([
                'code' => 400,
                'msg' => '用户名重复，请重新输入',
                'error_code' => 10030
            ]);
        }
        $user = self::where('email', $params['email'])->find();
        if ($user) {
            throw new UserException([
                'code' => 400,
                'msg' => '注册邮箱重复，请重新输入',
                'error_code' => 10030
            ]);
        }
        $params['password'] = md5($params['password']);
        $params['admin'] = 1;
        $params['active'] = 1;
        self::create($params);
    }

    /**
     * @param $uid
     * @param $params
     * @throws UserException
     */
    public static function updateUserInfo($uid, $params)
    {
        $user = self::find($uid);
        if (isset($params['email']) && $user['email'] != $params['email']) {
            $exists = self::where('email', $params['email'])
                ->field('email')
                ->find();

            if ($exists) throw  new UserException([
                'code' => 400,
                'msg' => '注册邮箱重复，请重新输入',
                'error_code' => 10030
            ]);
        }
        $user->save($params);
    }

    /**
     * @param $params
     * @return array
     * @throws \think\exception\DbException
     */
    public static function getAdminUsers($params)
    {
        $group = [];
        if (array_key_exists('group_id', $params)) $group = ['group_id' => $params['group_id']];

        list($start, $count) = paginate();

        $userList = self::where('admin', '<>', 2)
            ->where($group)
            ->field('password,delete_time,update_time', true);

        $totalNums = $userList->count();
        $userList = $userList->limit($start, $count)->select();

        $userList = array_map(function ($item) {
            $group = Group::get($item['group_id']);
            $item['group_name'] = $group['name'];
            return $item;
        }, $userList->toArray());

        $result = [
            'items' => $userList,
            'total' => $totalNums,
            'count' => Request::get('count/d'),
            'page' => Request::get('page/d'),
            'total_page' => ceil($totalNums / Request::get('count'))
        ];

        return $result;
    }

    public static function changePassword($uid, $params)
    {
        $user = self::find($uid);
        if (!self::checkPassword($user->password, $params['old_password'])) {
            throw new UserException([
                'code' => 400,
                'msg' => '原始密码错误，请重新输入',
                'error_code' => 10030
            ]);
        }

        $user->password = md5($params['new_password']);
        $user->save();
    }

    /**
     * @param $params
     * @throws UserException
     */
    public static function resetPassword($params)
    {
        $user = User::find($params['uid']);
        if (!$user) {
            throw new UserException();
        }

        $user->password = md5($params['new_password']);
        $user->save();
    }

    /**
     * @param $uid
     * @throws UserException
     */
    public static function deleteUser($uid)
    {
        $user = User::find($uid);
        if (!$user) {
            throw new UserException();
        }

        User::destroy($uid);
    }

    /**
     * @param $params
     * @throws UserException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function updateUser($params)
    {
        $user = User::find($params['uid']);
        if (!$user) {
            throw new UserException();
        }

        $emailExist = self::where('email', $params['email'])->find();
        if ($emailExist && $params['email'] != $user['email']) {
            throw new UserException([
                'code' => 400,
                'msg' => '注册邮箱重复，请重新输入',
                'error_code' => 10030
            ]);
        }

        $user->save($params);

    }


    /**
     * @param $params [url,uid]
     * @throws UserException
     */
    public static function updateUserAvatar($uid, $url)
    {
        $user = User::find($uid);
        if (!$user) {
            throw new UserException();
        }
        $user->avatar = $url;
        $user->save();
    }

    /**
     * @param $username
     * @param $password
     * @return array|\PDOStatement|string|\think\Model
     * @throws UserException
     */
    public static function verify($username, $password)
    {
        try {
            $user = self::where('username', $username)->findOrFail();
        } catch (Exception $ex) {
            throw new UserException();
        }

        if (!$user->active) {
            throw new UserException([
                'code' => 400,
                'msg' => '账户已被禁用，请联系管理员',
                'error_code' => 10070
            ]);
        }

        if (!self::checkPassword($user->password, $password)) {
            throw new UserException([
                'code' => 400,
                'msg' => '密码错误，请重新输入',
                'error_code' => 10030
            ]);
        }

        return $user->hidden(['password']);

    }

    /**
     * @param $uid
     * @return array|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws UserException
     */
    public static function getUserByUID($uid)
    {
        try {
            $user = self::field('password', true)
                ->findOrFail($uid)->toArray();
        } catch (Exception $ex) {
            throw new UserException();
        }

        $auths = Auth::getAuthByGroupID($user['group_id']);

        $auths = empty($auths) ? [] : split_modules($auths);

        $user['auths'] = $auths;

        return $user;
    }


    private static function checkPassword($md5Password, $password)
    {
        return $md5Password === md5($password);
    }

    function getAvatarAttr($value)
    {
        $url = $value;
        if ($value) {
            $host = Config::get('file.host') ?? "http://127.0.0.1:8000";
            $storeDir = Config::get('file.store_dir');
            $url = $host . '/' . $storeDir . '/' . $value;
        }

        return $url;
    }

}
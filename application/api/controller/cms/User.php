<?php

namespace app\api\controller\cms;

//use app\api\validate\user\LoginForm;  # 开启注释验证器以后，本行可以去掉，这里做更替说明
//use app\api\validate\user\RegisterForm; # 开启注释验证器以后，本行可以去掉，这里做更替说明
use app\lib\token\Token;
use app\common\model\Admin as AdminUser;
use think\Controller;
use think\facade\Hook;
use think\Request;

class User extends Controller
{
    /**
     * * 账户登陆
     * @param Request $request
     * @return array
     * @throws \app\lib\exception\user\UserException
     */
    public function login(Request $request)
    {
//        (new LoginForm())->goCheck();  # 开启注释验证器以后，本行可以去掉，这里做更替说明
        $params = $request->post();

        $user = AdminUser::verify($params['username'], $params['password']);
        $result = Token::getToken($user);

        Hook::listen('logger', array('uid' => $user->id, 'username' => $user->username, 'msg' => '登陆成功获取了令牌'));

        return $result;
    }

    /**
     * 用户更新信息
     * @param Request $request
     * @return \think\response\Json
     * @throws \UnexpectedValueException
     * @throws \app\lib\exception\token\TokenException
     * @throws \app\lib\exception\user\UserException
     * @throws \think\Exception
     */
    public function update(Request $request)
    {
        $params = $request->put();
        $uid = Token::getCurrentUID();
        AdminUser::updateUserInfo($uid, $params);
        return writeJson(201, '', '操作成功');
    }

    /**
     * 修改密码
     * @param Request $request
     * @return \think\response\Json
     * @throws \UnexpectedValueException
     * @throws \app\lib\exception\token\TokenException
     * @throws \app\lib\exception\user\UserException
     * @throws \think\Exception
     */
    public function changePassword(Request $request)
    {
        $params = $request->put();
        $uid = Token::getCurrentUID();
        AdminUser::changePassword($uid, $params);

        Hook::listen('logger', '修改了自己的密码');
        return writeJson(201, '', '密码修改成功');
    }


    /**
     * 查询自己拥有的权限
     * @return array|\PDOStatement|string|\think\Model
     * @throws \UnexpectedValueException
     * @throws \app\lib\exception\token\TokenException
     * @throws \app\lib\exception\user\UserException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllowedApis()
    {
        $uid = Token::getCurrentUID();
        $result = AdminUser::getUserByUID($uid);
        return $result;
    }

    /**
     * @auth('创建用户','管理员','hidden')
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\lib\exception\user\UserException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function register(Request $request)
    {
//        (new RegisterForm())->goCheck(); # 开启注释验证器以后，本行可以去掉，这里做更替说明

        $params = $request->post();
        AdminUser::createUser($params);

        Hook::listen('logger', '创建了一个用户');

        return writeJson(201, '', '用户创建成功');
    }

    /**
     * @return mixed
     * @throws \UnexpectedValueException
     * @throws \app\lib\exception\token\TokenException
     * @throws \think\Exception
     */
    public function getInformation()
    {
        $user = Token::getCurrentUser();
        return $user;
    }

    /**
     * @param Request $request
     * @param ('url','头像url','require|url')
     * @return \think\response\Json
     * @throws \UnexpectedValueException
     * @throws \app\lib\exception\token\TokenException
     * @throws \app\lib\exception\user\UserException
     * @throws \think\Exception
     */
    public function setAvatar(Request $request)
    {
        $url = $request->put('avatar');
        $uid = Token::getCurrentUID();
        AdminUser::updateUserAvatar($uid, $url);

        return writeJson(201, '', '更新头像成功');
    }


    /**
     * @return array
     * @throws \UnexpectedValueException
     * @throws \app\lib\exception\token\TokenException
     * @throws \think\Exception
     */
    public function refresh()
    {
        $result = Token::refreshToken();
        return $result;
    }

}

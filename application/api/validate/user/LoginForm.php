<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/12/28
 * Time: 15:20
 */

namespace app\api\validate\user;


use app\common\validate\BaseValidate;

class LoginForm extends BaseValidate
{
    protected $rule = [
        'username' => 'require',
        'password' => 'require',
    ];
    protected $message = [
        'username' => '用户名不能为空',
        'password' => '密码不能为空'
    ];
}
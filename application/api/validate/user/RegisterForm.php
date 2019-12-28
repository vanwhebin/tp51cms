<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/12/28
 * Time: 15:21
 */

namespace app\api\validate\user;


use app\common\validate\BaseValidate;

class RegisterForm extends BaseValidate
{
    protected $rule = [
        'password' => 'require|confirm:confirm_password',
        'confirm_password' => 'require',
        'username' => 'require|length:2,10',
        'group_id' => 'require|>:0|number',
        'email' => 'email'
    ];
}
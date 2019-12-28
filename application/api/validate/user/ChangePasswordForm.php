<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/12/28
 * Time: 15:19
 */

namespace app\api\validate\user;


use app\common\validate\BaseValidate;

class ChangePasswordForm extends BaseValidate
{
    protected $rule = [
        'old_password|原始密码' => 'require',
        'new_password|新密码' => 'require|confirm:confirm_password',
        'confirm_password|确认密码' => 'require',
    ];
}

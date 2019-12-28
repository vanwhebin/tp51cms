<?php
/**
 * Created by PhpStorm.
 * User: 沁塵
 * Date: 2019/4/30
 * Time: 16:22
 */

namespace app\lib\exception\token;


use app\lib\exception\BaseException;

class TokenException extends BaseException
{
    public $code = 401;
    public $msg  = 'Token已过期或无效Token';
    public $errorCode = '10001';
}
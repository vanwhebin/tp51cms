<?php
/**
 * Created by PhpStorm.
 * User: daogu
 * Date: 2017/6/1
 * Time: 22:19
 */

namespace app\lib\exception;


class ForbiddenException extends BaseException
{
    public $code = 403;
    public $msg  = 'You are not allowed to access';
    public $errorCode = 10002;
}
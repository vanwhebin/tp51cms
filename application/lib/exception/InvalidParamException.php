<?php

namespace app\lib\exception;

/**
 * Class InvalidParamException
 * @package app\lib\exception
 * 通用参数异常错误
 */
class InvalidParamException extends BaseException
{
    public $code = 400; // 参数错误通常400返回
    public $msg = 'Invalid params';
    public $errorCode = 10000;
}
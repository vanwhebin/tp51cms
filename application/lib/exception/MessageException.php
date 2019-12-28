<?php

namespace app\lib\exception;

class MessageException extends BaseException
{
    public $code = 400; // 参数错误通常400返回
    public $msg = 'Error occurred when sending to facebook';
    public $errorCode = 70000;
}
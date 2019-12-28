<?php

namespace app\lib\exception;


class TokenException extends BaseException
{
    public $code = 401;
    public $msg = 'Invalid Token or Expired Token';
    public $errorCode = 10001;

}
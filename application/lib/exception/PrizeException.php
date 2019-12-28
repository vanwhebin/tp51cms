<?php

namespace app\lib\exception;


class PrizeException extends BaseException
{
    public $code = 404;
    public $msg = 'No product data';
    public $errorCode = 20000;
}
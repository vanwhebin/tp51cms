<?php

namespace app\lib\exception;


class MissingException extends BaseException
{
    public $code = 404;
    public $msg = 'No data';
    public $errorCode = 40000;
}
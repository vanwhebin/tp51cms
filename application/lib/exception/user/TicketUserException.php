<?php
/**
 * Created by PhpStorm.
 * User: daogu
 * Date: 2017/5/29
 * Time: 23:50
 */

namespace LinCmsTp5\admin\exception\user;


use app\lib\exception\BaseException;

class TicketUserException extends BaseException
{
    public $code = 404;
    public $msg  = '用户不存在';
    public $error_code = '20000';
}
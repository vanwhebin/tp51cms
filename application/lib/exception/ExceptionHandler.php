<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/11/15
 * Time: 17:56
 */

namespace app\lib\exception;

use Exception;
use think\exception\Handle;
use think\facade\Log;
use think\facade\Request;

class ExceptionHandler extends Handle
{
    private $code;
    private $msg;
    private $errorCode;
    // 需要返回用户的请求url 路径

    public function render(Exception $e)
    {
        if ($e instanceof BaseException) {
            // 如果是自定义的异常
            $this->code = $e->code;
            $this->msg = $e->msg;
            $this->errorCode = $e->errorCode;

        } else {
            // 如果是服务器内部未知错误
            if (config('app_debug')) {
                return parent::render($e);
            } else {
                $this->code = 500;
                $this->msg = 'Sever unknown error';
                $this->errorCode = 999;
                $this->recordErrorLog($e);
            }
        }

        $result = [
            'msg' => $this->msg,
            'error_code' => $this->errorCode,
            'request_url' => Request::url(),
        ];

        return json($result, $this->code);
    }

    private function recordErrorLog(Exception $e)
    {
        Log::init([
            'type' => 'file',
            'path' => '../../log/',
            'level' => ['error'],
        ]);
        Log::record($e->getMessage(), 'error');
    }

}
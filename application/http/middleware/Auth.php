<?php

namespace app\http\middleware;

use app\lib\auth\Auth as Permission;
use app\lib\exception\token\ForbiddenException;

class Auth
{
    /**
     * 权限验证
     * @param $request
     * @param \Closure $next
     * @return mixed
     * @throws ForbiddenException
     * @throws \Exception
     * @throws \UnexpectedValueException
     * @throws \app\lib\exception\token\DeployException
     * @throws \app\lib\exception\token\TokenException
     * @throws \app\lib\exception\user\UserException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function handle($request, \Closure $next)
    {
        $auth = (new Permission($request))->check();
        if (!$auth) {
            throw new ForbiddenException();
        }
        return $next($request);
    }
}

<?php
namespace app\lib\token;

use app\lib\exception\token\TokenException;
use Firebase\JWT\JWT;
use \Firebase\JWT\SignatureInvalidException;
use \Firebase\JWT\BeforeValidException;
use \Firebase\JWT\ExpiredException;
use LinCmsTp5\admin\model\User;
use think\Exception;
use think\facade\Config;
use think\facade\Request;

class Token
{
    public static function getToken($user)
    {
        $accessToken = self::createAccessToken($user);
        $refreshToken = self::createRefreshToken($user);
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken
        ];
    }

    /**
     * @return array
     * @throws Exception
     * @throws TokenException
     * @throws \UnexpectedValueException
     */
    public static function refreshToken()
    {
        $user = self::getCurrentUser();
        $accessToken = self::createAccessToken($user);

        return [
            'access_token' => $accessToken,
        ];
    }

    private static function createAccessToken($user)
    {
        $key = config('secure.token_salt');
        $payload = [
            'iss' => Config::get('domain'), //签发者
            'iat' => time(), //什么时候签发的
            'exp' => time() + 7200, //过期时间
            'user' => $user,
        ];
        $token = JWT::encode($payload, $key);
        return $token;

    }

    private static function createRefreshToken($user)
    {
        $key = config('secure.token_salt');
        $payload = [
            'iss' => Config::get('domain'), //签发者
            'iat' => time(), //什么时候签发的
            'user' => $user,
        ];
        $token = JWT::encode($payload, $key);
        return $token;
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws TokenException
     * @throws \UnexpectedValueException
     */
    public static function getCurrentUser()
    {
        $uid = self::getCurrentUID();
        $user = User::get($uid);
        return $user->hidden(['password']);
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws TokenException
     * @throws \UnexpectedValueException
     */
    public static function getCurrentUID()
    {
        $uid = self::getCurrentTokenVar('id');
        return $uid;
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws TokenException
     * @throws \UnexpectedValueException
     */
    public static function getCurrentName()
    {
        $uid = self::getCurrentTokenVar('nickname');
        return $uid;
    }

    /**
     * @param $key
     * @return mixed
     * @throws Exception
     * @throws TokenException
     * @throws \UnexpectedValueException
     */
    private static function getCurrentTokenVar($key)
    {
        $authorization = Request::header('authorization');

        if (!$authorization) {
            throw new TokenException(['msg' => '请求未携带authorization信息']);
        }

        list($type, $token) = explode(' ', $authorization);

        if ($type !== 'Bearer') throw new TokenException(['msg' => '接口认证方式需为Bearer']);

        if (!$token) {
            throw new TokenException(['msg' => '尝试获取的authorization信息不存在']);
        }

        $secretKey = config('secure.token_salt');

        try {
            $jwt = (array)JWT::decode($token, $secretKey, ['HS256']);
        } catch (SignatureInvalidException $e) {  //签名不正确
            throw new TokenException(['msg' => '令牌签名不正确']);
        } catch (BeforeValidException $e) {  // 签名在某个时间点之后才能用
            throw new TokenException(['msg' => '令牌尚未生效']);
        } catch (ExpiredException $e) {  // token过期
            throw new TokenException(['msg' => '令牌已过期，刷新浏览器重试']);
        } catch (Exception $e) {  //其他错误
            throw new Exception($e->getMessage());
        }
        if (array_key_exists($key, $jwt['user'])) {
            return $jwt['user']->$key;
        } else {
            throw new TokenException(['msg' => '尝试获取的Token变量不存在']);
        }

    }
}
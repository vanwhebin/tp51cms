<?php


namespace app\common\validate;

use app\lib\exception\InvalidParamException;
use think\facade\Request;
use think\Validate;

class BaseValidate extends Validate
{
    /**
     * 自定义验证的基类, 继承框架验证基类满足业务需要
     * @return bool
     * @throws InvalidParamException
     */
    public function validate()
    {
        // 一个基本操作, 验证传递数据,所以首先要明白路由和数据传递方式
        $request = Request::instance();
        $params = $request->param();

        if (!$this->batch()->check($params)) {
            $e = new InvalidParamException([
                'msg' => $this->error,
            ]);
            throw $e;
        } else {
            return true;
        }
    }

    /**
     * @param mixed $value 传递的值
     * @param string $rule 规则
     * @param string $data 参数
     * @param string $field  具体的字段
     * @return mixed 返回结果
     */
    protected function isPositiveInteger($value, $rule = '', $data = '', $field = '')
    {
        if (is_numeric($value) && is_int($value + 0) && ($value + 0) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param mixed $value 传递的值
     * @param string $rule 规则
     * @param string $data 参数
     * @param string $field  具体的字段
     * @return mixed 返回结果
     */
    protected function isNotEmpty($value, $rule = '', $data = '', $field = '')
    {
        $value = $value . ' ';
        if (trim($value) !== '') {
            return true;
        } else {
            return false;
        }
    }
}
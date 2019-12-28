<?php

namespace app\api\validate;

use think\Validate;
use app\lib\exception\InvalidParamException;
use \think\Request;

class ApiValidate
{
    public $rule;
    public $field;
    public $request;
    public $scene;

    public function __construct($rule, Request $request,array $field = [], string $scene = null)
    {
        $this->request = $request;
        $this->rule = $rule;
        $this->field = $field;
        $this->scene = $scene;
    }

    /**
     * @return bool
     * @throws InvalidParamException
     */
    public function check(){
        if (is_string($this->rule)) {
            $validate = new $this->rule();
            (!empty($this->scene)) && $validate = $validate->scene($this->scene);
        }else{
            $validate = (new Validate())->make($this->rule,[],$this->field);
        }

        $res = $validate->batch()->check($this->request->param());
        if(!$res){
            throw new InvalidParamException([
                'message' => $validate->getError(),
            ]);
        }
        return true;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/10/14
 * Time: 20:09
 */

namespace app\api\model;


use app\lib\enum\PlatformEnum;

class ProductCode extends BaseModel
{
    protected  $hidden = ['delete_time'];

    public function getPlatformAttr($value, $data)
    {
        $typeArr = PlatformEnum::PLATFORM_TYPE;
        return $typeArr[$value];
    }

}
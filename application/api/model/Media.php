<?php

namespace app\api\model;


class Media extends BaseModel
{
    protected $hidden = ['delete_time', 'update_time', 'from', 'extension', 'md5'];

    /**
     * 获取图片保存的完整路径
     * @param $value string 当前图片路径
     * @param $data array 当前模型数据
     * @return string
     */
    public function getUrlAttr($value, $data)
    {
        return $this->prefixImgUrl($value, $data);
    }


}

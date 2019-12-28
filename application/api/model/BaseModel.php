<?php


namespace app\api\model;

use think\model\concern\SoftDelete;
use think\Model;

class BaseModel extends Model
{
    use SoftDelete;

    protected $autoWriteTimestamp = true;
    protected $hidden = ['delete_time'];

    protected function  prefixImgUrl($value, $data){
        $finalUrl = $value;
        if($data['from'] == 1){
            // 当文件为本地存储
            $finalUrl = config('img_prefix') . $value;
        }
        return $finalUrl;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/11/17
 * Time: 13:06
 */

namespace app\api\model;


class RebateUser extends BaseModel
{
    protected $hidden = ['delete_time'];

    public function image()
    {
        return $this->belongsTo('Media', 'image_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id');
    }


}
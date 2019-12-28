<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/10/16
 * Time: 12:17
 */

namespace app\api\model;


class Fakers extends BaseModel
{
    protected $hidden = ['delete_time'];

    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id');
    }

}
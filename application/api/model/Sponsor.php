<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/7/12
 * Time: 21:46
 */

namespace app\api\model;


class Sponsor extends BaseModel
{
    public function activity()
    {
        return $this->hasOne('Campaign', 'sponsor_id', 'id');
    }
}
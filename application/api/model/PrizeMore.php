<?php

namespace app\api\model;

use think\Model;

class PrizeMore extends Model
{
    protected $hidden = ['delete_time'];

    public function code()
    {
        return $this->belongsTo('PrizeCode', 'code_id', 'id');
    }

    public function activity()
    {
        return $this->belongsTo('Activity', 'activity_id', 'id');
    }

    public function prize()
    {
        return $this->belongsTo('Prize', 'prize_id', 'id');
    }

}

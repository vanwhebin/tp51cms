<?php

namespace app\api\model;

use think\Model;

class ProductMore extends Model
{
    protected $hidden = ['delete_time'];

    public function code()
    {
        return $this->belongsTo('ProductCode', 'code_id', 'id');
    }

    public function campaign()
    {
        return $this->belongsTo('Campaign', 'campaign_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo('Product', 'product_id', 'id');
    }

}

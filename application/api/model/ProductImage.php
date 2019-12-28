<?php

namespace app\api\model;

use think\exception\DbException;
use think\Model;

class ProductImage extends Model
{
    protected $hidden = ['delete_time', 'img_id', 'id', 'product_id'];

    public function img()
    {
        return $this->belongsTo('Media','img_id','id');
    }

    public function createImg($imgIDArr, $productID)
    {
        $prizeImg = [];
        foreach ($imgIDArr as $index=>$item) {
            $tmp['img_id'] = $item;
            $tmp['product_id'] = $productID;
            $tmp['order'] = $index;
            $prizeImg[] = $tmp;
        }
        try {
            $res = $this->saveAll($prizeImg);
            if (!$res) {
                throw new DbException('批量保存失败');
            }
            return ['error' => 0];
        } catch(\Exception $e) {
            return ['error' => 1, 'msg' => $e->getMessage()];
        }
    }

    public function updateImg($imgIDArr, $productID)
    {
        self::destroy(['product_id' => $productID]);
        return $this->createImg($imgIDArr, $productID);
    }


}

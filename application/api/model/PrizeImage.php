<?php

namespace app\api\model;

use think\exception\DbException;
use think\Model;

class PrizeImage extends Model
{
    protected $hidden = ['delete_time', 'img_id', 'id', 'prize_id'];

    public function img()
    {
        return $this->belongsTo('Media','img_id','id');
    }

    public function createImg($imgIDArr, $prizeID)
    {
        $prizeImg = [];
        foreach ($imgIDArr as $index=>$item) {
            $tmp['img_id'] = $item;
            $tmp['prize_id'] = $prizeID;
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

    public function updateImg($imgIDArr, $prizeID)
    {
        self::destroy(['prize_id' => $prizeID]);
        return $this->createImg($imgIDArr, $prizeID);
    }


}

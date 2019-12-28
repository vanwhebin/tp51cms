<?php

namespace app\api\model;

use app\api\model\Media as MediaModel;
use app\api\model\PrizeImage as PrizeImageModel;
use app\lib\exception\InvalidParamException;
use think\Db;
use think\Exception;
use think\exception\DbException;

class Prize extends BaseModel
{
    protected $hidden = [
        'delete_time',
        'pivot',
        'img_id',
        'video_id',
    ];

    public function mainImg()
    {
        return $this->belongsTo('Media', 'img_id', 'id');
    }

    public function video()
    {
        return $this->belongsTo('Media', 'video_id', 'id');
    }

    public function album()
    {
        return $this->hasMany('PrizeImage', 'prize_id', 'id');
    }

    /**
     * * 获取所有的奖品
     * @param int $num
     * @param int $page
     * @return array
     * @throws DbException
     */
    public function getAll($num=15, $page=1)
    {
        return self::order('id desc')->paginate($num, false, ['page' => $page])->toArray();
    }


    protected function getMainImgUrlAttr($value, $data)
    {
        return $this->prefixImgUrl($value, $data);
    }

    protected function getFromAttr($value, $data)
    {
        switch ($value){
            case 1:
                $value = "local";
                break;
            case 2:
                $value = "internet";
                break;
            default:
                $value = "local";
                break;
        }
        return $value;
    }

    /**
     * @param $num
     * @return array|\PDOStatement|string|\think\Collection
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function latest($num)
    {
        return self::with(['mainImg'])->order('create_time desc')->limit($num)->select();
    }

    /**
     * @param $data
     * @return array
     */
    public static function createOne($data)
    {
        Db::startTrans();
        try{
            if (count($data['img_id']) < 1) {
                throw new InvalidParamException(['msg' => '请上传图片']);
            } else {
                $mainImg = MediaModel::get($data['img_id'][0])->getData();
                $prize = self::create([
                    "name" => $data['name'],
                    "summary" => $data['summary'],
                    "img_id" => $mainImg['id'],
                    "video_id" => $data['video_id'] ?: null,
                    "main_img_url" => $mainImg['url'],
                ]);
                $prizeImageModel = new PrizeImageModel();
                $res = $prizeImageModel->createImg($data['img_id'], $prize->id);
                if ($res['error']) {
                    throw new DbException('创建奖品数据失败');
                }
            }
        }catch(\Exception $e){
            Db::rollback();
            return ['error' => ['code' => $e->getCode(), 'msg' => $e->getMessage()]];
        }
        Db::commit();
        return ['error'=> ''];
    }

    /**
     * 获取具体分类下的产品
     * @param $id
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function productInCate($id)
    {
        return self::with(['mainImg'])->where('category_id', '=', $id)
            ->order('create_time desc')
            ->select();
    }

    /**
     * 获取具体id的产品详情
     * @param $id
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getOne($id)
    {
        return self::with(['album' => function($query){
           $query->with(['img'])->order('order', 'asc')->visible(['url', 'img_id']);
        }])->where('id', '=', $id)->find();
    }


    public static function updateOne($data)
    {
        // 处理更新操作
        $prizeImageModel = new PrizeImageModel();
        return $prizeImageModel->updateImg($data['img_id'], $data['id']);
    }

    public function deleteOne($id)
    {
        Db::startTrans();
        try{
            self::destroy($id);
            if (!PrizeImageModel::destroy(['prize_id' => $id])){
                throw new DbException('删除奖品图片失败');
            }
        }catch(Exception $e){
           Db::rollback();
           return ['error' => ['code' => $e->getCode(), 'msg' => $e->getMessage()]];
        }

        Db::commit();
        return ['error' => 0];
    }

    /*public static function all($num=20, $page=1)
    {
        // 获取所有prize信息
        return self::with(['video' => function($query){
            $query->field(['id', 'url', 'from']);
        }])->order('order desc, create_time desc, id desc')
            ->paginate($num, false, ['page' => $page]);
    }*/

}

<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/11/2
 * Time: 17:27
 */

namespace app\api\model;


use app\lib\enum\FreebieEnum;

class Freebies extends BaseModel
{
    protected $hidden = ['delete_time'];

    public function  prize()
    {
        return $this->belongsTo('Prize', 'prize_id', 'id');
    }

    /**
     * 用加密的landing code查询对应的freebie产品
     * @param $landingCode
     * @return array|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getFreebieByCode($landingCode)
    {
        return self::where(['landing_code' => $landingCode])->findOrEmpty();
    }


    /**
     * 用加密的slug查询对应的freebie产品
     * @param $slug
     * @return array|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getFreebieBySlug($slug)
    {
        return self::with(['prize', 'prize.album', "prize.album.img"])->where(['slug' => $slug])->find();
    }

    /**
     * 获取所有的Deals产品
     * @param int $num
     * @param int $page
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */

    public static function allDeals($num=20, $page=1)
    {
        // 获取所有的deals产品
        $all = self::with(['prize'=>function($query){
            $query->field(['id', 'main_img_url', 'from'])->visible(['main_img_url']);
        }])->order('order desc, start_time desc, id desc')
            ->where(['status'=> FreebieEnum::SHOW])
            ->where('stock', '>', 0)
            ->where('start_time', '<', time())
            ->where('over_time','>', time())
            ->hidden(['id', 'prize_id'])
            ->whereOr([['type', '=', FreebieEnum::BOTHTYPE], ['type', '=', FreebieEnum::DEAL]])
            ->paginate($num, false, ['page' => $page]);
        return $all;
    }

    /**
     * 获取所有的freebies产品
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function allFreebies()
    {
        return self::with(['prize'=> function($query){
            $query->field(['id', 'main_img_url', 'from'])->visible(['main_img_url']);
        }])->where('stock', '>', 0)
            ->where(['status'=> FreebieEnum::SHOW])
            ->where('start_time', '<', time())
            ->where('over_time','>', time())
            ->order('order', 'desc')
            ->select();
    }



}
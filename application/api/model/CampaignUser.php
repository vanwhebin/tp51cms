<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/6/17
 * Time: 16:01
 */

namespace app\api\model;

class CampaignUser extends BaseModel
{
    protected $hidden = ['delete_time'];

    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id');
    }

    public function campaign()
    {
        return $this->belongsTo('Campaign', 'campaign_id', 'id');
    }


    /**
     * @param int $num
     * @param int $page
     * @param $campaignID
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getUsersByCampaignID($num=7, $page=1, $campaignID)
    {
        return self::with(['user' => function($query){
            $query->visible(['name', 'avatar', 'id']);
        }])->where(['campaign_id' => $campaignID])
            ->order('id desc')
            ->visible(['user'])
            ->paginate($num, false, ['page' => $page]);
    }

    /**
     * @param $campaignID
     * @param $userID
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getOne($campaignID, $userID)
    {
        return self::where([
            'user_id'       =>  $userID,
            'campaign_id'   =>  $campaignID,
        ])->find();
    }

    /**
     * @param $userID
     * @param $campaignID
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public  static function getUserInfo($userID, $campaignID)
    {
        return self::with(['user' => function($query){
           $query->field(['id', 'name', 'avatar'])->hidden(['id']);
        }])->where(['user_id' => $userID, 'campaign_id' => $campaignID])
            ->field('user_id')
            ->visible(['user'])
            ->find();
    }





}
<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/6/17
 * Time: 15:43
 */

namespace app\api\model;

use app\api\model\CampaignProduct as ActivityPrizeModel;
use app\lib\enum\ActivityEnum;
use app\lib\enum\InfluencerEnum;
use app\lib\exception\InvalidParamException;
use think\Db;
use think\Exception;
use think\exception\DbException;

class Campaign extends BaseModel
{
    protected $hidden = ['delete_time', 'create_time', 'img_id'];


    public function thumb()
    {
        return $this->belongsTo('Media', 'img_id', 'id');
    }

    public function product()
    {
        return $this->belongsToMany('Product', 'CampaignProduct', 'product_id', 'campaign_id');
    }

    public function user()
    {
        return $this->belongsToMany('User','CampaignUser', 'user_id', 'campaign_id');
    }

    public function more()
    {
        return $this->hasMany('ProductMore', 'campaign_id', 'id');
    }

    public function sponsor()
    {
        return $this->belongsTo('Sponsor', 'sponsor_id', 'id');
    }



    /**
     * @param $slug
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getInfoBySlug($slug)
    {
        return self::where('slug','=', $slug)->find();
    }

    /**
     * 通过slug查找活动ID
     * @param $slug
     * @return mixed
     */
    public static function getIDBySlug($slug)
    {
        return self::where('slug', '=', $slug)->value('id');
    }


    /**
     * @param $id
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getSimpleInfo($id)
    {
        return self::with(['thumb'])->where('id','=', $id)->find();
    }


    /**
     * @param int $num
     * @param int $page
     * @return \think\Paginator
     * @throws DbException
     */
    public static function getAllSimpleInfo($num=15, $page=1)
    {
        return self::with([
            'thumb' => function($query){
                $query->visible(['url']);
            }, 'sponsor' => function($query){
                $query->visible(['name']);
            }
        ])->order('order desc, id desc, start_time desc')->paginate($num, false, ['page' => $page]);
    }


    /**
     * @param $id
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getActivityWithMultiPrize($id)
    {
        return self::with(['prize' => function ($query) {
            $query->with(['mainImg', 'video'])->field(['prize.id', 'name', 'img_id', 'video_id'])
                ->hidden(['id']);
        }, 'thumb'])->where(['id' => $id])
            ->hidden(['status', 'order', 'update_time', 'sponsor_id', 'influ_id', 'type'])
            ->find();
    }

    /**
     * @param $id
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getActivityWithSinglePrize($id)
    {
        return self::with(['prize' => function ($query) {
            $query->with(['album', 'album.img', 'video']);
        }, 'thumb', 'sponsor'=>function($query){
            $query->field(['id', 'name'])->visible(['name']);
        }])->hidden(['sponsor_id'])
            ->where(['id' => $id])->find();
    }

    /**
     * @param $id
     * @return float|string
     */
    public static function prizeCount($id)
    {
        return ActivityPrizeModel::getVarCount(['campaign_id' => $id]);
    }

    /**
     * @param $id
     * @return array
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function activitySimpleInfo($id)
    {
        $prizeCount = self::prizeCount($id);
        $info  = self::with(['prize'=>function($query){
            $query->visible(['name']);
        }, 'thumb' => function($query){
            $query->visible(['url']);
        }])->where(['id' => $id])->visible(['title', 'prize', 'thumb'])->find()->toArray();
        if ($prizeCount > 1){
            $tmp= array_reduce($info['prize'], function($item, $it){
                return $item.', '.$it['name'];
            });
            $info['prize'] = trim($tmp, ',');
        } else {
            $info['prize'] = $info['prize'][0]['name'];
        }
        return $info;
    }

    /**
     * @param $num
     * @param $page
     * @return \think\Paginator
     * @throws DbException
     */
    public static function allActivityWithPagination($num, $page)
    {
        $allActivity = self::with(['thumb','sponsor'=>function($query){
                $query->field(['name', 'id'])->visible(['name']);
            }])->order('order desc, start_time desc, id desc')
            ->where(['status'=> ActivityEnum::SHOW, 'private' => ActivityEnum::PUBLIC])
            ->paginate($num, false, ['page' => $page]);
        return $allActivity;
    }

    /**
     * @param $id
     * @return array
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function prizeTitle($id)
    {
        $prizeCount = self::prizeCount($id);
        $info  = self::with(['prize'=>function($query){
            $query->visible(['name']);
        }])->where(['id' => $id])->visible(['title', 'prize'])->find()->toArray();
        if ($prizeCount > 1){
            $tmp= array_reduce($info['prize'], function($item, $it){
                return $item.', '.$it['name'];
            });
            $info['prize'] = trim($tmp, ',');
        } else {
            $info['prize'] = $info['prize'][0]['name'];
        }
        return $info;
    }


    public static function createActivity($data)
    {
        Db::startTrans();
        try{
            $slugifyTitle = slugify($data['title']);
            $slug = !self::getInfoBySlug($slugifyTitle) ? $slugifyTitle : $slugifyTitle .'-'. date('m-d');

            $activity = self::create([
                "activity_img_id" =>  intval($data['thumb']),
                "title" => $data['title'],
                "seo_title" =>  $data['seo_title'],
                "slug" =>  $slug,
                "description" =>  htmlspecialchars(addslashes($data['description'])),
                "start_time" =>  self::handleCMSStartTime($data['start_time']),
                "status" =>  intval($data['status']),
                "order" =>  intval($data['order']),
                "sponsor_id" =>  intval($data['sponsor_id']),
                "private" =>  intval($data['private']),
            ]);
            $t = ActivityPrizeModel::create([
                'campaign_id' => $activity->id,
                'product_id' => intval($data['product_id']),
            ]);
        }catch(\Exception $e){
            Db::rollback();
            return ['error' => ['code' => $e->getCode(), 'msg' => $e->getMessage()]];
        }
        Db::commit();
        return ['error' => ''];
    }


    public static function handleCMSStartTime($timestamp)
    {
        if (strlen($timestamp) > 10) {
            $timestamp = substr($timestamp, 0, 10);
        }
        return intval($timestamp);
    }

    /**
     * 更新活动信息
     * @param $data
     * @return array
     */
    public static function updateActivity($data)
    {
        Db::startTrans();
        try{
            if (empty($data['id']) || empty($data['title']) ||
                empty($data['thumb']) || empty($data['seo_title']) ||
                empty($data['start_time']) || empty($data['sponsor_id']) ||
                empty($data['product_id'])) {
                throw new InvalidParamException(['msg' => '活动更新信息不完整']);
            }

            $act = self::get(intval($data['id']));
            $act->activity_img_id = $data['thumb'];
            $act->title = $data['title'];
            $act->seo_title = $data['seo_title'];
            $act->start_time = self::handleCMSStartTime($data['start_time']);
            $act->description = htmlspecialchars(addslashes($data['description']));
            $act->status = intval($data['status']);
            $act->order = intval($data['order']);
            $act->private = intval($data['private']);
            $act->sponsor_id = intval($data['sponsor_id']);
            $act->save();

            ActivityPrizeModel::where(['campaign_id' => $act->id])->update([
                'product_id' => intval($data['product_id']),
            ]);
        }catch(\Exception $e){
            Db::rollback();
            return ['error' => ['code' => $e->getCode(), 'msg' => $e->getMessage()]];
        }
        Db::commit();
        return ['error' => ''];
    }

    public function deleteOne($id)
    {
        Db::startTrans();
        try{
            self::destroy($id);
            if (!ActivityPrizeModel::destroy(['campaign_id' => $id])){
                throw new DbException('删除奖品图片失败');
            }
        }catch(Exception $e){
            Db::rollback();
            return ['error' => ['code' => $e->getCode(), 'msg' => $e->getMessage()]];
        }

        Db::commit();
        return ['error' => 0];
    }


    /**
     * 查出所有绑定红人活动
     * @param int $num
     * @param int $page
     * @return \think\Paginator
     * @throws DbException
     */
    public function getBindActivity($num=10, $page=1)
    {
        return self::with(['influencer'=> function($query){ $query->visible(['name', 'nickname']);}])
            ->field(['title', 'influ_id', 'slug', 'status', 'id'])
            ->where('influ_id','NEQ', InfluencerEnum::NONE)
            ->order('influ_id DESC, id desc')
            ->paginate($num, false, ['page' => $page]);
    }

    /**
     * 处理当前用户下的推荐活动
     * @param $userID  integer 用户ID
     * @param $limit  integer 限制数量
     * @param array $excludedAct  用户是否需要排除一些选项，数组值为活动ID
     * @return array
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getRecommendations($userID, $excludedAct=[], $limit=3)
    {
        $subQuery = function($query) use ($userID) {
            $query->table('activity_user')
                ->where(['user_id' => $userID])
                ->field('campaign_id')
                ->select();
        };
        $model = (new Campaign())->whereNotIn('id', $subQuery)
            ->where(['status' => ActivityEnum::SHOW, 'private' => ActivityEnum::PUBLIC])
            ->where('start_time','>', (time() + (3600*24)))
            ->with(['thumb'])
            ->order('start_time', 'desc')
            ->limit($limit)
            ->field(['id', 'title', 'slug', 'activity_img_id'])
            ->hidden(['activity_img_id']);

        if ($excludedAct) {
            $model->whereNotIn('id', $excludedAct);
        }

        return $model->select()->toArray();
    }



}
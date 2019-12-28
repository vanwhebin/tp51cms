<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/11/18
 * Time: 20:45
 */

namespace app\api\model;


use app\lib\exception\InvalidParamException;

class ReviewerOrder extends BaseModel
{
    protected $hidden = ['delete_time'];

    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo('Product', 'product_id', 'id');
    }

    /**
     * 创建记录
     * @param $data
     * @return bool|static
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function createOne($data)
    {
        $existedOrder = self::where([
            'product_id'    => $data['product_id'],
            'user_id'       => $data['user_id'],
            'message_id'    => $data['message_id']
        ])->find();
        if (!$existedOrder) {
            return self::create($data);
        }
        return false;
    }

    /**
     * 查找购买记录
     * @param null $userID
     * @param null $messageID
     * @param $productID
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws InvalidParamException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function findRec($userID=null, $messageID=null, $productID)
    {
        $where = ['product_id' => $productID];
        if (!$userID && !$messageID) {
            throw new InvalidParamException(['msg' => 'userID和MessageID至少要有一个']);
        }
        if ($userID) {
            $where['user_id'] = $userID;
        }

        if ($messageID) {
            $where['message_id'] = $messageID;
        }

        return self::where($where)->find();
    }




}
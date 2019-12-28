<?php
/**
 * Created by PhpStorm.
 * User: 沁塵
 * Date: 2019/4/26
 * Time: 21:42
 */

namespace app\common\model;

use think\facade\Request;
use think\Model;

class Log extends Model
{
    protected $createTime = 'time';
    protected $updateTime = false;
    protected $autoWriteTimestamp = 'datetime';

    /**
     * @param $params
     * @return array
     * @throws \app\lib\exception\InvalidParamException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getLogs($params)
    {
        $filter = [];
        if (isset($params['name'])) {
            $filter ['user_name'] = $params['name'];
        }

        if (isset($params['start']) && isset($params['end'])) {
            $filter['time'] = [$params['start'], $params['end']];
        }

        list($start, $count) = paginate();

        $logs = self::withSearch(['user_name', 'time'], $filter)
            ->order('time desc');

        $totalNums = $logs->count();
        $logs = $logs->limit($start, $count)->select();

        $result = [
            'items' => $logs,
            'total' => $totalNums,
            'count' => Request::get('count/d'),
            'page' => Request::get('page/d'),
            'total_page' => ceil($totalNums / Request::get('count'))
        ];
        return $result;

    }

    public function searchUserNameAttr($query, $value, $data)
    {
        if (!empty($value)) {
            $query->where('user_name', $value);
        }
    }

    public function searchTimeAttr($query, $value, $data)
    {
        if (!empty($value)) {
            $query->whereBetweenTime('time', $value[0], $value[1]);
        }
    }
}
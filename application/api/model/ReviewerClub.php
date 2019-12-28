<?php

namespace app\api\model;

class ReviewerClub extends BaseModel
{
    protected $hidden = ['delete_time'];


    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id');
    }


    public static function getClubUserByUserID($userID)
    {
        return self::where('user_id', '=', $userID)->find();
    }


    public static function getClubUserByMsgID($msgID)
    {
        return self::where('message_id', '=',  $msgID)->find();
    }

    public static function newClubUser($userID, $messageID="")
    {
        return self::create([
            'user_id'       => $userID,
            'message_id'    => $messageID,
        ]);
    }



}

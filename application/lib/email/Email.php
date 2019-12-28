<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/10/19
 * Time: 17:28
 */

namespace app\lib\email;


class Email
{
    CONST MAILGUN = 'app\lib\email\MailgunClient';
    // 留待定义其他邮件方式

    public $sender;

    public $recipients;

    public $subject;

    public $content;

    public $emailClient;

    public function __construct($clientName="")
    {
        if (empty($clientName)) {
            $clientName = (self::MAILGUN);
        }
        $this->emailClient = (new $clientName());
    }

    /**
     * @param $msgTitle string 标题
     * @param $recommends  array [['title'=> 'foo', 'slug'=> 'bar']]
     * @return string
     */
    public function handleRecomEmailStr($msgTitle, $recommends)
    {
        $activityArr = [];
        foreach($recommends as $key=>$value) {
            $activityArr[] = $value['title'].PHP_EOL.getActivityUrl($value['slug']);
        }

        $msg  = $msgTitle.PHP_EOL.PHP_EOL. implode(PHP_EOL.PHP_EOL, $activityArr);
        return $msg;
    }

}
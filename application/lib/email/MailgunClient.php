<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/9/9
 * Time: 12:04
 */

namespace app\lib\email;

use app\lib\exception\InvalidParamException;
use Mailgun\Mailgun;
use Mailgun\Message\Exceptions\RuntimeException;
use think\facade\Log;


class MailgunClient
{
    // mailgun 的api key 在control panel获取
    private $apiKey ;
    //  mailgun 代发的域名
    public $domain;
    // 邮件内容 默认以html格式为主
    public $emailTextContent = "";
    public $emailHtmlContent = "";
    // 邮件接收对象
    public $emailRecipients;
    // 模板变量数组
    public $emailRecipientsVariables;
    // dkim 验证
    public $dkim = "true";
    // 是否跟踪
    public $tracking = "true";
    // 跟踪点击
    public $trackingClick = "true";
    // 发送邮件数组的偏移量
    public $offset;
    // 发送邮件数组的步长
    public $limit;
    // 邮件发件人名称
    public $senderName;
    // 发件人邮箱
    public $senderEmail;
    public $sender;
    // 邮件主题
    public $subject;
    // 发送时间 gmt string date('r')
    public $deliveryTime;

    public function __construct()
    {
        $this->apiKey = config('email.apiKey');
    }


    /**
     * 对多用户收件人进行检查
     * @param $options
     * @param $recipients
     * @return mixed
     */
    protected function _isMultiRecipients($options, $recipients)
    {
        // 判断模板中是否有recipients的id
        try{
            if (!empty($options['html']) && strpos($options['html'], '%recipient.id%') === false) {
                throw new InvalidParamException(['邮件模板中缺少多人模板ID']);
            }
            $recipientsVar = [];
            foreach($recipients as $key=>$value) {
                $recipientsVar[] = [$value => ['id' => $key, "email" => $value]];
            };

            $options['recipient-variables'] = json_encode($recipientsVar);

        }catch (InvalidParamException $e) {
            Log::record(__CLASS__ . $e->getMessage());
            $options['recipient-variables'] = false;
        }
        return $options;
    }


    /**
     * 对发送邮件配置信息进行检查，返回如果是false，则需要中断发送
     * @return array|bool|mixed
     */
    protected function _checkOptions()
    {
        $this->sender = sprintf("%s <%s>", $this->senderName, $this->senderEmail);
        $options = [
            'from'      => $this->sender,
            'to'        => $this->emailRecipients,
            'subject'   => $this->subject,
            'recipient-variables' => json_encode($this->emailRecipientsVariables)
        ];

        if ($this->emailTextContent) {
            $options['text'] = $this->emailTextContent;
        }

        if ($this->emailHtmlContent) {
            $options['html'] = $this->emailHtmlContent;
            unset($options['text']);
        }

        if (count($this->emailRecipients) >1) {
            $options = $this->_isMultiRecipients($options, $this->emailRecipients);
        }

        foreach($options as $key=>$item){
            if (!$item) {
                $options = false;
                break;
            }
        }

        if ($options) {
            $options['o:dkim'] = $this->dkim;
            $options['o:tracking'] = $this->tracking;
            $options['o:tracking-clicks'] = $this->trackingClick;
        }

        return $options;
    }

    /**
     * 处理使用mailgun发送邮件
     * @return bool
     */
    public function send()
    {
        try{
            // 发送mailgun邮件
            $options = $this->_checkOptions();
            if (!$options) {
                throw new InvalidParamException(['msg' => 'mailgun发送邮件配置项错误']);
            }
        }catch (InvalidParamException $e) {
            Log::record('mailgun发送邮件失败:'.$e->getLine().'\r\n'.$e->getCode().'\r\n'.$e->getMessage());
            return false;
        }

        if ($this->deliveryTime){
            $this->deliveryTime = is_int($this->deliveryTime) ? date('r', $this->deliveryTime):date('r');
            $this->deliveryTime = str_replace("+0000","GMT", $this->deliveryTime);
            $options['o:deliverytime'] = $this->deliveryTime;
        }

        try {
            $res = Mailgun::create($this->apiKey)->messages()->send($this->domain, $options);
            if (!$res->getId()) {
                Log::record('mailgun发送邮件失败:'.$res->getMessage());
                return false;
            }
        } catch (RuntimeException $e) {
            Log::record('mailgun发送邮件失败:'.$e->getLine().'\r\n'.$e->getCode().'\r\n'.$e->getMessage());
            return false;
        }

        return true;
    }

}
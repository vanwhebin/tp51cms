<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/9/11
 * Time: 15:28
 */

namespace app\lib\email;
use app\lib\exception\InvalidParamException;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use think\Exception;

class SwiftMailer
{
    public static $charset = 'UTF-8';
    public $smtpHost;
    public $smtpPort;
    public $smtpEncrypt;
    public $smtpUser;
    public $smtpPassword;

    public $senderName;
    public $senderEmail;
    public $recipients;
    public $subject;
    public $content;
    public $client;

    public function __construct()
    {
        $this->senderName = config('email.senderName');
        $this->senderEmail = config('email.senderEmail');
        $this->smtpHost = config('email.smtp_host');
        $this->smtpPort = config('email.smtp_port');
        $this->smtpEncrypt = config('email.smtp_protocol');
        $this->smtpUser = config('email.smtp_username');
        $this->smtpPassword = config('email.smtp_password');
        $this->client = $this->SMTPEmailClient();
    }

    public function SMTPEmailClient()
    {
        $transport = (new Swift_SmtpTransport($this->smtpHost, $this->smtpPort, $this->smtpEncrypt))
            ->setUsername($this->smtpUser)
            ->setPassword($this->smtpPassword);

        $mailer = new Swift_Mailer($transport);
        return $mailer;
    }

    /**
     * 发送邮件
     * @return SwiftMailer|array|bool|int
     * @throws InvalidParamException
     */
    public function send()
    {
        $message = $this->content($this->subject, $this->content, $this->recipients);
        if ($message && is_array($message) && !empty($message['error'])) {
            return $message;
        }
        return $this->client->send($message);
    }

    /**
     * 准备邮件内容
     * @param $subject
     * @param $content
     * @param $recipients
     * @return SwiftMailer|array|bool
     * @throws InvalidParamException
     */
    public function content($subject, $content, $recipients)
    {
        return $this->generateContent($subject, $content, $recipients);
    }

    /**
     * @param $subject
     * @param $content
     * @param $recipients
     * @return $this|array|bool
     * @throws InvalidParamException
     */
    public function generateContent($subject, $content, $recipients)
    {
        try{
            if ($this->_checkRecipients($recipients) &&
                $this->_checkContent($content) &&
                $this->_checkSubject($subject)
            ) {
                $message = (new Swift_Message($this->subject))
                    ->setFrom([$this->senderEmail => $this->senderName])
                    ->setTo($recipients)
                    ->setBody($this->content, 'text/html', 'utf-8');
                return $message;
            }
            return false;
        } catch (Exception $e){
            return ['error' => $e->getMessage()];
        }
    }


    /**
     * @param $subject
     * @return bool
     * @throws InvalidParamException
     */
    protected function _checkSubject($subject)
    {
        // 检查发送主题是否符合要求
        if (empty($subject)) {
            // throw new
            throw new InvalidParamException(['msg' => '邮件标题不能为空']);
        }
        return true;
    }

    /**
     * @param $content
     * @return bool
     * @throws InvalidParamException
     */
    protected function _checkContent($content)
    {
        // 查看发送内容是否为html或者是普通文本
        if (!$content) {
            // return false;
            // 使用html作为内容
            throw new InvalidParamException(['msg' => '内容不能为空']);
        } else {
            return true;
        }
    }

    /**
     * @param $recipients
     * @return bool
     * @throws InvalidParamException
     */
    protected function _checkRecipients($recipients)
    {
        // 检查收件人是否符合要求
        if (!$recipients && !is_array($recipients)) {
            throw new InvalidParamException(['msg' => '收件人不能为空']);
        } else {
            return true;
        }
    }
}
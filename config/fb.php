<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/11/13
 * Time: 21:35
 */

use think\facade\Env;

return [
    'APP_ID'                => Env::get('fb.app_id','1440777089424317'),
    'APP_SECRET'            => Env::get('fb.app_secret','0f080ec046959c94b90be4fe209435d4'),
    'DEFAULT_GRAPH_VERSION' => 'v5.0',
    'PAGE_ID'               => Env::get('fb.page_id','102171124580553'),
    'WEBHOOK_TOKEN'         => '47Gh7gjtCajBUAmmy9sNrVUM6VA7U6FR82uMBVMCM7tcrzTD3p98RuBvcfsuB4mp',
    'PAGE_ACCESS_TOKEN'     => Env::get('fb.page_token','EAAUeYQ6dm70BAAzImedL90f52aYY6eMFHpfVj47mpsXLKF4qaW0QEYjjZAZCBUosJpZCGDbZAZA8N1ETQDIc3SOdaEr8YE9YvRF2qKI6dL8E1msOcJ296JVe95pTcAch2YuZB9ZCLsNeRZBIPTPC4RgVgjrle9HBLwEl9pWJxitf8wZDZD'),
    'PAGE_URL'              => 'https://www.facebook.com/FreebieQueenUSA/',
    'SEND_MESSAGE_API'      => 'https://graph.facebook.com/v3.3/me/messages?access_token=%s',
    'TOKEN'                 => 'token',
    /**--------messenger end---------------*/
    'RETRIEVE_ID_API'       => 'https://graph.facebook.com/%s/ids_for_apps',
    'RETRIEVE_MESSENGER_INFO_API' => "https://graph.facebook.com/%s?fields=first_name,last_name,profile_pic&access_token=%s",

    /*-------------------------------------*/
    "MANAGER_MSG_ID"        => Env::get('fb.manager_msg_id',"2995057970522480"),
    "MSG_SENT_ERROR_TPL"    => "Error on sending message, detail: %s",

];
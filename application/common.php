<?php
// 应用公共文件
use app\lib\auth\AuthMap;
use app\lib\exception\InvalidParamException;
use think\facade\Request;
use app\api\model\Message as MessageModel;
// use Mailgun\Mailgun;
use app\lib\email\SwiftMailer;



/*
*下载远程图片保存到本地
*参数：文件url,保存文件目录,保存文件名称，使用的下载方式
*当保存文件名称为空时则使用远程文件原来的名称
*/
function downloadImage($url,$save_dir='',$filename='',$type=0){
    if(trim($url)==''){
        return ['file_name'=>'','save_path'=>'','error'=>1];
    }
    if(trim($save_dir)==''){
        $save_dir='./';
    }
    if(trim($filename)==''){//保存文件名
        $ext=strrchr($url,'.');
        if($ext!='.gif' && $ext!='.jpg' && $ext!='.png'){
            return ['file_name'=>'','save_path'=>'','error'=>3];
        }
        $filename=time().$ext;
    }
    if(0 !== strrpos($save_dir,'/')){
        $save_dir.='/';
    }
    //创建保存目录
    if(!file_exists($save_dir) && !mkdir($save_dir,0777,true)){
        return ['file_name'=>'','save_path'=>'','error'=>5];
    }
    //获取远程文件所采用的方法
    if($type){
        $ch=curl_init();
        $timeout=300;
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
        $img=curl_exec($ch);
        curl_close($ch);
    }else{
        ob_start();
        readfile($url);
        $img=ob_get_contents();
        ob_end_clean();
    }
    //$size=strlen($img);
    //文件大小
    $fp2=@fopen($save_dir.$filename,'a');
    fwrite($fp2,$img);
    fclose($fp2);
    unset($img,$url);
    return ['file_name'=>$filename,'save_path'=>$save_dir.$filename,'error'=>0];
}


function easyEncode($data) {
    return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode(serialize($data)));
}
/**
 * 安全URL解码
 * @param $string
 * @return string
 */
function easyDecode($string) {
    $data = str_replace(array('-', '_'), array('+', '/'), $string);
    $mod4 = strlen($data) % 4;
    ($mod4) && $data .= substr('====', $mod4);
    return unserialize(base64_decode($data));
}



/**
 * 使用mailgun发送邮件
 */
/*function sendMailGunEmail($email, $subject, $message){
    $mg = Mailgun::create(config('email.apiKey'));
    $res = $mg->messages()->send(config('email.senderDomain'), [
        'from'    => config('email.senderName')." <". config('email.senderEmail') .">",
        'to'      => $email,
        'subject' => $subject,
        'html'    => $message,
        'o:dkim' => 'true',
        'o:tracking' => 'true',
        'o:tracking-clicks' => 'true',
    ]);
    return $res;
}*/

/**
 * 使用smtp发送邮件
 * @param $email
 * @param $subject
 * @param $message
 * @return SwiftMailer|array|bool|int
 * @throws \app\lib\exception\InvalidParamException
 */
function sendSMTPEmail($email, $subject, $message){
    $mailer = new SwiftMailer();
    $mailer->subject = $subject;
    $mailer->recipients = [$email];
    $mailer->content = $message;
    return $mailer->send();
}

/**
 * @param $slug string 活动唯一链接标志
 * @return string 活动链接地址
 */
function getActivityUrl($slug){
    if (strpos($slug, config('domain')) === 0) {
        return $slug;
    } else {
        return config('domain').'/surprize/'. $slug;
    }
}

/**
 * 生成excel(csv)
 * @param string $fileName 输出Excel文件名
 * @param array $headList  第一行,列名
 * @param array $data 导出数据
 */
function downloadCSV($fileName = "demo",$headList = [],$data = []){
    //生成csv文件
    $path = "./".$fileName.'_'.date("YmdHis",time()).'.csv';

    header("Content-type: application/octet-stream");
    header("Accept-Ranges: bytes");
    header("Connection: Keep-Alive");
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="'.$fileName.'.csv"');
    // header('Cache-Control: max-age=0');

    //打开PHP文件句柄,php://output 表示直接输出到浏览器
    $fp = fopen($path, 'a');

    //输出Excel列名信息
    foreach ($headList as $key => $value) {
        //CSV的Excel支持GBK编码，一定要转换，否则乱码
        $headList[$key] = iconv('utf-8', 'gbk', $value);
    }

    //将数据通过fputcsv写到文件句柄
    fputcsv($fp, $headList);

    //计数器
    $num = 0;

    //每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
    $limit = 100000;

    //逐行取出数据，不浪费内存
    $count = count($data);
    for ($i = 0; $i < $count; $i++) {

        $num++;

        //刷新一下输出buffer，防止由于数据过多造成问题
        if ($limit == $num) {
            ob_flush();
            flush();
            $num = 0;
        }

        $row = $data[$i];
        foreach ($row as $key => $value) {
            $row[$key] = iconv('utf-8', 'gbk', $value);
        }

        fputcsv($fp, $row);

    }
    fclose($fp);

    header('Content-Description: File Transfer'); //描述页面返回的结果
    header('Content-Type: application/octet-stream'); //返回内容的类型，此处只知道是二进制流。具体返回类型可参考http://tool.oschina.net/commons
    header('Content-Disposition: attachment; filename="'.basename($path).'"');//可以让浏览器弹出下载窗口
    header('Content-Transfer-Encoding: binary');//内容编码方式，直接二进制，不要gzip压缩
    header('Expires: 0');//过期时间
    header('Cache-Control: must-revalidate');//缓存策略，强制页面不缓存，作用与no-cache相同，但更严格，强制意味更明显
    header('Pragma: public');
    header('Content-Length: ' . filesize($path));//文件大小，在文件超过2G的时候，filesize()返回的结果可能不正确

    readfile($path);
    unlink($path);
//    redirect(sp_get_host().substr($path,1));
}

/**
 * 处理中奖用户名称问题
 * @param $userName
 * @return string
 */
function hideUserName($userName) {
    // $userName = preg_replace('~[^\pL\d]+~u', '-', $userName);
    // setlocale(LC_ALL, 'en_US.utf8');
    // $userName = iconv('utf-8', 'us-ascii//TRANSLIT//IGNORE', $userName);
    $nameArr = explode(' ', $userName);
    if(count($nameArr) > 1){
        $name =  ucfirst($nameArr[0]) .' '. strtoupper(mb_substr(array_pop($nameArr), 0,1));
    } else {
        $name = mb_substr($userName, 0 ,5) .'*';
    }
    return $name;
}

/*
* array unique_rand( int $min, int $max, int $num )
* 生成一定数量的不重复随机数
* $min 和 $max: 指定随机数的范围
* $num: 指定生成数量
*/
function unique_rand($min, $max, $num) {
    $count = 0;
    $return = array();
    while ($count < $num) {
        $return[] = mt_rand($min, $max);
        $return = array_flip(array_flip($return));
        $count = count($return);
    }
    shuffle($return);
    return $return;
}



/**
 * slugify标题
 * @param $text
 * @return null|string|string[]
 */
function slugify($text)
{
    // Strip html tags
    $text=strip_tags($text);
    // Replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterate
    setlocale(LC_ALL, 'en_US.utf8');
    $text = iconv('utf-8', 'us-ascii//TRANSLIT//IGNORE', $text);
    // Remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim
    $text = trim($text, '-');
    // Remove duplicate -
    $text = preg_replace('~-+~', '-', $text);
    // Lowercase
    $text = strtolower($text);
    // Check if it is empty
    if (empty($text)) { return 'n-a'; }
    // Return result
    return $text;
}


/**
 * 生成分享链接
 * @param $url
 * @return string
 */
function shareUrl($url) {
    $fbUrl = "https://www.facebook.com/dialog/share";
    $shareUrl = $fbUrl . "?". http_build_query([
            "app_id" => config('fb.APP_ID'),
            "display" => "popup",
            "href" => $url,
            "redirect_uri" => $url,
        ]);
    return $shareUrl;
}


/**
 *  * 给站点管理人员发送messenger消息
 * @param $text
 * @return bool
 * @throws Exception
 * @throws \think\Exception
 */
function sendMsg2Manager($text)
{
    $info = sprintf(config('fb.MSG_SENT_ERROR_TPL'), $text) . "current time: ".date('Y-m-d H:i:s');
    MessageModel::sendTextMessage(config('fb.MANAGER_MSG_ID'), $info);
    return true;
}

/**
 * 获取媒体资源文件路径
 * @param $file
 * @param $from
 * @return string
 */
function getMediaUrl($file, $from=1) {
    if(strpos($file,"http")===0){
        return $file;
    }else{
        if($from == 1){
            $file =  config('img_prefix') . $file;
            // } else {
            // $ossConfig = C('ALI_OSS');
            // $endpoint  = $ossConfig['DOMAIN'];
            // $filepath = 'http://'.$endpoint."/".$file;
        }
        return $file;
    }
}


/**
 * 处理日期格式
 * @param $timeStamp
 * @return string
 */
function dateFormat($timeStamp){
    // date_default_timezone_set('America/Los_Angeles');
    if ( $timeStamp == 9999999999 ) {  // 一种特殊时间，暂不公布的状态
        return "TBD";  // to be declaimed
    } else {
        $time = date('n/j/y', $timeStamp);
        $h = intval(date('G', $timeStamp));
        if ($h > 12) {
            $h = date('g:i', $timeStamp) . ' pm';
        } else {
            if ($h === 0) {
                $h = '0:00 am';
            } else {
                $h = date('g:i', $timeStamp) . ' am';
            }
        }


        return ($time.' '. $h. ' PDT');
    }
}


/**
 * 判断移动端和PC端
 * @return bool
 */
function isMobile(){
    if ( empty($_SERVER['HTTP_USER_AGENT']) ) {
        $mobile = false;
    } elseif ( strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false // many mobile devices (all iPhone, iPad, etc.)
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Silk/') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Kindle') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'facebookexternalhit') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi') !== false ) {
        $mobile = true;
    } else {
        $mobile = false;
    }

    return $mobile;
}

/**
 * 封装请求方法1
 * @param $url
 * @param array $params
 * @param string $method
 * @param array $header
 * @param bool $multi
 * @return mixed
 * @throws Exception
 * @throws \think\Exception
 */
function curlHttp($url, $params = [], $method = 'GET', $header = [], $multi = false){
    $opts = [
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => $header
    ];

    /* 根据请求类型设置特定参数 */
    switch(strtoupper($method)){
        case 'GET':
            $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
            break;
        case 'POST':
            //判断是否传输文件
            $params = $multi ? $params : http_build_query($params);
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
            break;
        default:
            throw new Exception('不支持的请求方式！');
    }

    /* 初始化并执行curl请求 */
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data  = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if($error) {
        throw new \think\Exception('请求发生错误：' . $error);
    }
    return  $data;
}

/**
 * 封装请求方法2
 * @param $data
 * @param $pageAccessToken
 * @return mixed
 */
function http($data, $pageAccessToken)
{
    //首先检测是否支持curl
    if (!extension_loaded("curl")) {
        trigger_error("对不起，请开启curl功能模块！", E_USER_ERROR);
        M('log')->add([
            'log'=> json_encode(['data' =>$data, 'error' =>'对不起，请开启curl功能模块！']),
            'create_time' => date('Y-m-d H:i:s'),
            'type' => 2
        ]);
    }
    $url = "https://graph.facebook.com/v3.3/me/messages?access_token=".$pageAccessToken;
    //初始一个curl会话
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // 设置请求为post类型
    curl_setopt($ch, CURLOPT_POST, 1);
    // 添加post数据到请求中
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json','Content-Length: ' . strlen($data)]);
    // 3. 执行一个cURL会话并且获取相关回复
    $response = curl_exec($ch);
    // 4. 释放cURL句柄,关闭一个cURL会话
    curl_close($response);
    return $response;
}

/**
 * @param $code
 * @param $errorCode
 * @param $data
 * @param $msg
 * @return \think\response\Json
 */

function writeJson($code, $data, $msg = 'ok', $errorCode = 0)
{
    $data = [
        'error_code' => $errorCode,
        'result' => $data,
        'msg' => $msg
    ];
    return json($data, $code);
}

function rand_char($length)
{
    $str = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;

    for ($i = 0; $i < $length; $i++) {
        $str .= $strPol[rand(0, $max)];
    }

    return $str;
}

function split_modules($auths, $key = 'module')
{
    if (empty($auths)) {
        return [];
    }

    $items = [];
    $result = [];

    foreach ($auths as $key => $value) {
        if (isset($items[$value['module']])) {
            $items[$value['module']][] = $value;
        } else {
            $items[$value['module']] = [$value];
        }
    }
    foreach ($items as $key => $value) {
        $item = [
            $key => $value
        ];
        array_push($result, $item);
    }
    return $result;

}

/**
 * @param $auth
 * @return array
 * @throws ReflectionException
 */
function findAuthModule($auth)
{
    $authMap = (new AuthMap())->run();
    foreach ($authMap as $key => $value) {
        foreach ($value as $k => $v) {
            if ($auth === $k) {
                return [
                    'auth' => $k,
                    'module' => $key
                ];
            }
        }
    }
}

///**
// * @param string $message
// * @param string $uid
// * @param string $nickname
// * @throws \app\lib\exception\token\TokenException
// * @throws \think\Exception
// */
//function logger(string $message, $uid = '', $nickname = '')
//{
//    if ($message === '') {
//        throw new LoggerException([
//            'msg' => '日志信息不能为空'
//        ]);
//    }
//
//    $params = [
//        'message' => $nickname ? $nickname . $message : Token::getCurrentName() . $message,
//        'user_id' => $uid ? $uid : Token::getCurrentUID(),
//        'user_name' => $nickname ? $nickname : Token::getCurrentName(),
//        'status_code' => Response::getCode(),
//        'method' => Request::method(),
//        'path' => Request::path(),
//        'authority' => ''
//    ];
//    LinLog::create($params);
//}

/**
 * @return array
 * @throws InvalidParamException
 */
function paginate()
{
    $count = intval(Request::get('count'));
    $start = intval(Request::get('page'));

    $count = $count >= 15 ? 15 : $count;

    $start = $start * $count;

    if ($start < 0 || $count < 0) throw new InvalidParamException();

    return [$start, $count];
}

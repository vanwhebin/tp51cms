<?php


namespace app\api\controller\cms;

use think\facade\Request;
use app\lib\file\LocalUploader;
use app\lib\exception\file\FileException;

/**
 * Class File
 * @package app\api\controller\cms
 */
class File
{
    /**
     * @return array
     * @throws FileException
     */
    public function postFile()
    {
        try {
            $request = Request::file();
        } catch (\Exception $e) {
            throw new FileException([
                'msg' => '字段中含有非法字符',
            ]);
        }
        $file = (new LocalUploader($request))->upload();
        return $file;
    }
}

<?php
/*
* Created by DevilKing
* Date: 2019-06-08
*Time: 16:19
*/

namespace app\lib\file;

use think\facade\Config;
use app\api\model\Media;
use app\lib\exception\file\FileException;
use extend\file\File;
/**
 * Class LocalUploader
 * @package app\lib\file
 */
class LocalUploader extends File
{
    /**
     * @return array
     * @throws FileException
     */
    public function upload()
    {
        $ret = [];
        $host = Config::get('file.host') ?? "http://127.0.0.1:8000";
        foreach ($this->files as $key => $file) {
            $md5 = $this->generateMd5($file);
            $exists = Media::get(['md5' => $md5]);
            if ($exists) {
                array_push($ret, [
                    'key' => $key,
                    'id' => $exists['id'],
                    'url' => getMediaUrl($exists['url'], $exists['from'])
                ]);
            } else {
                $size = $this->getSize($file);
                $info = $file->move($this->storeDir);
                if ($info) {
                    $extension = '.' . $info->getExtension();
                    $path = str_replace('\\','/',$info->getSaveName());
                    $name = $info->getFilename();
                } else {
                    throw new FileException([
                        'msg' => $this->getError,
                        'error_code' => 60001
                    ]);
                }
                $uploadFile = Media::create([
                    'name' => $name,
                    'url' => $path,
                    'size' => $size,
                    'extension' => $extension,
                    'md5' => $md5,
                    'from' => 1
                ]);
                array_push($ret, [
                    'key' => $key,
                    'id' => $uploadFile->id,
                    'url' => getMediaUrl($uploadFile['url'], $uploadFile['from'])
                ]);
            }
        }
        return $ret;
    }
}

<?php
declare(strict_types=1);

namespace app\controller;

use think\Request;
use app\BaseController;
use app\model\VenueFile;
use think\exception\ValidateException;

class Upload extends BaseController
{
    const MAX_SIZE = 20971520; // 20M
    const EXT_IMAGE = 'png,jpg,jpeg,bmp,gif';
    const EXT_REGULAR = 'xls,xlsx,doc,dot,docx,ppt,pptx,pot,pdf,csv,txt,zip,rar';

    // 初始化
    protected function initialize()
    {
        parent::initialize();

        // 默认控制器
        // 此控制器主要包含测试接口，无需调用相关中间件
        $this->middleware = [];
    }

    public function index()
    {
        return <<<HTML
<form action="/upload/file" method="post" enctype="multipart/form-data">
    <input type="file" name="upFile[]" multiple="multiple" /><br>
    <input type="submit" value="上传"/>
</form>
HTML;
    }
    
    /**
     * 上传文件
     *
     * @return \think\Response
     */
    public function file()
    {
        return $this->_upload();
    }

    /**
     * 上传图片
     *
     * @return \think\Response
     */
    public function image()
    {
        return $this->_upload(true);
    }

    /**
     * 上传文件
     * @param boolean $image 是否图片上传
     * @return array 上传的文件
     */
    private function _upload($image=false)
    {
        // 获取表单上传文件
        $files = request()->file('upFile');
        try {
            validate([
                'upFile' => 'filesize:'.self::MAX_SIZE.'|fileExt:'.self::EXT_IMAGE.($image ? '' : (','.self::EXT_REGULAR))
            ])
            ->message([
                'upFile.filesize' => '文件大小不能超过'.intval(self::MAX_SIZE/1024/1024).'M', 
                'upFile.fileExt' => '仅支持'.self::EXT_IMAGE.($image ? '' : (','.self::EXT_REGULAR)).'文件格式', 
            ])
            ->check(['upFile' => $files]);

            $isMulti = false;
            if (is_object($files)) {
                $files = [$files];
            } else {
                $isMulti = true;
            }
            
            $uploads = [];
            foreach($files as $file) {
                $md5 = $file->md5();
                $sha1 = $file->sha1();
                $find = VenueFile::where(['md5' => $md5, 'sha1' => $sha1])->find();
                if (empty($find)) {
                    $path = \think\facade\Filesystem::disk('public')->putFile( '', $file, function ($finfo) {
                        $md5 = $finfo->md5();
                        $md5 = str_split($md5, intval(strlen($md5) / 4));
                        $md5 = implode('/', $md5);
                        return $md5.$finfo->extension();
                    });
                    if (empty($path))   continue;

                    $find = VenueFile::create([
                        'name' => $file->getOriginalName(), 
                        'path' => $path, 
                        'ext' => $file->getOriginalExtension(),
                        'mime_type' => $file->getOriginalMime(),
                        'size' => $file->getSize(),
                        'md5' => $md5, 
                        'sha1' => $sha1, 
                        'created_by' => app()->user->isGuest() ? 0 : app()->user->id
                    ]);
                }

                $uploads[] = [
                    'id' => $find->id, 
                    'name' => $find->name, 
                    'path' => $find->path, 
                    'ext' => $find->ext,
                    'mime_type' => $find->mime_type, 
                    'size' => $find->size
                ];
            }
        } catch (ValidateException $e) {
            return $this->jsonErr($e->getError());
        } catch (\Exception $e) {
            return $this->jsonErr($e->getMessage());
        }

        return $isMulti ? json(['files' => $uploads ?? []]) : json(empty($uploads) ? null : array_shift($uploads));
    }
}

<?php

namespace app\wxwork;

use app\helper\StringHelper;

abstract class Response
{
    protected $data;

    /**
     * @var object reply data
     */
    protected $retData;

    public function __construct($data) { $this->data = $data; }

    abstract protected function deal();
    
    /*
	 * 回复图文消息 
     * articles array 格式如下： 
     * array( array('Title'=>'','Description'=>'','PicUrl'=>'','Url'=>''), array('Title'=>'','Description'=>'','PicUrl'=>'','Url'=>'') );
	 */
    public function replyNews($articles)
    {
        $this->retData = new \stdClass();
        $this->retData->Articles = $articles;
        $this->retData->ArticleCount = count($articles);

        return $this->_replyData('news');
    }

    public function replyText($content)
    {
        $this->retData = new \stdClass();
        $this->retData->Content = $content;

        return $this->_replyData('text');
    }

    public function replyImage($media_id)
    {
        $this->retData = new \stdClass();
        $this->retData->Image->MediaId = $media_id;

        return $this->_replyData('image');
    }

    public function replyVoice($media_id)
    {
        $this->retData = new \stdClass();
        $this->retData->Voice->MediaId = $media_id;

        return $this->_replyData('voice');
    }

    public function replyVideo($media_id, $title = '', $description = '')
    {
        $this->retData = new \stdClass();
        $this->retData->Video->Title = $title;
        $this->retData->Video->MediaId = $media_id;
        $this->retData->Video->Description = $description;

        return $this->_replyData('video');
    }

    protected function _replyData($msgType)
    {
        $this->retData->ToUserName = strval($this->data->FromUserName);
        $this->retData->FromUserName = strval($this->data->ToUserName);
        $this->retData->CreateTime = time();
        $this->retData->MsgType = $msgType;

        return '<xml>'.StringHelper::convertDataToXml($this->retData).'</xml>';
    }
}

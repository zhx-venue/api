<?php
declare(strict_types=1);

namespace app\controller\campus;

use app\BaseController;
use app\campus\DealChange;

class Handle extends BaseController
{
    // 重置中间件
    protected function _middleware() {}

    public function response()
    {
        $Sign = input('get.Sign', '', 'strval');
        $params = [
            'Seq' => input('get.Seq', '', 'strval'),
            'AppId' => input('get.AppId', 0, 'intval'),
            'Nonce' => input('get.Nonce', 0, 'intval'),
            'OrgId' => input('get.OrgId', 0, 'intval'),
            'Action' => input('get.Action', '', 'strval'),
            'SecretId' => input('get.SecretId', '', 'strval'),
            'Timestamp' => input('get.Timestamp', 0, 'intval'),
        ];
        $urlInfo = parse_url($_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
        $signValue = \shophy\campus\Campus::signRequestData(config('campus.secretKey') ?? '', $_SERVER['REQUEST_METHOD'], $urlInfo['host'].$urlInfo['path'], $params, '');
        if ($Sign != $signValue)    return $this->jsonErr('invalid sign');

        json(['code' => 0, 'msg' => 'ok'])->send();

        try {
            (new DealChange())->do($params['Seq'], $params['OrgId']);
        } catch (\Exception $e) {
            trace('campus handle response failed！'.$e->getMessage());
        }
        
        // 写入日志
        app()->log->save();
    }
}

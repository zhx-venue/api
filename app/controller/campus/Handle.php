<?php
declare(strict_types=1);

namespace app\controller\campus;

use app\BaseController;
use app\campus\DealChange;
use shophy\campus\Campus;
use shophy\campus\models\GetChangeListRequest;

class Handle extends BaseController
{
    // 初始化
    protected function initialize()
    {
        parent::initialize();

        $this->middleware = [];
    }

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
        $signValue = Campus::signRequestData(config('campus.secretKey') ?? '', $_SERVER['REQUEST_METHOD'], $urlInfo['host'].$urlInfo['path'], $params, '');
        if ($Sign != $signValue)    return $this->jsonErr('invalid sign');

        try {
            $request = new GetChangeListRequest();
            $request->deserialize(['Seq' => $params['Seq']]);

            $campus = new Campus(config('campus.appId') ?? '', config('campus.secretId') ?? '', config('campus.secretKey') ?? '');
            $campus->orgId = $params['OrgId'];
            $changeList = $campus->GetChangeList($request);
        } catch (\Exception $e) {
            return $this->jsonErr($e->getMessage());
        }

        json(['code' => 0, 'msg' => 'ok'])->send();

        // 处理数据变更
        $dealChange = new DealChange();
        array_walk($changeList->DataList, [$dealChange, 'do']);
    }
}

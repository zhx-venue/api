<?php
declare(strict_types=1);

namespace app\controller\miniprogram;

use app\BaseController;
use app\model\User;
use app\model\VenueVisitor;
use app\validate\VenueVisitor as VVenueVisitor;
use think\exception\ValidateException;

class register extends BaseController
{
    // 初始化
    protected function initialize()
    {
        parent::initialize();

        $this->middleware = [];
    }

    /**
     * 小程序访客注册
     */
    public function index()
    {
        $data = input('post.');
        try {
            validate(VVenueVisitor::class)->scene('add')->batch(true)->check($data);

            (new VenueVisitor)->addItem($data);
        } catch (ValidateException $e) {
            return $this->jsonErr($e->getError());
        } catch (\Exception $e) {
            return $this->jsonErr($e->getMessage());
        }

        $visitor = VenueVisitor::where('openid', $data['openid'])->find();
        if (empty($visitor))    return $this->jsonErr('注册失败');

        $userToken = User::generateToken($visitor);
        isset($data['session_key']) && $userToken['session_key'] = isset($data['session_key']);
        return $this->jsonOk($userToken);
    }
}

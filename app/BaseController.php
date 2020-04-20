<?php
declare (strict_types = 1);

namespace app;

use think\App;
use think\exception\ValidateException;
use think\Validate;
use app\model\User;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 绑定用户类
        bind('user', User::class);

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
        trace($this->request->controller().'/'.$this->request->action(), 'info');
        trace('$_GET:'.json_encode($this->request->get()), 'info');
        trace('$_POST:'.json_encode($this->request->post()), 'info');
        trace('$_SERVER:'.json_encode($this->request->server()), 'info');

        $this->middleware = [
            // 接口鉴权
            \app\middleware\Authorization::class,
            // 权限验证
            \app\middleware\CheckAccess::class
        ];
    }

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    protected function jsonOk($data=[], $msg='ok', $http_code=200, $header = [], $options = [])
    {
        $json = ['code' => 0];
        empty($msg) || $json['message'] = $msg;
        empty($data) || $json = array_merge($json, (array)$data);

        return json($json, $http_code, $header, $options);
    }

    protected function jsonErr($msg='error', $code=-1, $data=[], $http_code=200, $header = [], $options = [])
    {
        $json = ['code' => $code, 'message' => $msg];
        empty($data) || $json = array_merge($json, (array)$data);

        return json($json, $http_code, $header, $options);
    }
}

<?php
declare (strict_types = 1);

namespace app\middleware;

use app\model\User;
use thans\jwt\middleware\BaseMiddleware;
use thans\jwt\exception\TokenExpiredException;

class Authorization extends BaseMiddleware
{
    protected function setAuthentication($response, $token = null)
    {
        $token = $token ?: $this->auth->refresh();
        $this->auth->setToken($token);

        return $response->header(['Authorization' => 'Bearer '.$token]);
    }

    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        try {
            $payload = $this->auth->auth();
            foreach ($payload as $key => &$value) {
                $payload[$key] = $value->getValue();
            }
            
            app()->user->setPayload($payload);
        } catch (TokenExpiredException $e) {
            $this->auth->setRefresh();
            $response = $next($request);

            return $this->setAuthentication($response);
        } catch (\Exception $e) {
            return json(['code' => -1, 'msg' => $e->getMessage()], 401);
        }

        return $next($request);
    }
}

<?php

namespace app\miniprogram;

use shophy\miniprogram\Api as MiniApi;

class Api extends MiniApi
{
    public function __construct($appid=null)
	{
		if (!empty($appid)) {
			$appid = env($appid.'_APPID');
			$secret = env($appid.'_SECRET');
		}
		if (empty($appid)) {
			$config = config('miniprogram') ?? [];
			$appid = $config['appid'] ?? '';
			$secret = $config['secret'] ?? '';
		}

		parent::__construct($appid, $secret);
    }

    /**
	 * 设置缓存，按需重载
	 * @param string $cachename
	 * @param mixed $value
	 * @param int $expired
	 * @return boolean
	 */
	protected function setCache($cachename,$value,$expired=null){
		//TODO: set cache implementation
		return cache($cachename, $value, $expired);
	}

	/**
	 * 获取缓存，按需重载
	 * @param string $cachename
	 * @return mixed
	 */
	protected function getCache($cachename){
		//TODO: get cache implementation
		return cache($cachename);
	}

	/**
	 * 清除缓存，按需重载
	 * @param string $cachename
	 * @return boolean
	 */
	protected function removeCache($cachename){
		//TODO: remove cache implementation
		return cache($cachename, null);;
	}
}
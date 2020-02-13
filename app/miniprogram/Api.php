<?php

namespace app\miniprogram;

use shophy\miniprogram\Api as MiniApi;

class Api extends MiniApi
{
    public function __construct()
	{
		$config = config('miniprogram') ?? [];
		parent::__construct($config['appid'] ?? '', $config['secret'] ?? '');
    }

    /**
	 * 设置缓存，按需重载
	 * @param string $cachename
	 * @param mixed $value
	 * @param int $expired
	 * @return boolean
	 */
	protected function setCache($cachename,$value,$expired){
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
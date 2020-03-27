<?php

namespace Tengyue\Infra;

interface FactoryInterface
{
	/**
	 * @param \Tengyue\Infra\Config|array config
	 */
	public static function load($config);
}

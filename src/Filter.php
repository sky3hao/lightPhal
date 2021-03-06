<?php

namespace Tengyue\Infra;

use Tengyue\Infra\FilterInterface;
use Tengyue\Infra\Filter\Exception;

/**
 * Tengyue\Infra\Filter
 *
 *<code>
 * $filter = new \Tengyue\Infra\Filter();
 *
 * $filter->sanitize("some(one)@exa\\mple.com", "email"); // returns "someone@example.com"
 * $filter->sanitize("hello<<", "string"); // returns "hello"
 * $filter->sanitize("!100a019", "int"); // returns "100019"
 * $filter->sanitize("!100a019.01a", "float"); // returns "100019.01"
 *</code>
 */
class Filter implements FilterInterface
{
	const FILTER_EMAIL         = "email";

	const FILTER_ABSINT        = "absint";

	const FILTER_INT           = "int";

	const FILTER_INT_CAST      = "int!";

	const FILTER_STRING        = "string";

	const FILTER_FLOAT         = "float";

	const FILTER_FLOAT_CAST    = "float!";

	const FILTER_ALPHANUM      = "alphanum";

	const FILTER_TRIM          = "trim";

	const FILTER_STRIPTAGS     = "striptags";

	const FILTER_LOWER         = "lower";

	const FILTER_UPPER         = "upper";

	const FILTER_URL           = "url";

	const FILTER_SPECIAL_CHARS = "special_chars";

	protected $_filters;

	/**
	 * Adds a user-defined filter
	 */
	public function add($name, $handler) 
	{
		if (!is_object($handler) && !is_callable($handler)) {
			throw new Exception("Filter must be an object or callable");
		}

		$this->_filters[$name] = $handler;
		return $this;
	}

	/**
	 * Sanitizes a value with a specified single or set of filters
	 */
	public function sanitize($value, $filters, $noRecursive = false) 
	{
		/**
		 * Apply an array of filters
		 */
		if (is_array($filters)) {
			if ($value !== null) {
				foreach ($filters as $filter) {
					/**
					 * If the value to filter is an array we apply the filters recursively
					 */
					if (is_array($value) && !$noRecursive) {
						$arrayValue = [];
						foreach ($value as $itemKey => $itemValue) {
							$arrayValue[$itemKey] = $this->_sanitize($itemValue, $filter);
						}
						$value = $arrayValue;
					} else {
						$value = $this->_sanitize($value, $filter);
					}
				}
			}
			return $value;
		}

		/**
		 * Apply a single filter value
		 */
		if (is_array($value) && !$noRecursive) {
			$sanitizedValue = [];
			foreach ($value as $itemKey => $itemValue) {
				$sanitizedValue[$itemKey] = $this->_sanitize($itemValue, $filters);
			}
			return $sanitizedValue;
		}

		return $this->_sanitize($value, $filters);
	}

	/**
	 * Internal sanitize wrapper to filter_var
	 */
	protected function _sanitize($value, $filter)
	{
		if (isset($this->_filters[$filter])) {
			$filterObject = $this->_filters[$filter];
			/**
			 * If the filter is a closure we call it in the PHP userland
			 */
			if ((is_object($filterObject) && $filterObject instanceof \Closure)
				|| is_callable($filterObject)
			) {
				return call_user_func_array($filterObject, [$value]);
			}

			return $filterObject->filter($value);
		}

		switch ($filter) {

			case Filter::FILTER_EMAIL:
				/**
				 * The 'email' filter uses the filter extension
				 */
				return filter_var($value, constant("FILTER_SANITIZE_EMAIL"));

			case Filter::FILTER_INT:
				/**
				 * 'int' filter sanitizes a numeric input
				 */
				return filter_var($value, FILTER_SANITIZE_NUMBER_INT);

			case Filter::FILTER_INT_CAST:

				return intval($value);

			case Filter::FILTER_ABSINT:

				return abs(intval($value));

			case Filter::FILTER_STRING:

				return filter_var($value, FILTER_SANITIZE_STRING);

			case Filter::FILTER_FLOAT:
				/**
				 * The 'float' filter uses the filter extension
				 */
				return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, ["flags" => FILTER_FLAG_ALLOW_FRACTION]);

			case Filter::FILTER_FLOAT_CAST:

				return doubleval($value);

			case Filter::FILTER_ALPHANUM:

				return preg_replace("/[^A-Za-z0-9]/", "", $value);

			case Filter::FILTER_TRIM:

				return trim($value);

			case Filter::FILTER_STRIPTAGS:

				return strip_tags($value);

			case Filter::FILTER_LOWER:

				if (function_exists("mb_strtolower")) {
					/**
					 * 'lower' checks for the mbstring extension to make a correct lowercase transformation
					 */
					return mb_strtolower($value);
				}
				return strtolower($value);

			case Filter::FILTER_UPPER:

				if (function_exists("mb_strtoupper")) {
					/**
					 * 'upper' checks for the mbstring extension to make a correct lowercase transformation
					 */
					return mb_strtoupper($value);
				}
				return strtoupper($value);

			case Filter::FILTER_URL:

				return filter_var($value, FILTER_SANITIZE_URL);

			case Filter::FILTER_SPECIAL_CHARS:

				return filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);

			default:
				throw new Exception("Sanitize filter '" . $filter . "' is not supported");
		}
	}

	/**
	 * Return the user-defined filters in the instance
	 */
	public function getFilters() 
	{
		return $this->_filters;
	}
}

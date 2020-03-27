<?php

namespace Tengyue\Infra;

use Tengyue\Infra\Config\Exception;

/**
 * Tengyue\Infra\Config
 *
 *<code>
 * $config = new \Infra\Config(
 *     [
 *         "database" => [
 *             "adapter"  => "Mysql",
 *             "host"     => "localhost",
 *             "username" => "scott",
 *             "password" => "cheetah",
 *             "dbname"   => "test_db",
 *         ],
 *         "app" => [
 *             "controllersDir" => "../app/controllers/",
 *             "modelsDir"      => "../app/models/",
 *         ],
 *     ]
 * );
 *</code>
 */
class Config implements \ArrayAccess, \Countable
{

	protected static $_pathDelimiter;

	const DEFAULT_PATH_DELIMITER = ".";

	/**
	 * Tengyue\Infra\Config constructor
	 */
	public function __construct($arrayConfig = null)
	{   
        foreach ($arrayConfig as $key => $value) {
            $this->offsetSet($key, $value);
        }
	}

	/**
	 * Allows to check whether an attribute is defined using the array-syntax
	 *
	 *<code>
	 * var_dump(
	 *     isset($config["database"])
	 * );
	 *</code>
	 */
	public function offsetExists($index)
	{
		$index = strval($index);

		return isset($this->${$index});
	}

	/**
	 * Returns a value from current config using a dot separated path.
	 *
	 *<code>
	 * echo $config->path("unknown.path", "default", ".");
	 *</code>
	 */
	public function path($path, $defaultValue = null, $delimiter = null)
	{   
        if (isset($this->{$path})) {
            return $this->{$path};
        }

        if (empty($delimiter)) {
            $delimiter = self::getPathDelimiter();
        }
            
        $config = $this;
        $keys = explode($delimiter, $path);

        while (!empty($keys)) {
            $key = array_shift($keys);

            if (!isset($config->{$key})) {
                break;
            }
            if (empty($keys)) {
                return $config->{$key};
            }

            $config = $config->{$key};
            if (empty($config)) {
                break;
            }
        }

        return $defaultValue;
	}

	/**
	 * Gets an attribute from the configuration, if the attribute isn't defined returns null
	 * If the value is exactly null or is not defined the default value will be used instead
	 *
	 *<code>
	 * echo $config->get("controllersDir", "../app/controllers/");
	 *</code>
	 */
	public function get($index, $defaultValue = null)
	{
		$index = strval($index);

		if (isset($this->{$index})) {
			return $this->{$index};
		}

		return $defaultValue;
	}

	/**
	 * Gets an attribute using the array-syntax
	 *
	 *<code>
	 * print_r(
	 *     $config["database"]
	 * );
	 *</code>
	 */
	public function offsetGet($index)
	{
		$index = strval($index);

		return $this->{$index};
	}

	/**
	 * Sets an attribute using the array-syntax
	 *
	 *<code>
	 * $config["database"] = [
	 *     "type" => "Sqlite",
	 * ];
	 *</code>
	 */
	public function offsetSet($index, $value)
	{
		$index = strval($index);

		if (is_array($value)) {
			$this->{$index} = new self($value);
		} else {
			$this->{$index} = $value;
		}
	}

	/**
	 * Unsets an attribute using the array-syntax
	 *
	 *<code>
	 * unset($config["database"]);
	 *</code>
	 */
	public function offsetUnset($index)
	{
		$index = strval($index);

		//unset(this->{$index});
		$this->{$index} = null;
	}

	/**
	 * Merges a configuration into the current one
	 *
	 *<code>
	 * $appConfig = new \Infra\Config(
	 *     [
	 *         "database" => [
	 *             "host" => "localhost",
	 *         ],
	 *     ]
	 * );
	 *
	 * $globalConfig->merge($appConfig);
	 *</code>
	 */
	public function merge($config)
	{
		return $this->_merge($config);
	}

	/**
	 * Converts recursively the object to an array
	 *
	 *<code>
	 * print_r(
	 *     $config->toArray()
	 * );
	 *</code>
	 */
	public function toArray()
	{
		$arrayConfig = [];
        foreach (get_object_vars($this) as $key => $value) {
            if (is_object($value)) {
                if (method_exists($value, "toArray")) {
                    $arrayConfig[$key] = $value->toArray();
                } else {
                    $arrayConfig[$key] = $value;
                }
            } else {
                $arrayConfig[$key] = $value;
            }
        }
		return $arrayConfig;
	}

	/**
	 * Returns the count of properties set in the config
	 *
	 *<code>
	 * print count($config);
	 *</code>
	 *
	 * or
	 *
	 *<code>
	 * print $config->count();
	 *</code>
	 */
	public function count()
	{
		return count(get_object_vars($this));
	}

	/**
	 * Restores the state of a Tengyue\Infra\Config object
	 */
	public static function __set_state($data)
	{
		return new self($data);
	}

	/**
	 * Sets the default path delimiter
	 */
	public static function setPathDelimiter($delimiter = null)
	{
		self::$_pathDelimiter = $delimiter;
	}

	/**
	 * Gets the default path delimiter
	 */
	public static function getPathDelimiter()
	{
		$delimiter = self::$_pathDelimiter;
		if (!$delimiter) {
			$delimiter = self::DEFAULT_PATH_DELIMITER;
		}

		return $delimiter;
	}

	/**
	 * Helper method for merge configs (forwarding nested config instance)
	 *
	 * @param Config config
	 * @param Config instance = null
	 *
	 * @return Config merged config
	 */
	protected final function _merge($config, $instance = null)
	{
        if (!is_object($instance)) {
            $instance = $this;
        }

		$number = $instance->count();
        
        foreach (get_object_vars($config) as $key => $value) {
            $property = strval($key);
            if (isset($instance->{$property})) {
                $localObject = $instance->{$property};
                if (is_object($localObject) && is_object($value)) {
                    if ($localObject instanceof Config && $value instanceof Config) {
                        $this->_merge($value, $localObject);
                        continue;
                    }
                }
            }

			if (is_numeric($key)) {
				$key = strval($number);
				$number++;
			}
		    $instance->{$key} = $value;
        }

		return $instance;
	}
}

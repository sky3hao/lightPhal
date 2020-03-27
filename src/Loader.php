<?php

namespace Tengyue\Infra;

use Tengyue\Infra\Helper\Common;
use Tengyue\Infra\Loader\Exception;

/**
 * Tengyue\Infra\Loader
 *
 */
class Loader
{
	protected $_foundPath = null;

	protected $_checkedPath = null;

	protected $_classes = [];

	protected $_extensions = ["php"];

	protected $_namespaces = [];

	protected $_directories = [];

	protected $_files = [];

	protected $_registered = false;

	protected $fileCheckingCallback = "is_file";

	/**
	 * Sets the file check callback.
	 *
	 * <code>
	 * // Default behavior.
	 * $loader->setFileCheckingCallback("is_file");
	 *
	 * // Faster than `is_file()`, but implies some issues if
	 * // the file is removed from the filesystem.
	 * $loader->setFileCheckingCallback("stream_resolve_include_path");
	 *
	 * // Do not check file existence.
	 * $loader->setFileCheckingCallback(null);
	 * </code>
     */
	public function setFileCheckingCallback($callback = null)
	{
		if (is_callable($callback)) {
			$this->fileCheckingCallback = $callback;
		} elseif ($callback === null) {
			$this->fileCheckingCallback = function ($file) {
				return true;
			};
		} else {
			throw new Exception("The 'callbak' parameter must be either a callable or NULL.");
		}

		return $this;
	}

	/**
	 * Sets an array of file extensions that the loader must try in each attempt to locate the file
	 */
	public function setExtensions($extensions)
	{
		$this->_extensions = $extensions;
		return $this;
	}

	/**
	 * Returns the file extensions registered in the loader
	 */
	public function getExtensions()
	{
		return $this->_extensions;
	}

	/**
	 * Register namespaces and their related directories
	 */
	public function registerNamespaces($namespaces, $merge = false)
	{
		$preparedNamespaces = $this->prepareNamespace($namespaces);

		if ($merge) {
			foreach ($preparedNamespaces as $name => $paths) {
				if (!isset($this->_namespaces[$name])) {
					$this->_namespaces[$name] = [];
				}

				$this->_namespaces[$name] = array_merge($this->_namespaces[$name], $paths);
			}
		} else {
			$this->_namespaces = $preparedNamespaces;
		}

		return $this;
	}

	protected function prepareNamespace($namespace)
	{
		$prepared = [];
		foreach ($namespace as $name => $paths) {
			if (!is_array($paths)) {
				$localPaths = [$paths];
			} else {
				$localPaths = $paths;
			}

			$prepared[$name] = $localPaths;
		}

		return $prepared;
	}

	/**
	 * Returns the namespaces currently registered in the autoloader
	 */
	public function getNamespaces()
	{
		return $this->_namespaces;
	}

	/**
	 * Register directories in which "not found" classes could be found
	 */
	public function registerDirs($directories, $merge = false)
	{
		if ($merge) {
			$this->_directories = array_merge($this->_directories, $directories);
		} else {
			$this->_directories = $directories;
		}

		return $this;
	}

	/**
	 * Returns the directories currently registered in the autoloader
	 */
	public function getDirs()
	{
		return $this->_directories;
	}

	/**
	 * Registers files that are "non-classes" hence need a "require". $this is very useful for including files that only
	 * have functions
	 */
	public function registerFiles($files, $merge = false)
	{
		if ($merge) {
			$this->_files = array_merge($this->_files, $files);
		} else {
			$this->_files = $files;
		}

		return $this;
	}

	/**
	 * Returns the files currently registered in the autoloader
	 */
	public function getFiles()
	{
		return $this->_files;
	}

	/**
	 * Register classes and their locations
	 */
	public function registerClasses($classes, $merge = false)
	{
		if ($merge) {
			$this->_classes = array_merge($this->_classes, $classes);
		} else {
			$this->_classes = $classes;
		}

		return $this;
	}

	/**
	 * Returns the class-map currently registered in the autoloader
	 */
	public function getClasses()
	{
		return $this->_classes;
	}

	/**
	 * Register the autoload method
	 */
	public function register($prepend = null)
	{
		if ($this->_registered === false) {
			/**
			 * Registers directories & namespaces to PHP's autoload
			 */
			spl_autoload_register([$this, "autoLoad"], true, $prepend);

			$this->_registered = true;
		}
		return $this;
	}

	/**
	 * Unregister the autoload method
	 */
	public function unregister()
	{
		if ($this->_registered === true) {
			spl_autoload_unregister([$this, "autoLoad"]);
			$this->_registered = false;
		}
		return $this;
	}

	/**
	 * Autoloads the registered classes
	 */
	public function autoLoad($className)
	{
		/**
		 * First we check for static paths for classes
		 */
		$classes = $this->_classes;
		if (isset($classes[$className])) {
			require $classes[$className];
			return true;
		}

		$extensions = $this->_extensions;

		$ds = DIRECTORY_SEPARATOR;
		$ns = "\\";

		/**
		 * Checking in namespaces
		 */
		$namespaces = $this->_namespaces;

		$fileCheckingCallback = $this->fileCheckingCallback;

		foreach ($namespaces as $nsPrefix => $directories) {

			/**
			 * The class name must start with the current namespace
			 */
			if (!Common::startsWith($className, $nsPrefix)) {
				continue;
			}

			/**
			 * Append the namespace separator to the prefix
			 */
			$fileName = substr($className, strlen($nsPrefix . $ns));

			if (!$fileName) {
				continue;
			}

			$fileName = str_replace($ns, $ds, $fileName);

			foreach ($directories as $directory) {
				/**
				 * Add a trailing directory separator if the user forgot to do that
				 */
				$fixedDirectory = rtrim($directory, $ds) . $ds;

				foreach ($extensions as $extension) {

					$filePath = $fixedDirectory . $fileName . "." . $extension;

					/**
					 * $this is probably a good path, let's check if the file exists
					 */
					if (call_user_func($fileCheckingCallback, $filePath)) {

						/**
						 * Simulate a require
						 */
						require $filePath;

						/**
						 * Return true mean success
						 */
						return true;
					}
				}
			}
		}

		/**
		 * Change the namespace separator by directory separator too
		 */
		$nsClassName = str_replace("\\", $ds, $className);

		/**
		 * Checking in directories
		 */
		$directories = $this->_directories;

		foreach ($directories as $directory) {

			/**
			 * Add a trailing directory separator if the user forgot to do that
			 */
			$fixedDirectory = rtrim($directory, $ds) . $ds;

			foreach ($extensions as $extension) {

				/**
				 * Create a possible path for the file
				 */
				$filePath = $fixedDirectory . $nsClassName . "." . $extension;

				/**
				 * Check in every directory if the class exists here
				 */
				if (call_user_func($fileCheckingCallback, $filePath)) {

					/**
					 * Simulate a require
					 */
					require filePath;

					/**
					 * Return true meaning success
					 */
					return true;
				}
			}
		}

		/**
		 * Cannot find the class, return false
		 */
		return false;
	}

	/**
	 * Get the path when a class was found
	 */
	public function getFoundPath()
	{
		return $this->_foundPath;
	}

	/**
	 * Get the path the loader is checking for a path
	 */
	public function getCheckedPath()
	{
		return $this->_checkedPath;
	}
}

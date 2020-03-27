<?php

namespace Tengyue\Infra\Http;

use Tengyue\Infra\DiInterface;
use Tengyue\Infra\Http\Request\File;
use Tengyue\Infra\Http\Request\Exception;
use Tengyue\Infra\Di\InjectionAwareInterface;
use Tengyue\Infra\Helper\Common;

/**
 * Tengyue\Infra\Http\Request
 *
 * Encapsulates request information for easy and secure access from application controllers.
 *
 * The request object is a simple value object that is passed between the dispatcher and controller classes.
 * It packages the HTTP request environment.
 *
 *<code>
 * use Tengyue\Infra\Http\Request;
 *
 * $request = new Request();
 *
 * if ($request->isPost() && $request->isAjax()) {
 *     echo "Request was made using POST and AJAX";
 * }
 *
 * $request->getServer("HTTP_HOST"); // Retrieve SERVER variables
 * $request->getMethod();            // GET, POST, PUT, DELETE, HEAD, OPTIONS, PATCH, PURGE, TRACE, CONNECT
 * $request->getLanguages();         // An array of languages the client accepts
 *</code>
 */
class Request implements RequestInterface, InjectionAwareInterface
{

	protected $_dependencyInjector;

	protected $_rawBody;

	protected $_filter;

	protected $_putCache;

	protected $_httpMethodParameterOverride = false;

	protected $_strictHostCheck = false;

	/**
	 * Sets the dependency injector
	 */
	public function setDI(DiInterface $dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;
	}

	/**
	 * Returns the internal dependency injector
	 */
	public function getDI()
	{
		return $this->_dependencyInjector;
	}

	/**
	 * Gets a variable from the $_REQUEST superglobal applying filters if needed.
	 * If no parameters are given the $_REQUEST superglobal is returned
	 *
	 *<code>
	 * // Returns value from $_REQUEST["user_email"] without sanitizing
	 * $userEmail = $request->get("user_email");
	 *
	 * // Returns value from $_REQUEST["user_email"] with sanitizing
	 * $userEmail = $request->get("user_email", "email");
	 *</code>
	 */
	public function get($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
	{
		return $this->getHelper($_REQUEST, $name, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
	}

	/**
	 * Gets a variable from the $_POST superglobal applying filters if needed
	 * If no parameters are given the $_POST superglobal is returned
	 *
	 *<code>
	 * // Returns value from $_POST["user_email"] without sanitizing
	 * $userEmail = $request->getPost("user_email");
	 *
	 * // Returns value from $_POST["user_email"] with sanitizing
	 * $userEmail = $request->getPost("user_email", "email");
	 *</code>
	 */
	public function getPost($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
	{
		return $this->getHelper($_POST, $name, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
	}

	/**
	 * Gets a variable from put request
	 *
	 *<code>
	 * // Returns value from $_PUT["user_email"] without sanitizing
	 * $userEmail = $request->getPut("user_email");
	 *
	 * // Returns value from $_PUT["user_email"] with sanitizing
	 * $userEmail = $request->getPut("user_email", "email");
	 *</code>
	 */
	public function getPut($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
	{
		$put =$this->_putCache;

        if (!is_array($put)) {
            $contentType = $this->getContentType();
            if (is_string($contentType) && stripos($contentType, "json") != false) {
                $put = $this->getJsonRawBody(true);
                if (!is_array($put)) {
                    $put = [];
                }
            } else {
                $put = [];
                parse_str($this->getRawBody(), $put);
            }

            $this->_putCache = $put;
        }

		return $this->getHelper($put, $name, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
	}

	/**
	 * Gets variable from $_GET superglobal applying filters if needed
	 * If no parameters are given the $_GET superglobal is returned
	 *
	 *<code>
	 * // Returns value from $_GET["id"] without sanitizing
	 * $id = $request->getQuery("id");
	 *
	 * // Returns value from $_GET["id"] with sanitizing
	 * $id = $request->getQuery("id", "int");
	 *
	 * // Returns value from $_GET["id"] with a default value
	 * $id = $request->getQuery("id", null, 150);
	 *</code>
	 */
	public function getQuery($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
	{
		return $this->getHelper($_GET, $name, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
	}

	/**
	 * Helper to get data from superglobals, applying filters if needed.
	 * If no parameters are given the superglobal is returned.
	 */
	protected final function getHelper($source, $name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
	{
		if ($name === null) {
			return $source;
		}

		if (!isset($source[$name])) {
			return $defaultValue;
		}
		$value = $source[$name];

		if ($filters !== null) {
			$filter =$this->_filter;
			if (!is_object($filter)) {
				$dependencyInjector = $this->_dependencyInjector;
				if (!is_object($dependencyInjector)) {
					throw new Exception("A dependency injection object is required to access the 'filter' service");
				}
				$filter = $dependencyInjector->getShared("filter");
				$this->_filter = $filter;
			}

			$value = $filter->sanitize($value, $filters, $noRecursive);
		}

		if (empty($value) && $notAllowEmpty === true) {
			return $defaultValue;
		}

		return $value;
	}

	/**
	 * Gets variable from $_SERVER superglobal
	 */
	public function getServer($name)
	{
		if (isset($_SERVER[$name])) {
			return $_SERVER[$name];
		}
		return null;
	}

	/**
	 * Checks whether $_REQUEST superglobal has certain index
	 */
	public function has($name)
	{
		return isset($_REQUEST[$name]);
	}

	/**
	 * Checks whether $_POST superglobal has certain index
	 */
	public function hasPost($name)
	{
		return isset($_POST[$name]);
	}

	/**
	 * Checks whether the PUT data has certain index
	 */
	public function hasPut($name)
	{
		$put =$this->getPut();

		return isset($put[$name]);
	}

	/**
	 * Checks whether $_GET superglobal has certain index
	 */
	public function hasQuery($name)
	{
		return isset($_GET[$name]);
	}

	/**
	 * Checks whether $_SERVER superglobal has certain index
	 */
	public final function hasServer($name)
	{
		return isset($_SERVER[$name]);
	}

    /**
     * Checks whether headers has certain index
     */
    public final function hasHeader($header)
    {
        $name = strtoupper(strtr($header, "-", "_"));

        if (isset($_SERVER[$name])) {
            return true;
        }

        if (isset($_SERVER["HTTP_" . $name])) {
            return true;
        }

        return false;
    }

	/**
	 * Gets HTTP header from request data
	 */
	public final function getHeader($header)
	{
		$name = strtoupper(strtr($header, "-", "_"));

		if (isset($_SERVER[$name])) {
			return $_SERVER[$name];
		}

		if (isset($_SERVER["HTTP_" . $name])) {
			return $_SERVER["HTTP_" . $name];
		}

		return "";
	}

	/**
	 * Gets HTTP schema (http/https)
	 */
	public function getScheme()
	{
		$https =$this->getServer("HTTPS");
		if ($https) {
			if ($https == "off") {
				$scheme = "http";
			} else {
				$scheme = "https";
			}
		} else {
			$scheme = "http";
		}
		return $scheme;
	}

	/**
	 * Checks whether request has been made using ajax
	 */
	public function isAjax()
	{
		return isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] === "XMLHttpRequest";
	}

	/**
	 * Checks whether request has been made using SOAP
	 */
	public function isSoap()
	{
		if (isset($_SERVER["HTTP_SOAPACTION"])) {
			return true;
		} else {
			$contentType =$this->getContentType();
			if (!empty($contentType)) {
				return Common::memstr($contentType, "application/soap+xml");
			}
		}
		return false;
	}

	/**
	 * Checks whether request has been made using any secure layer
	 */
	public function isSecure()
	{
		return $this->getScheme() === "https";
	}

	/**
	 * Gets HTTP raw request body
	 */
	public function getRawBody()
	{
		$rawBody =$this->_rawBody;
		if (empty($rawBody)) {

			$contents = file_get_contents("php://input");

			/**
			 * We need store the read raw body because it can't be read again
			 */
			$this->_rawBody = $contents;
			return $contents;
		}
		return $rawBody;
	}

    /**
     * Gets decoded JSON HTTP raw request body
     */
    public function getJsonRawBody($associative = false,$filters = null)
    {
        $rawBody =$this->getRawBody();
        if (!is_string($rawBody)) {
            return false;
        }

        $rowBodyArr = json_decode($rawBody, $associative);
        if(null !== $filters && is_array($rowBodyArr) && !empty($rowBodyArr)){
            foreach ($rowBodyArr as $rowKey => $rowValue){
                if(!is_numeric($rowValue)) {
                    $rowBodyArr[$rowKey] = $this->getHelper($rowBodyArr, $rowKey, $filters);
                }
            }
        }

        return $rowBodyArr;
    }

	/**
	 * Gets active server address IP
	 */
	public function getServerAddress()
	{
		if (isset($_SERVER["SERVER_ADDR"])) {
			return $_SERVER["SERVER_ADDR"];
		}
		return gethostbyname("localhost");
	}

	/**
	 * Gets active server name
	 */
	public function getServerName()
	{
		if (isset($_SERVER["SERVER_NAME"])) {
			return $_SERVER["SERVER_NAME"];
		}

		return "localhost";
	}

	/**
	 * Gets host name used by the request.
	 *
	 * `Request::getHttpHost` trying to find host name in following order:
	 *
	 * - `$_SERVER["HTTP_HOST"]`
	 * - `$_SERVER["SERVER_NAME"]`
	 * - `$_SERVER["SERVER_ADDR"]`
	 *
	 * Optionally `Request::getHttpHost` validates and clean host name.
	 * The `Request::$_strictHostCheck` can be used to validate host name.
	 *
	 * Note: validation and cleaning have a negative performance impact because
	 * they use regular expressions.
	 *
	 * <code>
	 * use Tengyue\Infra\Http\Request;
	 *
	 * $request = new Request;
	 *
	 * $_SERVER["HTTP_HOST"] = "example.com";
	 * $request->getHttpHost(); // example.com
	 *
	 * $_SERVER["HTTP_HOST"] = "example.com:8080";
	 * $request->getHttpHost(); // example.com:8080
	 *
	 * $request->setStrictHostCheck(true);
	 * $_SERVER["HTTP_HOST"] = "ex=am~ple.com";
	 * $request->getHttpHost(); // UnexpectedValueException
	 *
	 * $_SERVER["HTTP_HOST"] = "ExAmPlE.com";
	 * $request->getHttpHost(); // example.com
	 * </code>
	 */
	public function getHttpHost()
	{
		$strict =$this->_strictHostCheck;

		/**
		 * Get the server name from $_SERVER["HTTP_HOST"]
		 */
		$host =$this->getServer("HTTP_HOST");
		if (!$host) {

			/**
			 * Get the server name from $_SERVER["SERVER_NAME"]
			 */
			$host =$this->getServer("SERVER_NAME");
			if (!$host) {
				/**
				 * Get the server address from $_SERVER["SERVER_ADDR"]
				 */
				$host =$this->getServer("SERVER_ADDR");
			}
		}

		if ($host && $strict) {
			/**
			 * Cleanup. Force lowercase as per RFC 952/2181
			 */
			$host = strtolower(trim($host));
			if (Common::memstr($host, ":")) {
				$host = preg_replace("/:[[:digit:]]+$/", "", $host);
			}

			/**
			 * Host may contain only the ASCII letters 'a' through 'z' (in a case-insensitive manner),
			 * the digits '0' through '9', and the hyphen ('-') as per RFC 952/2181
			 */
			if ("" !== preg_replace("/[a-z0-9-]+\.?/", "", $host)) {
				throw new \UnexpectedValueException("Invalid host " . $host);
			}
		}

		return (string) $host;
	}

	/**
	 * Sets if the `Request::getHttpHost` method must be use strict validation of host name or not
	 */
	public function setStrictHostCheck($flag = true)
	{
		$this->_strictHostCheck = $flag;

		return $this;
	}

	/**
	 * Checks if the `Request::getHttpHost` method will be use strict validation of host name or not
	 */
	public function isStrictHostCheck()
	{
		return $this->_strictHostCheck;
	}

	/**
	 * Gets information about the port on which the request is made.
	 */
	public function getPort()
	{
		/**
		 * Get the server name from $_SERVER["HTTP_HOST"]
		 */
		$host =$this->getServer("HTTP_HOST");
		if ($host) {
			if (Common::memstr($host, ":")) {
				$pos = strrpos($host, ":");

				if (false !== $pos) {
					return (int) substr($host, $pos + 1);
				}
			}

			return "https" === $this->getScheme() ? 443 : 80;
		}

		return (int)$this->getServer("SERVER_PORT");
	}

	/**
	 * Gets HTTP URI which request has been made
	 */
	public final function getURI()
	{
		if (isset($_SERVER["REQUEST_URI"])) {
			return $_SERVER["REQUEST_URI"];
		}

		return "";
	}

	/**
	 * Gets most possible client IPv4 Address. This method searches in
	 * $_SERVER["REMOTE_ADDR"] and optionally in $_SERVER["HTTP_X_FORWARDED_FOR"]
	 */
	public function getClientAddress($trustForwardedHeader = false)
	{
		$address = null;

		/**
		 * Proxies uses this IP
		 */
		if ($trustForwardedHeader) {
			$address = $_SERVER["HTTP_X_FORWARDED_FOR"];
			if ($address === null) {
				$address = $_SERVER["HTTP_CLIENT_IP"];
			}
		}

		if ($address === null) {
			$address = $_SERVER["REMOTE_ADDR"];
		}

		if (is_string($address)){
			if (Common::memstr($address, ",")) {
				/**
				 * The client address has multiples parts, only return the first part
				 */
				return explode(",", $address)[0];
			}
			return $address;
		}

		return false;
	}

	/**
	 * Gets HTTP method which request has been made
	 *
	 * If the X-HTTP-Method-Override header is set, and if the method is a POST,
	 * then it is used to determine the "real" intended HTTP method.
	 *
	 * The _method request parameter can also be used to determine the HTTP method,
	 * but only if setHttpMethodParameterOverride(true) has been called.
	 *
	 * The method is always an uppercased string.
	 */
	public final function getMethod()
	{
		$returnMethod = "";

		if (isset($_SERVER["REQUEST_METHOD"])) {
			$returnMethod = strtoupper($_SERVER["REQUEST_METHOD"]);
		} else {
			return "GET";
		}

		if ("POST" === $returnMethod) {
			$overridedMethod = $this->getHeader("X-HTTP-METHOD-OVERRIDE");
			if (!empty($overridedMethod)) {
				$returnMethod = strtoupper($overridedMethod);
			} elseif ($this->_httpMethodParameterOverride) {
				if (isset($_REQUEST["_method"])) {
					$returnMethod = strtoupper($_REQUEST["_method"]);
				}
			}
		}

		if (!$this->isValidHttpMethod($returnMethod)) {
			return "GET";
		}

		return $returnMethod;
	}

	/**
	 * Gets HTTP user agent used to made the request
	 */
	public function getUserAgent()
	{
		if (isset($_SERVER["HTTP_USER_AGENT"])) {
			return $_SERVER["HTTP_USER_AGENT"];
		}
		return "";
	}

	/**
	 * Checks if a method is a valid HTTP method
	 */
	public function isValidHttpMethod($method)
	{
		switch (strtoupper($method)) {
			case "GET":
			case "POST":
			case "PUT":
			case "DELETE":
			case "HEAD":
			case "OPTIONS":
			case "PATCH":
			case "PURGE": // Squid and Varnish support
			case "TRACE":
			case "CONNECT":
				return true;
		}

		return false;
	}

	/**
	 * Check if HTTP method match any of the passed methods
	 * When strict is true it checks if validated methods are real HTTP methods
	 */
	public function isMethod($methods, $strict = false)
	{
		$httpMethod =$this->getMethod();

		if (is_string($methods)) {
			if ($strict && !$this->isValidHttpMethod($methods)) {
				throw new Exception("Invalid HTTP method: " . $methods);
			}
			return $methods == $httpMethod;
		}

		if (is_array($methods)) {
			foreach ($methods as $method) {
				if ($this->isMethod($method, $strict)) {
					return true;
				}
			}

			return false;
		}

		if ($strict) {
			throw new Exception("Invalid HTTP method: non-string");
		}

		return false;
	}

	/**
	 * Checks whether HTTP method is POST. if $_SERVER["REQUEST_METHOD"]==="POST"
	 */
	public function isPost()
	{
		return $this->getMethod() === "POST";
	}

	/**
	 * Checks whether HTTP method is GET. if $_SERVER["REQUEST_METHOD"]==="GET"
	 */
	public function isGet()
	{
		return $this->getMethod() === "GET";
	}

	/**
	 * Checks whether HTTP method is PUT. if $_SERVER["REQUEST_METHOD"]==="PUT"
	 */
	public function isPut()
	{
		return $this->getMethod() === "PUT";
	}

	/**
	 * Checks whether HTTP method is PATCH. if $_SERVER["REQUEST_METHOD"]==="PATCH"
	 */
	public function isPatch()
	{
		return $this->getMethod() === "PATCH";
	}

	/**
	 * Checks whether HTTP method is HEAD. if $_SERVER["REQUEST_METHOD"]==="HEAD"
	 */
	public function isHead()
	{
		return $this->getMethod() === "HEAD";
	}

	/**
	 * Checks whether HTTP method is DELETE. if $_SERVER["REQUEST_METHOD"]==="DELETE"
	 */
	public function isDelete()
	{
		return $this->getMethod() === "DELETE";
	}

	/**
	 * Checks whether HTTP method is OPTIONS. if $_SERVER["REQUEST_METHOD"]==="OPTIONS"
	 */
	public function isOptions()
	{
		return $this->getMethod() === "OPTIONS";
	}

	/**
	 * Checks whether HTTP method is PURGE (Squid and Varnish support). if $_SERVER["REQUEST_METHOD"]==="PURGE"
	 */
	public function isPurge()
	{
		return $this->getMethod() === "PURGE";
	}

	/**
	 * Checks whether HTTP method is TRACE. if $_SERVER["REQUEST_METHOD"]==="TRACE"
	 */
	public function isTrace()
	{
		return $this->getMethod() === "TRACE";
	}

	/**
	 * Checks whether HTTP method is CONNECT. if $_SERVER["REQUEST_METHOD"]==="CONNECT"
	 */
	public function isConnect()
	{
		return $this->getMethod() === "CONNECT";
	}

	/**
	 * Checks whether request include attached files
	 */
	public function hasFiles($onlySuccessful = false)
	{
		$numberFiles = 0;

		$files = $_FILES;

		if (!is_array($files)) {
			return 0;
		}

		foreach ($files as $file) {
			if (isset($file["error"])) {
				$error = $file["error"];
				if (!is_array($error)) {
					if (!$error || !$onlySuccessful) {
						$numberFiles++;
					}
				}

				if (is_array($error)) {
					$numberFiles += $this->hasFileHelper($error, $onlySuccessful);
				}
			}
		}

		return $numberFiles;
	}

	/**
	 * Recursively counts file in an array of files
	 */
	protected final function hasFileHelper($data, $onlySuccessful)
	{
		$numberFiles = 0;

		if (!is_array($data)) {
			return 1;
		}

		foreach ($data as $value) {
			if (!is_array($value)) {
				if (!$value || !$onlySuccessful) {
					$numberFiles++;
				}
			}

			if (is_array($value)) {
				$numberFiles +=$this->hasFileHelper($value, $onlySuccessful);
			}
		}

		return $numberFiles;
	}

	/**
	 * Gets attached files as Tengyue\Infra\Http\Request\File instances
	 */
	public function getUploadedFiles($onlySuccessful = false)
	{
		$files = [];

		$superFiles = $_FILES;

		if (count($superFiles) > 0) {

			foreach ($superFiles as $prefix => $input) {
				if (is_array($input["name"])) {
					$smoothInput =$this->smoothFiles(
						$input["name"],
						$input["type"],
						$input["tmp_name"],
						$input["size"],
						$input["error"],
						$prefix
					);

					foreach ($smoothInput as $file) {
						if ($onlySuccessful == false || $file["error"] == UPLOAD_ERR_OK) {
							$dataFile = [
								"name" => $file["name"],
								"type" => $file["type"],
								"tmp_name" => $file["tmp_name"],
								"size" => $file["size"],
								"error" => $file["error"]
							];

							$files[] = new File($dataFile, $file["key"]);
						}
					}
				} else {
					if ($onlySuccessful == false || $input["error"] == UPLOAD_ERR_OK) {
						$files[] = new File($input, $prefix);
					}
				}
			}
		}

		return $files;
	}

	/**
	 * Smooth out $_FILES to have plain array with all files uploaded
	 */
	protected final function smoothFiles($names, $types, $tmp_names, $sizes, $errors, $prefix)
	{
		$files = [];

		foreach ($names as $idx => $name) {
			$p = $prefix . "." . $idx;

			if (is_string($name)) {

				$files[] = [
					"name" => $name,
					"type" => $types[$idx],
					"tmp_name" => $tmp_names[$idx],
					"size" => $sizes[$idx],
					"error" => $errors[$idx],
					"key" => $p
				];
			}

			if (is_array($name)) {
				$parentFiles =$this->smoothFiles(
					$names[$idx],
					$types[$idx],
					$tmp_names[$idx],
					$sizes[$idx],
					$errors[$idx],
					$p
				);

				foreach ($parentFiles as $file) {
					$files[] = $file;
				}
			}
		}

		return $files;
	}

	/**
	 * Returns the available headers in the request
	 *
	 * <code>
	 * $_SERVER = [
	 *     "PHP_AUTH_USER" => "Tengyue\Infra",
	 *     "PHP_AUTH_PW"   => "secret",
	 * ];
	 *
	 * $headers = $request->getHeaders();
	 *
	 * echo $headers["Authorization"]; // Basic cGhhbGNvbjpzZWNyZXQ=
	 * </code>
	 */
	public function getHeaders()
	{
		$headers = [];
		$contentHeaders = ["CONTENT_TYPE" => true, "CONTENT_LENGTH" => true, "CONTENT_MD5" => true];

		foreach ($_SERVER as $name => $value) {
			// Note: The starts_with uses case insensitive search here
			if (Common::startsWith($name, "HTTP_")) {
				$name = ucwords(strtolower(str_replace("_", " ", substr($name, 5))));
				$name = str_replace(" ", "-", $name);
				$headers[$name] = $value;

				continue;
			}

			// The "CONTENT_" headers are not prefixed with "HTTP_".
			$name = strtoupper($name);
			if (isset($contentHeaders[$name])) {
				$name = ucwords(strtolower(str_replace("_", " ", $name)));
				$name = str_replace(" ", "-", $name);
				$headers[$name] = $value;
			}
		}

		$authHeaders =$this->resolveAuthorizationHeaders();

		// Protect for future (child classes) changes
		if (is_array($authHeaders)) {
			$headers = array_merge($headers, $authHeaders);
		}

		return $headers;
	}

	/**
	 * Resolve authorization headers.
	 */
	protected function resolveAuthorizationHeaders()
	{
		$authHeader = null;
		$headers = [];

		if (isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"])) {
			$headers["Php-Auth-User"] = $_SERVER["PHP_AUTH_USER"];
			$headers["Php-Auth-Pw"] = $_SERVER["PHP_AUTH_PW"];
		} else {
			if (isset($_SERVER["HTTP_AUTHORIZATION"])) {
				$authHeader = $_SERVER["HTTP_AUTHORIZATION"];
			} elseif (isset($_SERVER["REDIRECT_HTTP_AUTHORIZATION"])) {
				$authHeader = $_SERVER["REDIRECT_HTTP_AUTHORIZATION"];
			}

			if ($authHeader) {
				if (stripos($authHeader, "basic ") === 0) {
					$exploded = explode(":", base64_decode(substr($authHeader, 6)), 2);
					if (count($exploded) == 2) {
						$headers["Php-Auth-User"] = $exploded[0];
						$headers["Php-Auth-Pw"]   = $exploded[1];
					}
				} elseif (stripos($authHeader, "digest ") === 0 && !isset($_SERVER["PHP_AUTH_DIGEST"])) {
					$headers["Php-Auth-Digest"] = $authHeader;
				} elseif (stripos($authHeader, "bearer ") === 0) {
					$headers["Authorization"] = $authHeader;
				}
			}
		}

		if (!isset($headers["Authorization"])) {
			if (isset($headers["Php-Auth-User"])) {
				$headers["Authorization"] = "Basic " . base64_encode($headers["Php-Auth-User"] . ":" . $headers["Php-Auth-Pw"]);
			} elseif (isset($headers["Php-Auth-Digest"])) {
				$headers["Authorization"] = $headers["Php-Auth-Digest"];
			}
		}

		return $headers;
	}

	/**
	 * Gets web page that refers active request. ie: http://www.google.com
	 */
	public function getHTTPReferer()
	{
		if (isset($_SERVER["HTTP_REFERER"])) {
			return $_SERVER["HTTP_REFERER"];
		}
		return "";
	}

	/**
	 * Process a request header and return the one with best quality
	 */
	protected final function _getBestQuality($qualityParts, $name)
	{

		$i = 0;
		$quality = 0.0;
		$selectedName = "";

		foreach ($qualityParts as $accept) {
			if ($i == 0) {
				$quality = (double) $accept["quality"];
				$selectedName = $accept[$name];
			} else {
				$acceptQuality = (double) $accept["quality"];
				if ($acceptQuality > $quality) {
					$quality = $acceptQuality;
					$selectedName = $accept[$name];
				}
			}
			$i++;
		}
		return $selectedName;
	}

	/**
	 * Gets content type which request has been made
	 */
	public function getContentType()
	{
		if (isset($_SERVER["CONTENT_TYPE"])) {
			return $_SERVER["CONTENT_TYPE"];
		} else {
			/**
			 * @see https://bugs.php.net/bug.php?id=66606
			 */
			if (isset($_SERVER["HTTP_CONTENT_TYPE"])) {
				return $_SERVER["HTTP_CONTENT_TYPE"];
			}
		}

		return null;
	}

	/**
	 * Gets an array with mime/types and their quality accepted by the browser/client from $_SERVER["HTTP_ACCEPT"]
	 */
	public function getAcceptableContent()
	{
		return $this->_getQualityHeader("HTTP_ACCEPT", "accept");
	}

	/**
	 * Gets best mime/type accepted by the browser/client from $_SERVER["HTTP_ACCEPT"]
	 */
	public function getBestAccept()
	{
		return $this->_getBestQuality($this->getAcceptableContent(), "accept");
	}

	/**
	 * Gets a charsets array and their quality accepted by the browser/client from $_SERVER["HTTP_ACCEPT_CHARSET"]
	 */
	public function getClientCharsets()
	{
		return $this->_getQualityHeader("HTTP_ACCEPT_CHARSET", "charset");
	}

	/**
	 * Gets best charset accepted by the browser/client from $_SERVER["HTTP_ACCEPT_CHARSET"]
	 */
	public function getBestCharset()
	{
		return $this->_getBestQuality($this->getClientCharsets(), "charset");
	}

	/**
	 * Gets languages array and their quality accepted by the browser/client from $_SERVER["HTTP_ACCEPT_LANGUAGE"]
	 */
	public function getLanguages()
	{
		return $this->_getQualityHeader("HTTP_ACCEPT_LANGUAGE", "language");
	}

	/**
	 * Gets best language accepted by the browser/client from $_SERVER["HTTP_ACCEPT_LANGUAGE"]
	 */
	public function getBestLanguage()
	{
		return $this->_getBestQuality($this->getLanguages(), "language");
	}

	/**
	 * Gets auth info accepted by the browser/client from $_SERVER["PHP_AUTH_USER"]
	 */
	public function getBasicAuth()
	{
		if (isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"])) {
			$auth = [];
			$auth["username"] = $_SERVER["PHP_AUTH_USER"];
			$auth["password"] = $_SERVER["PHP_AUTH_PW"];
			return $auth;
		}

		return null;
	}

	/**
	 * Gets auth info accepted by the browser/client from $_SERVER["PHP_AUTH_DIGEST"]
	 */
	public function getDigestAuth()
	{
		$auth = [];
		if (isset($_SERVER["PHP_AUTH_DIGEST"])) {
			$matches = [];
			if (!preg_match_all("#(\\w+)=(['\"]?)([^'\" ,]+)\\2#", $_SERVER["PHP_AUTH_DIGEST"], $matches, 2)) {
				return $auth;
			}
			if (is_array($matches)) {
				foreach ($matches as $match) {
					$auth[$match[1]] = $match[3];
				}
			}
		}

		return $auth;
	}

	/**
	 * Process a request header and return an array of values with their qualities
	 */
	protected final function _getQualityHeader($serverIndex, $name)
	{
		$returnedParts = [];
		foreach (preg_split("/,\\s*/",$this->getServer($serverIndex), -1, PREG_SPLIT_NO_EMPTY) as $part) {

			$headerParts = [];
			foreach (preg_split("/\s*;\s*/", trim($part), -1, PREG_SPLIT_NO_EMPTY) as $headerPart) {
				if (strpos($headerPart, "=") !== false) {
					$split = explode("=", $headerPart, 2);
					if ($split[0] === "q") {
						$headerParts["quality"] = (double) $split[1];
					} else {
						$headerParts[$split[0]] = $split[1];
					}
				} else {
					$headerParts[$name] = $headerPart;
					$headerParts["quality"] = 1.0;
				}
			}

			$returnedParts[] = $headerParts;
		}

		return $returnedParts;
	}

	public function getHttpMethodParameterOverride()
	{
		return $this->_httpMethodParameterOverride;
	}

	public function setHttpMethodParameterOverride($httpMethodParameterOverride)
	{
		$this->_httpMethodParameterOverride = $httpMethodParameterOverride;
	}
}

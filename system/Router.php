<?php namespace System;

/**
 * Request router.
 */
class Router
{
	protected $directory;
	protected $controller;
	protected $method;

	//--------------------------------------------------------------------

	public function __construct($config)
	{
		$this->controller = $config->defaultController;
		$this->method = 'index';
	}

	//--------------------------------------------------------------------

	public function handle($uri = '')
	{
		if (empty($uri))
		{
			$uri = $this->parseURI();
		}

		if ($uri === '/')
		{
			$uri = $this->controller;
		}

		$this->autoRoute($uri);

		return $this->controller;
	}

	//--------------------------------------------------------------------

	public function controllerName()
	{
		return $this->controller;
	}

	//--------------------------------------------------------------------

	public function methodName()
	{
		return $this->method;
	}

	//--------------------------------------------------------------------

	public function directory()
	{
	  return ! empty($this->directory) ? $this->directory : '';
	}

	//--------------------------------------------------------------------

	/**
	 * Attempts to match a URI path against Controllers and directories
	 * found in APPPATH/Controllers, to find a matching route.
	 *
	 * @param string $uri
	 */
	public function autoRoute($uri)
	{
		$segments = explode('/', $uri);

		$segments = $this->validateRequest($segments);

		// If we don't have any segments left - try the default controller;
		// WARNING: Directories get shifted out of the segments array.
		if (empty($segments))
		{
			$this->setDefaultController();
		}
		// If not empty, then the first segment should be the controller
		else
		{
			$this->controller = ucfirst(array_shift($segments));
		}

		// Use the method name if it exists.
		// If it doesn't, no biggie - the default method name
		// has already been set.
		if (! empty($segments))
		{
			$method = array_shift($segments);

			if (!empty($method))
			{
				$this->method = $method;
			}
		}

		if (strpos($this->controller, '\\') === false)
		{
			$this->controller = str_replace('/', '\\', 'App\Controllers\\'.$this->directory.$this->controller);
		}
	}

	//--------------------------------------------------------------------

	/**
	 * Attempts to validate the URI request and determine the controller path.
	 *
	 * @param array $segments URI segments
	 *
	 * @return array URI segments
	 */
	protected function validateRequest($segments)
	{
		$c = count($segments);
		$directory_override = isset($this->directory);

		// Loop through our segments and return as soon as a controller
		// is found or when such a directory doesn't exist
		while ($c-- > 0)
		{
			$test = $this->directory . ucfirst($segments[0]);

			if ( ! file_exists(APPPATH . 'Controllers/' . $test . '.php') && $directory_override === false && is_dir(APPPATH . 'Controllers/' . $this->directory . ucfirst($segments[0]))	)
			{
				$this->setDirectory(array_shift($segments), true);
				continue;
			}

			return $segments;
		}

		// This means that all segments were actually directories
		return $segments;
	}

	//--------------------------------------------------------------------

	/**
	 * Sets the sub-directory that the controller is in.
	 *
	 * @param string|null $dir
	 * @param bool|false  $append
	 */
	protected function setDirectory($dir = null, $append = false)
	{
		$dir = ucfirst($dir);

		if ($append !== TRUE || empty($this->directory))
		{
			$this->directory = str_replace('.', '', trim($dir, '/')).'/';
		}
		else
		{
			$this->directory .= str_replace('.', '', trim($dir, '/')).'/';
		}
	}

	//--------------------------------------------------------------------

	/**
	 * Sets the default controller.
	 */
	protected function setDefaultController()
	{
		if (empty($this->controller))
		{
			throw new \Exception('Unable to determine what should be displayed. A default route has not been specified in the routing file.');
		}

		// Is the method being specified?
		if (sscanf($this->controller, '%[^/]/%s', $class, $this->method) !== 2)
		{
			$this->method = 'index';
		}

		if (! file_exists(APPPATH.'Controllers/'.$this->directory.ucfirst($class).'.php'))
		{
			return;
		}

		$this->controller = ucfirst($class);
	}

	//--------------------------------------------------------------------

	protected function parseURI()
	{
		if ( ! isset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']))
		{
			return '';
		}

		// parse_url() returns false if no host is present, but the path or query string
		// contains a colon followed by a number
		$parts = parse_url('http://dummy'.$_SERVER['REQUEST_URI']);
		$query = isset($parts['query']) ? $parts['query'] : '';
		$uri   = isset($parts['path']) ? $parts['path'] : '';

		if (isset($_SERVER['SCRIPT_NAME'][0]))
		{
			if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0)
			{
				$uri = (string)substr($uri, strlen($_SERVER['SCRIPT_NAME']));
			}
			elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0)
			{
				$uri = (string)substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
			}
		}
		// This section ensures that even on servers that require the URI to be in the query string (Nginx) a correct
		// URI is found, and also fixes the QUERY_STRING getServer var and $_GET array.
		if (trim($uri, '/') === '' && strncmp($query, '/', 1) === 0)
		{
			$query                   = explode('?', $query, 2);
			$uri                     = $query[0];
			$_SERVER['QUERY_STRING'] = isset($query[1]) ? $query[1] : '';
		}
		else
		{
			$_SERVER['QUERY_STRING'] = $query;
		}

		parse_str($_SERVER['QUERY_STRING'], $_GET);

		if ($uri === '/' || $uri === '')
		{
			return '/';
		}

		return $this->removeRelativeDirectory($uri);
	}

	protected function removeRelativeDirectory($uri)
	{
		$uris = [];
		$tok  = strtok($uri, '/');
		while ($tok !== false)
		{
			if (( ! empty($tok) || $tok === '0') && $tok !== '..')
			{
				$uris[] = $tok;
			}
			$tok = strtok('/');
		}
		return implode('/', $uris);
	}
}

/* End of file Router.php */
/* Location: ./system/Router.php */

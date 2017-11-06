<?php namespace System;

/**
 * Class Controller
 */
class Controller
{
  public function __construct()
	{
	}

  protected function loadView($view, $data = [])
	{
    $names = explode(':', $view);
    $view = $names[0];
    $block = isset($names[1]) ? $names[1] : '';

		$view = str_replace('.php', '', $view).'.php';

    if (strpos($view, '/') === 0)
    {
      $file = ROOTPATH.trim($view, '/');
    }
    else
    {
      $file = APPPATH.'Views/'.$view;
    }

		if ( ! file_exists($file))
		{
      return 'View not found!';
		}

		// Make our view data available to the view.
		extract($data, EXTR_SKIP);

		ob_start();

    if (preg_match('#\.t\.php$#i', $view))
    {
      $template = new Template();
      $file = $template->make($view, $block);
    }

    include($file);	// PHP will be processed

		$output = ob_get_contents();
		@ob_end_clean();

		return ltrim($output);
	}

  protected function getGet($key = '', $default = '')
  {
    if ($key === '')
    {
      return $_GET;
    }
    else
    {
      return isset($_GET[$key]) ? $_GET[$key] : $default;
    }
  }

  protected function getPost($key = '', $default = '')
  {
    if ($key === '')
    {
      return $_POST;
    }
    else
    {
      return isset($_POST[$key]) ? $_POST[$key] : $default;
    }
  }
}

/* End of file Controller.php */
/* Location: ./system/Controller.php */

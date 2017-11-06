<?php namespace System;

class RedirectException extends \Exception
{
}

class PageNotFoundException extends \Exception
{
}

/**
 * This class is the core of the System
 */
class Core
{
  /**
   * The current version of System
   */
  const VERSION = '0.0.1';

  protected $config;
  protected $router;
  protected $controller;
  protected $method;
  protected $uri;
  protected $output;

  //--------------------------------------------------------------------

  public function __construct($config)
  {
    $this->config = $config;
  }

  //--------------------------------------------------------------------

  /**
   * Launch the application!
   *
   * This is "the loop" if you will.
   *
   */
  public function run()
  {
    try
    {
      $this->router = new \System\Router($this->config);

      ob_start();

      $this->controller = $this->router->handle($this->uri);
      $this->method = $this->router->methodName();

      if (class_exists($this->controller))
      {
        $controller = new $this->controller($this->config);

        if (is_callable([$controller, $this->method]))
        {
          $returned = $controller->{$this->method}();
        }
        else
        {
          throw new PageNotFoundException('Action ' . $this->method . ' not found!');
        }
      }
      else
      {
        throw new PageNotFoundException('Controller ' . $this->controller . ' not found!');
      }

      $this->gatherOutput($returned);

      $this->sendResponse();
    }
    catch (RedirectException $e)
    {
      $this->gatherOutput();
      $this->sendResponse();
      $this->callExit();
    }
    catch (PageNotFoundException $e)
    {
      $this->display404errors($e);
    }
  }

  public function setURI($uri)
  {
    $this->uri = $uri;
  }

  //--------------------------------------------------------------------

  protected function gatherOutput($returned = null)
  {
    $this->output = ob_get_contents();
    ob_end_clean();

    if (is_string($returned))
    {
      $this->output .= $returned;
    }
  }

  //--------------------------------------------------------------------

  protected function sendResponse()
  {
    if (strlen($this->output) > 1000) {
      ob_start('ob_gzhandler');
    }

    echo $this->output;
  }

  protected function callExit($code = 0)
  {
    exit($code);
  }

  protected function display404errors($e)
  {
    @header("HTTP/1.1 404 Not Found");

    $message = $e->getMessage();

    if (strlen($message) > 1000) {
      ob_start('ob_gzhandler');
    }

    echo $message;

    $this->callExit(4);
  }
}

/* End of file Core.php */
/* Location: ./system/Core.php */

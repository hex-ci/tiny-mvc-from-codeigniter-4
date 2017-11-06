<?php namespace System;

class Autoloader
{
  private $psr4 = [
    'System' => BASEPATH,
    'App' => APPPATH,
  ];

  public function register()
  {
    spl_autoload_extensions('.php,.inc');
    spl_autoload_register([$this, 'loadClass'], true, true);
  }

  public function loadClass($class)
  {
    $class = trim($class, '\\');
    $class = str_ireplace('.php', '', $class);
    $mapped_file = $this->loadInNamespace($class);

    return $mapped_file;
  }

  private function loadInNamespace($class)
  {
    if (strpos($class, '\\') === false)
    {
      return false;
    }

    foreach ($this->psr4 as $namespace => $directories)
    {
      if (is_string($directories))
      {
        $directories = [$directories];
      }
      foreach ($directories as $directory)
      {
        if (strpos($class, $namespace) === 0)
        {
          $filePath = $directory . str_replace('\\', '/', substr($class, strlen($namespace))) . '.php';
          $filename = $this->requireFile($filePath);

          if ($filename)
          {
            return $filename;
          }
        }
      }
    }

    // never found a mapped file
    return false;
  }

  private function requireFile($file)
  {
    if (file_exists($file))
    {
      require_once $file;
      return $file;
    }

    return false;
  }
}

/* End of file Autoloader.php */
/* Location: ./system/Autoloader.php */

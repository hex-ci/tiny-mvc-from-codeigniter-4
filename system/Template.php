<?php namespace System;

/**
 * 模板引擎类库
 *
 * 一个实现了模板继承的，基于 PHP 原生语法的模板引擎
 *
 */

class Template
{
  private $l_delim = '<#';
  private $r_delim = '#>';
  private $ext = '.t.php';
  private $version = '0.2';

  public function __construct()
  {
    $this->src_path = APPPATH.'Views/';
    $this->dest_path = sys_get_temp_dir().'/SystemCache/';

    $this->depth = 0;
    $this->templates = [];
  }

  public function make($name, $block = '')
  {
    $dest_filename = $this->dest_path.sha1($name . ($block === '' ? '' : ':' . $block)).$this->ext;

    // 先检查是否需要重编译
    if (!$this->check($dest_filename))
    {
      return $dest_filename;
    }

    // 支持直接获取 block 内容
    if ($block !== '')
    {
      // 先读取文件名
      $filename = $this->src_path.$name;
      // TODO: 可能需要先判断文件是否存在，并给出错误信息
      $content = file_get_contents($filename);
      $this->templates[$name] = filemtime($filename);
      $blocks = $this->get_blocks($content);

      // 写入文件
      $this->write_file($dest_filename, isset($blocks[$block]) ? $blocks[$block] : 'Block ' . $block . ' not found!');

      return $dest_filename;
    }

    // 先读取文件名
    $filename = $this->src_path.$name;
    // TODO: 可能需要先判断文件是否存在，并给出错误信息
    $content = file_get_contents($filename);
    $this->templates[$name] = filemtime($filename);

    $extends_name = $this->get_extends($content);

    // 判断是否第一行是否有 extends 指令
    if (!empty($extends_name))
    {
      // 有 extends 指令，表示需要解析父模板
      $content = $this->parse_parent($extends_name, $content);
    }

    $content = $this->remove_command($content);

    // 写入文件
    $this->write_file($dest_filename, $content);

    return $dest_filename;
  }

  public function check($filename)
  {
    if (!file_exists($filename))
    {
      return true;
    }

    // 非调试环境则不做文件更新时间检查，直接返回无需重编译状态
    // if (ENVIRONMENT != 'development')
    // {
    //   return false;
    // }

    $content = file($filename);

    $templates = unserialize($content[1]);

    // 检查每个文件是否过期
    // 只要有一个文件过期则重编译整个模板
    foreach ($templates as $key => $value)
    {
      //$old_time = filemtime($this->src_path.$key.$this->ext);
      $new_time = filemtime($this->src_path.$key);
      if ($new_time > $value)
      {
        // 文件有更新
        return true;
      }
    }

    return false;
  }

  private function parse_parent($name, $sub_content)
  {
    $this->depth++;

    $name .= $this->ext;

    $filename = $this->src_path.$name;
    $content = file_get_contents($filename);
    $this->templates[$name] = filemtime($filename);

    $extends_name = $this->get_extends($content);
    if (!empty($extends_name))
    {
      // 有 extends 指令，表示需要加载父模板
      $content = $this->parse_parent($extends_name, $content);
    }

    // 解析 block 指令
    $sub_blocks = $this->get_blocks($sub_content);

    $content = $this->parse_parent_block($content, $sub_blocks);

    $this->depth--;

    return $content;
  }

  private function get_extends($content)
  {
    $pattern = $this->create_open_matcher('extends');

    if (preg_match($pattern, $content, $match, PREG_OFFSET_CAPTURE))
    {
      if ($match[0][1] == 0)
      {
        return $match[2][0];
      }
    }

    return '';
  }

  private function get_blocks($content)
  {
    $pattern = $this->create_matcher('block');

    preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

    $blocks = array();

    foreach ($matches as $item)
    {
      if (!empty($item[2]))
      {
        $param = preg_split('/\s+/', trim($item[2]));
        $name = $param[0];

        $blocks[$name] = $item[3];
      }
    }

    return $blocks;
  }

  private function parse_parent_block($content, $blocks)
  {
    $pattern = $this->create_plain_matcher('block');

    $this->current_blocks = $blocks;

    $content = preg_replace_callback($pattern, array(&$this, 'callback_parent_block'), $content);

    return $content;
  }

  // --------------------------------------------------------------------

  // block 内容处理函数
  private function callback_parent_block($match)
  {
    $param = preg_split('/\s+/', trim($match[3]));

    $name = $param[0];

    $mode = isset($param[1]) ? $param[1] : '';

    if ($mode == 'hide' && $name !== '' && !isset($this->current_blocks[$name]))
    {
      if ($this->depth > 1)
      {
        return $match[0];
      }
      else
      {
        return $match[1].$match[5];
      }
    }

    if ($name !== '' && isset($this->current_blocks[$name]))
    {
      $this->current_blocks[$name] = $this->command_parent($this->current_blocks[$name], $match[4]);
      $this->current_blocks[$name] = $this->command_child($this->current_blocks[$name], $match[4]);

      return $match[1].$this->current_blocks[$name].$match[5];
    }

    return $match[0];
  }

  private function command_parent($content, $parent_content)
  {
    $pattern = $this->create_open_matcher('parent');

    $content = preg_replace($pattern, $parent_content, $content);

    return $content;
  }

  private function command_child($content, $parent_content)
  {
    $pattern = $this->create_open_matcher('child');

    if (preg_match($pattern, $parent_content))
    {
        $content = preg_replace($pattern, $content, $parent_content);
    }

    return $content;
  }

  private function remove_command($content)
  {
    $pattern = $this->create_open_matcher('.+?');

    $content = preg_replace($pattern, '', $content);

    return $content;
  }

  private function create_matcher($function)
  {
    return '~' . preg_quote($this->l_delim, '~') . '\s*('. $function .')(?:\s+([^#]+?)|\s*)\s*' . preg_quote($this->r_delim, '~') .
      '(?:\r?\n)?(.*?)'.preg_quote($this->l_delim, '~') . '\s*/' . $function . '\s*'. preg_quote($this->r_delim, '~') . '(?:\r?\n)?~s';
  }

  private function create_open_matcher($function)
  {
    return '~' . preg_quote($this->l_delim, '~') . '\s*('. $function .')(?:\s+([^#]+?)|\s*)\s*' . preg_quote($this->r_delim, '~') . '(?:\r?\n)?~';
  }

  private function create_plain_matcher($function)
  {
    return '~(' . preg_quote($this->l_delim, '~') . '\s*('. $function .')(?:\s+([^#]+?)|\s*)\s*' . preg_quote($this->r_delim, '~') .
      '(?:\r?\n)?)(.*?)('.preg_quote($this->l_delim, '~') . '\s*/' . $function . '\s*'. preg_quote($this->r_delim, '~') . '(?:\r?\n)?)~s';
  }

  // --------------------------------------------------------------------

  /**
   *  Set the left/right variable delimiters
   *
   * @access  public
   * @param   string
   * @param   string
   * @return  void
   */
  public function set_delimiters($l = '[', $r = ']')
  {
    $this->l_delim = $l;
    $this->r_delim = $r;
  }

  // --------------------------------------------------------------------

  private function read_file($file)
  {
    if ( ! file_exists($file))
    {
      return FALSE;
    }

    if (function_exists('file_get_contents'))
    {
      return file_get_contents($file);
    }

    if ( ! $fp = @fopen($file, FOPEN_READ))
    {
      return FALSE;
    }

    flock($fp, LOCK_SH);

    $data = '';
    if (filesize($file) > 0)
    {
      $data =& fread($fp, filesize($file));
    }

    flock($fp, LOCK_UN);
    fclose($fp);

    return $data;
  }

  private function write_file($path, $data, $mode = 'wb')
  {
    $dir = dirname($path);

    $mask = umask(0);

    if (!is_dir($dir))
    {
      @mkdir($dir, 0777, true);
    }

    if ( ! $fp = @fopen($path, $mode))
    {
      return FALSE;
    }

    $prefix = "<?php /* template engine v" . $this->version . "\n".serialize($this->templates)."\n*/ ?>\n";

    flock($fp, LOCK_EX);
    fwrite($fp, $prefix.$data);
    flock($fp, LOCK_UN);
    fclose($fp);

    //chmod($path, 0777);

    umask($mask);

    return TRUE;
  }

  private function get_filenames($source_dir, $include_path = FALSE, $_recursion = FALSE)
  {
    static $_filedata = array();

    if ($fp = @opendir($source_dir))
    {
      // reset the array and make sure $source_dir has a trailing slash on the initial call
      if ($_recursion === FALSE)
      {
        $_filedata = array();
        $source_dir = rtrim(realpath($source_dir), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
      }

      while (FALSE !== ($file = readdir($fp)))
      {
        if (@is_dir($source_dir.$file) && strncmp($file, '.', 1) !== 0)
        {
          $this->get_filenames($source_dir.$file.DIRECTORY_SEPARATOR, $include_path, TRUE);
        }
        elseif (strncmp($file, '.', 1) !== 0)
        {
          $_filedata[] = ($include_path == TRUE) ? $source_dir.$file : $file;
        }
      }

      return $_filedata;
    }
    else
    {
      return FALSE;
    }
  }

}

/* End of file Template.php */
/* Location: ./system/Template.php */

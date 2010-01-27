<?php

class MojoUtils extends Mojo
{
  public function __construct($args)
  {
    $this->args = $ags;
    return $this;
  }

  static function getConfig($key)
  {
    if(class_exists('sfConfig')) return sfConfig::get($key);
    if(isset($_SESSION[$key])) return $_SESSION[$key];
    else return false;
  }

  static function setConfig($key,$val)
  {
    if(class_exists('sfConfig')) return sfConfig::set($key,$val);
    else $_SESSION[$key] = $val;
    return;
  }

  static function editStream($args,$stream)
  {
    if(empty($args)) return self::prompt("Cannot edit stream, no args found");
    foreach($args as $k => $v) $stream = str_replace("%".strtoupper($k)."%",$v,$stream);
    return (string)$stream;
  }

  static function write($file, $string)
  {
    $fp = fopen($file,"w");
    for ($written = 0; $written < strlen($string); $written += $fwrite) {
        $fwrite = fwrite($fp, substr($string, $written));
        if (!$fwrite) {
            return $fwrite;
        }
    }
    return $written;
  }

}

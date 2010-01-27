<?php

class MojoFile extends Mojo
{
  function __construct($args){
    $this->args = $args;
  }
  
  static function editStream($args,$stream)
  {
    if(empty($args)) return Mojo::prompt("Cannot edit stream, no args found");
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

?>

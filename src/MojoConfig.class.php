<?php

class MojoConfig extends Mojo
{
  function __construct($args){
    $this->args = $args;
    return $this;
  }

  static function get($key)
  {
    if(class_exists('sfConfig')) return sfConfig::get($key);
    if(isset($_SESSION[$key])) return $_SESSION[$key];
    else return false;
  }

  static function set($key,$val)
  {
    if(class_exists('sfConfig')) return sfConfig::set($key,$val);
    else $_SESSION[$key] = $val;
    return;
  }
 
  static function bootstrap(){
    $path = str_replace(basename(__FILE__),"",getFile(basename(__FILE__)));
	
    if(!file_exists($path.'mojo.config')){
      $_SESSION['mojo_task_lib'] = $path;
      MojoFile::write($path.'mojo.config',json_encode($_SESSION));

      echo "\n";
      Mojo::prompt('mojo.config created @ . '.$path.'mojo.config');
      Mojo::prompt('Please run "mojo Config Setup --help" to configure this tool');
      echo "\n";
      exit;
  	}

		if(self::get('mojo_config_loaded') == false){
    	$config = json_decode(file_get_contents($path.'mojo.config'));
	    foreach($config as $key => $value) $_SESSION[$key] = $value;
			$_SESSION['mojo_config_loaded'] = true;
  	}
  }

  static function Help(){
    Mojo::help('Usage: mojo Config Setup --mojo_js_dir="../relative/path/to"');
    exit;
  }

  public function Setup(){
    foreach($this->args as $key => $value){
      $_SESSION[$key] = $value;
      Mojo::prompt('Updating config for '.$key.' to '.$value);
    }
    MojoFile::write(self::get('mojo_task_lib').'mojo.config',json_encode($_SESSION));
		echo "\n";
  }

  public function Clean(){
    unlink(self::get('mojo_task_lib').'mojo.config');
  }
}

?>

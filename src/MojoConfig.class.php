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
 
  static function bootstrap($args)
	{
    $path = str_replace(basename(__FILE__),"",getFile(basename(__FILE__)));
	
    if(!file_exists($path.'mojo.config')){
      $_SESSION['mojo_task_lib'] = $path;
      MojoFile::write($path.'mojo.config',json_encode($_SESSION));

      echo "\n";
			Mojo::prompt('Mojo Tasks initialized');
      Mojo::prompt('mojo.config created @ . '.$path.'mojo.config');
      Mojo::prompt('Please run "mojo Config Update --help" to configure this tool');
      echo "\n";
      exit;
  	}

		if(self::get('mojo_config_loaded') == false){
    	$config = json_decode(file_get_contents($path.'mojo.config'));
	    foreach($config as $key => $value) $_SESSION[$key] = $value;
			$_SESSION['mojo_config_loaded'] = true;
  	}

		if(isset($args['action']) && $args['action'] != 'Update')
			self::validate();

	}
	
	static function validate()
	{
		if(self::get('mojo_js_dir') == false) Mojo::exception('Cannot find config for mojo_js_dir - please configure via  mojo Config Update');
  }

  static function Help()
	{
    Mojo::help('Usage: mojo Config Update --mojo_js_dir="relative/path/to/tasks || /absolute/path/to" - This should be the SiteMap.js directory"');
    exit;
  }

  public function Update()
	{
    foreach($this->args as $key => $value){
			switch($key){
				case 'mojo_js_dir':
				$sitemap = getFile('SiteMap.js',realpath($value));
				if($sitemap) $value = str_replace(basename($sitemap),"",$sitemap);
				else Mojo::exception('SiteMap.js not found at '.realpath($sitemap));
				break;
			}
      $_SESSION[$key] = $value;

      Mojo::prompt('Updating config for '.$key.' to '.$value);

    }

    MojoFile::write(self::get('mojo_task_lib').'mojo.config',json_encode($_SESSION));

		if(array_key_exists('mojo_js_dir',$this->args)) 
			Mojo::exception('You can now use scaffolding, ex: mojo Behavior Scaffold --name="myapp.behavior.SampleBehavior" --description="Description" --author="You"',' - SUCCESS - ');
  }

  public function Clear()
	{
    if(unlink(self::get('mojo_task_lib').'mojo.config'))
			Mojo::exception('mojo.config removed','- SUCCESS -');
  }
}

?>

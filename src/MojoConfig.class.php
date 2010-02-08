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
      Mojo::prompt('Running Mojo setup...');
      Mojo::prompt('==============================');
      self::Setup();
      Mojo::prompt('==============================');
      echo "\n";

      exit;
    }

    if(self::get('mojo_config_loaded') == false){
      $config = json_decode(file_get_contents($path.'mojo.config'));
      foreach($config as $key => $value) $_SESSION[$key] = $value;
      $_SESSION['mojo_config_loaded'] = true;
    }

    if(isset($args['action']) && $args['action'] != 'Setup' && $args['action'] != 'Clear')
      self::validate();

  }

  static function validate()
  {

    if(self::get('mojo_js_dir') ===  false) 
      Mojo::exception('Cannot find path to Mojo - please configure via  mojo Config Setup');
  }

  static function Help()
  {
    Mojo::help('Usage: mojo Config Setup"');
    exit;
  }

  public function Setup()
  {

    $config = array();
    $config['mojo_js_dir'] = promptUser('Please provide the full system path to your Mojo application - This is directory that contains SiteMap.js - (Include trailing slash)');

    $arr = explode('/',$config['mojo_js_dir']);
    $config['mojo_app_name'] = $arr[count($arr)-2];

    foreach($config as $key => $value){
      switch($key){
        case 'mojo_js_dir':
          $sitemap = getFile('SiteMap.js',$value);

          if($sitemap){ 
            $value = str_replace(basename($sitemap),"",$sitemap);
          }else{
            self::Clear(false);
            Mojo::exception('SiteMap.js not found at '.$value);
          }

          break;
      }
      $_SESSION[$key] = $value;

      echo "\n";		
      Mojo::prompt('Updated config for '.$key.' to '.$value);
    }

    MojoFile::write(self::get('mojo_task_lib').'mojo.config',json_encode($_SESSION));

    if(array_key_exists('mojo_js_dir',$config)) 
      Mojo::exception('You can now use scaffolding, ex: mojo Behavior Scaffold --name="myapp.behavior.SampleBehavior" --description="Description" --author="You"',' - SUCCESS - ');

  }

  public function Clear($prompt=true)
  {
    if(unlink(self::get('mojo_task_lib').'mojo.config') && $prompt)
      Mojo::exception('mojo.config removed',' - SUCCESS - ');
  }
  
  public function Show($value=false)
  {
    Mojo::prompt("Here is your current working config: \n");
    print_r($_SESSION); echo "\n";
    Mojo::prompt("To update a value use $ mojo Config Setup\n");
  }  
}

?>

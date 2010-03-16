<?php

/**
 * Config Class
 *
 * @package    mojo
 * @author     Kyle Campbell
 */

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

  static function set($key=false,$val=false)
  {

    if(class_exists('sfConfig')) return sfConfig::set($key,$val);
    else $_SESSION[$key] = $val;
    return self::save();
  }

  static function save(){
    return self::Setup(false);
  }


  static function bootstrap($args)
  {
  
    $path = str_replace(basename(__FILE__),"",realpath(__FILE__));
   
    if(!file_exists($path.'mojo.config')){
      $_SESSION['mojo_task_lib'] = $path;
      MojoFile::write($path.'mojo.config',json_encode($_SESSION));

      echo "\n";
      Mojo::prompt('Mojo Tasks initialized');
      Mojo::prompt('mojo.config created @ . '.$path.'mojo.config');
      Mojo::prompt('Running Mojo setup...');
      self::Setup();
      echo "\n";

      exit;
    }
    
   
    if(self::get('mojo_config_loaded') == false){
      $config = json_decode(file_get_contents($path.'mojo.config'));
      foreach($config as $key => $value) $_SESSION[$key] = $value;
      $_SESSION['mojo_config_loaded'] = true;
      if($_SESSION['mojo_js_dir'] == ""){
      self::Setup(true);
    }

    }

    if(isset($args['action']) && $args['action'] != 'Setup' && $args['action'] != 'Clear')
      self::validate();

  }

  static function validate()
  {

    $js_dir = self::get('mojo_js_dir');
    if($js_dir ===  false) 
      Mojo::exception('Cannot find path to Mojo - please configure via  mojo Config Setup');
  }

  public function Setup($prompt=true)
  {

    $config = array();
    foreach($_SESSION as $k => $v) $config[$k] = $v;

    if($prompt){

      $config['mojo_js_dir'] = promptUser('Please provide the full system path to your Mojo installation '
          .'- This is directory that contains SiteMap.js - (Include trailing slash)');

      $arr = explode(DIRECTOY_SEPARATOR,$config['mojo_js_dir']);
      $config['mojo_app_name'] = $arr[count($arr)-2];
    }

    $arr = explode(DIRECTORY_SEPARATOR,self::get('mojo_task_lib'));
    $config['mojo_bin_dir'] = join(DIRECTORY_SEPARATOR,array_slice($arr,0,count($arr)-2))
                            .DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR ;

    foreach($config as $key => $value){
      switch($key){
        case 'mojo_js_dir':
          $sitemap = getFile('SiteMap.js',$value);

          if($sitemap){ $value = str_replace(basename($sitemap),"",$sitemap);
          }else{
            self::Clear(false);
            Mojo::exception('SiteMap.js not found at '.$value);
          }

          break;
      }
      $_SESSION[$key] = $value;
      if($prompt) Mojo::prompt('Updated config for '.$key.' to '.$value);
    }

    MojoFile::write(self::get('mojo_task_lib').'mojo.config',json_encode($_SESSION));

    if(array_key_exists('mojo_js_dir',$config) && $prompt){
      Mojo::prompt('Congratulations, your project is now setup, please read the docs below:');
      MojoHelp::Docs();
    }
  }

  public function Clear($prompt=true)
  {
    if(unlink(self::get('mojo_task_lib').'mojo.config') && $prompt)
      Mojo::exception('mojo.config removed',' - SUCCESS - ');
  }

  public function Update(){
    
    $app = (!empty($this->args['app']))?$this->args['app']:'main';
    unset($this->args['app']);

    Mojo::line();
    foreach($this->args as $k => $v){
    
      if($k == 'mojo_base_dependencies'){        
        $_SESSION['mojo_'.(!empty($app)?strtolower(trim($app)).'_':'').'base_dependencies'] = $v;
      }else{
        $_SESSION[$k] = trim($v);
      }
        
      Mojo::prompt($k.' updated to '.$v);
    }
    Mojo::line();
    self::save();
  }  

  public function Show($value=false)
  {
    Mojo::line();
    Mojo::prompt("Here is your current working configuration: \n");
    print_r($_SESSION); echo "\n";
    Mojo::prompt("To update a value use $ mojo Config Update --key=value\n");
  }  
}

?>
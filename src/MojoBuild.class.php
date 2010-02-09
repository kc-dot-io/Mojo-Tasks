<?php

/**
 * Build Tasks.
 *
 * @package    kiwi-web
 * @subpackage mojo - MojoBuild
 * @author     Kyle Campbell
 */

class MojoBuild extends Mojo
{
  function __construct($args)
  {
    $this->args = $args;
    return $this;
  }
  
  function Compress(){
    global $bc; 
  
    define('JS_DEBUG',false);
    
    include "phpjs/js.php";       
    
    $controllers = $rules = $dependencies = array();    
    $app = (isset($this->args['app']))?$this->args['app']:'';
      
    $js_dir = explode('/',MojoConfig::get('mojo_js_dir'));
    $js_dir = array_slice($js_dir,0,7);
    $js_dir = join('/',$js_dir).'/';

    $dependencies[] = $js_dir.'extlib/dojo/dojo/dojo.js.uncompressed.js';
    $dependencies[] = $js_dir.'extlib/mojo.js.uncompressed.js';
    $dependencies[] = $js_dir.'extlib/mootools-core.js';
    $dependencies[] = $js_dir.'extlib/mootools-more.js';
    $dependencies[] = $js_dir.'extlib/s_code-20.3.js';

    $sitemap = MojoConfig::get('mojo_js_dir') . $app . 'SiteMap.js';
    $src = file_get_contents($sitemap);
   
    
    jsc::compile($src);       
    
    foreach($bc['.'] as $k => $v){
     
      
      if(isset($v[7])){ //sitemap entries
      
       if(isset($v[7][2][1])){ //controllers
        $controller = str_replace("'","",$v[7][2][1]);
        $controller = $js_dir.join('/',explode('.',$controller)).'.js';
        $controllers[] = $controller;      
       
        if(!array_search($controller,$dependencies))
          $dependencies[] = $controller;
        }
        
        if(isset($v[7][6][0][1]) && isset($v[7][6][2][1])){ //rules
          if($v[7][6][0][1] == 'formrules'){
            $rule = str_replace("'","",$v[7][6][2][1]);
            $rule = $js_dir.join('/',explode('.',$rule)).'.js';
            $rules[] = $rule;
            if(!array_search($rule,$dependencies))
              $dependencies[] = $rule;              
          }
        }
       }
    }
    
    foreach($controllers as $controller){ //commands
            
      $c = file_get_contents($controller);
      preg_match_all("/addCommand[^,]*[^\"']*('|\")([^'\"]*)('|\")/",$c,$matches);
      
      foreach($matches[2] as $commands => $command){        
        $command = $js_dir.join('/',explode('.',$command)).'.js';
        
        if(!empty($command) && !array_search($command,$dependencies))
          $dependencies[] = $command;          
      }
    }   
  
    $i18n = MojoFile::getAll(MojoConfig::get('mojo_js_dir').'_i18n/');
    
    foreach($rules as $rule){
      $c = file_get_contents($rule);      
      preg_match_all("/\\.locale[^\"]*\"\.([^\"]*)\"/",$c,$matches);      

      foreach($i18n as $locale => $files){
        foreach($files as $file){
        
          $f = $js_dir.MojoConfig::get('mojo_app_name').'/rules/'.$locale.'/'.$file;
          if((strpos($file,$matches[1][0]) > -1) && !array_search($f,$dependencies))
            $dependencies[] = $f;            
        }
      }
    }    
     
    
    $config = (!empty($app))
      ?MojoConfig::get('mojo_app_name').'.'.strtolower($app).'.config.js'
      :MojoConfig::get('mojo_app_name').'.config.js';
    
    $dependencies[] = $js_dir.MojoConfig::get('mojo_app_name').'/services/Locator.js';
    $dependencies[] = $js_dir.$config;    
    
    print_r($dependencies); exit;
    
    $js = "";
    foreach($dependencies as $dependency){
     $js .= file_get_contents( $dependency );
    }
    
    if(MojoFile::write($js_dir.'application.uncompressed.js',$js))
      Mojo::prompt('application.uncompressed.js written to '.$js_dir.'application.uncompressed.js');
    
    Mojo::prompt('YUI Compressing application.uncompressed.js to '.$js_dir.'compressed/application.js');
    passthru('java -jar '.$js_dir.'lib/yuicompressor-2.4.2/build/yuicompressor-2.4.2.jar '.$js_dir.'application.uncompressed.js -o '.$js_dir.'compressed/application.js --charset UTF-8')."\n";
    
    if(unlink($js_dir.'application.uncompressed.js'))
      Mojo::prompt('Cleaning up...Done.');
    
  }
  
  


}

?>


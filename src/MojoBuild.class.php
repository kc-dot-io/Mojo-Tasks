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

  function Read(){
    global $bc; 
  
    define('JS_DEBUG',false);
    
    include "phpjs/js.php";   

    
    $controllers = $rules = $dependencies = array();    
    $app = (isset($this->args['app']))?$this->args['app']:'';
    
    $mojo = explode('/',MojoConfig::get('mojo_js_dir'));
    $mojo = array_slice($mojo,0,7);
    $mojo = join('/',$mojo).'/';

    $sitemap = MojoConfig::get('mojo_js_dir') . $app . 'SiteMap.js';
    $src = file_get_contents($sitemap);
   
    
    jsc::compile($src);       
    
    #print_r($bc); exit;
    
    foreach($bc['.'] as $k => $v){
     
      
      if(isset($v[7])){ //sitemap entries
      
       if(isset($v[7][2][1])){ //controllers
        $controller = str_replace("'","",$v[7][2][1]);
        $controller = join('/',explode('.',$controller)).'.js';
        $controllers[] = $controller;      
       
        if(!array_search($controller,$dependencies))
          $dependencies[] = $controller;
        }
        
        if(isset($v[7][6][0][1]) && isset($v[7][6][2][1])){ //rules
          if($v[7][6][0][1] == 'formrules'){
            $rule = str_replace("'","",$v[7][6][2][1]);
            $rule = join('/',explode('.',$rule)).'.js';
            $rules[] = $rule;
            if(!array_search($rule,$dependencies))
              $dependencies[] = $rule;              
          }
        }
       }
    }
    
    foreach($controllers as $controller){ //comands
            
      $c = file_get_contents($mojo . $controller);
      preg_match_all("/addCommand[^,]*[^\"']*('|\")([^'\"]*)('|\")/",$c,$matches);
      
      foreach($matches[2] as $commands => $command){        
        $command = join('/',explode('.',$command)).'.js';
        
        if(!empty($command) && !array_search($command,$dependencies))
          $dependencies[] = $command;          
      }
    }
   
  
    $i18n = MojoFile::getAll(MojoConfig::get('mojo_js_dir').'_i18n/');
    
    foreach($rules as $rule){
      $c = file_get_contents($mojo . $rule);      
      preg_match_all("/\\.locale[^\"]*\"\.([^\"]*)\"/",$c,$matches);      

      foreach($i18n as $locale => $files){
        foreach($files as $file){
        
          $f = MojoConfig::get('mojo_app_name').'/rules/'.$locale.'/'.$file;
          if((strpos($file,$matches[1][0]) > -1) && !array_search($f,$dependencies))
            $dependencies[] = $f;
            
        }
      }
    }    
     
    
    $config = (!empty($app))
      ?MojoConfig::get('mojo_app_name').'.'.strtolower($app).'.config.js'
      :MojoConfig::get('mojo_app_name').'.config.js';
        
    
    $dependencies[] = MojoConfig::get('mojo_app_name').'/services/Locator.js';
    $dependencies[] = MojoConfig::get('mojo_app_name').'/../'.$config;

    
    #print_r($dependencies); exit;
    
    $js = "";
    foreach($dependencies as $dependency){
     $js .= file_get_contents( $mojo . $dependency );
    }
    
    echo $js;
    
  }
  
  


}

?>


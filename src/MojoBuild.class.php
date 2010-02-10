<?php

/**
 * Build Tasks.
 *
 * @package    mojo
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
    $js_dir = array_slice($js_dir,0,count($js_dir)-2);
    $js_dir = join('/',$js_dir).'/';


    $dependencies[] = $js_dir.'lib/mojo/mojo.js.uncompressed.js';
 
    $sitemap = MojoConfig::get('mojo_js_dir') . $app . 'SiteMap.js';
    $src = file_get_contents($sitemap);
       
    jsc::compile($src);       

	//==============================================

	//==============================================

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

	//==============================================
    
	//==============================================

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

	//==============================================
    
	//==============================================

    foreach($controllers as $controller){ //commands
      $c = file_get_contents($controller);
      preg_match_all("/addCommand[^,]*[^\"']*('|\")([^'\"]*)('|\")/",$c,$matches);
      
      foreach($matches[2] as $commands => $command){        
        $command = $js_dir.join('/',explode('.',$command)).'.js';
        
        if(!empty($command) && !array_search($command,$dependencies))
          $dependencies[] = $command;          
      }
    }   

	//==============================================

	//===============================================

    $js = "";
    foreach($dependencies as $dependency){
     $js .= file_get_contents( $dependency );
    }
    
    $js = preg_replace( '/dojo\.require\("[^\)]+"\);/i','',$js);    
    $js = preg_replace( "/dojo\.require\('[^\)]+'\);/i",'',$js);

    $config = (!empty($app))
      ?$js_dir.MojoConfig::get('mojo_app_name').'.'.strtolower($app).'.config'
      :$js_dir.MojoConfig::get('mojo_app_name').'.config';
			$js .= file_get_contents($config);

    Mojo::prompt("Built dependencies: "); //add mojo.config after as require is needed here.
    $dependencies[] = $config;
    print_r($dependencies);

	//===============================================

	//===============================================

		$build = MojoConfig::get('mojo_build_number');
		if(empty($build)) $build = '0';
		if(!isset($this->args['overwrite'])) $build++;

		if(!is_dir($js_dir.'dist/')) mkdir($js_dir.'dist/');
		if(!is_dir($js_dir.'dist/'.$build.'/')) mkdir($js_dir.'dist/'.$build.'/');

		MojoConfig::set('mojo_build_number',$build);

    if(MojoFile::write($js_dir.'dist/'.$build.'/application.uncompressed.js',$js))
      Mojo::prompt('application.uncompressed.js written to '.$js_dir.'dist/'.$build.'/application.uncompressed.js');
   
    Mojo::prompt('YUI Compressing application.uncompressed.js to '
				.$js_dir.'dist/'.$build.'/application.js');

    passthru('java -jar '.MojoConfig::get('mojo_bin_dir').'yui.jar '
				.$js_dir.'dist/'.$build.'/application.uncompressed.js -o '
				.$js_dir.'dist/'.$build.'/application.js --charset UTF-8')."\n";

	//===============================================
    
  }
}

?>


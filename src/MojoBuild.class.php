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
      
    $js_dir = explode(DIRECTORY_SEPARATOR,MojoConfig::get('mojo_js_dir'));
    $js_dir = array_slice($js_dir,0,count($js_dir)-2);
    $js_dir = join(DIRECTORY_SEPARATOR,$js_dir).DIRECTORY_SEPARATOR;


    $dependencies = array();
    
  //==============================================

	//==============================================
    $base = MojoConfig::get('mojo_'.(!empty($app)?strtolower(trim($app)).'_':'').'base_dependencies');
        
    if(isset($base) ){
      $base = str_replace("/",DIRECTORY_SEPARATOR,$base);
      $base = explode(',',$base);      
      foreach($base as $dependent){
        $dependencies[] = $js_dir.trim($dependent);
      }
    }    
    
  //==============================================

	//==============================================
   
    $sitemap = MojoConfig::get('mojo_js_dir') . 'SiteMap'.$app.'.js';
    $src = file_get_contents($sitemap);
       
    jsc::compile($src);       
    
	//==============================================

	//==============================================

    foreach($bc['.'] as $k => $v){
      #print_r($v);
      
      if(isset($v[9])){
      
        if(isset($v[9][2][1])){ //controllers
          $controller = str_replace("'","",$v[9][2][1]);
          $controller = $js_dir.join(DIRECTORY_SEPARATOR,explode('.',$controller)).'.js';
          $controllers[] = $controller;      
       
          if(!array_search($controller,$dependencies))
            $dependencies[] = $controller;
        }
      }
      
      if(isset($v[7])){ //sitemap entries
        
        if(isset($v[7][2][1])){ //controllers
          $controller = str_replace("'","",$v[7][2][1]);
          $controller = $js_dir.join(DIRECTORY_SEPARATOR,explode('.',$controller)).'.js';
          $controllers[] = $controller;      
         
          if(!array_search($controller,$dependencies))
            $dependencies[] = $controller;
        }
          
        if(isset($v[7][6][0][1]) && isset($v[7][6][2][1])){ //rules
          if($v[7][6][0][1] == 'formrules'){
            $rule = str_replace("'","",$v[7][6][2][1]);
            $rule = $js_dir.join(DIRECTORY_SEPARATOR,explode('.',$rule)).'.js';
            $rules[] = $rule;                  
          }

          if($v[7][6][0][1] == 'metricsmap'){
            $controller = str_replace("'","",$v[7][6][2][1]);
            $controller = $js_dir.join(DIRECTORY_SEPARATOR,explode('.',$controller)).'.js';
            $controllers[] = $controller;      
           
            if(!array_search($controller,$dependencies))
              $dependencies[] = $controller;
          }
          
        }
      }
    }

	//==============================================
    
	//==============================================
    
    $locale = (!empty($this->args['locale']))?$this->args['locale']:'en_US';
    
    $i18n = MojoFile::getAll(MojoConfig::get('mojo_js_dir').'_i18n'.DIRECTORY_SEPARATOR.$locale.DIRECTORY_SEPARATOR);
          
    foreach($rules as $rule){
      $c = file_get_contents($rule);      
      preg_match_all("/\\.locale[^\"]*\"\.([^\"]*)\"/",$c,$matches);      

      foreach($i18n as $files => $file){
        
          $f = $js_dir.MojoConfig::get('mojo_app_name').DIRECTORY_SEPARATOR.'_i18n'.DIRECTORY_SEPARATOR.$locale.DIRECTORY_SEPARATOR.$file;
          if((strpos($file,$matches[1][0]) > -1) && !array_search($f,$dependencies))
            $dependencies[] = $f;            
      }
      
      if(!array_search($rule,$dependencies))
        $dependencies[] = $rule;     
    }    

	//==============================================
    
	//==============================================

    foreach($controllers as $controller){ //commands
      $c = file_get_contents($controller);
      preg_match_all("/addCommand[^,]*[^\"']*('|\")([^'\"]*)('|\")/",$c,$matches);
      
      foreach($matches[2] as $commands => $command){        
        $command = $js_dir.join(DIRECTORY_SEPARATOR,explode('.',$command)).'.js';
        
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
      ?$js_dir.MojoConfig::get('mojo_app_name').'.'.strtolower($app).'.config.js'
      :$js_dir.MojoConfig::get('mojo_app_name').'.config.js';
      $js .= file_get_contents($sitemap);
			$js .= file_get_contents($config);
    $dependencies[] = $sitemap;  
    $dependencies[] = $config;
    
    
    if(isset($this->args['debug'])) print_r($dependencies);
    

	//===============================================

	//===============================================
  
    $app = (!empty($app))?$app.'.':'main.';
    $locale = strtolower($locale).'.';
    $build = MojoConfig::get('mojo_build_number');    
    $build = (!empty($build))?$build:'0';
    
    if(!isset($this->args['overwrite'])) $build++;

    if(!is_dir($js_dir.'dist'.DIRECTORY_SEPARATOR)) mkdir($js_dir.'dist'.DIRECTORY_SEPARATOR);
    if(!is_dir($js_dir.'dist'.DIRECTORY_SEPARATOR.$build.DIRECTORY_SEPARATOR)) mkdir($js_dir.'dist'.DIRECTORY_SEPARATOR.$build.DIRECTORY_SEPARATOR);

    MojoConfig::set('mojo_build_number',$build);

    if(MojoFile::write($js_dir.'dist'.DIRECTORY_SEPARATOR.$build.DIRECTORY_SEPARATOR.'application.'.$app.$locale.'uncompressed.js',$js))
      Mojo::prompt('application.'.$app.$locale.'uncompressed.js written to '.$js_dir.'dist'.DIRECTORY_SEPARATOR.$build.DIRECTORY_SEPARATOR.'application.'.$app.$locale.'uncompressed.js');

    Mojo::prompt('YUI Compressing application.'.$app.$locale.'uncompressed.js to '
        .$js_dir.'dist'.DIRECTORY_SEPARATOR.$build.DIRECTORY_SEPARATOR.'application.'.$app.$locale.'js');

    passthru('java -jar '.MojoConfig::get('mojo_bin_dir').'yui.jar '
        .$js_dir.'dist'.DIRECTORY_SEPARATOR.$build.DIRECTORY_SEPARATOR.'application.'.$app.$locale.'uncompressed.js -o '
        .$js_dir.'dist'.DIRECTORY_SEPARATOR.$build.DIRECTORY_SEPARATOR.'application.'.$app.$locale.'js --charset UTF-8')."\n";

    if($this->args['deploy']){
      Mojo::prompt('Copying to deploy directory: '.$js_dir.DIRECTORY_SEPARATOR.$this->args['deploy']);
      passthru('cp '.$js_dir.'dist'.DIRECTORY_SEPARATOR.$build.DIRECTORY_SEPARATOR.'application.'.$app.$locale.'js '.$js_dir.DIRECTORY_SEPARATOR.$this->args['deploy']);
    }
	//===============================================
    
  }
}

?>

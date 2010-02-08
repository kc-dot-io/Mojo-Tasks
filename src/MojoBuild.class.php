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

  function readMap(){

    include "phpjs/js.php";
    ini_set('error_reporting',0);

    $path = MojoConfig::get('mojo_js_dir'); //FIXME
    $controllers = array();
    $dependencies = array();  
    
    echo file_get_contents($path .'SiteMap.js'); exit;
   
    jsc::compile(file_get_contents($path .'SiteMap.js')); //FIXME

    foreach($bc['.'] as $k => $v){

      if(isset($v[7])){
       $controller = str_replace("'","",$v[7][2][1]);
       $controller = join('/',explode('.',$controller)).'.js'; 
       $controllers[] = $controller;      
       if(!array_search($controller,$dependencies))
          $dependencies[] = $controller;
      }
    }

    foreach($controllers as $controller){
      $c = file_get_contents($path.$controller);
      preg_match_all("/addCommand\([^\")](.*)[^\"]\)/",$c,$matches);
      foreach($matches[1] as $commands => $command){
        $command = str_replace("'","",$command);
        $command = explode(", ",$command);
        if(!empty($command[1]) && !array_search($command[1],$dependencies)) 
          $dependencies[] = join('/',explode('.',$command[1])).'.js';
      }
    }

    print_r($dependencies);

  }

}

?>

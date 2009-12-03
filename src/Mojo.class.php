#!/usr/bin/php -q
<?php

require_once(dirname(__FILE__).'/MojoUtils.class.php');

class Mojo
{

  function __construct($arguments = array(), $options = array()) 
  {
    array_shift($arguments);
    foreach($arguments as $k=>$v){
      if(strpos($v,"=") > -1 || strpos($v,"-") > -1){
          $v = str_replace("--","",$v);
          $split = explode("=",$v);
          $options[$split[0]] = $split[1];
      }else{
        switch($k):
          case 0: $k="module";break;
          case 1: $k="action";break;
        endswitch;
        $arguments[$k] = $v;
      }
    } 
    self::config();
    self::handler($arguments,$options);
  }

  function config()
  {
    //Change these paths to point to your project dirs
    MojoUtils::setConfig('sf_lib_dir',dirname(__FILE__).'/');
    MojoUtils::setConfig('sf_web_dir',dirname(__FILE__).'/../../../web');
    MojoUtils::setConfig('sf_mojo_dir',MojoUtils::getConfig('sf_web_dir').'/js/kiwi/');
    MojoUtils::setConfig('sf_mojo_lib_dir',MojoUtils::getConfig('sf_lib_dir'));
  }

  function handler($arguments = array(), $options = array())
  {
   
    if(count($arguments) < 2 || array_key_exists("help",$options))
       Mojo::exception("Acceptable use: $ mojo [Module] [Action] --name=(string) --author=(string) --description=(string)"," - HELP - ");
 
    $arguments["requestObj"] = array(
         "name" => $options['name'],
         "author" => $options['author'],
         "description" => $options['description']
    );

    if(!class_exists($arguments['module']) && file_exists(MojoUtils::getConfig('sf_mojo_lib_dir').'Mojo'.$arguments['module'].'.class.php')){

      $class = 'Mojo'.$arguments['module'];  
      $action = $arguments['action'];

      require(MojoUtils::getConfig('sf_lib_dir').$class.'.class.php');
      $$class = new $class($arguments['requestObj']);

      if(method_exists($$class,$action)) $$class->$action();
      else Mojo::exception("You did not provide a mojo action or your mojo action doesn not exist");

    }else{
      Mojo::exception("You did not provide a mojo module or your mojo module does not exist");  
    }
  }

  static function prompt($msg="")
  {
    echo "[mojo]: ".$msg."\n\n";
  }

  static function exception($msg="",$prefix=" - ERROR - ")
  {
    echo "\n[mojo]:".$prefix.$msg."\n\n";
    exit;
  }

}

  new Mojo($argv);

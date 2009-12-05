#!/usr/bin/php -q
<?php

require_once(dirname(__FILE__).'/MojoUtils.class.php');
ini_set('error_reporting','E_ALL');

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
    MojoUtils::setConfig('sf_lib_dir',dirname(__FILE__).'');
    MojoUtils::setConfig('sf_web_dir',dirname(__FILE__).'../../../web');
    MojoUtils::setConfig('sf_mojo_dir',MojoUtils::getConfig('sf_web_dir').'/js/kiwi/');
    MojoUtils::setConfig('sf_mojo_lib_dir',MojoUtils::getConfig('sf_lib_dir'));
  }

  function handler($arguments = array(), $options = array())
  {

    $class = 'Mojo'.$arguments['module'];  
    $action = $arguments['action'];

    if(file_exists(MojoUtils::getConfig('sf_mojo_lib_dir').'/'.$class.'.class.php')){

      require(MojoUtils::getConfig('sf_lib_dir').'/'.$class.'.class.php');
      $$class = new $class($options);

      //if the module has a help method - run that, other wise, provide general
      if(count($arguments) < 2 || array_key_exists("help",$options)){
        echo method_exists($$class,"Help");

        if(method_exists($$class,"Help")) $$class->Help();
        else Mojo::exception("Acceptable use: $ mojo [Module] [Action] --name=(string) --author=(string) --description=(string)"," - HELP - ");
      }

      if(method_exists($$class,$action)) $$class->$action();
      else Mojo::exception("You did not provide a mojo action or your mojo action doesn not exist");

    }else{
      Mojo::exception("You did not provide a mojo module or your mojo module does not exist");  
    }
  }

  static function prompt($msg="")
  {
    echo "[mojo]: ".$msg."\n";
  }

  static function exception($msg="",$prefix=" - ERROR - ")
  {
    echo "\n[mojo]:".$prefix.$msg."\n\n";
    exit;
  }

}

  new Mojo($argv);

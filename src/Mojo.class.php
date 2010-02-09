#!/usr/bin/php -q
<?php

require_once(dirname(__FILE__).'/MojoFunctions.php');
require_once(dirname(__FILE__).'/MojoFile.class.php');
require_once(dirname(__FILE__).'/MojoConfig.class.php');
ini_set('error_reporting','E_ALL');

class Mojo
{

  public $config = array();
  function __construct($arguments = array(), $options = array()) 
  {
    array_shift($arguments);
    foreach($arguments as $k=>$v){
      if(strpos($v,"=") > -1 || strpos($v,"-") > -1){
          $v = str_replace("--","",$v);
          $split = explode("=",$v);
          $options[$split[0]] = $split[1];
      }else{
				unset($arguments[$k]);
        switch($k):
          case 0: $k="module";break;
          case 1: $k="action";break;
        endswitch;
        $arguments[$k] = $v;
      }
    } 

  	MojoConfig::bootstrap($arguments);
    self::handler($arguments,$options);
  }

  function handler($arguments = array(), $options = array())
  {

    $class = (!empty($arguments['module']))?'Mojo'.$arguments['module']:false;
    $action = (!empty($arguments['action']))?$arguments['action']:false;

    if(file_exists(MojoConfig::get('mojo_task_lib').$class.'.class.php')){ 

      include_once(MojoConfig::get('mojo_task_lib').$class.'.class.php');
      $$class = new $class($options);


      //if the module has a help method - run that, other wise, provide general
      if(count($arguments) < 2 || array_key_exists("help",$options)){
        if(method_exists($$class,"Help")) $$class->Help();
        else self::exception("Acceptable use: $ mojo [Module] [Action] --name=(string) --author=(string) --description=(string)"," - HELP - ");
      }

      if(method_exists($$class,$action)) $$class->$action();
      else self::exception("You did not provide a mojo action or your mojo action doesn not exist");

    }else{

			if(MojoConfig::get('mojo_task_lib'))
	      self::exception("You did not provide a mojo module or your mojo module does not exist");  
			else MojoConfig::Setup();
    }
  }

  static function prompt($msg="")
  {
    echo "[mojo]: ".$msg."\n";
  }

  static function exception($msg="",$prefix=" - ERROR - ")
  {
    echo "\n[mojo]:".$prefix.' '.$msg."\n\n";
    exit;
  }

  static function help($msg="",$prefix=" - HELP - ")
  {
		self::exception($msg,$prefix);
    exit;
  }

}

  new Mojo($argv);

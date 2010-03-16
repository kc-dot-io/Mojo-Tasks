#!/usr/bin/php -q
<?php

require_once(dirname(__FILE__).'/MojoFunctions.php');
require_once(dirname(__FILE__).'/MojoFile.class.php');
require_once(dirname(__FILE__).'/MojoConfig.class.php');
require_once(dirname(__FILE__).'/MojoHelp.class.php');
ini_set('error_reporting','E_ALL');

class Mojo
{

  public $config = array();
  function __construct($arguments = array(), $options = array()) 
  {
    array_shift($arguments);
    foreach($arguments as $k=>$v){

      if(strpos($v,"=") > -1 || strpos($v,"--") > -1)
      {      
        $v = str_replace("--","",$v);
        $split = explode("=",$v);
        $options[$split[0]] = $split[1];
        
        for($i=($k+1);$i<=count($arguments);$i++)
        {
          if(strpos($arguments[$i],"--") > -1) break;          
          $options[$split[0]] .= " ".$arguments[$i];
        }
      }
      
      if($k < 2)
      {
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

      if(count($arguments) < 2 || array_key_exists("help",$options)){
        if(method_exists($$class,"Help")) $$class->Help();       
        else MojoHelp::Setup();
      }

      if(method_exists($$class,$action)) $$class->$action();
      else MojoConfig::Setup();

    }else{

      if(MojoConfig::get('mojo_task_lib')) MojoHelp::Docs();
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

  static function line($msg="\n")
  {
    echo $msg;
  }

}

new Mojo($argv);

?>
<?php

/**
 * Command Tasks.
 *
 * @package    kiwi-web
 * @subpackage mojo - MojoCommand
 * @author     Kyle Campbell
 */

class MojoCommand extends Mojo
{
  function __construct($requestObj)
  {
      $this->requestObj = $requestObj;
      return $this;
  }

  function Scaffold()
  {

      //Replace this with a validation method
      if(empty($this->requestObj['name'])) 
          return MojoUtils::prompt('Provide a full mojo path in your params string, ie: name=mojo.command.myCommand');

      if(strpos($this->requestObj['name'],'command.') < 1)
          return MojoUtils::prompt('The name you provided for your Command appears to be incorrect. '
                              .'Please use full Command path, ie: name=mojo.command.myCommand');
      $source = self::source();
     
      $name = explode('command.',$this->requestObj['name']); $name = $name[1];
      if(strpos($name,'.') > -1) { //handle commands in a sub dir
          $tmp = explode('.',$name); //check if this dir exists, and create it if it does not
          if(!file_exists(MojoUtils::getConfig('sf_mojo_dir').'command/'.$tmp[0])) mkdir(MojoUtils::getConfig('sf_mojo_dir').'command/'.$tmp[0]);
          $name = $tmp[0].'/'.$tmp[1]; //full path including new dir
      }
      MojoUtils::write(MojoUtils::getConfig('sf_mojo_dir').'command/'.$name.'.js',MojoUtils::editStream($this->requestObj,$source));
      MojoUtils::prompt('Generated Command Scaffolding to '.MojoUtils::getConfig('sf_mojo_dir').'command/'.$name.'.js'); 
  }

  function source()
  {
      ob_start();
return <<<EOF
/* 
        Class: %NAME%
        Author: %AUTHOR%
        Description: %DESCRIPTION%
*/

dojo.provide('%NAME%');
dojo.require('mojo.command.Command');
dojo.require('mojo.Model');

dojo.declare('%NAME%', mojo.command.Command,
{
  execute: function(requestObj) {
    
  },
  onResponse: function(data) {

  },
  onError: function(error) {

  }
});
EOF;
      ob_end_flush();
      return ob_get_contents();
  }
}

?>

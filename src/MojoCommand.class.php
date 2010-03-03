<?php

/**
 * Command Tasks.
 *
 * @package    mojo
 * @author     Kyle Campbell
 */

class MojoCommand extends Mojo
{
  function __construct($args)
  {
      $this->args = $args;
      return $this;
  }

  function Scaffold()
  {

      //Replace this with a validation method
      if(empty($this->args['name'])) 
          return Mojo::prompt('Provide a full mojo path in your params string, ie: name=mojo.command.myCommand');

      if(strpos($this->args['name'],'command.') < 1)
          return Mojo::prompt('The name you provided for your Command appears to be incorrect. '
                              .'Please use full Command path, ie: name=mojo.command.myCommand');
      $source = self::Source();
     
      $name = explode('command.',$this->args['name']); $name = $name[1];
      if(strpos($name,'.') > -1) { //handle commands in a sub dir
          $tmp = explode('.',$name); //check if this dir exists, and create it if it does not
          if(!file_exists(MojoConfig::get('sf_mojo_dir').'command/'.$tmp[0])) mkdir(MojoConfig::get('sf_mojo_dir').'command/'.$tmp[0]);
          $name = $tmp[0].'/'.$tmp[1]; //full path including new dir
      }
      MojoFile::write(MojoConfig::get('mojo_js_dir').'command/'.$name.'.js',MojoFile::editStream($this->args,$source));
      Mojo::prompt('Generated Command Scaffolding to '.MojoConfig::get('mojo_js_dir').'command/'.$name.'.js'); 
  }

  function Source()
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

		console.log('command executed',requestObj);
    
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

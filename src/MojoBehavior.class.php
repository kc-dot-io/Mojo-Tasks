<?php

/**
 * Behavior Tasks.
 *
 * @package    mojo
 * @author     Kyle Campbell
 */

class MojoBehavior extends Mojo
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
          return Mojo::prompt('Provide a full mojo path in your params string, ie: name=mojo.behavior.myBehavior');

      if(strpos($this->args['name'],'behavior.') < 1)
          return Mojo::prompt('The name you provided for your Behavior appears to be incorrect. '
                              .'Please use full Behavior path, ie: name=mojo.behavior.myBehavior');
      $source = self::Source();
     
      $name = explode('behavior.',$this->args['name']); $name = $name[1];
      if(strpos($name,'.') > -1) { //handle behaviors in a sub dir
          $tmp = explode('.',$name); //check if this dir exists, and create it if it does not
          if(!file_exists(MojoConfig::get('mojo_js_dir').'behavior/'.$tmp[0])) mkdir(MojoConfig::get('mojo_js_dir').'behavior/'.$tmp[0]);
          $name = $tmp[0].'/'.$tmp[1]; //full path including new dir
      }
      MojoFile::write(MojoConfig::get('mojo_js_dir').'behavior/'.$name.'.js',MojoFile::editStream($this->args,$source));
      Mojo::exception('Generated Behavior Scaffolding to '.MojoConfig::get('mojo_js_dir').'behavior/'.$name.'.js');
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
dojo.require('mojo.command.Behavior')

dojo.declare('%NAME%', mojo.command.Behavior,
{
  execute: function(args){


  }
});
EOF;
      ob_end_flush();
      return ob_get_contents();
  }
}

?>

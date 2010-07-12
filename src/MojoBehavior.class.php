<?php

/**
 * Behavior Tasks.
 *
 * @package    mojo
 * @author     Kyle Campbell
 */

class MojoBehavior extends MojoFile
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
      $file = self::makeNewFile($this->args['name'],'behavior');
      
      MojoFile::write($file,MojoFile::editStream($this->args,$source));
      Mojo::prompt('Generated Behavior Scaffolding to '.$file);
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

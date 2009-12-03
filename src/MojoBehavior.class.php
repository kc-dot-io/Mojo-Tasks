<?php

/**
 * Behavior Tasks.
 *
 * @package    kiwi-web
 * @subpackage mojo - MojoBehavior
 * @author     Kyle Campbell
 */

class MojoBehavior extends Mojo
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
          return MojoUtils::prompt('Provide a full mojo path in your params string, ie: name=mojo.behavior.myBehavior');

      if(strpos($this->requestObj['name'],'behavior.') < 1)
          return MojoUtils::prompt('The name you provided for your Behavior appears to be incorrect. '
                              .'Please use full Behavior path, ie: name=mojo.behavior.myBehavior');
      $source = self::source();
     
      $name = explode('behavior.',$this->requestObj['name']); $name = $name[1];
      if(strpos($name,'.') > -1) { //handle behaviors in a sub dir
          $tmp = explode('.',$name); //check if this dir exists, and create it if it does not
          if(!file_exists(MojoUtils::getConfig('sf_mojo_dir').'behavior/'.$tmp[0])) mkdir(MojoUtils::getConfig('sf_mojo_dir').'behavior/'.$tmp[0]);
          $name = $tmp[0].'/'.$tmp[1]; //full path including new dir
      }
      MojoUtils::write(MojoUtils::getConfig('sf_mojo_dir').'behavior/'.$name.'.js',MojoUtils::editStream($this->requestObj,$source));
      MojoUtils::prompt('Generated Behavior Scaffolding to '.MojoUtils::getConfig('sf_mojo_dir').'behavior/'.$name.'.js');
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
dojo.require('mojo.command.Behavior')

dojo.declare('%NAME%', mojo.command.Behavior,
{
  execute: function(requestObj){


  }
});
EOF;
      ob_end_flush();
      return ob_get_contents();
  }
}

?>

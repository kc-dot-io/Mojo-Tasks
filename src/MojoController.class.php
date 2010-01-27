<?php

/**
 * Controller Tasks.
 *
 * @package    kiwi-web
 * @subpackage mojo - MojoController
 * @author     Kyle Campbell
 */

class MojoController extends Mojo
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
          return Mojo::prompt('Provide a full mojo path in your params string, ie: name=mojo.controller.myController');

      if(strpos($this->args['name'],'controller.') < 1)
          return Mojo::prompt('The name you provided for your Controller appears to be incorrect. '
                              .'Please use full Controller path, ie: name=mojo.controller.myController');
      $source = self::Source();
     
      $name = explode('controller.',$this->args['name']); 
      MojoUtils::write(MojoConfig::get('sf_mojo_dir').'controller/'.$name[1].'.js',MojoFile::editStream($this->args,$source));
      Mojo::prompt('Generated Controller Scaffolding to '.MojoConfig::get('sf_mojo_dir').'controller/'.$name[1].'.js');
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
dojo.require('mojo.controller.Controller');

dojo.declare('%NAME%', mojo.controller.Controller,
{
  params: {

  },
  addObservers: function() {
    this.addObserver('sample', 'onSample', 'do_sample', function(context, caller) { 
      return {

      };
    });
  },
  addCommands: function() {
    this.addCommand('do_sample', 'kiwi.command.sampleController');
  },
  addIntercepts: function() {

  }
});
EOF;
      ob_end_flush();
      return ob_get_contents();
  }
}

?>

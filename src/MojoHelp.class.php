<?php

/**
 * Help Class
 *
 * @package    mojo
 * @author     Kyle Campbell
 */

class MojoHelp extends Mojo
{
  function __construct($args){
    $this->args = $args;
  }
  
  static function Docs(){

		Mojo::line();
    Mojo::prompt("Welcome to the Mojo Task interactive prompt help utility...");
    Mojo::prompt("Here is the default application syntax:");
		Mojo::prompt("$./mojo [Class] [Method] --param1=(arg1) --param2=(arg2) --param3=(arg3)");
		Mojo::line("\n------------------------------------------------\n\n");
		Mojo::prompt("CONFIGURATION");
		Mojo::line();
		Mojo::prompt("$./mojo Config Setup");
		Mojo::prompt("$./mojo Config Show");
		Mojo::prompt("$./mojo Config Clear");
		Mojo::prompt("$./mojo Config Update");
		Mojo::line("\n------------------------------------------------\n\n");
		Mojo::prompt("SCAFFOLDING");
		Mojo::line();
		Mojo::prompt("$./mojo Controller Scaffold --name='myapp.controller.SampleController' --author='Author' --description='Description'");
		Mojo::prompt("@params:");
		Mojo::prompt("	name: name of the module");
		Mojo::prompt("	author: author name if not in config");
		Mojo::prompt("	description: describe the module");
		Mojo::line("\n------------------------------------------------\n\n");
		Mojo::prompt("BUILDING / COMPRESSING");
		Mojo::line();
		Mojo::prompt("$./mojo Build Compress --overwrite=true|false");
		Mojo::prompt("@params:");
		Mojo::prompt("	overwrite - will stop the out put of the build from incrementing");
		Mojo::prompt("	ex: mojojsdir/dist/1/application.js will not increment to 2");
		Mojo::line();
		exit;
  }

  function debug(){
		print_r($this->args);
  }
}

?>

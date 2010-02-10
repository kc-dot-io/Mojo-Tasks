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
    Mojo::prompt("To get started following the directions:");
		Mojo::line();
		Mojo::prompt("To use: $ mojo [Module] [Action] --name=(string) --author=(string) --description=(string)"," - HELP - ");
		Mojo::line();
  }

  function debug(){
		print_r($this->args);
  }
}

?>

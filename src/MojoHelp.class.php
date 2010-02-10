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
  
  function docs(){
    MojoUtils::prompt("Welcome to the Mojo Task interactive prompt");
    MojoUtils::prompt("To get started following the directions:");
    MojoUtils::prompt("To be continued...");
  }

  function debug(){
	print_r($this->args);
  }
}

?>

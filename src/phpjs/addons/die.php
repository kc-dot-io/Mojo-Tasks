<?php

/*
   Adds the PHP 'die()' language construct, which tries to terminate
   the whole script BY UNSETTING $bc and issuing an infinite break.
   So this is VERY UNCLEAN and simply a hack, nothing more.
*/


$js_vars["die"] = $js_funcs[] = "jsrt_die";

function jsrt_die($r=NULL) {
   global $bc, $jsi_break;

   $bc = array();   // completely remove all bytecode
   $jsi_break = 65535;   // infinite break;

   if (isset($r) && is_string($r)) {
      echo $r;
   }
}


?>
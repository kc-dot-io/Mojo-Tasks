<?php

/*
   adds the JavaScript/PHP 'eval()' language construct / function
   (untested)
*/


$js_vars["eval"] = $js_funcs[] = "jsrt_eval";

function jsrt_eval($code) {
   global $bc, $jsi_break;

   #-- store current runtime context (all interpreter state vars
   #   and the 'bytecode', but not the global variables)
   $old_bc = $bc;
   $old_rt = array(
      $jsi_break
   );


   #-- run code
   js_exec($code, "_CLEAN=1");


   #-- restore previous runtime context
   $bc = $old_bc;
   list(
      $jsi_break
   )
   = $old_rt;

}


?>
<?php
/*
  $result = js_calc(" sin( 5+ x) * 2.7 ");

  · calculates a user supplied 'formular', which may contain variable
    names from earlier calculations [or even jsi_run()s];
  · $expression cannot be more than one formular and has not to be a
    language construct (do, while, if, ...), also has no trailing ";"
  · $expression may however be a variable assignment expr ("x=2+5")
*/


function js_calc($expression) {

   global $bc;

   ob_start();
   ob_implicit_flush(0);
   {
      js_compile($expression);
      $r = jsi_expr($bc["."]);
   }
   ob_end_clean();

   return($r);
}


?>
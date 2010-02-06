<?php
/*
   phpjs interpreter
   -----------------
   Runs over a loaded $bc bytecode tree and evaluates all expressions,
   control constructs and allowed commands.
*/



 ## ##   ## ###### ###### ######  ######  ######  ###### ###### ###### ######
 ## ###  ##   ##   ##     ##   ## ##   ## ##   ## ##       ##   ##     ##   ##
 ## #### ##   ##   ##     ##   ## ##   ## ##   ## ##       ##   ##     ##   ##
 ## ## ####   ##   ####   ######  ######  ######  ####     ##   ####   ######
 ## ##  ###   ##   ##     ## ##   ##      ## ##   ##       ##   ##     ## ##
 ## ##   ##   ##   ##     ##  ##  ##      ##  ##  ##       ##   ##     ##  ##
 ## ##   ##   ##   ###### ##   ## ##      ##   ## ######   ##   ###### ##   ##


#-- interpreter part
class jsi {


   #-- runs the main program (in $bc["."])
   function run()
   {
      global $bc, $jsi_vars, $jsi_break, $jsi_return;
      
      #-- reset runtime environment
      $jsi_lvars = array();
      $jsi_break = 0;
      $jsi_return = 0;

      #-- run main code block
      jsi::block($bc["."]);
   }


   #-- prepare variables
   function mk_runtime_env()
   {
      // not used anymore
   }


   #-- adds function in $jsi_vars[] and $jsi_funcs[] correctly
   function register_func($js_f, $php_f)
   {
      global $jsi_vars, $jsi_funcs;

      $jsi_vars[$js_f] = $php_f;  // functions are also variables/objects
      $jsi_funcs[] = $php_f;
   }


   function err($s)
   {
      echo "\nINTERPRETER ERROR: $s\n";
   }




   #----------------------------------------------------------------------


   #-- executes a block of commands
   #   (grouped into a subarray)
   function block(&$bc)
   {
      global $jsi_break, $jsi_return;

      $pc = 0;
      $pc_end = count($bc);
      for ($pc=0; $pc<$pc_end; $pc++) {

         if ($jsi_break) { 
            return;
         }

         if (is_array($bc[$pc]))  // else it is expression(/value) in void context
         switch ($bc[$pc][0]) {
            case JS_FCALL:
            case JS_ASSIGN:
            case JS_MATH:
            case JS_CMP:
            case JS_VALUE:
            case JS_VAR:
               jsi::expr($bc[$pc]);
               break;
            case JS_FOR:
               jsi::constr_for($bc[$pc]);
               break;
            case JS_COND:
               jsi::constr_cond($bc[$pc]);
               break;
            case JS_SWITCH:
               jsi::constr_switch($bc[$pc]);
               break;
            case JS_RT:
               jsi::constr_rt($bc[$pc]);
               break;
            case JS_BREAK:
               jsi::constr_break($bc[$pc]);
               break;
            default:
               if (is_array($bc[$pc])) {
                  jsi::block($bc[$pc]);
               }
               else {
                  jsi::err("unknown processing code @$pc");
               }
         }
      }
   }



   #----------------------------------------------------------------------
   #-- language constructs


   function constr_break(&$bc) {
      global $jsi_break;
      $jsi_break = jsi::expr($bc[1]);
   }

   function constr_for(&$bc) {
      global $jsi_break;
      while ( jsi::expr($bc[1]) ) {
         jsi::block($bc[3]);
         if ($jsi_break && $jsi_break--) { return; }
         jsi::expr($bc[2]);
      }
   }


   #-- conditional statements (if, while, ...)
   function constr_cond(&$bc) {
      global $jsi_break;

      #-- if/elseif/else
      if ($bc[1]==JS_IF) {
         for ($i=2; $i<count($bc); $i+=2) {
            if ( jsi::expr($bc[$i]) ) {
               jsi::block($bc[$i+1]);
               if ($jsi_break && $jsi_break--) { return; }
               break;   // execute always only one tree
            }
         }
      }
      #-- while
      elseif ($bc[1] == JS_WHILE) {
         while ( jsi::expr($bc[2]) ) {
            jsi::block($bc[3]);
            if ($jsi_break && $jsi_break--) { return; }
         }
      }
      #-- repeat until / do while
      elseif ($bc[1] == JS_DO) {
         do {
            jsi::block($bc[3]);
            if ($jsi_break && $jsi_break--) { return; }
         }
         while ( jsi::expr($bc[2]) );
      }
   }


   #-- switch/case constructs
   function constr_switch(&$bc) {
      global $jsi_break;

      #-- walk through case expressions
      $value = jsi::expr($bc[1]);
      $triggered = 1;
      for ($i=2; $i<count($bc); $i+=2) {
         if ($triggered
         or is_array($bc[$i]) && ($bc[$i][0]==JS_DEFAULT)
         or ($value == jsi::expr($bc[$i]))) {
            $triggered = 1;
         }
         if ($triggered) {  // execute all code blocks from here until break;
            jsi::block($bc[$i+1]);
            if ($jsi_break && $jsi_break--) { return; }
         }
      }
   }


   #-- runtime functions (pseudo-func calls)
   function constr_rt(&$bc) {
      global $jsi_vars;
      $args = array();
      for ($i=2; $i<count($bc); $i++) {
         $args[] = jsi::expr($bc[$i]);
      }
      switch ($bc[1]) {
         case JS_PRINT:
            echo implode("", $args);
            break;

         default:
            break;
      }
   }



   #----------------------------------------------------------------------
   #-- variable handling


   #-- create new variable in jsi context
   function &mk_var(&$bc, $local_context=0)
   {
      global $jsi_vars;

      #-- name.name.name
      $p = & $jsi_vars;
      foreach (explode(".", $bc[1]) as $i) {
         if (!isset($p[$i])) {
            $p[$i] = false;
         }
         $p = & $p[$i];
      }

      #-- additional array indicies
      if (isset($bc[2])) for ($in=2; $in<count($bc); $in++) {
         $i = jsi::expr($bc[$in]);
         if (!isset($p[$i])) {
            $p[$i] = false;
         }
         $p = & $p[$i];
      }
   /*
   echo "\n---VARcreateREFERENCE---\n";
   print_r($bc);
   echo "p=";
   var_dump($p);
   */

      return($p);
   }


   #-- get contents of a jsi context variable
   function get_var(&$bc)
   {
      global $jsi_vars;

      #-- name.name.name
      $p = & $jsi_vars;
      foreach (explode(".", $bc[1]) as $i) {
         $p = & $p[$i];
      }

      #-- additional array indicies
      if (isset($p) && isset($bc[2])) for ($i=2; $i<count($bc); $i++) {
         $p = & $p[ jsi::expr($bc[$i]) ];
      }
   /*
   echo "\n---VARget---\n";
   print_r($bc);
   echo "p=";
   var_dump($p);
   */
      return($p);
   }



   #----------------------------------------------------------------------
   #-- expressions


   #-- runs a function (internal / external)
   function fcall(&$bc)
   {
      global $jsi_vars, $jsi_funcs;
      $r = 0;

      #-- a function is also a variable/object
      $var_bc = array(JS_VAR, $bc[1]);
      if ($name = jsi::get_var($var_bc)) {

         #-- prepare func call arguments
         $args = array();
         for ($i=2; $i<count($bc); $i++) {
            if ($bc[$i][0] == JS_VAR) {
               $args[] = & jsi::mk_var($bc[$i]);    // pass-by-ref
            }
            else {
               $args[] = jsi::expr($bc[$i]);
            }
         }

         #-- system functions (PHP code)
         if (in_array($name, $jsi_funcs) && function_exists($name)) {
            $r = call_user_func_array($name, $args);
         }
         else {
            echo "FUNCNOTEXI($name) ";
         }

         #-- inline functions
         // (in separate $bc)
         // ...

      }
      else {
         echo "NOVARFOR($bc[1]) ";
      }

      return($r);
   }


   #-- variable = assignment code
   function assign(&$bc)
   {
      $var = & jsi::mk_var($bc[1]);
      $var = jsi::expr($bc[2]);
      return($var);
   }



   #-- evaluate the pre-arranged (parser did it all) expressions
   function math(&$bc)
   {
      $constant = 1;
      $val = NULL;
      for ($i=0; $i<count($bc); $i+=2) {

         $add = jsi::expr($bc[$i+1]);
         $constant = $constant && is_scalar($bc[$i+1]);

         switch ($bc[$i]) {

            #-- initial value
            case JS_MATH:
               $val = $add;
               break;

            #-- basic math
            case "+":
               if ((!JS_PHPMODE) && (is_string($var) || is_string($add))) {
                  $val .= $add;
               }
               else {
                  $val += $add;
               }
               break;
            case "-":
               $val -= $add;
               break;
            case "*":
               $val *= $add;
               break;
            case "/":
               $val /= $add;
               break;
            case "%":
               $val %= $add;
               break;

            #-- bit
            case "&":
               $val &= $add;
               break;
            case "|":
               $val |= $add;
               break;
            case "^":
               $val ^= $add;
               break;
            #-- unary operator "~" (only two args, the first always zero, unused)
            case "~":
               $val = ~$add;
               break;

            #-- bool
            case "&&":
               $val = ($val && $add) ?1:0;
               break;
            case "||":
               $val = ($val || $add) ?1:0;
               break;
            case "!":  // unary operation, first argument zero and unused
               $val = (!$add) ?1:0;
               break;

            #-- string
            case ".":
               $val .= $add;
               break;

            #-- error
            default:
               jsi::err("expression operator '$bc[$i]' fault");
         }
      }
      if ($constant) {   // replace tree with constant
         $bc = $val;
      }
      return($val);
   }


   #-- does the boolean math
   function cmp(&$bc)
   {
      $val = 0;
      $A = jsi::expr($bc[1]);
      $B = jsi::expr($bc[3]);
      switch ($bc[2]) {
         case "<":
            $val = ($A < $B) ?1:0;
            break;
         case "<=":
            $val = ($A <= $B) ?1:0;
            break;
         case ">":
            $val = ($A > $B) ?1:0;
            break;
         case ">=":
            $val = ($A >= $B) ?1:0;
            break;
         case "===":
            $val = ($A === $B) ?1:0;
            break;
         case "==":
            $val = ($A == $B) ?1:0;
            break;
         case "!==":
            $val = ($A !== $B) ?1:0;
            break;
         case "!=":
            $val = ($A != $B) ?1:0;
            break;
         default:
            jsi::err("unknown boolean operation '$bc[2]'");
      }
      if (is_scalar($bc[1]) && is_scalar($bc[3])) {
         $bc = $val;
      }
      return($val);
   }


   #-- huh, simple
   function expr(&$bc)
   {
      if (is_array($bc)) {
         switch ($bc[0]) {
            case JS_ASSIGN:
               return jsi::assign($bc);
               break;
            case JS_MATH:
               return jsi::math($bc);
               break;
            case JS_CMP:
               return jsi::cmp($bc);
               break;
            case JS_VALUE:
               $bc = $bc[1];
               return jsi::expr($bc);
               break;
            case JS_VAR:
               return jsi::get_var($bc);
               break;
            case JS_FCALL:
               return jsi::fcall($bc);
               break;
            default:
               jsi::err("expression fault <<".substr(serialize($bc),0,128).">>");
         }
      }
      else {
         return($bc);   // must be direct value
      }
   }


} // end of class jsi



?>
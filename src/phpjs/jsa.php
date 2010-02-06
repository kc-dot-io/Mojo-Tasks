<?php
/*
   phpjs accelerator
   ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯
   This module converts the generated phpjs bytecode into sandboxed
   (safe/escaped) PHP code for fast execution of user supplied
   scripts. It works almost like the ordinary interpreter but requires
   a bit glue code for secured (user-)function execution.

   You need both "js.php" and "jsa.php" loaded at compile time. Use
   it this way:
    - $php_code = jsa::assemble();
    - $php_code = jsa::assemble($js_source_code);  // this only works,
                          // if the compiler "jsc.php" is also present

   To actually execute the generated $php_code somewhen later, you also (or
   only) need to have the "jsrt.php" part loaded:
    - eval($php_code);
    
   orig. author: <mario*erphesfurt,de>
   contributions: Eric Anderton <ericanderton*yahoo,com>: var, functions
*/


define("JSA_FUNC_PREFIX", "js_ufnc_");
define("NEWLINE", "\n");

#-- everything encapsulated into a static class (a namespace more or less)
class jsa {


   #-- transformation starts here
   function assemble($js_src="", $ticks=0) {
      global $bc, $is_function, $force_create, $jsa_loop_inject;
      $is_function = 'false';
      $force_create = 'false';
      $jsa_loop_inject = $ticks ? ' && !isset($jsi_die)' : '';

      #-- compile into $bc
      if ($js_src) {
         jsc::compile($js_src);
      }

      #-- functions
      //$o = "\n/* compiled by phpjs accelerator */\n\n";
      $o = "";
      foreach ($bc as $funcname=>$code) {

         #-- main code block
         if ($funcname == ".") {
         $o.="#.MAIN\n";
            $o .= jsa::block($bc[$funcname],false);
         }
         else {
         $o.="#FUNCDEF\n";
            $o .= jsa::func_def($funcname, $bc[$funcname]);
         }
      }

      #-- return string with php-sandbox code
      return($o);
   }


   #-- error while transforming into sandboxed PHP code
   function err($MSG) {
      die("jsa::err('$MSG')\n");
   }


   #-- a block of code (expressions / language constructs)
   function block(&$bc, $use_braces=true) {
      static $bn=0;
      $bn++;
      $o = "";

      #-- multiple lines in every block
      for ($pc=0; $pc<=count($bc); $pc++) {
         if (is_array($bc[$pc]))  // else it is a plain value in void context
         switch ($bc[$pc][0]) {
           case JS_FCALL:
           case JS_ASSIGN:
           case JS_MATH:
           case JS_CMP:
           case JS_VALUE:
           case JS_VAR:
              $o .= jsa::expr($bc[$pc]) . ";\n";
              break;
           case JS_VAR_STATEMENT:
              $o .= jsa::constr_var($bc[$pc]);
              break;
           case JS_FOR:
              $o .= jsa::constr_for($bc[$pc]);
              break;
           case JS_COND:
              $o .= jsa::constr_cond($bc[$pc]);
              break;
           case JS_SWITCH:
              $o .= jsa::constr_switch($bc[$pc]);
              break;
           case JS_RT:
              $o .= jsa::constr_rt($bc[$pc]);
              break;
           case JS_BREAK:
              $o .= jsa::constr_break($bc[$pc]);
              break;
           case JS_RETURN:
              $o .= jsa::constr_return($bc[$pc]);
              break;
           case JS_FUNCDEF:
              $o .= jsa::constr_funcdef($bc[$pc]);
              break;
           default:
              if (is_array($bc[$pc])) {
                 $o .= jsa::block($bc[$pc]);
              }
              else {
                 jsa::err("unknown processing code @$pc");
              }
         }
      }

      if ($use_braces) {
         $o = "{\n" . $o . "}\n";
      }
      return($o);
   }


   #------------------------------------------------------------------
   #-- language constructs


   #-- break statement
   function constr_break(&$bc) {
      return("break $bc[1];\n");
   }

   #-- return statement
   function constr_return(&$bc) {
      return('return('.jsa::expr($bc[1]).');'.NEWLINE);
   }

   #-- var statement
   function constr_var(&$bc){
      $o = 'jsrt::rt_var(array(\''.$bc[1].'\'),false,true)';
      if (count($bc)==3) {
         $o = 'jsrt::assign('.$o.','.jsa::expr($bc[2]).')';
      }
      return $o.";\n";
   }

   #-- for-loop
   function constr_for(&$bc) {
      global $jsa_loop_inject;
      return
         "/* was a FOR-loop */\n"
         . "while ((" . jsa::expr($bc[1])  . ")$jsa_loop_inject) {\n"
         . jsa::block($bc[3])
         . jsa::expr($bc[2]) . ";\n}\n";
   }


   #-- conditional loops, code blocks
   function constr_cond(&$bc) {
      global $jsa_loop_inject;
      $o = "";

      #-- IF
      if ($bc[1]==JS_IF) {
         for ($i=2; $i<count($bc); $i+=2) {
            $o .= ($i >= 4 ? "elseif" : "if") . " ("
               . jsa::expr($bc[$i]) . ") "
               . jsa::block($bc[$i+1]);
         }
      }
      #-- WHILE
      elseif ($bc[1] == JS_WHILE) {
         $o .= "while ((" . jsa::expr($bc[2]) . ")$jsa_loop_inject)"
            . jsa::block($bc[3]);
      }
      #-- DO
      elseif ($bc[1] == JS_DO) {
         $o .= "do " . jsa::block($bc[3])
            . " while ((" . jsa_expr($bc[2]) . ")$jsa_loop_inject);\n";
      }

      return $o;
   }


   #-- switch/case constructs
   function constr_switch(&$bc) {
      $o = "switch (" . jsa::expr($bc[1]) . ") {\n";
      for ($i=2; $i<count($bc); $i+=2) {
         if (is_array($bc[$i]) && ($bc[$i][0]==JS_DEFAULT)) {
            $o .= "default:\n";
         }
         else {
            $o .= "case " . jsa::expr($bc[$i]) . ":\n";
         }
         $o .= jsa::block($bc[$i+1]);
      }
      $o .= "}\n";
      return $o;
   }


   #-- runtime functions (pseudo-func calls)
   function constr_rt($bc) {
      array_shift($bc);
      $func = array_shift($bc);
      $o = "";
      if (empty($bc)) {
         $o .= "/* js_rt_func_ without args! */\n";
      }
      else foreach ($bc as $arg) {
         $arg = jsa::expr($arg);
         switch ($func) {
            case JS_PRINT:
               $o .= 'print '. $arg .";\n";
               break;
            default:
               $o .= "/* js_rt_func_($func, $arg) */\n";
               break;
         }
      }
      return($o);
   }


   #-- function definitons (everything beside $bc["."])
   function constr_funcdef($bc){
      $o .= 'jsrt::assign(jsrt::rt_var(array(\''.$bc[1].'\'),true,true),jsrt::ref(\''.$bc[1].'\'));'.NEWLINE;
      $o .= 'function &jsrt_'.$bc[1];

      $args = array();
      $parameters = &$bc[2];
      $preamble = "";

      for ($i=0; isset($parameters[$i]); $i++) {
         $name = $parameters[$i][0];
         $argument = '$'.$name;
         if($parameters[$i][1] != null){
             $argument .= '='.$parameters[$i][1];
         }
         else{
            $argument .= '=null';
         }
         $args[] = $argument;
         $preamble .= NEWLINE.'jsrt::set_arg(\''.$name.'\',$'.$name.');';
      }

      $args = implode(",", $args);

      $o .= '('.$args.'){'.$preamble.NEWLINE;
      $o .= jsa::block($bc[3]).'}';

      return($o);
   }


   #-------------------------------------------------------------------
   #-- variable handling



   #-- always yields link into $jsi_vars[] array
   function variable(&$bc)
   {
      global $is_function,$force_create;

      $varname = addslashes($bc[1]);
      $namespace = "";

      $old_is_function = $is_function;
      $is_function = 'false';

      $parts = split('\.',$varname);
      foreach($parts as $part){
        $namespace .= '\'' . $part . '\',';
      }

      #-- additional array indicies
      for ($i=2; isset($bc[$i]); $i++) {
         $namespace .= jsa::expr($bc[$i]).',';
      }
      $namespace = substr($namespace,0,-1);

      $is_function = $old_is_function;
      $o = 'jsrt::rt_var(array('.$namespace.'),'.$is_function.','.$force_create.')';

      return($o);
   }


   #-------------------------------------------------------------------
   #-- expressions


   #-- turn a function into a safety enhanced PHP code string
   function fcall(&$bc)
   {
      // "$tjfn" stands for temporary-javascript-function-name
      // needs to get more complicated for OO/type features

      $args = array();
      for ($i=2; isset($bc[$i]); $i++) {
         $args[] = jsa::expr($bc[$i]);
      }
      $args = implode(",", $args);
      $pfix = JSA_FUNC_PREFIX;
      $variable = array(JS_VAR, $bc[1]);

      global $is_function;
      $is_function = 'true';
      if(count($bc) > 2){
          $o = 'jsrt::fcall('.jsa::variable($variable).',array('.$args.'))';
      }
      else{
        $o = 'jsrt::fcall('.jsa::variable($variable).',array())';
      }
      $is_function = 'false';
      return $o;
   }


   #-- variable := assignment
   function assign(&$bc)
   {
      global $force_create;

      $force_create = 'true';
      $lvalue = jsa::variable($bc[1]);
      $force_create = 'false';
      return 'jsrt::assign('.$lvalue.','.jsa::expr($bc[2]).')';
   }


   #-- evaluate the pre-arranged (parser-did-it-all) expressions
   function math(&$bc) {
      $o = "";

      #-- walk through contained elements
      for ($i=0; $i<count($bc); $i+=2) {

         #-- current expression
         $op = $bc[$i];
         $add = jsa::expr($bc[$i+1]);

         #-- change operator
         if (JS_PHPMODE && ($op=="+")) {
            // ooops, we cannot change that behaviour at will anymore
         }
         #-- unary operators are special
         elseif (($op == "~") or ($op == "!")) {
            $o = "$op$add";
            // the very first argument $bc[1] was zero
         }

         #-- very first element
         if ($op==JS_MATH) {
            $o .= $add;
         }
         else {
            $o .= " $op $add";
         }
      }

      return "($o)";
   }


   #-- the magic boolean math
   function cmp(&$bc)
   {
      $op = $bc[2];
      $A = jsa::expr($bc[1]);
      $B = jsa::expr($bc[3]);
      return 'jsrt::bool('."$A $op $B".')';
   }


   #-- huh, even simpler than in the interpreter
   function expr(&$bc)
   {
      $o = "";
      if (is_array($bc)) {
         $type = $bc[0];
         switch ($type) {
            case JS_ASSIGN: return jsa::assign($bc);
            case JS_MATH:   return jsa::math($bc);
            case JS_CMP:    return jsa::cmp($bc);
            case JS_STR:    return 'jsrt::ref('.jsa::expr($bc[1]).')';
            case JS_VALUE:  return 'jsrt::ref('.jsa::expr($bc[1]).')';
            case JS_VAR:    return jsa::variable($bc);
            case JS_FCALL:  return jsa::fcall($bc);
            default:
              jsa::err("expression fault <<".substr(serialize($bc),0,128).">>");
         }
      }
      else {
         return($bc);   // must be literal value
      }
   }


} // end of class

?>
<?php define("JS_VERSION", "0.01011");  /* it's a binary version number */

/*
  the javascript interpreter for php
  ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯
  Executes JavaScript-lookalike code safely in a sandbox. Interfaces into
  (variables, functions) the hosting interpreter (PHP) are possible. This
  is useful for embedding into CMS/Wiki engines to allow users to extend
  a site without having direct and full server access.

  Exceptionally this is FreeWare and not PublicDomain. (Read: you can
  use it within and modify it for any other project, but replacing this
  paragraph with the GNU GPL comment is not allowed [yet]. But see the
  README.)   (c) 2004,2005 WhoEver wants to.

  orig. author: <mario*erphesfurt·de>
  enhancements: Eric Anderton <ericanderton*yahoo·com>

  Interfaces
  ¯¯¯¯¯¯¯¯¯¯
   · js_exec($source_code)                 // all-in-one, output to screen
   · $output = js($source_code)            // alternative, uses accelerator

   · jsc::compile($source_code)            // compile into $bc
   · jsi::register_func($js_func_name, $php_func)
   · $jsi_vars["varname"] = "value"
   · jsi::run()                             // execute the loaded $bc
*/


// _NOTICEs should be turned off (you have been warned)
error_reporting(error_reporting() & (0xFFFF^E_NOTICE));


#-- a few settings
define("JS_CACHE", "/tmp/js");
define("JS_DEBUG", 0);
define("JS_FAST_CODE", !JS_DEBUG);
define("JS_PHPMODE", 0);   // forget it!
define("JS_TICKMAX", 50000);  // limit execution time for interpreted scripts


#-- language tokens (enable for more speed and less mem use)
if (JS_FAST_CODE) {
   define("JS_RT",	1);
   define("JS_OP_PFIX",	2);
   define("JS_FOREACH",	3);
   define("JS_OP_UNARY",	4);
   define("JS_QESTMARK",	5);
   define("JS_VALUE",	6);
   define("JS_ELSE",	7);
   define("JS_FOR",	8);
   define("JS_ASSIGN",	9);
   define("JS_ERROR",	10);
   define("JS_OP_BOOL_OR",	11);
   define("JS_MATH",	12);
   define("JS_WHILE",	13);
   define("JS_BREAK",	14);
   define("JS_INT",	15);
   define("JS_FCALL",	16);
   define("JS_OP_BIT",	17);
   define("JS_SWITCH",	18);
   define("JS_WORD",	19);
   define("JS_CMP",	20);
   define("JS_VAR",	21);
   define("JS_COLON",	22);
   define("JS_DO",	23);
   define("JS_COMMENT",	24);
   define("JS_BOOL",	25);
   define("JS_OP_BOOL_AND",	26);
   define("JS_OP_PLUS",	27);
   define("JS_PRINT",	28);
   define("JS_REAL",	29);
   define("JS_OP_MULTI",	30);
   define("JS_BRACE0",	31);
   define("JS_ELSEIF",	32);
   define("JS_BRACE1",	33);
   define("JS_CURLYBR0",	34);
   define("JS_CURLYBR1",	35);
   define("JS_COMMA",	36);
   define("JS_END",	37);
   define("JS_FUNCDEF",	38);
   define("JS_SQBRCKT0",	39);
   define("JS_SQBRCKT1",	40);
   define("JS_STR",	41);
   define("JS_OP_CMP",	42);
   define("JS_COND",	43);
   define("JS_CASE",	44);
   define("JS_IF",	45);
   define("JS_DEFAULT",	46);
   define("JS_VAR_STATEMENT",	47);
   define("JS_EOF",	255);
}


#-- easy execution interface -------------------------------------------
define("JSE_ACCEL", 0x01);
define("JSE_CACHE", 0x02);
define("JSE_CLEAN", 0x08);
define("JSE_TICKS", 0x20);

#-- simple, straight to screen
function js_exec($script, $flags=0x0001)
{
   #-- parse code into global $bc
   jse_compile($script, $flags);

   #-- run interpreter
   jse_run($flags);
    
   #-- clean
   if ($flags & JSE_CLEAN) {
      unset($GLOBALS["bc"]);
   }
}

#-- output as return value
function js($script="", $flags=0x0003)
{
   #-- start buffer
   ob_start();
   ob_implicit_flush(0);

   #-- compile, exec
   js_exec($script, $flags);

   #-- get result
   $r = ob_get_contents();
   ob_end_clean();
   return($r);
}

#-- compile+cache
function jse_compile($script="", $flags) {
   global $bc;

   #-- only if script/codestring given
   if ($script) {
      if ($flags & JSE_CACHE) {
         $md5 = JS_CACHE."/".md5($script) . ".js.bc.gz";
         #-- already cached
         if (file_exists(JS_CACHE) && file_exists(JS_CACHE."/".$md5)) {
            if ($f = gzopen($md5, "rb")) {
               $bc = unserialize(gzread($f, 1<<20));
               gzclose($f);
            }
         }
         #-- compile and save
         else {
            jsc::compile($script);
            if (!file_exists(JS_CACHE)) { mkdir(JS_CACHE); }
            if ($f = gzopen($md5, "wb")) {
               fwrite($f, serialize($bc));
               gzclose($f);
            }
         }
      }
      #-- without any caching
      else {
         jsc::compile($script, $flags&JSE_CLEAN);
      }
   }
}

#-- accelerated or in old interpreter
function jse_run($flags=JSE_ACCEL) {

   // interrupt every 1000 low-level statements
   if ($flags & JSE_TICKS) {
      register_tick_function(array("jsrt", "tick_safe"));
      declare(ticks=1000);
   }
   
   #-- check for accelerator/assembler presence
   if (($flags&JSE_ACCEL) && class_exists("jsa") && class_exists("jsrt")) {
      eval( jsa::assemble(NULL, $flags&JSE_TICKS) );
   }
   else {
      jsi::run();
   }
   
   #-- clean up
   if ($flags & JSE_TICKS) {
      unregister_tick_function(array("jsrt", "tick_safe"));
      declare(ticks=0);
   }
}



#-- load other parts
$dir = dirname(__FILE__);
require("$dir/jsc.php");
require("$dir/jsi.php");
require("$dir/jsa.php");
require("$dir/jsrt.php");


?>
<?php
/*
    ####   ####  ##   ## ##### #### ##     ###### #####
   ##  ## ##  ## ### ### ##  ## ##  ##     ##     ##  ##
   ##     ##  ## ####### ##  ## ##  ##     ##     ##  ##
   ##     ##  ## ## # ## #####  ##  #      ####   #####
   ##     ##  ## ##   ## ##     ##  ##     ##     ####
   ##  ## ##  ## ##   ## ##     ##  ##     ##     ## ##
    ####   ####  ##   ## ##    #### ###### ###### ##  ##

   phpjs compiler (lexer, parser)
   ------------------------------
   Converts a given js code string into a parse tree, and stores that
   in the global $bc variable.
*/

global $has_errors;
$has_errors = false;

#-- compiler part into separate namespace
class jsc {


   #-- calls lexer and parser
   function compile($codestr, $cleanup=0)
   {
      global $type, $val, $next, $nextval, $jsp_i,$has_errors;

      #-- cut source code into lexograpic tokens
      jsc::lex($codestr);
      if (JS_DEBUG) {
         jsc::delex();
      }

      #-- parse into bytecode
      $rescue = array($type, $val, $next, $nextval, $jsp_i);
      {
         jsc::parse();
      }
      if ($cleanup) {
         unset($GLOBALS["tn"]);
      }
      list($type, $val, $next, $nextval, $jsp_i) = $rescue;
      return $has_errors;
   }



   #-- Cuts the input source text into more easily analyzeable chunks
   #   (tokens), each with a type flag associated.
   function lex($str) {

      global $tn;
      $tn = array();

      #-- regular expressions to detect tokens
      static $types = array(
         JS_REAL    => '\d+\.\d+',
         JS_INT => '\d+',
         JS_BOOL    => '(?i:TRUE|FALSE)',
         JS_WORD    => '\$?[_A-Za-z]+(?:\.?[_\w]+)*',
         JS_STR => '(?:\"[^\"]*?\"|\'[^\']*?\')',
         JS_COMMENT => '(?:/\*.*?\*/|//[^\n]*)',
         JS_OP_CMP  => '(?:[<>]=?|[=!]==?)',
         JS_ASSIGN  => '(?:[-/%&|^*+:]=|=)',
         JS_OP_PFIX => '(?:\+\+|--)',
         JS_OP_MULTI    => '[*/%.]',
         JS_OP_BOOL_AND => '&&',
         JS_OP_BOOL_OR => '\|\|',
         JS_OP_BIT  => '[&|^]',
         // else $types1
      );
      static $types1 = array(
         '+' => JS_OP_PLUS,
         '-' => JS_OP_PLUS,
         ';' => JS_END,
         '!' => JS_OP_UNARY,
         '~' => JS_OP_UNARY,
         '(' => JS_BRACE1,   // for braces, 1 means opening, 0 the closing one
         ')' => JS_BRACE0,
         '[' => JS_SQBRCKT1,
         ']' => JS_SQBRCKT0,
         '{' => JS_CURLYBR1,
         '}' => JS_CURLYBR0,
         ',' => JS_COMMA,
         '?' => JS_QESTMARK,
         ':' => JS_COLON,
         // else JS_ERROR,
      );
      static $typetrans = array(
         JS_INT => JS_VALUE,
         JS_REAL => JS_VALUE,
         JS_STR => JS_VALUE,
      );
      static $typetrans_word = array(
         "for" => JS_FOR,
         "foreach" => JS_FOREACH,
         "function" => JS_FUNCDEF,
         "while" => JS_WHILE,
         "do" => JS_DO,
         "break" => JS_BREAK,
         "return" => JS_RETURN,
         "if" => JS_IF,
         "else" => JS_ELSE,
         "elseif" => JS_ELSEIF,
         "switch" => JS_SWITCH,
         "case" => JS_CASE,
         "default" => JS_DEFAULT,
         "echo" => JS_PRINT,
         "print" => JS_PRINT,
         "var" => JS_VAR_STATEMENT,
      );

      #-- make large combined regex
      $regex = "#^(?:(" . implode(")|(", $types) . "))#";
      $type = array_keys($types);
      $typesnum = count($types);

      $str = trim($str);
      while ($str) {

         #-- split into tokens, guess type by regex
         if (preg_match($regex, $str, $uu)) {
            $val = $uu[0];

            $T = JS_ERROR;
            for ($i=1; $i<=$typesnum; $i++) {
               if (strlen($uu[$i])) {
                  $T = $type[$i-1];
                  break;
            }  }
         }
         #-- else it is an one-char token
         else {
            $val = $str[0];
            ($T = $types1[$val]) or ($T = JS_ERROR);
         }

         #-- unknown token
         if ($T == JS_ERROR) {
            jsc::err("cannot handle '".substr($str,0,10)."...'");
         }


         #-- strip found thingi away from input string
         $str = substr($str, strlen($val));


         #-- special cases to take care of in the lexer
         switch ($T) {

            case JS_COMMENT:
               $str = ltrim($str);
               continue 2;
               break;

            case JS_STR:
               $val = substr($val, 1, strlen($val) - 2);
//@WARNING1@
               $val = str_replace("\'","\\\'",$val); // encode string so it can be wrapped in quotes
               break;

            case JS_WORD:
               $val = strtolower($val);
               if ($new = $typetrans_word[$val]) {
                  $T = $new;
                  $val = NULL;
               }
               while ($val[0] == "$") {
                  $val = substr($val, 1);
               }
               break;

            case JS_BOOL:
               $T = JS_INT;
               $val = (strlen($val) == 4) ?1:0;
               break;
            case JS_INT:
               $val = (int) $val;
               break;
            case JS_REAL:
               $val = (double) $val;
               break;
         }

         #-- valid language token
         if ($new = $typetrans[$T]) {
            $tn[] = array($new, $val, $T);
         }
         else {
            $tn[] = array($T, $val);
         }

         $str = ltrim($str);
      }
   }


   #-- prints the token streams` contents
   function delex() {
      global $tn, $bc;
      foreach ($tn as $data) {
         list($T, $str) = $data;
         if (!strlen($str)) { $str = $T; }
         echo "$str";
         if (($T==JS_END) or ($T==JS_CURLYBR0)) {
            echo "\n";
         }
      }
   }

   #-- prints the tokens (_DEBUG)
   function print_tokens($tn) {
      foreach ($tn as $i=>$d) {
         $t = strlen($d[0])<8 ? "\t" : "";
         echo "#$i\t$d[0]$t\t$d[1]\t$d[2]\n";
      }
   }





   ######   #####  ######   #####  ####### ######
   ##   ## ##   ## ##   ## ##   ## ##   ## ##   ##
   ##   ## ##   ## ##   ## ##      ##      ##   ##
   ######  ####### ######   #####  #####   ######
   ##      ##   ## ####         ## ##      ####
   ##      ##   ## ## ##   ##   ## ##      ## ##
   ##      ##   ## ##  ##  ##   ## ##   ## ##  ##
   ##      ##   ## ##   ##  #####  ####### ##   ##

   #-- get first entry from token stream
   function get() {

      global $type, $val, $subtype, $next, $nextval,
         $jsp_i, $tn;

      $val = $nextval = false;
      $next = JS_EOF;

      if (isset($tn[$jsp_i])) {
         list($type, $val) = $tn[$jsp_i];
         if($type == JS_VALUE){
            $subtype = $tn[$jsp_i][2]; // capture the subtype for values
         }
      }
      else {
         $type = JS_END;
      }
      if (isset($tn[$jsp_i+1])) {
         list($next, $nextval) = $tn[$jsp_i+1];
      }

      if (JS_DEBUG) {
         echo "@$jsp_i: t=$type,v=$val,n=$next,nv=$nextval\n";
      }

      $jsp_i++;
   }


   #-- gets second entry from token stream, but as current $type
   function getnext() {
      global $jsp_i;
      jsc::get();
      $jsp_i--;
   }


   #-- write an error message (better just collect and bail out later?)
   function err($s) {
      global $has_errors;
      $has_errors = true;
      echo "\nPARSER ERROR: $s\n";
   }
   function bug($s) {
      jsc::err("this IS A BUG in phpjs: $s");
   }


   #-- compare current token type (and subtype),
   #   put out an error message, if it does not match desired tags
   #   (call this "assert" instead?)
   function assert($t, $str=false, $caller=false) {
      global $type, $val, $next, $nextval, $jsp_i;
      if (($type != $t) || (is_array($t) && !in_array($type, $t)) ) {
   //           || ($str) && ($val != $str)
         if ($str) {
            $t = $str;
            $type = $val;
         }
         jsc::err("PARSE ERROR: '$t' expected, but '$type' seen @".($jsp_i-1)
                 . " by $caller");
      }
   }

   #-- combined get() and assert()
   function want($t, $str=false, $caller=false) {
      jsc::get();
      jsc::assert($t, $str, $caller);
   }


   #-------------------------------------------------------------------------


   #-- starting point for parsing given script
   function parse() {

      global $bc, $tn, $jsp_i;
      $jsp_i = 0;

      #-- initial mini transformations
      if (JS_DEBUG) {
         echo "\nall parsed \$tokens:\n";
         jsc::print_tokens($tn);
      }

      #-- array of expressions/commands
      $bc = array();

      #-- parse main program
      jsc::code_lines($bc["."]);

      if (JS_DEBUG) {
         echo "\ngenerated \$bytecode = ";
         print_r($bc);
      }
   }






   /*
     ###### #   ## #####  #####  ###### ####   #### #### ####  ##  ##  ####
     ##     ##  ## ##  ## ##  ## ##    ##  ## ##  ## ## ##  ## ### ## ##  ##
     ##      ####  ##  ## ##  ## ##    ##     ##     ## ##  ## ###### ##
     ####     ##   #####  #####  ####   ####   ####  ## ##  ## ######  ####
     ##      ####  ##     ####   ##        ##     ## ## ##  ## ## ###     ##
     ##     ##  ## ##     ## ##  ##    ##  ## ##  ## ## ##  ## ##  ## ##  ##
     ###### ##  ## ##     ##  ## ###### ####   #### #### ####  ##  ##  ####


     following code uses a token look-ahead paradigm,
     where $next is examined, and (current) $type
     usually treaten as the left side argument of any
     expression
   */



   #-- <assignment> ::= <identifier> <assign_operator> <expr>
   function assign(&$var) {
      if(JS_DEBUG) echo "_ASSIGN\n";
      global $type, $val, $next, $nextval;

      #-- left side (varname)
      $r = array(JS_ASSIGN);
      $r[] = $var;
      jsc::assert(JS_ASSIGN, 0, "assign");

      #-- combined assignment+operator
      $math = $val[0];
      if (($math == "=") || ($math == ":")) {
         $math = false;
      }

      #-- right side (expression)
      if ($math) {
         $r[] = array(JS_MATH, $r[1], $math, jsc::expr_start());
      } else {
         $r[] = jsc::expr_start();
      }

      return($r);
   }


   #-- <function_call> ::= <identifier> "(" (<expr> ("," <expr>)* )? ")"
   function function_call() {
      if(JS_DEBUG) echo "_FCALL\n";
      global $type, $val, $next, $nextval;
      $r = array(JS_FCALL, $val);
      jsc::get();
      jsc::append_list($r, JS_BRACE0);
      jsc::get();
      jsc::assert(JS_BRACE0, ")", "function_call");
      return($r);
   }


   #-- adds var[expr][expr] for identified array variables
   function array_var(&$var) {
      do {
         jsc::get();
         $var[] = jsc::expr_start();
         jsc::want(JS_SQBRCKT0, "]", "_array_var");
      }
      while ($next == JS_SQBRCKT1);
   }


   #-- <var_or_func> ::= <idf> | <assignment> | <function_call> | <idf> (++|--)
   function var_or_func() {
      global $type, $val, $next, $nextval;

      if(JS_DEBUG) echo "_VAR\n";
      jsc::assert(JS_WORD, 0, "var_or_func");

      #-- plain var
      $var = array(JS_VAR, $val);

      #-- array
      if ($next == JS_SQBRCKT1) {
         jsc::array_var($var);
      }

      #-- actual type
      if ($next == JS_BRACE1) {
         return(jsc::function_call());
      }
      elseif ($next == JS_ASSIGN) {
         jsc::get();
         return(jsc::assign($var));
      }
      elseif ($next == JS_OP_PFIX) {
         jsc::get();
         return
            array(JS_ASSIGN, $var, array(JS_MATH, $var, $val[0], 1));
      }
      else {
         return($var);
      }
   }


   #-- <pfix_var> ::=  (++ | --) <identifier>
   #   are transformed into regular "var := var (+|-) 1" interpreter stream
   function prefix_var() {
      global $val;
      $operation = $val[0];
      jsc::get();
      $var = jsc::var_or_func();   // bad: we shouldn't get a function here at all!
      if ($var[0] != JS_VAR) {   // (except if they may return references, hmm??)
         jsc::err("complex construct where variable reference expected @$GLOBALS[jsp_i]");
      }
      return
         array(JS_ASSIGN, $var,
            array(JS_MATH, $var, $operation, 1)
         );
   }


   #-- <expr_op_unary> ::=   "~" <value>  |  "!" <value>
   function expr_op_unary() {
      global $type, $val, $next, $nextval, $jsp_i;
      switch ($val) {
         case "~":
         case "!":
         case "+":
         case "-":
            return array(JS_MATH, 0, $val, jsc::expr_value());
         default:
            jsc::bug("unary operator mistake");
      }
   }


   #-- <value> ::= "(" <expr> ")" | <var_or_func> | <constant> | <expr_op_unary>
   function expr_value($uu=0) {
      global $type, $val, $subtype, $next, $nextval, $jsp_i;

      jsc::get();
      switch ($type) {

         case JS_BRACE1:
            if(JS_DEBUG) echo "_(\n";
            jsc::assert(JS_BRACE1, "(", "_expr_value");
            $r = jsc::expr_start();
            jsc::want(JS_BRACE0, ")", "_expr_value");
            return($r);
            break;

         case JS_OP_PFIX:
            return jsc::prefix_var();
            break;

         case JS_OP_UNARY:
         case JS_OP_PLUS:
            return jsc::expr_op_unary();
            break;

         case JS_WORD:
            return jsc::var_or_func();
            break;

         default:
            if(JS_DEBUG) echo "_CONST\n";
            jsc::assert(JS_VALUE, 0, "_expr_value");
            if($subtype == JS_STR) return(array(JS_VALUE, "'$val'"));
            return(array(JS_VALUE, $val));
      }
   }




   #-- ABSTRACT <expr_math> ::=  <_value> | <_value> (OPERATOR) <_value>
   function expr_math($num=0) {
      global $type, $val, $next, $nextval;

      #-- expression grammar
      #   (defines the precedence of operators)
      static $jsp_expr_math = array(
         JS_OP_BOOL_OR,
         JS_OP_BOOL_AND,
         JS_OP_BIT,
         JS_OP_PLUS,
         JS_OP_MULTI,
      );
      # <expr_multiply>  ::=  <_value> | <_value> (*|/|%) <_value>
      # <expr_plusminus> ::=  <_multiply> | <_multiply> (+|-) <_multiply>
      # <expr_bitop>     ::=  <_plusminus> | <_plusminus> (&|^|"|") <_plusminus>
      # <expr_booland>   ::=  <_bitop> | <_bitop> ("&&") <_bitop>
      # <expr_boolor>    ::=  <_booland> | <_booland> ("||") <_booland>
      //

      $upfunc = "expr_math";
      $OPERATOR = $jsp_expr_math[$num];
      $num++;
      if ($OPERATOR==JS_OP_MULTI) {
         $upfunc = "expr_value";
      }

      #-- get first expression
      $A = jsc::$upfunc($num);

      #-- check for (expected) operator
      if ($next == $OPERATOR) {
         $r = array(
            JS_MATH,
            $A,
         );
         while ($next == $OPERATOR) {
            jsc::get();
            $r[] = $val;   // +,- or *,/,% or &&,|| or
            $r[] = jsc::$upfunc($num);

         }
         return($r);
      }
      else {
         return($A);
      }
   }


   #-- <expr> ::= <_math> | <_math> (">=" | "<=" | "==" | ">" | "<" | "!=") <_math>
   function expr_cmp() {
      global $type, $val, $next, $nextval;

      #-- get left side expression
      $A = jsc::expr_math();

      #-- check for comparision operator
      if ($next == JS_OP_CMP) {
         jsc::get();
         $r = array(
            JS_CMP,
            $A,
            $val,
         );
         $r[] = jsc::expr_math();
         return($r);
      }
      else {
         return($A);
      }
   }


   #-- <expr> ::= <expr_plusminus>
   function expr_start() {
      return jsc::expr_cmp();
   }





     ##       ##   ##  ##  ####  ##  ##   ##    ####  ######
     ##      ####  ### ## ##  ## ##  ##  ####  ##  ## ##
     ##     ##  ## ###### ##     ##  ## ##  ## ##     ##
     #      ###### ###### ## ### ##  ## ###### ## ### ####
     ##     ##  ## ## ### ##  ## ##  ## ##  ## ##  ## ##
     ##     ##  ## ##  ## ##  ## ##  ## ##  ## ##  ## ##
     ###### ##  ## ##  ##  ####   ####  ##  ##  ####  ######

      ####   ####  ##  ##  #### ###### #####  ##  ##  #### ###### ####
     ##  ## ##  ## ### ## ##  ##  ##   ##  ## ##  ## ##  ##  ##  ##  ##
     ##     ##  ## ###### ##      ##   ##  ## ##  ## ##      ##  ##
     ##     ##  ## ######  ####   ##   #####  ##  ## ##      ##   ####
     ##     ##  ## ## ###     ##  ##   ####   ##  ## ##      ##      ##
     ##  ## ##  ## ##  ## ##  ##  ##   ## ##  ##  ## ##  ##  ##  ##  ##
      ####   ####  ##  ##  ####   ##   ##  ##  ####   ####   ##   ####

   /*
     unlike the expression code above, the following
     language construct analyzation functions don't
     have yet a filled-in $type, but in real called
     jsc::getnext() to have the values for the next
     token in $type and $val (pre-examine)

     therefore the language construct functions (except
     _block and _lines) usually start stripping the
     first token with jsc::get()
   */


   #-- extracts a comma separated list (of expressions)
   function append_list(&$bc, $term=JS_END, $comma=JS_COMMA) {
      global $type, $val, $next, $nextval;
      while (($next!=$term) && ($next!=JS_EOF)) {
         $bc[] = jsc::expr_start();
         if ($next == $comma) {
            jsc::get();
         }
      }
   }


   #-- a break; statement
   function constr_break(&$bc) {
      global $type, $val, $next, $nextval;

      #-- remove token
      jsc::get();   # "break"
      $r = array(JS_BREAK, 1);

      #-- ";" or expression/value follows
      if ($next != JS_END) {
         $r[1] = jsc::expr_start();
      }
      $bc[] = $r;
   }

   #-- a return; statement
   function constr_return(&$bc) {
      global $type, $val, $next, $nextval;

      #-- remove token
      jsc::get();   # "return"
      $r = array(JS_RETURN, 1);

      #-- ";" or expression/value follows
      if ($next != JS_END) {
         $r[1] = jsc::expr_start();
      }
      $bc[] = $r;
   }


   #-- chunk a for() loop
   function constr_for(&$bc) {

      #-- remove tokens, get list (<expr>; <expr>; <expr>)
      jsc::get();   # "for"
      jsc::want(JS_BRACE1, "(", "_constr_for0");   # "("
      $r = array();
      jsc::append_list($r, JS_BRACE0, JS_END);
      jsc::get();   # remove closing brace
      if (count($r) != 3) {
         jsc::err("there must be exactly three arguments in a for() loop");
      }

      #-- initial expression goes into the bc stream (before the JS_FOR entry)
      $bc[] = $r[0];
      $r[0] = JS_FOR;   # convert into bytecode stream for jsi_
      $r[3] = array();  # append code block
      jsc::block($r[3]);
      $bc[] = $r;       # output into stream
   }


   #-- if statement
   function constr_if(&$bc) {
      global $type, $val, $next, $nextval;

      $r = array(JS_COND, JS_IF);  # if-conditional in bytecode

      #-- loop through if() and elseif() conditions and blocks
      while (($type==JS_IF) || ($next==JS_ELSEIF)) {

         #-- remove tokens
         jsc::get();   # "if" or "elseif" or "else"
         $is = $type;
         jsc::want(JS_BRACE1, "(", "_constr_if");  # "("

         #-- generate bc stream
         $r[] = jsc::expr_start();
         $r[] = array();
         jsc::want(JS_BRACE0, ")", "_constr_if2"); # ")"

         if($next == JS_CURLYBR1){
            jsc::block($r[count($r)-1]);
         }
         else{
            jsc::code_lines($r[count($r)-1]);
         }
      }

      #-- optional else block
      if ($type==JS_ELSE) {
         jsc::get();
         $r[] = array(JS_VALUE, 1);
         $r[] = array();
         if($next == JS_CURLYBR1){
            jsc::block($r[count($r)-1],true);
         }
         else{
            jsc::code_lines($r[count($r)-1]);
         }
      }

      $bc[] = $r;
   }


   #-- while statement
   function constr_while(&$bc) {
      global $type, $val, $next, $nextval;

      #-- remove tokens
      jsc::want(JS_WHILE);
      jsc::want(JS_BRACE1, "(", "_constr_while");

      #-- while-conditional in bytecode
      $r = array(
         JS_COND,
         JS_WHILE,
         jsc::expr_start(),
         array()    // placeholder
      );
      jsc::want(JS_BRACE0, ")", "_constr_while2");
      jsc::block($r[3]);

      $bc[] = $r;
   }


   #-- do statement
   function constr_do(&$bc) {
      global $type, $val, $next, $nextval;
      #-- generate bc stream
      $r = array(
         JS_COND,
         JS_DO,
         0,        // placeholder
         array()   // placeholder
      );
      #-- remove tokens
      jsc::get();   # "do"
//      $r[1] = array();
      jsc::block($r[3]);
      #-- while post condition
      jsc::want(JS_WHILE, false, "_constr_repeat");
      jsc::want(JS_BRACE1, "(", "_constr_repeat2");
      $r[2] = jsc::expr_start();
      jsc::want(JS_BRACE0, ")", "_constr_repeat3");
      #-- add to parent bytecode stream
      $bc[] = $r;
   }


   #-- switch() statement
   function constr_switch(&$bc) {
      global $type, $val, $next, $nextval;
      #-- prepare bc stream
      $r = array(
         JS_SWITCH,
         0,        // placeholder for compare-me expression
      );
      #-- remove tokens
      jsc::want(JS_SWITCH);
      jsc::want(JS_BRACE1);
      $r[1] = jsc::expr_start();
      jsc::want(JS_BRACE0);
      #-- read body
      jsc::want(JS_CURLYBR1);
      #-- search for "case" statements
      $i = 2;
      do {
         jsc::get();   # "case" or "}"
         if ($type == JS_CASE) {
            //jsc::get();    # ":"
            $r[$i++] = jsc::expr_start();
            jsc::want(JS_COLON, ":", "_constr_switch4");
            jsc::block($r[$i++], array(JS_CASE, JS_DEFAULT, JS_CURLYBR0, JS_END), false);
         }
         elseif ($type == JS_DEFAULT) {
            jsc::want(JS_COLON, ":", "_constr_switch5");
            $r[$i++] = array(JS_DEFAULT);
            jsc::block($r[$i++], array(JS_CASE, JS_CURLYBR0, JS_END), false);
         }
         else {
            jsc::err("malformed switch construct");
         }
      }
      while (($type != JS_CURLYBR0) && ($next != JS_EOF));
      jsc::want(JS_CURLYBR0);
      #-- end
      $bc[] = $r;
   }


   #-- runtime/lang functions (echo, print)
   function constr_rt(&$bc) {
      global $type, $val, $next, $nextval;
      $r = array(JS_RT, $type);
      jsc::get();
      jsc::append_list($r, JS_END);
      $bc[] = $r;
   }

   function constr_var(&$bc){
      global $type, $val, $next, $nextval;
      $r = array(JS_VAR_STATEMENT);
      jsc::want(JS_VAR_STATEMENT,false,"_constr_var0");
      jsc::want(JS_WORD,false,"_constr_var1");
      $r[] = $val;
      jsc::get();
      switch($type){
      case JS_ASSIGN:
        jsc::append_list($r, JS_END);
        break;
      case JS_END:
        break;
      default:
        jsc::assert(JS_END,false,"_constr_var2");
      }
      $bc[] = $r;
   }

   function constr_func(&$bc){
      global $type, $val, $next, $nextval;

      $r = array(JS_FUNCDEF);
      jsc::get();

      jsc::want(JS_WORD,"identifier","_constr_func0");
      $r[] = $val;

      jsc::want(JS_BRACE1,"(","_constr_func1");

      if($next == JS_WORD){
        $args = array();

        while (($next!=JS_BRACE0) && ($next!=JS_EOF)) {
          jsc::want(JS_WORD,"argument","_constr_func_arg");
          $lvalue = $val;

          if($next == JS_ASSIGN){
             jsc::get();
             jsc::want(JS_VALUE,"constant","_constr_func_arg_default");
             $rvalue = $val;
          }
          else $rvalue = null;

          $args[] = array($lvalue,$rvalue);

          if ($next == JS_COMMA) {
             jsc::get();
          }
        }
        $r[] = $args;
      }
      else{
        $r[] = null;
      }
      jsc::want(JS_BRACE0,")","_constr_func2");

      $r[] = array();
      jsc::block($r[count($r)-1]);

      $bc[] = $r;
   }


   #-- reads one command/expr/line;
   function code_lines(&$bc, $term=JS_END) {
      global $type, $val, $next, $nextval;
      $term = (array)$term;


      jsc::getnext();
      while ($type && !in_array($type, $term)) {
         #echo("code_lines debug: [$type,$val,$next]<br/>");
         switch($type) {

            case JS_CURLYBR1:
               $bc[] = array();
               jsc::block($bc[count($bc)-1]);
               break;
            case JS_CURLYBR0:
               return;

            case JS_BREAK:
               jsc::constr_break($bc);
               break;

            case JS_RETURN:
               jsc::constr_return($bc);
               break;

            case JS_FOR:
               jsc::constr_for($bc);
               break;

            case JS_IF:
               jsc::constr_if($bc);
               break;
            case JS_WHILE:
               jsc::constr_while($bc);
               break;
            case JS_DO:
               jsc::constr_do($bc);
               break;

            case JS_SWITCH:
               jsc::constr_switch($bc);
               $type = 900;  // go on
               break;

            case JS_PRINT:
               jsc::constr_rt($bc);
               break;

            case JS_VAR_STATEMENT:
               jsc::constr_var($bc);
               break;

            case JS_FUNCDEF:
               jsc::constr_func($bc);
               break;

            case JS_END:
               break;

            case JS_EOF:
               break;

            default:
               $bc[] = jsc::expr_start();
         }

         #-- end of line
         while ($next == JS_END) {
            jsc::get();
         }
         if ($type==JS_CURLYBR0) {
            jsc::getnext();
//            echo "going home...";
            return;
         }

         jsc::getnext();
      }
   }


   #-- parses a block of code
   function block(&$bc, $term=JS_CURLYBR0, $need_braces=true) {
      global $type, $val, $next, $nextval;

      if ($need_braces) {
         jsc::want(JS_CURLYBR1, "{", "_block_{");
      }
   #echo "_P_BLOCK,$type,$val,$next:\n";
   #print_r($bc);

      $bc = array();
      jsc::code_lines($bc, $term);
   #echo "_P_BLOCK,$type,$val,$next:\n";
   #print_r($bc);

      if ($need_braces) {
         jsc::want(JS_CURLYBR0, "}", "_block_}");
      }
      jsc::getnext();
   }


} // end of class


?>
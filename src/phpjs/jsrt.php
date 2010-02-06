<?php define("JSRT_VERSION", "0.2");  /* it's a non-binary version number */

/*
   phpjs runtime functions and data
   ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯
   Must be loaded to start the phpjs interpreter or to execute jsa::
   compiled sandbox code. Provides interfaces to PHP mathematical and
   string functions and hardened interfaces to regular expressions,
   and some other (js-only) utility code.

   original part from <mario*erphesfurt,de>
   extended by Eric Anderton <ericanderton*yahoo,com> - jsa support class
*/


#-- prepare vars
global $jsi_vars, $jsi_lvars, $jsi_funcs, $jsi_break;
$jsi_vars = (array)$jsi_vars;
$jsi_funcs = (array)$jsi_funcs;

#-- standard functions (sub-arrays are for JS class structure)
$jsi_funcs = array(
    "math" => array(
        "abs" => "abs",
        "acos" => "acos",
        "asin" => "asin",
        "atan" => "atan",
        "ceil" => "ceil",
        "cos" => "cos",
        "exp" => "exp",
        "floor" => "floor",
        "log" => "log",
        "man" => "man",
        "min" => "min",
        "pow" => "pow",
        "random"=>"rand",
        "round" => "round",
        "sin" => "sin",
        "sqrt" => "sqrt",
        "tan" => "tan",
    ),
    "system" => array(
        "time" => "time",
    ),
    "document" => array(
        "writeln" => "jsrt_writeln",
        "write" => "jsrt_write",
    ),
    "string" => array(
        "cmp" => "strcmp",
        "length" => "len",
    ),
    "write" => "jsrt_write",
    "writeln" => "jsrt_writeln",
    "debug" => "jsrt_debug",
    "count" => "count",
);

#-- functions aliases for basic features (jsi:: only)
//$jsi_vars["write"] = "write"; //"jsrt_write";
//$jsi_vars["writeln"] = "writeln"; //"jsrt_writeln";
//$jsi_vars["strcmp"] = 'strcmp';
//$jsi_vars["count"] = 'count';
//$jsi_vars["debug"] = 'debug';

#-- default and standard values
$jsi_vars["system"]["version"] = JSRT_VERSION;
$jsi_vars["Screen"]["width"] = 80;
$jsi_vars["Screen"]["height"] = 25;
$jsi_vars["Screen"]["pixelDepth"] = 4;
$jsi_vars["Screen"]["colorDepth"] = 4;

#-- add-ons for Cilantro/CML (Eric)
$jsi_vars["this"] = &$jsi_vars;
$jsi_vars["thisname"] = null;
#
#class Test{
#    var $field1;
#    function method1($value){
#        echo("from test: $value [{$this->field1}]");
#    }
#}
#
#$jsi_vars["object"] = new Test();


#-----------------------------------------------------------------
#-- (old) run time functions

function jsrt_write($str="") {
   echo $str;
}
function jsrt_writeln($str="") {
   echo $str .= "\n";
}

function jsrt_debug($arg1=null){
    jsrt::debug($arg1);
}

global $jsi_stack,$jsi_cmp,$jsi_scope;
$jsi_scope = null;
$jsi_cmp = false;
$jsi_stack = Array();


#-----------------------------------------------------------------
#-- (new) static runtime class,
#   mostly used from jsa:: compiled sandbox scripts
class jsrt {

    #-- low-level PHP interrupt call, counts execution statements
    function tick_safe() {
       static $ticks;
       if ($ticks++ > JS_TICKMAX) {
          $_GLOBALS["jsi_die"] = 1;        // hack in jsa:: sandbox code
          $_GLOBALS["jsi_break"] = 65535;  // infinite break in jsi::
       }
    }


    function error($str){
        echo("JSRT Error: $str"); die;
    }

    #-- variable scopes (CML, ColdFusion-like markup)
    function scope_start(&$scope){
        global $jsi_stack,$jsi_vars,$jsi_cmp,$jsi_scope;
        array_push($jsi_stack,$jsi_scope);
        array_push($jsi_stack,$jsi_vars['this']);
        array_push($jsi_stack,$jsi_vars['thisname']);
        array_push($jsi_stack,$jsi_cmp);

        $jsi_scope = (is_array($scope)?$scope:array($scope));
    }

    function scope_end(){
        global $jsi_stack,$jsi_vars,$jsi_cmp,$jsi_scope;
        $jsi_cmp = &array_pop($jsi_stack);
        $jsi_vars['thisname'] = &array_pop($jsi_stack);
        $jsi_vars['this'] = &array_pop($jsi_stack);
        $jsi_scope = &array_pop($jsi_stack);
    }

    function scope_next(&$index){
        global $jsi_vars,$jsi_scope;
        $jsi_vars['thisname'] = &$index;
        $jsi_vars['this'] = &$jsi_scope[$index];
    }

    function &scope(){
        global $jsi_scope;
        return $jsi_scope;
    }

    #-- sets JS object refernce "this"
    function set_this(&$value,$name){
        global $jsi_vars;
        $jsi_vars['this'] = &$value;
        $jsi_vars['thisname'] = $name;
    }

    #-- used instead of vanilla PHP = assign operator in jsa:: sandbox code
    function &assign(&$lvalue,&$rvalue){
        $lvalue = $rvalue;
        return($rvalue);
    }

    function &ref($value){
        return $value;
    }

    function &bool($value){
        if($value == true) return 1;
        return 0;
    }

    function set_cmp($value){
        global $jsi_cmp;
        $jsi_cmp = $value;
        return $value;
    }

    function get_cmp(){
        global $jsi_cmp;
        return $jsi_cmp;
    }

    function set_arg($name,&$value){
        global $jsi_vars;
        $jsi_vars['__arguments'][$name] = &$value;
    }

    function &fcall(&$handle,$args){
        global $jsi_vars,$jsi_stack;

        if(!$handle) return null;

        array_push($jsi_stack,$jsi_vars['__arguments']);
        unset($jsi_vars['__arguments']);
        $jsi_vars['__arguments'] = array();
        foreach($args as $name => $value){
            $jsi_vars['__arguments'] = &$values[$name];
        }
        $result = &call_user_func_array($handle,$args);

        global $jsi_vars,$jsi_stack;
        $jsi_vars['__arguments'] = &array_pop($jsi_stack);

        return $result;
    }

    function &rt_var($namespace,$is_function=false,$force_create=false){
        global $jsi_vars,$jsi_funcs;
        $result = null;
        if($is_function){
            $result = &jsrt::var_search($jsi_funcs,$namespace,true,false);
            if($result){
                return $result; // avoid function cleaning later, return now instead
            }
        }

        if($namespace[0] != '__arguments'){
            $result = &jsrt::var_search($jsi_vars,array_merge(array('__arguments'),$namespace),$is_function,false);
        }
        if(!$result && $namespace[0] != 'this'){
            $result = &jsrt::var_search($jsi_vars,array_merge(array('this'),$namespace),$is_function,false);
        }
        if(!$result){
            $result = &jsrt::var_search($jsi_vars,$namespace,$is_function,$force_create);
        }

        // function cleaning (make sure that user functions are sandboxed)
        if($is_function && is_scalar($result)){
            return 'jsrt_'.$result;
        }

        return $result;
    }

    function &var_search(&$root,$namespace,$is_function=false,$force_create=false){
        $this_object = &$root;
        foreach($namespace as $name){
            if(($this_object == null || is_scalar($this_object)) && $force_create){
                $this_object = array($name => null);
                $this_object = &$this_object[$name];
            }
            else if(is_object($this_object)){
                $class = get_class($this_object);
                $methods = get_class_methods($class);
                $members = get_object_vars($this_object);

                if(array_key_exists($name,$members)){
                    $this_object = &$this_object->{$name};
                }
                else if($is_function && in_array($name,$methods)){
                    $this_object = array($this_object,$name);
                    return($this_object);
                }
                else{
                    $this_object = null;
                    break;
                }
            }
            else if(is_array($this_object)){
                if(!array_key_exists($name,$this_object) && $force_create){
                    $this_object[$name] = null;
                }
                $this_object = &$this_object[$name];

            }
            else{
                return null;
            }
        }
        return $this_object;
    }

    function debug($var=null){
        global $jsi_stack,$jsi_scope,$jsi_vars;

        if($var){
            var_dump($var);
        }
        else{
            var_dump($jsi_vars);
        }
    }
}

?><?php
/*include_once('phpjs/jsrt.php');

    class Foo{
        var $y = 69;
        function bar(){
            echo("foobar!\n");
            return 42;
        }
    }

    function baz(){
        echo("zzzzzzzzzzzz\n");
    }

    $jsi_vars['x'] = new Foo();
    $jsi_vars['baz'] = 'baz';

    if(in_array('x',$jsi_vars)) echo "in array";

    $temp = &jsiv(array('scope','x','bar'),array('x','bar'),true);
    $temp = call_user_func($temp);

    echo($temp);

    $temp = &jsiv(array('scope','baz'),array('baz'),true);
    $temp = call_user_func($temp);
*/
?>
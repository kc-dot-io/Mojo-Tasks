#!/usr/bin/php -q
<?php

  ini_set('error_reporting','E_ALL');

  function getMojo( $name = 'Mojo.class.php', $path = '.', $level = 0 )
  {
    $mojo ="";
    //if you have your mojo lib in a diff set of libs set them here
    $scan = array( 'lib','vendor','src', 'tasks','Mojo-Tasks', $name );
    $dh = @opendir( $path );
    while( false !== ( $file = @readdir( $dh ) ) ){
        if( in_array( $file, $scan ) ){
            if( is_dir( "$path/$file" ) ){
                return getMojo( $name, "$path/$file", ($level+1) );
            } else {
                if($file == $name){
                    $mojo = $path.'/'.$file;
                    return realpath($mojo);
                }
            }
        }
    }
    @closedir( $dh );
    return $mojo;
  }
 
  array_shift($argv);  $cmd = "";
  foreach($argv as $v) $cmd .= " ".$v;
  passthru('php '.getMojo().$cmd);

?>

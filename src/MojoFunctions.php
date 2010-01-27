<?php  

  function getFile( $name = 'Mojo.class.php', $path = '.', $level = 0 )
  {
    $mojo ="";
    //if you have your mojo lib in a diff set of libs set them here
    $scan = array( 'lib','vendor','src', 'Mojo-Tasks', $name );
    $dh = @opendir( $path );
    while( false !== ( $file = @readdir( $dh ) ) ){
        if( in_array( $file, $scan ) ){
            if( is_dir( "$path/$file" ) ){
                return getFile( $name, "$path/$file", ($level+1) );
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

?>

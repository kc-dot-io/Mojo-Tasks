<?php  

ini_set('error_reporting','E_ALL');

function getFile( $name = 'Mojo.class.php', $path = '.', $level = 0 )
{
  $target ="";

  if(file_exists($path.$name)) return $path.$name;

  //if you have your mojo lib in a diff set of libs set them here
  $scan = array( 'lib','vendor','src', 'tasks','Mojo-Tasks', $name );
  $dh = @opendir( $path );
  while( false !== ( $file = @readdir( $dh ) ) ){
    if( in_array( $file, $scan ) ){
      if( is_dir( "$path/$file" ) ){
        return getFile( $name, $path.DIRECTORY_SEPARATOR .$file, ($level+1) );
      } else {
        if($file == $name){
          $target = $path.DIRECTORY_SEPARATOR.$file;
          return realpath($target);
        }
      }
    }
  }
  @closedir( $dh );
  return $target;
}

function promptUser($question,$default)
{
  if (preg_match('/^win/i', PHP_OS)) {
    $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
    file_put_contents(
      $vbscript, 'wscript.echo(InputBox("'
      . addslashes($question)
      . '", "", "'.$default.'"))');
    $command = "cscript //nologo " . escapeshellarg($vbscript);
    $password = rtrim(shell_exec($command));
    unlink($vbscript);
    return $password;
  } else {
    echo "\n";
    Mojo::prompt($question);
    return trim(fgets(STDIN));
    /*
    $command = "/usr/bin/env bash -c 'echo OK'";
    if (rtrim(shell_exec($command)) !== 'OK') {
      trigger_error("Can't invoke bash");
      return;
    }
    $command = "/usr/bin/env bash -c 'read -s -p \""
      . addslashes($prompt)
      . "\" mypassword && echo \$mypassword'";
    $password = rtrim(shell_exec($command));
    echo "\n";
    */
    return $password;
  }
}

?>

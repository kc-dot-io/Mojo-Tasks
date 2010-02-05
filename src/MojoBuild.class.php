<?php

/**
 * Build Tasks.
 *
 * @package    kiwi-web
 * @subpackage mojo - MojoBuild
 * @author     Kyle Campbell
 */

class MojoBuild extends Mojo
{
  function __construct($args)
  {
      $this->args = $args;
      return $this;
  }

	function readMap(){


			$sitemap = file_get_contents(MojoConfig::get('mojo_js_dir').'SiteMap.js');

			$arr = explode(MojoConfig::get('mojo_app_name').'.SiteMap = ',$sitemap );

			$map = str_replace('];',']',$arr[1]);
			echo trim($map);
//			$json = json_decode('{'.$map.'}');


			
	}

}

?>

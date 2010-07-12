<?php

/**
 * Rules Tasks.
 *
 * @package    mojo
 * @author     Kyle Campbell
 */

class MojoRules extends MojoFile
{
  function __construct($args)
  {
      $this->args = $args;
      return $this;
  }

  function Scaffold()
  {
    
      //Validation
      if(empty($this->args['name'])) return Mojo::exception('Provide a full mojo path in your params string, ie: name=mojo.rules.myRules');
      if(strpos($this->args['name'],'rules.') < 1) return Mojo::exception('Please use correct rules path, ie: name=mojo.rules.myRules');

      $source = self::Source();
      $file = self::makeNewFile($this->args['name'],'rules');     

      MojoFile::write($file,MojoFile::editStream($this->args,$source));
      Mojo::prompt('Generated Rules Scaffolding to '.$file);
  }

  function Source()
  {
      ob_start();
return <<<EOF
/* 
  Class: %NAME%
  Author: %AUTHOR%
  Description: %DESCRIPTION%
*/

dojo.provide("%NAME%");
dojo.require("mojo.helper.Validation");
var validate = mojo.helper.Validation.getInstance();

%NAME% = {
  "fieldname": [
    {
        "errorMsg": "This field is required",
        "rule": validate.isRequired
    },
    {
        "errorMsg": "Email address must be in the correct format",
        "rule": validate.isEmailAddress
    },
    {
        "errorMsg": "Please input the right type valie",
        "rule": validate.isType
    },
    {
        "errorMsg": "Cannot exceed 100 characters",
        "params": {
            "max": "100",
            "min": "1"
        },            
         "rule": validate.isRange
    },
    {
        "errorMsg": "Cannot exceed 100 characters",
        "params": {
            "max": "100",
            "min": "1"
        },
        "rule": validate.isLength
    },
    {
        "errorMsg": "Only numbers or does not match",
        "params": {
            "refValue": $(x).value,
            "regex": /^[0-9]*$/
         },
        "rule": validate.isMatch
    },
    {
        "errorMsg": "Incorrect zip code",
        "params": {
            "value": $(x).value
        },
        "rule": validate.isZipCode
    },
    {
        "errorMsg": "Incorrect zip code",
        "params": {
            "value": $(x).value
        },
        "rule": validate.isPostalCode
    }
  ]
};
EOF;
      ob_end_flush();
      return ob_get_contents();
  }
}

?>

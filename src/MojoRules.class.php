<?php

/**
 * Rules Tasks.
 *
 * @package    kiwi-web
 * @subpackage mojo - MojoRules
 * @author     Kyle Campbell
 */

class MojoRules extends Mojo
{
  function __construct($requestObj)
  {
      $this->requestObj = $requestObj;
      return $this;
  }

  function Scaffold()
  {

      //Replace this with a validation method
      if(empty($this->requestObj['name'])) 
          return MojoUtils::prompt('Provide a full mojo path in your params string, ie: name=mojo.rules.myRules');

      if(strpos($this->requestObj['name'],'rules.') < 1)
          return MojoUtils::prompt('The name you provided for your Rules appears to be incorrect. '
                              .'Please use full Rules path, ie: name=mojo.rules.myRules');
      $source = self::source();
     
      $name = explode('rules.',$this->requestObj['name']); $name = $name[1];
      if(strpos($name,'.') > -1) { //handle ruless in a sub dir
          $tmp = explode('.',$name); //check if this dir exists, and create it if it does not
          if(!file_exists(MojoUtils::getConfig('sf_mojo_dir').'rules/'.$tmp[0])) mkdir(MojoUtils::getConfig('sf_mojo_dir').'rules/'.$tmp[0]);
          $name = $tmp[0].'/'.$tmp[1]; //full path including new dir
      }
      MojoUtils::write(MojoUtils::getConfig('sf_mojo_dir').'rules/'.$name.'.js',MojoUtils::editStream($this->requestObj,$source));
      MojoUtils::prompt('Generated Rules Scaffolding to '.MojoUtils::getConfig('sf_mojo_dir').'rules/'.$name.'.js');
  }

  function source()
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

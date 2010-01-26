<?php

/**
 * Symfony Tasks.
 *
 * @package    kiwi-web
 * @subpackage mojo - MojoSymfony
 * @author     Kyle Campbell
 */

class MojoSymfony extends Mojo
{
  function __construct($args)
  {
    require_once(dirname(__FILE__).'/../../../../config/ProjectConfiguration.class.php');
    $configuration = ProjectConfiguration::getApplicationConfiguration('frontend', 'dev', true);
    sfContext::createInstance($configuration);
    new sfDatabaseManager($configuration);

    $this->args = $args;
    return $this;
  }

  function Help(){
    Mojo::exception("Acceptable use: $ mojo Symfony Translate --i18n=(./path/to/xml) --form=(string) --mojo_lib=(./path/to/mojo/install) --name=(string) --author=(string) --description=(string)"," - HELP - ");
  }
  
  function Validate(){

    if(empty($this->args['i18n'])) Mojo::exception("You must provide an xml or json source to read translations from. ex: --i18n=./path/to/messages.en.xml");
    if(!file_exists($this->args['i18n']) || !is_readable($this->args['i18n'])) Mojo::exception("Your source does not exist or cannot be read.");

    if(empty($this->args['form'])) Mojo::exception("You must provide an form source to implement rules for. ex: --form=FormName");
    if(!class_exists($this->args['form'])) Mojo::exception("The form object you provided does not exist in the current context.");

    if(!MojoUtils::getConfig('sf_mojo_dir') && isset($this->args['mojo_lib'])) MojoUtils::setConfig('sf_mojo_dir',$this->args['mojo_lib']);
    if(!MojoUtils::getConfig('sf_mojo_dir')) Mojo::exception("Your project directory is not defined. Specify it with --mojo_lib=./path/to/mojo/project/");
  }
  
  function Translate(){

    self::Validate();

    echo "\n";
    Mojo::prompt("symfony bootstrap complete");

    $xml = new SimpleXMLElement(file_get_contents($this->args['i18n']));
    $i18n = $xml->file->body;
    
    $class = $this->args['form'];
    $form = new $class;
    $embed = $this->args['embed'];

    Mojo::prompt($form->getName()." form is now loaded");
    $form->configure();
    Mojo::prompt($form->getName()." form is now configured");

    Mojo::prompt("=======================================================================================");
    Mojo::prompt("Analyzing form fields...");

    $rules = self::getRules($form,$i18n);

    if(isset($embed) && class_exists($embed)){
        $eform = new $embed;
        $eform->configure();
        $erules = self::getRules($eform,$i18n,$form);
        $rules[$form->getName()] = array_merge($rules[$form->getName()],$erules[$eform->getName()]);
    }

#    print_r($rules); exit;

    $name = explode('rules.',$this->args['name']);
    $name = $name[1];
    if(strpos($name,'.') > -1) { //handle behaviors in a sub dir
      $tmp = explode('.',$name); //check if this dir exists, and create it if it does not
      if(!file_exists(MojoUtils::getConfig('sf_mojo_dir').'rules/'.$tmp[0])) mkdir(MojoUtils::getConfig('sf_mojo_dir').'rules/'.$tmp[0]);
      $name = $tmp[0].'/'.$tmp[1]; //full path including new dir
    }

    Mojo::prompt("=======================================================================================");
    $written = MojoUtils::write(MojoUtils::getConfig('sf_mojo_dir').'rules/'.$name.'.js',self::printRule($rules,$form));
    if( $written > 0 ) Mojo::prompt("Rule written to: ".MojoUtils::getConfig('sf_mojo_dir')."rules/".$name.".js");
    else Mojo::prompt("There was an error writting to :".MojoUtils::getConfig('sf_mojo_dir')."rules/".$name.".js");

    $path = explode("/",$this->args['i18n']);
    $name = str_replace($path[count($path)-1],"",$this->args['i18n']);
    $file = str_replace("xml","js",$path[count($path)-1]);

    if(!file_exists(MojoUtils::getConfig('sf_mojo_dir')."rules/i18n/")) mkdir(MojoUtils::getConfig('sf_mojo_dir')."rules/i18n/"); 
    $written = MojoUtils::write(MojoUtils::getConfig('sf_mojo_dir')."rules/i18n/".$file,"var locData =".json_encode(self::printLocData($rules,$form)));
    if( $written > 0 ) Mojo::prompt("locData written to: ".MojoUtils::getConfig('sf_mojo_dir')."rules/i18n/".$file);
    else Mojo::prompt("There was an error writting to :".MojoUtils::getConfig('sf_mojo_dir')."rules/i18n/".$file);

    Mojo::Prompt("DONE - ".$form->getName());
    Mojo::prompt("\n\n=======================================================================================\n\n");
    if(isset($this->args['debug'])) echo self::printRule($rules,$form);
    
  }

  function getRules($form,$i18n,$parent=false){
    $rules = array();
    $rules[$form->getName()] = array();
    $schema = $form->getValidatorSchema();
    $fields = $schema->getFields();

    foreach($fields as $k => $v){
      $rules[$form->getName()][$form->getName().'['.$k.']'] = array();
      Mojo::prompt("-------------");
      Mojo::prompt("Building rules for field: ".$form->getName().'['.$k.']');
      Mojo::prompt("=======================================================================================");

      $messages = $v->getMessages();
      $options = $v->getOptions();
      #print_r($messages);
      #print_r($options);

        Mojo::prompt("Rules to build:");
        Mojo::prompt("-------------");
        foreach($messages as $i => $j){
            foreach($i18n->{'trans-unit'} as $t){
              if((string)$t->source == (string)$messages[$i]){
                if($parent == false){
                  $rules[$form->getName()][$form->getName().'['.$k.']'][$i]['value'] = (string)$t->target[0];
                  $rules[$form->getName()][$form->getName().'['.$k.']'][$i]['key'] = $messages[$i];
                  $rules[$form->getName()][$form->getName().'['.$k.']'][$i]['options'] = $options;
                  Mojo::prompt($form->getName().'['.$k.']'." - ".$t->target);
                }else{
                  $rules[$form->getName()][$parent->getName().'['.$form->getName().']['.$k.']'][$i]['value'] = (string)$t->target[0];
                  $rules[$form->getName()][$parent->getName().'['.$form->getName().']['.$k.']'][$i]['key'] = $messages[$i];
                  $rules[$form->getName()][$parent->getName().'['.$form->getName().']['.$k.']'][$i]['options'] = $options;
                }
              }
            }
        }
        if(count($rules[$form->getName()][$form->getName().'['.$k.']']) < 1) Mojo::prompt("No rules to build");
    }
    return $rules;
  }

  function printLocData($rules,$form)
  {
    $locData = array();
    foreach($rules[$form->getName()] as $value){
      foreach($value as $validator){
          $locData[$validator['key']] = $validator['value'];
      }
    }
    return $locData;
  }

  function printRule($rules,$form)
  {

      if(!isset($this->args['author'])) $this->args['author'] = 'Mojo Tasks - http://github.com/slajax/Mojo-Tasks';
      if(!isset($this->args['description'])) $this->args['description'] = 'Mojo Rules auto generated by Mojo Tasks';
    
      $str ="";
      $str .= MojoUtils::editStream($this->args,self::Source('header'));

      $rule_count=1;
      foreach($rules[$form->getName()] as $rule => $validators){

        // is this the last item in the arr?
        $rule_end = ( $rule_count == count( $rules[$form->getName()] ) )
                      ?true:false;
                    
        $this->args['field'] = $rule;

#        print_r($validators);

        if( count($validators) > 0){

          if($rule_count>1) $str .= MojoUtils::editStream($this->args,self::Source('comma'));
          $str .= MojoUtils::editStream($this->args,self::Source('field_start'));

          $validate_count=1;

          foreach($validators as $key => $value){

              $options = $value['options'];
              #Mojo::prompt($key);
              //is this the last item in the arr?
              $validators_end = ( $validate_count == count($validators) )
                              ?true:false;

              switch($key){
                case 'required':

                  $this->args['message'] = "locData.".$value['key'];
                  $this->args['rule'] = self::Source('is_required'); 
                  $this->args['params'] = "";
                  $str .= MojoUtils::editStream($this->args,self::Source('rule_start'));
                  $str .= MojoUtils::editStream($this->args,self::Source('rule_end',$validators_end));

                break;
                case 'min': case 'min_length':

                  $this->args['message'] = "locData.".$value['key'];
                  $this->args['rule'] = self::Source('is_length');
                  $this->args['min'] = (empty($options['min_length']))?1:$options['min_length'];
                  $this->args['max'] = (empty($options['max_length']))?100:$options['max_length'];
                  $str .= MojoUtils::editStream($this->args,self::Source('rule_start'));
                  $str .= MojoUtils::editStream($this->args,self::Source('min_max'));
                  $str .= MojoUtils::editStream($this->args,self::Source('rule_end',$validators_end));

                break;
                case 'invalid': case 'match':
                    switch(strtolower($value['key'])){
                      case 'confirm_email_match': case 'confirm_email': case 'confirm_password': case 'match':                                                 

                        $this->args['message'] = "locData.".$value['key'];
                        $this->args['rule'] = self::Source('is_match');
                        $this->args['match'] = str_replace("confirm","",str_replace("_","",$rule));
                        $str .= MojoUtils::editStream($this->args,self::Source('rule_start'));
                        $str .= MojoUtils::editStream($this->args,self::Source('match'));
                        $str .= MojoUtils::editStream($this->args,self::Source('rule_end',$validators_end));

                      break;
                      case 'email_invalid':

                        $this->args['message'] = "locData.".$value['key'];
                        $this->args['rule'] = self::Source('is_match');
                        $this->args['regex'] = $options['pattern'];
                        $str .= MojoUtils::editStream($this->args,self::Source('rule_start'));
                        $str .= MojoUtils::editStream($this->args,self::Source('regex'));
                        $str .= MojoUtils::editStream($this->args,self::Source('rule_end',$validators_end));

                      break;
                  }
                break;
              }
            $validate_count++;
          }
          $str .= MojoUtils::editStream($this->args,self::Source('field_end',$rule_end));
        }
        $rule_count++;
      }

      $str .= MojoUtils::editStream($this->args,self::Source('footer'));
      return $str;
  }

  function Source($partial,$end=false){
    ob_start();
    switch ($partial){
      case "header":
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
EOF;
      ob_end_flush();
      break;
      case "footer":

  return <<<EOF

};


EOF;
      ob_end_flush();
      break;
      case "comma":
  return <<<EOF
,
EOF;
      case "field_start":
  return <<<EOF

    "%FIELD%": [      

EOF;
      ob_end_flush();
      break;
      case "field_end":
  return <<<EOF
    ]
EOF;
      ob_end_flush();
      break;
      case "rule_start":
  return <<<EOF
      {
        "errorMsg": %MESSAGE%,
        "rule": %RULE%

EOF;
      ob_end_flush();
      break;
      case "rule_end":
if($end){
  return <<<EOF
      }

EOF;
}else{
  return <<<EOF
      },

EOF;
}
      ob_end_flush();
      break;
      case "min_max":
  return <<<EOF
        "params": {
            "max": %MAX%,
            "min": %MIN%
        }

EOF;
      ob_end_flush();
      break;
      case "match":
  return <<<EOF
        "params": {
            "ref": "%MATCH%"
        }

EOF;
      ob_end_flush();
      break;
      case "regex":
  return <<<EOF
        "params": {
            "regex": %REGEX%
        }

EOF;
      ob_end_flush();
      break;
      case "is_required":
  return <<<EOF
validate.isRequired
EOF;
      ob_end_flush();
      break;
      case "is_length":
  return <<<EOF
validate.isLength,
EOF;
      ob_end_flush();
      break;
      case "is_type":
  return <<<EOF
validate.isType
EOF;
      ob_end_flush();
      break;
      case "is_match":
  return <<<EOF
validate.isMatch,
EOF;
      ob_end_flush();
      break;

      default: return false; break;
    }
    return ob_get_contents();
  }  
}

?>

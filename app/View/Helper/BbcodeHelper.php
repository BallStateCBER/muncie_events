<?php
/*
 * BbcodeHelper utilises PEAR's BBcodeparser to parse the bbcode tags into
 * equivalent html tags.
 * 
 * @author : Kishor Kundan
 */
App::uses('AppHelper', 'View/Helper');
class BbcodeHelper extends AppHelper {

    protected $BbcodeParser;
    protected $RequiredPackages = array("PEAR", "HTML/BBCodeParser2");

    public function __construct(View $view, $settings = array()) {
        parent::__construct($view, $settings);

        array_map(
                function($FileName) {
                	if (stripos($_SERVER['SERVER_NAME'], 'localhost') === false) {
                    	ini_set('include_path', ini_get('include_path').':/usr/share/pear');
                	}
                    $FileName .= ".php";
                    return ((require_once($FileName)) ? true : false);
                }, 
                $this->RequiredPackages
       );
       return (($this->BbcodeParser = new HTML_BBCodeParser2()) ? true : false);
    }

   

    public function htmlize($bbcode = null) {
        if($bbcode === null) {
            return false;
        }
        return $this->output(
                $this->BbcodeParser->qParse(
                            htmlspecialchars($bbcode)
                        )
               );
    }
}
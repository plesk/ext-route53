<?php
/**
 * Form password input element with additional CSS-style
 */
class Modules_Route53_Form_Element_StyledPassword extends AdminPanel_Form_Element_Password
{
    public function init()
    {
        $cssClassesOrig = $this->getAttrib('class');
        parent::init();
        $cssClassesNew = $this->getAttrib('class');
        if (!empty($cssClassesOrig) && strpos($cssClassesNew, $cssClassesOrig) === false) {
            $this->setAttrib('class', "{$cssClassesOrig} {$cssClassesNew}");
        }
    }
}
# PHPHtmlStaticer
A php class for html staticing, which is easy to learn and use but hightly useful.

Usage:
    XXXController extends BaseController.
    BaseController:
    function __construct(){
        $this->__sh = StaticHtml::getInstance();
    }
    function __destruct(){
        $this->__sh->_static();
    }

<?php
/**
 * @package    Alligo.PlgSystemAlligovarnish
 * @author     Emerson Rocha Luiz <emerson@alligo.com.br>
 * @copyright  Copyright (C) 2015 Alligo Ltda. All rights reserved.
 * @license    GNU General Public License version 3. See license.txt
 */
defined('_JEXEC') or die;


// Modo com hack no core do Joomla! 2.5
// @see http://forum.joomla.org/viewtopic.php?f=621&t=720647
// \libraries\joomla\session\session.php line 115:
//  if (!JFactory::getApplication()->isSite()) {
//    if (isset($_COOKIE['allowCookies']) || in_array('administrator', explode("/", $_SERVER["REQUEST_URI"]))==true) {
//        $this->_start();
//    }
//
//
// Nota: (infelizmente) alterado core do joomla, na linha a seguir, como solução temporária de contorno (fititnt, 2014-12-28 14:04)
// \libraries\joomla\response\response.php line 137 (de else para):
// else if (('cache-control' !== strtolower($header['name']) && 'pragma' !== strtolower($header['name'])) && JFactory::getApplication()->isSite())

/**
 * Plugin Alligo Varnish
 *
 * @package  Alligo.PlgSystemAlligovarnish
 * @since    3.4
 */
class plgSystemAlligovarnish extends JPlugin
{

    /**
     * This plugin is running on Joomla frontend?
     *
     * @var Boolean 
     */
    protected $is_site = false;

    /**
     * Menu item ID (is is running on front-end)
     *
     * @var Integer 
     */
    protected $itemid = 0;

    /**
     * Parsed proxy cache exeptions for each menu item id
     *
     * @var Array 
     */
    protected $exptproxy = [];

    /**
     * Parsed browser exeptions for each menu item id
     *
     * @var Array 
     */
    protected $exptbrowser = [];

    /**
     * Debug info
     *
     * @var Array 
     */
    protected $debug = [];

    /**
     * Explode lines and itens separed by : and return and array,
     * with debug option if syntax error
     * 
     * @param   Array   $string  String to be converted
     * @return  Array
     */
    private function _getTimes($string)
    {
        $times = [];
        if (!empty($string)) {
            $lines = explode("\r\n", $string);

            foreach ($lines AS $line) {
                $itemid_hour = explode(":", $line);

                if (count($itemid_hour) < 2) {
                    $this->debug['wrongtime'] = empty($this->debug['wrongtime']) ? $line : $this->debug['wrongtime'] . ',' . $line;
                } else {
                    if (substr($itemid_hour[1], 0, 1) === "0") {
                        // Do not cache this
                        $times[(int) $itemid_hour[0]] = false;
                    } else if (!in_array(substr($itemid_hour[1], -1), ['s', 'h', 'd', 'w', 'm', 'y'])) {
                        $this->debug['wrongtime'] = empty($this->debug['wrongtime']) ? $line : $this->debug['wrongtime'] . ',' . $line;
                    } else {
                        $times[(int) $itemid_hour[0]] = $itemid_hour[1];
                    }
                }
            }
        }

        return $times;
    }

    /**
     * onAfterInitialise
     * 
     * @package  ImNotJoomla
     * @since    1.6
     * 
     * @deprecated
     * 
     * @return   void
     */
    public function onAfterInitialise()
    {
        $this->is_site = JFactory::getApplication()->isSite();
//        if (JFactory::getApplication()->isSite()) {
//            $this->_cleanHeaders();
//        }
    }

    public function onBeforeCompileHead()
    {
        if (JFactory::getApplication()->isSite()) {
            $this->_cleanHeaders();
        }
        //if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] !== 80) {
        //    JFactory::getDocument()->setMetaData('robots', 'noindex, nofollow');
        //}
    }

    public function onBeforeRender()
    {
        if ($this->is_site) {

            $this->itemid = JFactory::getApplication()->getMenu()->getActive()->id;
            $this->exptproxy = $this->_getTimes($this->params->get('exptproxy', ''));
            $this->exptbrowser = $this->_getTimes($this->params->get('exptbrowser', ''));
        }

//        if (JFactory::getApplication()->isSite()) {
//            $this->_cleanHeaders();
//        }
    }

    /**
     * onAfterRender
     * 
     * @return   void
     */
    public function onAfterRender()
    {
        if (JFactory::getApplication()->isSite()) {
            $this->_cleanHeaders();
            session_unset();
        }
//        if (JFactory::getApplication()->isSite()) {
//
//            // Destroi a sessão apenas se for usuário do site
//            JResponse::setHeader('Cache-control', 'public', true);
//            $session = JFactory::getSession();
//            $session->destroy();
//            //session_unset();
//        }
    }
}

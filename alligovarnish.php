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
     * Debug is enabled?
     *
     * @var Boolean
     */
    protected $debug_is = false;

    private function _getTimeAsSeconds($time)
    {
        $seconds = 0;
        if (!empty($time)) {
            switch (substr($time, -1)) {
                case 's':
                    $seconds = (int) substr($time, 0, -1);
                    break;
                case 'm':
                    $seconds = (int) substr($time, 0, -1);
                    $seconds = $seconds * 60;
                    break;
                case 'd':
                    $seconds = (int) substr($time, 0, -1);
                    $seconds = $seconds * 60 * 24;
                    break;
                case 'm':
                    $seconds = (int) substr($time, 0, -1);
                    $seconds = $seconds * 60 * 24 * 30;
                    break;
                case 'y':
                    $seconds = (int) substr($time, 0, -1);
                    $seconds = $seconds * 60 * 24 * 30 * 365;
                    break;
                default:
                    $seconds = (int) $time;
                    break;
            }
        }

        return $seconds;
    }

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
                        $times[(int) $itemid_hour[0]] = $this->_getTimeAsSeconds($itemid_hour[1]);
                    }
                }
            }
        }

        return $times;
    }

    /**
     * Set headers specific for the browser cache
     * 
     * @param   Integer   $time
     */
    public function setCacheBrowser($time = null)
    {
        if (empty($time)) {
            JFactory::getApplication()->allowCache(false);
            JFactory::getApplication()->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate', true);
            JFactory::getApplication()->setHeader('Pragma', 'no-cache', true);
            JFactory::getApplication()->setHeader('Expires', '0', true);
            if ($this->debug_is) {
                JFactory::getApplication()->setHeader('X-Alligo-BrowserCache', 'disabled');
            }
        } else {
            $epoch = strtotime('+' . $time . 's');

            JFactory::getApplication()->allowCache(true);
            JFactory::getApplication()->setHeader('Cache-Control', 'public, max-age=' . $time, true);
            JFactory::getApplication()->setHeader('Pragma', 'cache', true);
            JFactory::getApplication()->setHeader('Expires', date('D, j M Y H:i:s T', $epoch), true);
            if ($this->debug_is) {
                JFactory::getApplication()->setHeader('X-Alligo-BrowserCache', 'enabled, ' . $time . 's, datetime ' . date('D, j M Y H:i:s T', $epoch));
            }
        }
    }

    /**
     * Set headers specific for the proxy cache
     * 
     * @param   Integer   $time
     */
    public function setCacheProxy($time = null)
    {
        if (empty($time)) {
            JFactory::getApplication()->setHeader('Surrogate-Control', 'no-store', true);
            if ($this->debug_is) {
                JFactory::getApplication()->setHeader('X-Alligo-ProxyCache', 'disabled');
            }
        } else {
            $epoch = strtotime('+' . $time . 's');
            JFactory::getApplication()->setHeader('Surrogate-Control', 'public, max-age=' . $time, true);
            if ($this->debug_is) {
                JFactory::getApplication()->setHeader('X-Alligo-ProxyCache', 'enabled, ' . $time . 's, datetime ' . date('D, j M Y H:i:s T', $epoch));
            }
        }
    }

    /**
     * onAfterInitialise
     * 
     * This event is triggered after the framework has loaded and the application initialise method has been called.
     * 
     * @return   void
     */
    public function onAfterInitialise()
    {
        $this->is_site = JFactory::getApplication()->isSite();
    }

    /**
     * This event is triggered before the framework creates the Head section of the Document.
     */
    public function onBeforeCompileHead()
    {

        // @todo Feature: maybe implement a way to set robots "nofollow" if site
        // is not behind varnish cache
        //if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] !== 80) {
        //    JFactory::getDocument()->setMetaData('robots', 'noindex, nofollow');
        //}
    }

    /**
     * This event is triggered immediately before the framework has rendered
     *  the application. 
     * 
     * Rendering is the process of pushing the document buffers into the 
     * template placeholders, retrieving data from the document and pushing it 
     * into the JResponse buffer.
     */
    public function onBeforeRender()
    {
        if ($this->is_site) {

            $this->itemid = JFactory::getApplication()->getMenu()->getActive()->id;
            $this->exptproxy = $this->_getTimes($this->params->get('exptproxy', ''));
            $this->exptbrowser = $this->_getTimes($this->params->get('exptbrowser', ''));
            $this->debug_is = (bool) $this->params->get('debug', false);
            $this->setCacheBrowser(300);
            $this->setCacheProxy(300);
        } else {

            // Tip for varnish that we REALLY do not want cache this
            $this->setCacheProxy(false);
        }

//        if (JFactory::getApplication()->isSite()) {
//            $this->_cleanHeaders();
//        }
    }

    /**
     * This event is triggered after the framework has rendered the application.
     * 
     * Rendering is the process of pushing the document buffers into the 
     * template placeholders, retrieving data from the document and pushing 
     * it into the JResponse buffer.
     * 
     * When this event is triggered the output of the application is available in the response buffer.
     * 
     * @return   void
     */
    public function onAfterRender()
    {
//        if (JFactory::getApplication()->isSite()) {
////            $this->_cleanHeaders();
////            session_unset();
//        }
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

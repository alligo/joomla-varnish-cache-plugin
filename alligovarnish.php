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
     * Default time for browser time, if not specified on $exptbrowser
     * 
     * @var Integer
     */
    protected $browsertime = 0;

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

    /**
     * Parsed browser exeptions for each menu item id
     *
     * @var Array 
     */
    protected $exptbrowser = [];

    /**
     * Parsed proxy cache exeptions for each menu item id
     *
     * @var Array 
     */
    protected $exptproxy = [];

    /**
     * Extra headers enabled?
     *
     * @var Array 
     */
    protected $extrainfo = 0;

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
     * Default time for proxy time, if not specified on $exptproxy
     * 
     * @var Integer
     */
    protected $varnishtime = 0;

    /**
     * Time to inform Varnish cache that contend can be used as old
     * object from cache even if expired
     *
     * @var Integer 
     */
    protected $stale_time = 30;

    /**
     * Convert a string terminated by s, m, d or y to seconds
     * 
     * @param   String   $time
     * @return  Integer  Time in seconds
     */
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
                case 'y':
                    $seconds = (int) substr($time, 0, -1);
                    $seconds = $seconds * 60 * 24 * 30 * 365;
                    break;
                default:

                    // ¯\_(ツ)_/¯
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

    function onAfterDispatch()
    {
        $this->is_site && $this->prepareToCache();
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
        $this->is_site && $this->prepareToCache();
    }

    /**
     * This event is triggered before the framework creates the Head section of the Document.
     */
    public function onBeforeCompileHead()
    {
        $this->is_site && $this->prepareToCache();
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
        // Esse talvez não precise
        $this->is_site && $this->prepareToCache();
    }

    /**
     * Set cache. Should be called ONLY on Joomla front-end. Backend
     * should call $this->setCacheProxy(null) for ensure no cache;
     *
     * @see setCacheBrowser()
     * @see setCacheProxy()
     */
    public function setCache()
    {
        if ($this->setCacheExceptions()) {
            return;
        }

        $debug_has_defaults = '';
        // Exist specific instruction for Proxy cache time? No? Use defaults
        if (!empty($this->exptproxy[$this->itemid])) {
            $this->setCacheProxy($this->exptproxy[$this->itemid]);
            $debug_has_defaults .= 'Custom proxy time';
        } else {
            $this->setCacheProxy($this->varnishtime);
            $debug_has_defaults .= 'Default proxy time';
        }

        // Exist specific instruction for Broser cache time? No? Use defaults
        if (!empty($this->exptbrowser[$this->itemid])) {
            $this->setCacheBrowser($this->exptbrowser[$this->itemid]);
            $debug_has_defaults .= ', Custom browser time';
        } else {
            $this->setCacheBrowser($this->browsertime);
            $debug_has_defaults .= ', Default browser time';
        }
        if ($this->debug_is) {
            JFactory::getApplication()->setHeader('X-Alligo-JoomlaItemId', $this->itemid, true);
            JFactory::getApplication()->setHeader('X-Alligo-CacheTimes', $debug_has_defaults, true);
        }
    }

    /**
     * Some places of Jooma should never cache
     * 
     * @todo    Ainda não está funcional da forma como está sendo chamada. Deve
     *          ser chamada em fase mais inicial da sequencia de eventos do
     *          Joomla (fititnt, 2015-12-20 07:27)
     *
     * @reutuns Boolean
     */
    protected function setCacheExceptions()
    {
        $component = JFactory::getApplication()->input->getCmd('option', '');
        $reason = false;
        if ($component === 'com_ajax') {
            $this->setCacheProxy(false);
            $reason = 'Ajax Request';
        } else if ($component === 'com_banners') {
            $task = JFactory::getApplication()->input->getCmd('task', '');
            if ($task === 'click') {
                $this->setCacheProxy(false);
                $reason = 'Ajax Request';
            }
        }
        if ($reason) {
            $this->setCacheProxy(null);
            if ($this->debug_is) {
                JFactory::getApplication()->setHeader('X-Alligo-ProxyCache', 'disabled');
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set headers specific for the browser cache
     * 
     * @param   Integer   $time
     */
    protected function setCacheBrowser($time = null)
    {
        if (empty($time)) {
            //JFactory::getApplication()->allowCache(false);
            JFactory::getApplication()->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate', true);
            JFactory::getApplication()->setHeader('Pragma', 'no-cache', true);
            JFactory::getApplication()->setHeader('Expires', '0', true);
            if ($this->debug_is) {
                JFactory::getApplication()->setHeader('X-Alligo-BrowserCache', 'disabled');
            }
        } else {
            //date_default_timezone_set('GMT');
            $epoch = strtotime('+' . $time . 's', JFactory::getDate()->getTimestamp());

            //JFactory::getApplication()->allowCache(true);
            JFactory::getApplication()->setHeader('Cache-Control', 'public, max-age=' . $time, true);
            JFactory::getApplication()->setHeader('Pragma', 'cache', true);
            JFactory::getApplication()->setHeader('Expires', date('D, j M Y H:i:s T', $epoch), true);
            if ($this->extrainfo) {
                JFactory::getApplication()->setHeader('X-Cache-Control', 'public, max-age=' . $time, true);
                JFactory::getApplication()->setHeader('X-Pragma', 'cache');
                JFactory::getApplication()->setHeader('X-Expires', date('D, j M Y H:i:s T', $epoch), true);
            }
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
    protected function setCacheProxy($time = null)
    {
        if (empty($time)) {
            JFactory::getApplication()->setHeader('Surrogate-Control', 'no-store', true);
            if ($this->debug_is) {
                JFactory::getApplication()->setHeader('X-Alligo-ProxyCache', 'disabled');
            }
        } else {

            //date_default_timezone_set('GMT');
            $epoch = strtotime('+' . $time . 's', JFactory::getDate()->getTimestamp());
            //JFactory::getApplication()->setHeader('Surrogate-Control', 'public, max-age=' . $time, true);
            //JFactory::getApplication()->setHeader('Surrogate-Control', 'max-age=' . $time . ' + ' . $this->stale_time . ', content="ESI/1.0"', true);
            JFactory::getApplication()->setHeader('Surrogate-Control', 'max-age=' . $time . '+' . $this->stale_time, true);
            if ($this->debug_is) {
                JFactory::getApplication()->setHeader('X-Alligo-ProxyCache', 'enabled, ' . $time . 's, datetime ' . date('D, j M Y H:i:s T', $epoch));
            }
        }
    }

    /**
     * More than one plugin WILL try to change headers that define cache, so
     * its sad, but we need to setup more than one time this call. Remember
     * that just put on last event maybe will not work, because its not
     * very sure that will trigger the last event
     * 
     * @see https://docs.joomla.org/Plugin/Events/System
     */
    public function prepareToCache()
    {
        if ($this->debug_is) {
            // Se o varnish estiver enviando heades iniciadas com X-Joomla, 
            // devolver ao cliente final

            foreach ($_SERVER AS $key => $value) {
                if (strpos(strtolower($key), 'x_joomla')) {
                    $xheader = str_replace('_', '-', str_replace('http_', '', strtolower($key)));
                    JFactory::getApplication()->setHeader($xheader, $value, true);
                }
            }
        }

        if ($this->is_site) {

            $menu_active = JFactory::getApplication()->getMenu()->getActive();
            $this->itemid = empty($menu_active) || empty($menu_active->id) ? 0 : (int) $menu_active->id;
            $this->varnishtime = $this->_getTimeAsSeconds($this->params->get('varnishtime', ''));
            $this->browsertime = $this->_getTimeAsSeconds($this->params->get('browsertime', ''));
            $this->exptproxy = $this->_getTimes($this->params->get('exptproxy', ''));
            $this->exptbrowser = $this->_getTimes($this->params->get('exptbrowser', ''));
            $this->extrainfo = (bool) $this->params->get('extrainfo', false);
            $this->debug_is = (bool) $this->params->get('debug', false);
            $this->setCache();
        } else {

            // Tip for varnish that we REALLY do not want cache this
            $this->setCacheProxy(null);
        }
        if ($this->debug_is && count($this->debug)) {
            JFactory::getApplication()->setHeader('X-Alligo-Debug', json_encode($this->debug), true);
        }
    }
}

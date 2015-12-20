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
 * Plugin to fake the CMS
 *
 * @package  ImNotJoomla
 * @since    1.6
 */
class plgSystemAlligovarnish extends JPlugin
{

    /**
     * @see plgSystemePrivacy http://www.richeyweb.com/development/joomla-plugins/111-system-eu-e-privacy-directive
     * @copyright (C) 2010-2011 RicheyWeb - www.richeyweb.com
     * @return void
     */
    private function _cleanHeaders()
    {

        $session = JFactory::getSession();
        $session->destroy();
        //session_unset();
        //session_destroy();
        //JResponse::setHeader('Cache-control', 'public', false);
        //JResponse::setHeader('Cache-control', 'cache', true);
        //JResponse::setHeader('Pragma', 'cache', true);

        JResponse::allowCache(false);
        JFactory::getCache()->setCaching(false);
        $hasheaders = false;
        foreach (headers_list() as $header) {
            if ($hasheaders)
                continue;
            if (preg_match('/Set-Cookie/', $header)) {
                $hasheaders = true;
            }
        }
        if (!$hasheaders)
            return;
        if (version_compare(phpversion(), 5.3, '>=')) {
            header_remove('Set-Cookie');
        } else {
            header('Set-Cookie:');
        }
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
        if (JFactory::getApplication()->isSite()) {
            $this->_cleanHeaders();
        }
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
        if (JFactory::getApplication()->isSite()) {
            $this->_cleanHeaders();
        }
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

    /**
     * _hideHeaders
     * 
     * @package  ImNotJoomla
     * @since    1.6
     * 
     * @return   void
     */
    private function _hideHeaders()
    {
        #header_remove('X-Powered-By');// JResponse::setHeader('X-Powered-By', '', true);
    }

    /**
     * _hideHeaders
     * 
     * @deprecated
     * @package  ImNotJoomla
     * @since    1.6
     * 
     * @deprecated
     * @return   void
     */
    private function _trollHeaders()
    {
        //header_remove('X-Powered-By');
        JResponse::setHeader('X-Powered-By', 'ASP.NET', true); // I.e.: header('X-Powered-By: ASP.NET');
    }
}

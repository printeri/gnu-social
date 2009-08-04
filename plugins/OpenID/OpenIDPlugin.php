<?php
/**
 * Laconica, the distributed open-source microblogging tool
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Plugin
 * @package   Laconica
 * @author    Evan Prodromou <evan@controlyourself.ca>
 * @copyright 2009 Control Yourself, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://laconi.ca/
 */

if (!defined('LACONICA')) {
    exit(1);
}

/**
 * Plugin for OpenID authentication and identity
 *
 * This class enables consumer support for OpenID, the distributed authentication
 * and identity system.
 *
 * @category Plugin
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://laconi.ca/
 * @link     http://openid.net/
 */

class OpenIDPlugin extends Plugin
{
    /**
     * Initializer for the plugin.
     */

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Add OpenID-related paths to the router table
     *
     * Hook for RouterInitialized event.
     *
     * @return boolean hook return
     */

    function onRouterInitialized(&$m)
    {
        $m->connect('main/openid', array('action' => 'openidlogin'));
        $m->connect('settings/openid', array('action' => 'openidsettings'));
        $m->connect('xrds', array('action' => 'publicxrds'));
        $m->connect('index.php?action=finishopenidlogin', array('action' => 'finishopenidlogin'));
        $m->connect('index.php?action=finishaddopenid', array('action' => 'finishaddopenid'));

        return true;
    }

    function onEndLoginGroupNav(&$action)
    {
        $action_name = $action->trimmed('action');

        $action->menuItem(common_local_url('openidlogin'),
                          _('OpenID'),
                          _('Login or register with OpenID'),
                          $action_name === 'openidlogin');

        return true;
    }

    function onEndAccountSettingsNav(&$action)
    {
        $action_name = $action->trimmed('action');

        $action->menuItem(common_local_url('openidsettings'),
                          _('OpenID'),
                          _('Add or remove OpenIDs'),
                          $action_name === 'openidsettings');

        return true;
    }

    function onAutoload($cls)
    {
        switch ($cls)
        {
         case 'OpenidloginAction':
         case 'FinishopenidloginAction':
         case 'FinishaddopenidAction':
         case 'XrdsAction':
         case 'PublicxrdsAction':
         case 'OpenidsettingsAction':
            require_once(INSTALLDIR.'/plugins/OpenID/' . strtolower(mb_substr($cls, 0, -6)) . '.php');
            return false;
         case 'User_openid':
            require_once(INSTALLDIR.'/plugins/OpenID/User_openid.php');
            return false;
         default:
            return true;
        }
    }

    function onSensitiveAction($action, &$ssl)
    {
        switch ($action)
        {
         case 'finishopenidlogin':
         case 'finishaddopenid':
            $ssl = true;
            return false;
         default:
            return true;
        }
    }

    function onLoginAction($action, &$login)
    {
        switch ($action)
        {
         case 'openidlogin':
         case 'finishopenidlogin':
            $login = true;
            return false;
         default:
            return true;
        }
    }
}

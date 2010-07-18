<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2010, StatusNet, Inc.
 *
 * Send and receive notices using an IRC network
 *
 * PHP version 5
 *
 * This program is free software: you can redistribute it and/or modify
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
 * @category  IM
 * @package   StatusNet
 * @author    Luke Fitzgerald <lw.fitzgerald@googlemail.com>
 * @copyright 2010 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}
// We bundle the Phergie library...
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/extlib/phergie');
require 'Phergie/Autoload.php';
Phergie_Autoload::registerAutoloader();

/**
 * Plugin for IRC
 *
 * @category  Plugin
 * @package   StatusNet
 * @author    Luke Fitzgerald <lw.fitzgerald@googlemail.com>
 * @copyright 2010 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

class IrcPlugin extends ImPlugin {
    public $user =  null;
    public $password = null;
    public $publicFeed = array();

    public $transport = 'irc';

    /**
     * Get the internationalized/translated display name of this IM service
     *
     * @return string Name of service
     */
    public function getDisplayName() {
        return _m('IRC');
    }

    /**
     * Normalize a screenname for comparison
     *
     * @param string $screenname screenname to normalize
     * @return string an equivalent screenname in normalized form
     */
    public function normalize($screenname) {
        $screenname = str_replace(" ","", $screenname);
        return strtolower($screenname);
    }

    /**
     * Get the screenname of the daemon that sends and receives messages
     *
     * @return string Screenname
     */
    public function daemon_screenname() {
        return $this->nick;
    }

    /**
     * Validate (ensure the validity of) a screenname
     *
     * @param string $screenname screenname to validate
     * @return boolean
     */
    public function validate($screenname) {
        if (preg_match('/\A[a-z0-9\-_]{1,1000}\z/i', $screenname)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Load related modules when needed
     *
     * @param string $cls Name of the class to be loaded
     * @return boolean hook value; true means continue processing, false means stop.
     */
    public function onAutoload($cls) {
        $dir = dirname(__FILE__);

        switch ($cls) {
            case 'IrcManager':
                include_once $dir . '/'.strtolower($cls).'.php';
                return false;
            case 'Fake_Irc':
                include_once $dir . '/'. $cls .'.php';
                return false;
            default:
                return true;
        }
    }

    /*
     * Start manager on daemon start
     *
     * @return boolean
     */
    public function onStartImDaemonIoManagers(&$classes) {
        parent::onStartImDaemonIoManagers(&$classes);
        $classes[] = new IrcManager($this); // handles sending/receiving
        return true;
    }

    /**
    * Get a microid URI for the given screenname
    *
    * @param string $screenname
    * @return string microid URI
    */
    public function microiduri($screenname) {
        return 'irc:' . $screenname;
    }

    /**
     * Send a message to a given screenname
     *
     * @param string $screenname Screenname to send to
     * @param string $body Text to send
     * @return boolean success value
     */
    public function send_message($screenname, $body) {
        $this->fake_irc->doPrivmsg($screenname, $body);
        $this->enqueue_outgoing_raw($this->fake_irc->would_be_sent);
        return true;
    }

    /**
     * Accept a queued input message.
     *
     * @return true if processing completed, false if message should be reprocessed
     */
    public function receive_raw_message($data) {
        $this->handle_incoming($data['sender'], $data['message']);
        return true;
    }

    /**
    * Initialize plugin
    *
    * @return boolean
    */
    public function initialize() {
        if (!isset($this->host)) {
            throw new Exception('must specify a host');
        }
        if (!isset($this->port)) {
            throw new Exception('must specify a port');
        }
        if (!isset($this->username)) {
            throw new Exception('must specify a username');
        }
        if (!isset($this->realname)) {
            throw new Exception('must specify a "real name"');
        }
        if (!isset($this->nick)) {
            throw new Exception('must specify a nickname');
        }

        $this->fake_irc = new Fake_Irc;
        return true;
    }

    /**
     * Get plugin information
     *
     * @param array $versions array to insert information into
     * @return void
     */
    public function onPluginVersion(&$versions) {
        $versions[] = array('name' => 'IRC',
                            'version' => STATUSNET_VERSION,
                            'author' => 'Luke Fitzgerald',
                            'homepage' => 'http://status.net/wiki/Plugin:IRC',
                            'rawdescription' =>
                            _m('The IRC plugin allows users to send and receive notices over an IRC network.'));
        return true;
    }
}

<?php
/**
 * NFTR - Not for the Robots!
 *
 * version 0.2
 *
 * DokuWiki got a built-in method to hide pages. Those pages have a 'noindex'
 * their metadata and they are hidden in feeds and search results but this
 * behaviour is not inherited to sub pages in the same namespace.
 *
 * This plugin provides the possibility to show pages to the user (feeds, search
 * results) but but provide metadata to prevent the indexing of pages and
 * namespaces by search engines. In the HTML header the plugin set the meta
 * element for robots to 'noindex' (<meta name="robots" content="index,follow" />),
 * additionally it sends 'X-Robots-Tag: noindex' via HTTP header.
 *
 * This does not mean that this content is not indexed by any search engines,
 * spiders and robots could ignore all the information. But in generally, their
 * spiders respect it (Google, Yahoo, ... won't index your content if you ask
 * not to do).
 *
 * METADATA
 *
 * @author    Michael Haschke @ eye48.com
 * @copyright 2009 Michael Haschke
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License 2.0 (GPLv2)
 * @version   0.2
 *
 * WEBSITES
 *
 * @link      http://eye48.com/go/nftr Plugin Website and Overview
 *
 * LICENCE
 * 
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation, version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @link      http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License 2.0 (GPLv2)
 *
 * CHANGELOG
 *
 * 0.2
 * - exchange licence b/c CC-BY-SA was incompatible with GPL
 * 0.1
 * - first release under CC-BY-SA
 **/

 
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');
 
class action_plugin_nftr extends DokuWiki_Action_Plugin {

    private $isHidden = null;

    /* -- Methods to manage plugin ------------------------------------------ */

    /**
    * return some info
    */
    function getInfo(){
        return array(
	         'author' => 'Michael Haschke',
	         'email'  => 'haschek@eye48.com',
	         'date'   => '2009-07-06',
	         'name'   => 'NFTR - Not for the Robots!',
	         'desc'   => 'Used to hide some namespaces and wikipages from the search engines
	                      (set robots to "noindex,follow" and send HTTP header
	                      "X-Robots-Tag: noindex") but not from the wiki/user itself
	                      (like the built in hidden function works).',
	         'url'    => 'http://eye48.com/go/nftr'
	         );
    }
 
    /**
    * Register its handlers with the DokuWiki's event controller
    */
    function register(&$controller)
    {
        // alter html/http header for meta data
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE',  $this, 'setNoindex');
        // alter http header for meta data
        $controller->register_hook('ACTION_HEADERS_SEND', 'BEFORE',  $this, 'setNoindex');
    }
 
    /* -- Event handlers ---------------------------------------------------- */

    function setNoindex(&$event)
    {
    
        if ($this->isHidden === null) $this->isHidden();
        if ($this->isHidden === false) return;

        if ($event->name == 'TPL_METAHEADER_OUTPUT')
        {
            $key = array_search(array( 'name'=>'robots', 'content'=>'index,follow'), $event->data['meta']);
            
            if ($key !== false)
                $event->data['meta'][$key] = array( 'name'=>'robots', 'content'=>'noindex,follow');
        }
        
        if ($event->name == 'ACTION_HEADERS_SEND')
        {
            $event->data[] = 'X-Robots-Tag: noindex';
        }
        
    }

    /* -- Helper methods ---------------------------------------------------- */

    function isHidden()
    {
        global $INFO;
        
        $ns = $INFO['namespace'];
        $id = $INFO['id'];
    
        $hidePages = explode(' ', $this->getConf('pages'));
        $hideSpaces = explode(' ', $this->getConf('spaces'));
        
        // wikisite should be hidden
        if (array_search($id, $hidePages) !== false)
        {
            $this->isHidden = true;
            return true;
        }
        
        // namespace should be hidden
        if (array_search($ns, $hideSpaces) !== false)
        {
            $this->isHidden = true;
            return true;
        }
        
        // wikisite is top element of hidden namespace
        if (array_search($id, $hideSpaces) !== false)
        {
            $this->isHidden = true;
            return true;
        }
        
        // namespace or wikisite is subpart of a hidden namespace
        foreach($hideSpaces as $hiddenpart)
        {
            if (strpos($id, $hiddenpart.':') === 0) // subsite
            {
                $this->isHidden = true;
                return true;
            }
            
            if (strpos($ns, $hiddenpart.':') === 0) // subspace
            {
                $this->isHidden = true;
                return true;
            }
        }
        
        // is not hidden
        $this->isHidden = false;
        return false;

    }
}



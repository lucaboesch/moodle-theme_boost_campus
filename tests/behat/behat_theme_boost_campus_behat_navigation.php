<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Navigation steps overrides.
 *
 * @copyright  2019 Luca Bösch <luca.boesch@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/tests/behat/behat_navigation.php');

use Behat\Mink\Exception\ExpectationException as ExpectationException;
use Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

/**
 * Steps definitions to navigate through the navigation tree nodes (overrides).
 *
 * @copyright  2019 Luca Bösch <luca.boesch@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_theme_boost_campus_behat_navigation extends behat_navigation {
    /**
     * Locates the administration menu in the <header> element and returns its xpath
     *
     * @param bool $mustexist if specified throws an exception if menu is not found
     * @return null|string
     */
    protected function find_header_administration_menu($mustexist = false) {
        $menuxpath = '//header[@id=\'page-header\']//div[contains(@class,\'moodle-actionmenu\')]';
        $actionmenupath = '//div[@id=\'region-main-settings-menu\']';

        $nodes = $this->getSession()->getPage()->findAll("xpath", $actionmenupath);
        $counter = count($nodes);
        if ($counter === 1) {
            // There is a activity 'action menu' cog around which basically should be adressed.
            $menuxpath = $actionmenupath;
        }

        if ($mustexist) {
            $exception = new ElementNotFoundException($this->getSession(), 'Page header administration menu is not found');
            $this->find('xpath', $menuxpath, $exception);
        } else if (!$this->getSession()->getPage()->find('xpath', $menuxpath)) {
            return null;
        }
        return $menuxpath;
    }

    /**
     * Finds a page edit cog and select an item from it
     *
     * If the page edit cog is in the page header and the item is not found there, click "More..." link
     * and find the item on the course/frontpage administration page
     *
     * @param array $nodelist
     * @throws ElementNotFoundException
     */
    protected function select_from_administration_menu($nodelist) {
        // Find administration menu.
        if ($menuxpath = $this->find_header_administration_menu()) {
            $isheader = true;
        } else {
            $menuxpath = $this->find_page_administration_menu(true);
            $isheader = false;
        }

        $this->execute('behat_navigation::toggle_page_administration_menu', [$menuxpath]);

        if (!$isheader || count($nodelist) >= 1) {
            $lastnode = end($nodelist);
            $linkname = behat_context_helper::escape($lastnode);
            $link = $this->getSession()->getPage()->find('xpath', $menuxpath . '//a[contains(normalize-space(.), ' . $linkname . ')]');
            if ($link) {
                $this->execute('behat_general::i_click_on', [$link, 'NodeElement']);
                return;
            }
        }

        if ($isheader) {
            // Course administration and Front page administration will have subnodes under "More...".
            $linkname = behat_context_helper::escape(get_string('morenavigationlinks'));
            $link = $this->getSession()->getPage()->find('xpath', $menuxpath . '//a[contains(normalize-space(.), ' . $linkname . ')]');
            if ($link) {
                $this->execute('behat_general::i_click_on', [$link, 'NodeElement']);
                $this->select_on_administration_page($nodelist);
                return;
            }
        }

        throw new ElementNotFoundException($this->getSession(),
            'Link "' . join(' > ', $nodelist) . '" in the current page edit menu"');
    }
}

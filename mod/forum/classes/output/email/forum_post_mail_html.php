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
 * This file contains the definition for the renderable classes for the forum
 *
 *
 * @package    mod_forum
 * @copyright  2015 Nine Lanterns {@link http://ninelanterns.com.au/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\output\email;

defined('MOODLE_INTERNAL') || die();

/**
 * Implements a renderable forum post mail in HTML
 *
 * @package   mod_forum
 * @copyright 2015 Nine Lanterns {@link http://ninelanterns.com.au/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forum_post_mail_html implements \renderable {

    /** @var object $course */
    private $course = null;
    /** @var object $cm */
    private $cm = null;
    /** @var object $forum */
    private $forum = null;
    /** @var object $discussion */
    private $discussion = null;
    /** @var object $post */
    private $post = null;
    /** @var object $userfrom */
    private $userfrom = null;
    /** @var object $userto */
    private $userto = null;
    /** @var string $replyaddress */
    private $replyaddress = null;

    /**
     * Constructor
     * @param object $course
     * @param object $cm
     * @param object $forum
     * @param object $discussion
     * @param object $post
     * @param object $userfrom
     * @param object $userto
     * @param string $replyaddress The inbound address that a user can reply to the generated e-mail with. [Since 2.8].
     */
    public function __construct($course, $cm, $forum, $discussion, $post, $userfrom, $userto, $replyaddress = null) {
        $this->course = $course;
        $this->cm = $cm;
        $this->forum = $forum;
        $this->discussion = $discussion;
        $this->post = $post;
        $this->userfrom = $userfrom;
        $this->userto = $userto;
        $this->replyaddress = $replyaddress;
    }

    /**
     * Setter
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        $this->$name = $value;
    }

    /**
     * Getter
     * @param string $name
     * @return mixed value
     */
    public function __get($name) {
        return $this->$name;
    }
}
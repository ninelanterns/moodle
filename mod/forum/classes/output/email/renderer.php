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
 * This file contains the definition for the renderer class for the forum email
 *
 * @package   mod_forum
 * @copyright 2015 Nine Lanterns {@link http://ninelanterns.com.au/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\output\email;

/**
 * Renderer classes for email
 *
 * @package   mod_forum
 * @copyright 2015 Nine Lanterns {@link http://ninelanterns.com.au/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {

    /**
     * Renderer for forum post mail in plain text
     *
     * @param forum_post_mail_text $mail
     * @return string $posttext the output
     */
    protected function render_forum_post_mail_text(forum_post_mail_text $mail) {
        global $CFG, $USER;

        $modcontext = \context_module::instance($mail->cm->id);

        if (!isset($mail->userto->viewfullnames[$mail->forum->id])) {
            $viewfullnames = has_capability('moodle/site:viewfullnames', $modcontext, $mail->userto->id);
        } else {
            $viewfullnames = $mail->userto->viewfullnames[$mail->forum->id];
        }

        if (!isset($mail->userto->canpost[$mail->discussion->id])) {
            $canreply = forum_user_can_post($mail->forum, $mail->discussion, $mail->userto, $mail->cm, $mail->course, $modcontext);
        } else {
            $canreply = $mail->userto->canpost[$mail->discussion->id];
        }

        $by = new \stdClass();
        $by->name = fullname($mail->userfrom, $viewfullnames);
        $by->date = userdate($mail->post->modified, "", $mail->userto->timezone);

        $strbynameondate = get_string('bynameondate', 'forum', $by);

        $strforums = get_string('forums', 'forum');

        $canunsubscribe = !\mod_forum\subscriptions::is_forcesubscribed($mail->forum);

        $posttext = '';

        if (!$mail->bare) {
            $shortname = format_string($mail->course->shortname, true,
                    array('context' => \context_course::instance($mail->course->id)));
            $posttext  .= "$shortname -> $strforums -> ".format_string($mail->forum->name, true);

            if ($mail->discussion->name != $mail->forum->name) {
                $posttext  .= " -> ".format_string($mail->discussion->name, true);
            }
        }

        // Add absolute file links.
        $mail->post->message = file_rewrite_pluginfile_urls($mail->post->message,
                'pluginfile.php', $modcontext->id, 'mod_forum', 'post', $mail->post->id);

        $posttext .= "\n";
        $posttext .= $CFG->wwwroot.'/mod/forum/discuss.php?d='.$mail->discussion->id;
        $posttext .= "\n---------------------------------------------------------------------\n";
        $posttext .= format_string($mail->post->subject, true);
        if ($mail->bare) {
            $posttext .= " ($CFG->wwwroot/mod/forum/discuss.php?d={$mail->discussion->id}#p{$mail->post->id})";
        }
        $posttext .= "\n".$strbynameondate."\n";
        $posttext .= "---------------------------------------------------------------------\n";
        $posttext .= format_text_email($mail->post->message, $mail->post->messageformat);
        $posttext .= "\n\n";
        $posttext .= forum_print_attachments($mail->post, $mail->cm, "text");

        if (!$mail->bare) {
            if ($canreply) {
                $posttext .= "---------------------------------------------------------------------\n";
                $posttext .= get_string("postmailinfo", "forum", $shortname)."\n";
                $posttext .= "$CFG->wwwroot/mod/forum/post.php?reply={$mail->post->id}\n";
            }

            if ($canunsubscribe) {
                if (\mod_forum\subscriptions::is_subscribed($mail->userto->id, $mail->forum, null, $mail->cm)) {
                    // If subscribed to this forum, offer the unsubscribe link.
                    $posttext .= "\n---------------------------------------------------------------------\n";
                    $posttext .= get_string("unsubscribe", "forum");
                    $posttext .= ": $CFG->wwwroot/mod/forum/subscribe.php?id={$mail->forum->id}\n";
                }
                // Always offer the unsubscribe from discussion link.
                $posttext .= "\n---------------------------------------------------------------------\n";
                $posttext .= get_string("unsubscribediscussion", "forum");
                $posttext .= ": $CFG->wwwroot/mod/forum/subscribe.php?id={$mail->forum->id}&d={$mail->discussion->id}\n";
            }
        }

        $posttext .= "\n---------------------------------------------------------------------\n";
        $posttext .= get_string("digestmailpost", "forum");
        $posttext .= ": {$CFG->wwwroot}/mod/forum/index.php?id={$mail->forum->course}\n";

        if ($mail->replyaddress) {
            $posttext .= "\n\n" . get_string('replytopostbyemail', 'mod_forum');
        }

        return $posttext;
    }

    /**
     * Renderer for forum post mail in html
     *
     * @param forum_post_mail_html $mail
     * @return string $posthtml the output
     */
    protected function render_forum_post_mail_html(forum_post_mail_html $mail) {
        global $CFG;

        if ($mail->userto->mailformat != 1) {  // Needs to be HTML.
            return '';
        }

        if (!isset($mail->userto->canpost[$mail->discussion->id])) {
            $canreply = forum_user_can_post($mail->forum, $mail->discussion, $mail->userto, $mail->cm, $mail->course);
        } else {
            $canreply = $mail->userto->canpost[$mail->discussion->id];
        }

        $strforums = get_string('forums', 'forum');
        $canunsubscribe = ! \mod_forum\subscriptions::is_forcesubscribed($mail->forum);
        $shortname = format_string($mail->course->shortname, true,
                array('context' => \context_course::instance($mail->course->id)));

        $posthtml = '<head>';
        /*    foreach ($CFG->stylesheets as $stylesheet) {
         //TODO: MDL-21120
        $posthtml .= '<link rel="stylesheet" type="text/css" href="'.$stylesheet.'" />'."\n";
        }*/
        $posthtml .= '</head>';
        $posthtml .= "\n<body id=\"email\">\n\n";

        $posthtml .= '<div class="navbar">'.
                '<a target="_blank" href="'.$CFG->wwwroot.'/course/view.php?id='.$mail->course->id.'">'.$shortname.'</a> &raquo; '.
                '<a target="_blank" href="'.$CFG->wwwroot.'/mod/forum/index.php?id='
                        .$mail->course->id.'">'.$strforums.'</a> &raquo; '.
                '<a target="_blank" href="'.$CFG->wwwroot.'/mod/forum/view.php?f='
                        .$mail->forum->id.'">'.format_string($mail->forum->name, true).'</a>';
        if ($mail->discussion->name == $mail->forum->name) {
            $posthtml .= '</div>';
        } else {
            $posthtml .= ' &raquo; <a target="_blank" href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$mail->discussion->id.'">'.
                    format_string($mail->discussion->name, true).'</a></div>';
        }
        $forumposthtml = new \mod_forum\output\email\forum_post_html($mail->course, $mail->cm, $mail->forum,
                $mail->discussion, $mail->post, $mail->userfrom, $mail->userto, false, $canreply, true, false);
        $posthtml .= $this->render($forumposthtml);

        if ($mail->replyaddress) {
            $posthtml .= \html_writer::tag('p', get_string('replytopostbyemail', 'mod_forum'));
        }

        $footerlinks = array();
        if ($canunsubscribe) {
            if (\mod_forum\subscriptions::is_subscribed($mail->userto->id, $mail->forum, null, $mail->cm)) {
                // If subscribed to this forum, offer the unsubscribe link.
                $unsublink = new \moodle_url('/mod/forum/subscribe.php', array('id' => $mail->forum->id));
                $footerlinks[] = \html_writer::link($unsublink, get_string('unsubscribe', 'mod_forum'));
            }
            // Always offer the unsubscribe from discussion link.
            $unsublink = new \moodle_url('/mod/forum/subscribe.php', array(
                            'id' => $mail->forum->id,
                            'd' => $mail->discussion->id,
            ));
            $footerlinks[] = \html_writer::link($unsublink, get_string('unsubscribediscussion', 'mod_forum'));

            $footerlinks[] = '<a href="' . $CFG->wwwroot . '/mod/forum/unsubscribeall.php">'
                    . get_string('unsubscribeall', 'forum') . '</a>';
        }
        $footerlinks[] = "<a href='{$CFG->wwwroot}/mod/forum/index.php?id={$mail->forum->course}'>"
                .get_string('digestmailpost', 'forum') . '</a>';
        $posthtml .= '<hr /><div class="mdl-align unsubscribelink">' . implode('&nbsp;', $footerlinks) . '</div>';

        $posthtml .= '</body>';

        return $posthtml;
    }

    /**
     * Rendering forum post in html, for inclusion in mail or digest
     *
     * @param forum_post_html $mail
     * @return string $output the output
     */
    protected function render_forum_post_html(forum_post_html $mail) {
        global $CFG, $OUTPUT;

        $modcontext = \context_module::instance($mail->cm->id);

        if (!isset($mail->userto->viewfullnames[$mail->forum->id])) {
            $viewfullnames = has_capability('moodle/site:viewfullnames', $modcontext, $mail->userto->id);
        } else {
            $viewfullnames = $mail->userto->viewfullnames[$mail->forum->id];
        }

        // Add absolute file links.
        $mail->post->message = file_rewrite_pluginfile_urls($mail->post->message,
                'pluginfile.php', $modcontext->id, 'mod_forum', 'post', $mail->post->id);

        // Format the post body.
        $options = new \stdClass();
        $options->para = true;
        $formattedtext = format_text($mail->post->message, $mail->post->messageformat, $options, $mail->course->id);

        $output = '<table border="0" cellpadding="3" cellspacing="0" class="forumpost">';

        $output .= '<tr class="header"><td width="35" valign="top" class="picture left">';
        $output .= $OUTPUT->user_picture($mail->userfrom, array('courseid' => $mail->course->id));
        $output .= '</td>';

        if ($mail->post->parent) {
            $output .= '<td class="topic">';
        } else {
            $output .= '<td class="topic starter">';
        }
        $output .= '<div class="subject">'.format_string($mail->post->subject).'</div>';

        $fullname = fullname($mail->userfrom, $viewfullnames);
        $by = new \stdClass();
        $by->name = '<a href="'.$CFG->wwwroot.'/user/view.php?id='
                .$mail->userfrom->id.'&amp;course='.$mail->course->id.'">'.$fullname.'</a>';
        $by->date = userdate($mail->post->modified, '', $mail->userto->timezone);
        $output .= '<div class="author">'.get_string('bynameondate', 'forum', $by).'</div>';

        $output .= '</td></tr>';

        $output .= '<tr><td class="left side" valign="top">';

        if (isset($mail->userfrom->groups)) {
            $groups = $mail->userfrom->groups[$mail->forum->id];
        } else {
            $groups = groups_get_all_groups($mail->course->id, $mail->userfrom->id, $mail->cm->groupingid);
        }

        if ($groups) {
            $output .= print_group_picture($groups, $mail->course->id, false, true, true);
        } else {
            $output .= '&nbsp;';
        }

        $output .= '</td><td class="content">';

        $attachments = forum_print_attachments($mail->post, $mail->cm, 'html');
        if ($attachments !== '') {
            $output .= '<div class="attachments">';
            $output .= $attachments;
            $output .= '</div>';
        }

        $output .= $formattedtext;

        // Commands.
        $commands = array();

        if ($mail->post->parent) {
            $commands[] = '<a target="_blank" href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.
                    $mail->post->discussion.'&amp;parent='.$mail->post->parent.'">'.get_string('parent', 'forum').'</a>';
        }

        if ($mail->reply) {
            $commands[] = '<a target="_blank" href="'.$CFG->wwwroot.'/mod/forum/post.php?reply='.$mail->post->id.'">'.
                    get_string('reply', 'forum').'</a>';
        }

        $output .= '<div class="commands">';
        $output .= implode(' | ', $commands);
        $output .= '</div>';

        // Context link to post if required.
        if ($mail->link) {
            $output .= '<div class="link">';
            $output .= '<a target="_blank" href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='
                    .$mail->post->discussion.'#p'.$mail->post->id.'">'
                    .get_string('postincontext', 'forum').'</a>';

            $output .= '</div>';
        }

        if ($mail->footer) {
            $output .= '<div class="footer">'.$mail->footer.'</div>';
        }
        $output .= '</td></tr></table>'."\n\n";

        return $output;
    }

}

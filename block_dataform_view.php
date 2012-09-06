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
 * @package block
 * $subpackage dataform_view
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 *
 */
class block_dataform_view extends block_base {

    /**
     *
     */
    function init() {
        $this->title = get_string('pluginname','block_dataform_view');            
    }

    /**
     *
     */
    function specialization() {
        global $CFG;
        
        $this->course = $this->page->course;

        // load userdefined title and make sure it's never empty
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname','block_dataform_view');
        } else {
            $this->title = $this->config->title;
        }

        if (empty($this->config->dataform)) {
            if (!empty($this->config->view)) {
                $this->config->view = null;
                $this->config->filter = null;
            }
            return false;
        }

        if (empty($this->config->view)) {
            return false;
        }
    }

    /**
     *
     */
    function instance_allow_multiple() {
        return true;
    }

    /**
     *
     */
    function get_content() {
        global $CFG, $DB, $SITE;

        if (empty($this->config->dataform)) {
            return null;
        }
        
        // For embedded views use cache
        if (!empty($this->config->embed) and !empty($this->content->text)) {
            return $this->content;
        }

        $dataformid = $this->config->dataform;
        $viewid = $this->config->view;
        $filterid = $this->config->filter;
        $course = $this->page->course;

        // validate dataform and reconfigure if needed
        // (we can get here if the dataform has been deleted)
        require_once("$CFG->dirroot/course/lib.php");
        $modinfo = get_fast_modinfo($course);
        if (!empty($modinfo->instances['dataform'][$dataformid])) {
            $found = true;
        } else {
            // check the site
            if ($course->id != $SITE->id) {
                $modinfo = get_fast_modinfo($SITE);
                if (!empty($modinfo->instances['dataform'][$dataformid])) {
                    $found = true;
                }
            }
        }
        
        if (empty($found)) {
            $this->config->dataform = 0;
            $this->config->view = 0;
            $this->config->filter = 0;
            $this->instance_config_commit();

            $this->content->text   = '';
            $this->content->footer = '';
            return $this->content;
        }

        // validate view and reconfigure if needed
        // (we can get here if the view has been deleted)
        if (!$DB->record_exists('dataform_views', array('id' => $viewid, 'dataid' => $dataformid))) {
            // someone deleted the view after configuration
            $this->config->view = 0;
            $this->instance_config_commit();

            $this->content->text   = '';
            $this->content->footer = '';
            return $this->content;
        }

        // validate filter
        if (!empty($filterid) and !$DB->record_exists('dataform_filters', array('id' => $filterid, 'dataid' => $dataformid))) {
            // someone deleted the view after configuration
            $this->config->filter = 0;
            $this->instance_config_commit();
        }

        // content->text or ->footer has to contain something for the _print_block to be called
        $this->content = new object;
        $this->content->text = '';

        // if embed create the container
        if (!empty($this->config->embed)) {
            $dataurl = new moodle_url(
                '/mod/dataform/embed.php',
                array('d' => $dataformid, 'view' => $viewid)
            );
            if (!empty($filterid)) {
                $dataurl->param('filter', $filterid);
            }
            $styles = $this->parse_css_style($this->config->style);
            if (!isset($styles['width'])) {
                $styles['width'] = '100%;';
            }
            if (!isset($styles['height'])) {
                $styles['height'] = '200px;';
            }
            if (!isset($styles['border'])) {
                $styles['border'] = '0;';
            }
            $stylestr = implode('',array_map(function($a, $b){return "$a: $b";}, array_keys($styles), $styles));            

            // The iframe attr
            $params = array('src' => $dataurl, 'style' => $stylestr);
            
            // Add scrolling attr
            if (!empty($styles['overflow']) and $styles['overflow'] == 'hidden;') {
                $params['scrolling'] = 'no';
            }

            $this->content->text = html_writer::tag(
                'iframe',
                null,
                $params  
            );
            return $this->content;
        }

        
        // Set a dataform object with guest autologin
        require_once("$CFG->dirroot/mod/dataform/mod_class.php");
        if ($df = new dataform($dataformid, null, true)) {
            if ($view = $df->get_view_from_id($viewid)) {
                if (!empty($filterid)) {
                    $view->set_filter($filterid);
                }        
                $params = array(
                        'js' => true,
                        'css' => true,
                        'modjs' => true,
                        'completion' => true,
                        'comments' => true,
                        'nologin' => true,
                );        
                $pageoutput = $df->set_page('external', $params);

                $view->set_content();
                $viewcontent = $view->display(array('tohtml' => true));
                $this->content->text = $viewcontent;
            }
            return $this->content;
        }

    }

    /**
     *
     */
    function hide_header() {
        if (isset($this->config->title) and empty($this->config->title)) {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    private function parse_css_style($stylestr) {
        $styles = array();
        if (!empty($stylestr) and $arr = explode(';', $stylestr)) {
            foreach ($arr as $rule) {
                if ($rule = trim($rule) and strpos($rule, ':')) {
                    list($attribute, $value) = array_map('trim', explode(':', $rule));
                    if ($value !== '') {
                        $styles[$attribute] = "$value;";
                    }
                }
            }                
        }    
        return $styles;
    }

}

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

class block_dataform_view_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $DB, $SITE, $CFG, $PAGE;

        // buttons
        //-------------------------------------------------------------------------------
    	$this->add_action_buttons();

        // Fields for editing HTML block title and contents.
        //--------------------------------------------------------------
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        //title
        $mform->addElement('text', 'config_title', get_string('title', 'block_dataform_view'));
        $mform->setDefault('config_title', get_string('pluginname','block_dataform_view'));
        $mform->setType('config_title', PARAM_MULTILANG);

        // dataforms menu
        $courseids = array($SITE->id, $this->block->course->id);
        list($insql, $params) = $DB->get_in_or_equal($courseids);
        if ($dataforms = $DB->get_records_select_menu('dataform', " course $insql ", $params, 'name', 'id,name')) {        
            foreach($dataforms as $key => $value) {
                $dataforms[$key] = strip_tags(format_string($value, true));
            }
            $dataforms = array(0 => get_string('choosedots')) + $dataforms;
        } else {
            $dataforms = array(0 => get_string('nodataforms', 'block_dataform_view'));
        }    
        $mform->addElement('select', 'config_dataform', get_string('selectdataform', 'block_dataform_view'), $dataforms);

        // views menu
        $options = array(0 => get_string('choosedots'));
        $mform->addElement('select', "config_view", get_string('selectview', 'block_dataform_view'), $options);
        $mform->disabledIf("config_view", "config_dataform", 'eq', 0);
        
        // filters menu
        $options = array(0 => get_string('choosedots'));
        $mform->addElement('select', "config_filter", get_string('selectfilter', 'block_dataform_view'), $options);
        $mform->disabledIf("config_filter", "config_dataform", 'eq', 0);

        // embed
        $mform->addElement('selectyesno', "config_embed", get_string('embed', 'dataform'));

        // container style
        $mform->addElement('text', 'config_style', get_string('style', 'block_dataform_view'), array('size'=>'64'));
        $mform->disabledIf("config_style", "config_embed", 'eq', 0);

        // ajax view loading
        $options = array(
            'dffield' => 'config_dataform',
            'viewfield' => 'config_view',
            'filterfield' => 'config_filter',
            'acturl' => "$CFG->wwwroot/mod/dataform/loaddfviews.php"
        );

        $module = array(
            'name' => 'M.mod_dataform_load_views',
            'fullpath' => '/mod/dataform/dataformloadviews.js',
            'requires' => array('base','io','node')
        );

        $PAGE->requires->js_init_call('M.mod_dataform_load_views.init', array($options), false, $module);
    }
    
    /**
     *
     */
    function definition_after_data() {
        global $DB;

        if ($selectedarr = $this->_form->getElement('config_dataform')->getSelected()) {
            $dataformid = reset($selectedarr);
        } else {
            $dataformid = 0;
        }
        
        if ($selectedarr = $this->_form->getElement('config_view')->getSelected()) {
            $viewid = reset($selectedarr);
        } else {
            $viewid = 0;
        }
        
        if ($dataformid) {           
            if ($views = $DB->get_records_menu('dataform_views', array('dataid' => $dataformid), 'name', 'id,name')) {
                $configview = &$this->_form->getElement('config_view');
                foreach($views as $key => $value) {
                    $configview->addOption(strip_tags(format_string($value, true)), $key);
                }
            }
        
            if ($viewid) {           
                if ($filters = $DB->get_records_menu('dataform_filters', array('dataid' => $dataformid), 'name', 'id,name')) {
                    $configfilter = &$this->_form->getElement('config_filter');
                    foreach($filters as $key => $value) {
                        $configfilter->addOption(strip_tags(format_string($value, true)), $key);
                    }
                }
            }
        }
    }
    
    /**
     *
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $errors= array();
        
        if (!empty($data['config_dataform']) and empty($data['config_view'])) {
            $errors['config_view'] = get_string('missingview','block_dataform_view');
        }

        return $errors;
    }
    
}

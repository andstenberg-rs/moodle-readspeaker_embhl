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
 * ReadSpeakers webReader for Moodle block.
 *
 * @package    block_readspeaker_embhl
 * @copyright  2016 ReadSpeaker <info@readspeaker.com>
 * @author     Richard Risholm
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_readspeaker_embhl extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_readspeaker_embhl');

        // Set all ReadSpeaker variables
        $this->plugin_config_language = get_config('readspeaker_embhl', 'lang');
        $this->plugin_config_customerid = get_config('readspeaker_embhl', 'cid');
        $this->plugin_config_readid = get_config('readspeaker_embhl', 'readid');
        $this->plugin_config_version = get_config('readspeaker_embhl', 'webreader_version');

        $this->plugin_config_docreader = get_config('readspeaker_embhl', 'docreaderenabled');
        $this->plugin_config_region = get_config('readspeaker_embhl', 'region');

        $this->plugin_custom_javascriptparams = get_config('readspeaker_embhl', 'customjavascript');
        $this->plugin_custom_params = get_config('readspeaker_embhl', 'customparams');

        $this->plugin_custom_showincontent = get_config('readspeaker_embhl', 'showincontent');

    }

    public function specialization() {

        if (isset($this->config)) {
            if (!empty($this->config->lang)) {
                $this->plugin_config_language = $this->config->lang;
            }
            if (!empty($this->config->customparams)) {
                $this->plugin_custom_params = $this->config->customparams;
            }
        }
    }

    public function get_content() {

        global $CFG;

        if ($this->content !== NULL) {
            return $this->content;
        }

        // Set title text.
        $temp = get_string('titletext_' . $this->plugin_config_language, 'block_readspeaker_embhl');
        $this->title = (!empty($temp)) ? $temp : get_string('titletext_en_us', 'block_readspeaker_embhl');

        // Set Listen button text.
        $temp = get_string('listentext_' . $this->plugin_config_language, 'block_readspeaker_embhl');
        $listen_text = (!empty($temp)) ? $temp : get_string('listentext_en_us', 'block_readspeaker_embhl');

        // Set region.
        $region = $this->plugin_config_region;

        // An encoded version of the page URL.
        $encoded_url = urlencode($this->page->url);

        // Set path for docReader proxy component.
        $docreader_path = $CFG->wwwroot."/blocks/readspeaker_embhl/docreader/proxy.php";
        $this->content       = new stdClass;
        $this->content->text = '';
        // Get the docReader ID
        $docreader_id = $this->plugin_config_docreader ? 'cid: "'.$this->plugin_config_docreader.'", ' : '';

        // Request the JS component.
        $this->page->requires->yui_module('moodle-block_readspeaker_embhl-ReadSpeaker', 'M.block_RS.ReadSpeaker.init');

        // Set the download file name the same as the current page title.
        $audiofile_name = "";
        $current_page_title = $this->page->title;
        if (!empty($current_page_title)) {
            // Prepare the page title:  replace diacritic letters with its regular analogues; convert all spaces to underscores;
            //                          leave only letters, digits, minuses and underscores; put everything in lowercase.
            $audiofile_name = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(" ", "_", trim($this->clear_letters($current_page_title)))));

            // Prepare the parameter to be inserted to the URL query directly.
            $audiofile_name = '&amp;audiofilename=' . $audiofile_name;
        }

        // Check whether the Listen button should be moved to the readid element.
        $show_in_content = $this->plugin_custom_showincontent ? $this->plugin_config_readid : '';
        // Check if user is in editing mode.
        $edit_mode = $this->page->user_is_editing();

        // HTML code for inline settings to webReader.
        $script_code = implode(PHP_EOL, [
            '<script type="text/javascript">',
            '   window.rsConf = {',
            '       general: {',
            '           usePost: true',
            '       },',
            '       moodle: {',
            '           customerid: "'.$this->plugin_config_customerid.'",',
            '           region: "'.$region.'",',
            '           showInContent: "'.$show_in_content.'",',
            '           em: "'.$edit_mode.'"',
            '       }',
            '   };',
            '   window.rsDocReaderConf = {',
            '       ' . $docreader_id .  '',
            '       proxypath: "'.$docreader_path.'",',
            '       lang: "'.$this->plugin_config_language.'"',
            '   };',
            '</script>'
        ]);

        // Determine href to use for Listen button.
        $href = 'https://app-'.$region.
        '.readspeaker.com/cgi-bin/rsent?customerid='.$this->plugin_config_customerid.
        '&amp;lang='.$this->plugin_config_language.
        '&amp;readid='.$this->plugin_config_readid.
        '&amp;url='.$encoded_url.
        $audiofile_name.
        $this->plugin_custom_params;

        // HTML code for Listen button in block.
        $listen_button_code = implode(PHP_EOL, [
            '<div id="readspeaker_button1" class="rs_skip rsbtn rs_preserve rscompact">',
            '   <a accesskey="L" class="rsbtn_play" title="'.$this->title.'" href='.$href.'>',
            '       <span class="rsbtn_left rsimg rspart">',
            '           <span class="rsbtn_text">',
            '               <span>',
            '                   '.$listen_text.'',
            '               </span>',
            '           </span>',
            '       </span>',
            '       <span class="rsbtn_right rsimg rsplay rspart"></span>',
            '   </a>',
            '</div>'
        ]);

        // Populate the block content with the inline script settings and Listen button.
        $this->content->text .= $script_code.$listen_button_code;

        return $this->content;
    }

    public function instance_allow_config() {
        return true;
    }

    public function has_config() {
        return true;
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function applicable_formats() {
        return array('all' => true, 'tag' => false);
    }

    /**
     * Function replaces diacritic letters in the sting with
     * its regular analogues and returns "clear" string.
     *
     * @param string $str
     * @return string
     */
    private function clear_letters($str)
    {
        $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
        $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');
        return str_replace($a, $b, $str);
    }
}

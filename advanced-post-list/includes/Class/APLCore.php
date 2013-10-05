<?php

//TODO create a way to check for multiple categories
// and then unset if any of them are false
// create a ((repeating function))

/**
 * <p><b>Desc:</b> Contains all the main operations/methods for Advanced
 *                 Post List plugin.</p>
 * @package APLCore
 * @since 0.1.0
 * @version 0.2.0
 * @version 0.3.0
 */
class APLCore
{
    //Varibles
    //???MIGHT WANT TO ADD VERSION TO OPTIONS DB

    /**
     * @var string
     * @since 0.1.0
     */
    private $_error;

    /**
     * @since 0.1.0
     * @var string
     */
    private $_errorLog;

    /**
     * @since 0.1.0
     * @var string
     */
    var $plugin_basename;

    /**
     * @since 0.1.0
     * @var string
     */
    var $plugin_dir_path;

    /**
     * @var type 
     * @since 0.1.0
     */
    var $plugin_file_path;

    /**
     * @since 0.1.0
     * @var string
     */
    var $plugin_dir_url;

    /**
     * @since 0.1.0
     * @var string
     */
    var $plugin_file_url;

    /**
     * @since 0.1.0
     * @var string
     */
    var $_APL_OPTION_NAME = "APL_Options";
    
    
    /**
     * <p><b>Desc:</b></p>
     * @access private protected public
     * @param datatype1|datatype2 $paramname description
     * @return datatype1|datatype2 description
     * 
     * @since version/info string [unspecified format]
     * @version versionstring [unspecified format]
     * 
     * @uses file.ext|elementname|class::methodname()|class::$variablename|
     *        functionname()|function functionname description of how the 
     *        element is used
     * 
     * @tutorial 
     * <ol>
     * <li value="1"></li>
     * <li value="2"></li>
     * <li value="3"><b>Steps 4-6</b></li>
     * <li value="4"></li>
     * <li value="5"></li>
     * <li value="6"></li>
     * </ol>
     */
    /**
     * <p><b>Desc:</b> Constructs the core attributes to run ALPCore functions.<p>
     * @access public
     * @param string $file Contains data about the file itself 
     * 
     * @since 0.1.0
     * @version 0.2.0 - Refined how version checking was performed.
     * 
     * @uses $this->APL_load_plugin_data($file)
     * @uses $this->APL_options_load()
     * @uses $this->APL_options_save($APLOptions)
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Set plugin file data/properties</li>
     * <li value="2">Check database version with the current file version</li>
     * <li value="3">Register activation, deactivation, and uninstall hooks 
     *                with WordPress</li>
     * <li value="4">Add Shortcode to WordPress action hooks</li>
     * <li value="5">If the current user has admin rights, do <b>Step 6</b></li>
     * <li value="6">Add APL's menu to WordPress 'admin_menu' action hook and
     *                APL's initial admin action hooks to WordPress 'admin_menu'
     *                action hook.</li>
     * </ol>
     */
    public function __construct($file)
    {
        //STEP 1
        $this->APL_load_plugin_data($file);
        
        //STEP 2
        //////// DATABASE ///////
        $APLOptions = $this->APL_options_load();
        if (isset($APLOptions['version']))
        {
            if ($APLOptions['version'] === '1.0.1')
            {
                $APLOptions['version'] = APL_VERSION;
            }
            
            ////// UPGRADES //////
            //Put upgrade database functions in here. Not before.
            // Ex. APL_upgrade_to_XXX()
            $oldversion = $APLOptions['version'];
            
            //UPGRADE TO 0.3.X
            if (version_compare('0.3.a1', $oldversion, '>'))
            {
                $this->APL_upgrade_to_030();
            }
            
            ////// UPDATE VERSION NUMBER //////
            if (version_compare(APL_VERSION, $oldversion, '>'))
            {
                $APLOptions['version'] = APL_VERSION;
            }
            $this->APL_options_save($APLOptions);
        }
        
        //////// ACTION & FILTERS HOOKS ////////
        //STEP 3
        ////// ACTIVATE/DE-ACTIVATE/UNINSTALL HOOKS //////
        register_activation_hook($this->plugin_file_path,
                                 array(&$this, 'APL_handler_activation'));
        register_deactivation_hook($this->plugin_file_path,
                                   array(&$this, 'APL_handler_deactivation'));
        register_uninstall_hook($this->plugin_file_path,
                                array(&$this, 'APL_handler_uninstall'));

        //add_action('widgets_init', array($this, 'APL_handler_widget_init'));
        //STEP 4
        add_shortcode('post_list',
                      array($this, 'APL_handler_shortcode'));
        //STEP 5
        if (is_admin())
        {
            //STEP 6
            add_action('admin_menu',
                       array($this, 'APL_handler_admin_menu'));
            //STEP 7
            add_action('admin_init',
                       array($this, 'APL_handler_admin_init'));
        }
    }
    /**
     * <p><b>Desc: </b>Updates the database in Wordpress options.</p>
     * @access private
     * 
     * @since 0.3.0
     * 
     * @uses APLCore::APL_options_load()
     * @uses APLCore::APL_options_save()
     * @uses APLPresetDbObj::__construct($db_name)
     * @uses APLPresetDbObj::options_save_db()
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Update admin options to carry jquery ui theme.</li>
     * <li value="2">Get preset database options for update.</li>
     * <li value="3">Foreach preset setting, do <b>Steps 4-6</b></li>
     * <li value="4">Process and set the post parent settings accordingly.</li>
     * <li value="5">Process and set the post type and taxonomy settings 
     *                within the built-in defaults.</li>
     * <li value="6">Set other preset settings.</li>
     * <li value="7">Save Preset Database Options.</li>
     * </ol>
     */
    private function APL_upgrade_to_030()
    {
        // STEP 1
        //////// UPDATE ADMIN OPTIONS ////////
        $APLOptions = $this->APL_options_load();
        $APLOptions['jquery_ui_theme'] = 'overcast';
        $this->APL_options_save($APLOptions);
        
        // Step 2
        //////// UPDATE PRESET DATABASE OPTIONS ////////
        $presetDbObj = new APLPresetDbObj('default');
        $tmp_preset_db = new stdClass();
        // Step 3
        foreach($presetDbObj->_preset_db as $presetObj_name => $presetObj_value)
        {
            // Step 4
            //// SET PARENT SETTING ////
            $tmp_presetObj = new APLPresetObj();
            if ($presetObj_value->_postParent === 'current')
            {
                $tmp_presetObj->_postParent[0] = "-1";
            }
            else if ($presetObj_value->_postParent !== 'None' && $presetObj_value->_postParent !== '')
            {
                $tmp_presetObj->_postParent[0] = $presetObj_value->_postParent;
            }
            
            // Step 5
            //// SET POST TYPES & TAXONOMIES SETTINGS ////
            if ($presetObj_value->_catsSelected !== '')
            {
                
                $tmp_presetObj->_postTax->post->taxonomies->category->require_taxonomy = false;//NEW
                $tmp_presetObj->_postTax->post->taxonomies->category->require_terms = true;
                if ($presetObj_value->_catsRequired === 'false')
                {
                    $tmp_presetObj->_postTax->post->taxonomies->category->require_terms = false;
                }
                $tmp_presetObj->_postTax->post->taxonomies->category->include_terms = true;
                if ($presetObj_value->_catsInclude === 'false')
                {
                    $tmp_presetObj->_postTax->post->taxonomies->category->include_terms = false;
                }
                $terms = explode(',', $presetObj_value->_catsSelected);
                $i = 0;
                foreach ($terms as $term)
                {
                    $tmp_presetObj->_postTax->post->taxonomies->category->terms[$i] = intval($term);
                    $i++;
                }
                unset($tmp_presetObj->_postTax->post->taxonomies->category->terms[($i - 1)]);
            }
            if ($presetObj_value->_tagsSelected !== '')
            {
                
                $tmp_presetObj->_postTax->post->taxonomies->post_tag->require_taxonomy = false;//NEW
                $tmp_presetObj->_postTax->post->taxonomies->post_tag->require_terms = true;
                if ($presetObj_value->_tagsRequired === 'false')
                {
                    $tmp_presetObj->_postTax->post->taxonomies->post_tag->require_terms = false;
                }
                $tmp_presetObj->_postTax->post->taxonomies->post_tag->include_terms = true;
                if ($presetObj_value->_tagsInclude === 'false')
                {
                    $tmp_presetObj->_postTax->post->taxonomies->post_tag->include_terms = false;
                }
                $terms = explode(',', $presetObj_value->_tagsSelected);
                $i = 0;
                foreach ($terms as $term)
                {
                    $tmp_presetObj->_postTax->post->taxonomies->post_tag->terms[$i] = intval($term);
                    $i++;
                }
                unset($tmp_presetObj->_postTax->post->taxonomies->post_tag->terms[($i - 1)]);
            }
            // Step 6
            //// SET THE LIST AMOUNT ////
            $tmp_presetObj->_listAmount = intval($presetObj_value->_listAmount);
            
            //// SET THE ORDER AND ORDERBY SETTINGS ////
            $tmp_presetObj->_listOrder = $presetObj_value->_listOrder;
            $tmp_presetObj->_listOrderBy = $presetObj_value->_listOrderBy;
            
            //// SET THE POST STATUS AS THE DEFAULT ////
            ////  SETTING                           ////
            $tmp_presetObj->_postStatus = 'publish';
            
            //// SET THE EXCLUDE CURRENT POST SETTING ////
            $tmp_presetObj->_postExcludeCurrent = true;
            if ($presetObj_value->_postExcludeCurrent === 'false')
            {
                $tmp_presetObj->_postExcludeCurrent = false;
            }
            
            //// SET THE STYLE (BEFORE/CONTENT/AFTER) //// 
            ////  CONTENT SETTINGS                    ////
            $tmp_presetObj->_before = $presetObj_value->_before;
            $tmp_presetObj->_content = $presetObj_value->_content;
            $tmp_presetObj->_after = $presetObj_value->_after;
            $tmp_preset_db->$presetObj_name = $tmp_presetObj;
        }
        // Step 7
        //// STORE AND SAVE THE PRESET DATABASE OPTIONS ////
        $presetDbObj->_preset_db = $tmp_preset_db;
        $presetDbObj->options_save_db();
    }
    /**
     * <p><b>Desc: </b>Stores all the file (dir/path) values for defining 
     *                  file structure/paths</p>
     *        
     * @access private 
     * @param string $plugin_path __FILE__ variable that is sent from 
     *                             /wp-content/plugins/advanced-post-list/advanced-post-list.php
     * 
     * @since 0.1.0
     * 
     * @tutorial 
     * <ol>
     * <li value="1"><b>$this->plugin_basename</b> = 'advanced-post-list/advanced-post-list.php'</li>
     * <li value="2"><b>$this->plugin_dir_path</b> = 'C:\xampp\htdocs\wordpress/wp-content/plugins/advanced-post-list/'</li>
     * <li value="3"><b>$this->plugin_file_path</b> = 'C:\xampp\htdocs\wordpress/wp-content/plugins/advanced-post-list/advanced-post-list.php'</li>
     * <li value="4"><b>$this->plugin_dir_url</b> = 'http://localhost/wordpress/wp-content/plugins/advanced-post-list/'</li>
     * <li value="5"><b>$this->plugin_file_url</b> = 'http://localhost/wordpress/wp-content/plugins/advanced-post-list/advanced-post-list.php/'</li>
     * </ol>
     */
    private function APL_load_plugin_data($plugin_path)
    {
        // Step 1
        $this->plugin_basename = plugin_basename($plugin_path);
        // Step 2
        $this->plugin_dir_path = trailingslashit(dirname(trailingslashit(WP_PLUGIN_DIR) . $this->plugin_basename));
        // Step 3
        $this->plugin_file_path = trailingslashit(WP_PLUGIN_DIR) . $this->plugin_basename;
        // Step 4
        $this->plugin_dir_url = trailingslashit(plugins_url(dirname($this->plugin_basename)));
        // Step 5
        $this->plugin_file_url = trailingslashit(plugins_url($this->plugin_basename));
    }

    /**
     * <p><b>Desc:</b> Adds plugin action hooks to admin_init for 
     *                 loading up when the user has admin rights.</p>
     * @access public
     * 
     * @since 0.1.0
     * @version 0.2.0 - Added export, import, and save settings 
     *                  ajax functions.
     * @version 0.3.0 - Added wp_enqueue_script & wp_enqueue_style to place them
     *                  in seperate files properly. Also added a theme setting
     *                  to be loaded.
     * 
     * @uses APLCore::APL_options_load()
     * @uses APLCore::APL_options_save($APLOptions)
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Add action hooks for AJAX.</li>
     * <li value="2">De-register scripts and style for a clean register.</li>   
     * <li value="3">Load APLOptions to load selected JQuery UI theme.</li>
     * <li value="4">Register scripts to be enqueued.</li>
     * <li value="5">Register styles</li>
     * </ol>
     */
    public function APL_handler_admin_init()
    {
        //STEP 1
       /**************************************************************
        *************** AJAX ACTION HOOKS **************************** 
        **************************************************************/
        add_action('wp_ajax_APL_handler_save_preset',
                   array($this, 'APL_handler_save_preset'));
        add_action('wp_ajax_APL_handler_delete_preset',
                   array($this, 'APL_handler_delete_preset'));
        add_action('wp_ajax_APL_handler_restore_preset',
                   array($this, 'APL_handler_restore_preset'));

        add_action('wp_ajax_APL_handler_export',
                   array($this, 'APL_handler_export'));
        add_action('wp_ajax_APL_handler_import',
                   array($this, 'APL_handler_import'));

        add_action('wp_ajax_APL_handler_save_settings',
                   array($this, 'APL_handler_save_settings'));

        // Step 2
       /**************************************************************
        *************** REMOVE SCRIPTS & STYLES ********************** 
        **************************************************************/
        wp_deregister_script('jquery');
        wp_deregister_script('apl-admin');
        wp_deregister_script('jquery-ui');
        wp_deregister_script('apl-admin-ui');
        wp_deregister_script('jquery-ui-multiselect');
        
        wp_deregister_style('apl-admin-css');
        wp_deregister_style('apl-admin-ui-css');

        
        // Step 3
        $APLOptions = $this->APL_options_load();
        if (!isset($APLOptions['jquery_ui_theme']))
        {
            $APLOptions['jquery_ui_theme'] = 'overcast';
            $this->APL_options_save($APLOptions);
        }
        
        // Step 4
       /**************************************************************
        *************** REGISTER SCRIPTS ***************************** 
        **************************************************************/
        $script_deps = array();
        wp_register_script('jquery',
                           'https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js',
                           $script_deps,
                           APL_VERSION,
                           false);
        $script_deps = array('jquery');
        wp_register_script('apl-admin',
                           plugins_url() . '/advanced-post-list/includes/js/APL-admin.js',
                           $script_deps,
                           APL_VERSION,
                           false);

        $script_deps = array('jquery');
        wp_register_script('jquery-ui',
                           'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.17/jquery-ui.min.js',
                           $script_deps,
                           APL_VERSION,
                           false);

        $script_deps = array('jquery', 'jquery-ui');
        wp_register_script('apl-admin-ui',
                           plugins_url() . '/advanced-post-list/includes/js/APL-admin_ui.js',
                           $script_deps,
                           APL_VERSION,
                           false);
        $script_deps = array('jquery', 'jquery-ui');
        wp_register_script('jquery-ui-multiselect',
                           plugins_url() . '/advanced-post-list/includes/js/jquery.multiselect.min.js',
                           $script_deps,
                           APL_VERSION,
                           false);
        $script_deps = array('jquery', 'jquery-ui');
        wp_register_script('jquery-ui-multiselect-filter',
                           plugins_url() . '/advanced-post-list/includes/js/jquery.multiselect.filter.min.js',
                           $script_deps,
                           APL_VERSION,
                           false);
        
        // Step 5
       /**************************************************************
        *************** REGISTER STYLES ****************************** 
        **************************************************************/
        wp_enqueue_style('apl-admin-css',
                         plugins_url() . '/advanced-post-list/includes/css/APL-admin.css',
                         false,
                         APL_VERSION,
                         false);
        
        wp_enqueue_style('apl-admin-ui-css',
                         'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.17/themes/' . $APLOptions['jquery_ui_theme'] . '/jquery-ui.css',
                         false,
                         APL_VERSION,
                         false);
        
        wp_enqueue_style('apl-jquery-ui-multiselect-css',
                         plugins_url() . '/advanced-post-list/includes/css/jquery-ui-multiselect-widget.css',
                         false,
                         APL_VERSION,
                         false);
    }
    
    /**
     * <p><b>Desc:</b> Set and saves the plugin's default settings</p>
     * @access private
     * 
     * @since 0.1.0
     * 
     * @uses APLCore::APL_options_set_to_default()
     * @uses APLCore::APL_options_save()
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Set APLOptions to default.</li>
     * <li value="2">Save APLOptions.</li>
     * </ol>
     */
    private function APL_install()
    {
        //STEP 1
        $APLOptions = $this->APL_options_set_to_default();
        //STEP 2
        $this->APL_options_save($APLOptions);
    }

    /**
     * <p><b>Desc:</b> Handles the activation method when the plugin is 
     *                 first activated.</p>
     * @access public
     * 
     * @since 0.1.0
     * @version versionstring [unspecified format]
     * 
     * @uses APLCore::APL_options_load()
     * @uses APLCore::APL_install()
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Load APLOptions.</li>
     * <li value="2">If no options was loaded then install options to be loaded.</li>
     * </ol>
     */
    public function APL_handler_activation()
    {
        // Step 1
        $APLOptions = $this->APL_options_load();
        // Step 2
        if ($APLOptions === false)
            $this->APL_install();
    }

    /**
     * <p><b>Desc:</b> Handles the deactivation method when plugin is 
     *                 deactivated</p></p>
     * @access public
     * 
     * @since 0.1.0
     * @version 0.2.0 - Added delete_option('APL_preset_db-default')
     *                  for deleting preset database data.
     * 
     * @uses APLCore::APL_options_load()
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Load Options from database</li>
     * <li value="2">If user has delete database set to true, then 
     *               delete all options</li>
     * </ol>
     */
    public function APL_handler_deactivation()
    {
        //STEP 1
        $APLOptions = $this->APL_options_load();
        //STEP 2
        if ($APLOptions['delete_core_db'] == 'true')
        {
            delete_option($this->_APL_OPTION_NAME);
            delete_option('APL_preset_db-default');
        }
    }

    /**
     * <p><b>Desc:</b> Handles the uninstall method when plugin is 
     *                 uninstalled.</p>
     * @access public
     * 
     * @since 0.1.0
     * @version 0.2.0 - Changed to delete all plugin data, whether 
     *                  'delete plugin data upon deactivation' is set
     *                  or not.
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Delete APLOptions/Core settings from WordPress.</li>
     * <li value="2">Delete preset database options.</li> 
     * </ol>
     */
    public function APL_handler_uninstall()
    {
        // Step 1
        delete_option($this->_APL_OPTION_NAME);
        // Step 2
        //Alt uninstall that uses the 'delete upon deactivation' setting
        delete_option('APL_preset_db-default');
        
    }

    //// FIX SET TO DEFAULT THEN OVERWRITE AND RETURN
    /**
     * <p><b>Desc:</b>Gets APLOptions from wordpress database and 
     *                 send the option data back if any.</p>
     * @access private
     * @return object|boolean APLOptions or false if options doesn't exist.
     * 
     * @since 0.1.0
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Get APLOptions from WordPress Database or get false if 
     *               options doesn't exist.</li>
     * <li value="2">If Options exists, then return object. Otherwise return 
     *               false.</li>
     * </ol>
     */
    private function APL_options_load()
    {
        // Step 1
        $APLOptions = get_option($this->_APL_OPTION_NAME);
        
        // Step 2
        if ($APLOptions !== false)
            return $APLOptions;
        else
            return false;
    }
    /**
     * <p><b>Desc:</b></p>
     * @access private
     * @param object $APLOptions Option data that holds the core settings.
     * 
     * @since 0.1.0
     * 
     * @uses APLCore::_APL_OPTION_NAME
     * 
     * @tutorial 
     * <ol>
     * <li value="1">If option data (param) exists, save option data to 
     *               WordPress database.</li>
     * </ol>
     */
    private function APL_options_save($APLOptions)
    {
        //STEP 1
        if (isset($APLOptions))
            update_option($this->_APL_OPTION_NAME,
                          $APLOptions);
    }

    //simply returns all our default option values
    /**
     * <p><b>Desc:</b> Sets APLOptions to default values.</p>
     * @access private
     * @return object Option data that contains core settings.
     * 
     * @since 0.1.0
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Set version with constant that is set in initial plugin file.</li>
     * <li value="2">Set preset database names, for now, its set to default.</li>
     * <li value="3">Set delete plugin database to true.</li>
     * <li value="4">Set JQuery UI Theme to over case as a default.</li>
     * <li value="5">Set error to an empty string.</li>
     * <li value="6">Return APLOptions.</li>
     * </ol>
     */
    private function APL_options_set_to_default()
    {
        $APLOptions = array();
        // Step 1
        $APLOptions['version'] = APL_VERSION;
        // Step 2
        $APLOptions['preset_db_names'] = array(0 => 'default');
        // Step 3
        $APLOptions['delete_core_db'] = true;
        // Step 4
        $APLOptions['jquery_ui_theme'] = 'overcast';
        // Step 5
        $APLOptions['error'] = '';

        // Step 6
        return $APLOptions;
    }
    /**
     * <p><b>Desc:</b> Adds the plugins widget to wordpress.</p>
     * @access public
     * 
     * @since 0.1.0
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Register widget.</li>
     * </ol>
     */
    public function APL_handler_widget_init()
    {
        //$widget = new APLWidget();
        //register_widget($widget);
        register_widget('APLWidget');
    }
    /**
     * <p><b>Desc:</b>Adds the plugin's menu links to the WordPress.</p>
     * @access public
     * 
     * @since 0.1.0
     * @todo create APL's own menu once addition pages are available
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Add a submenu to Wordpress settings menu.</li>
     * <li value="2">Add action scripts to that menu page.</li>
     * </ol>
     */
    public function APL_handler_admin_menu()
    {
        //STEP 1
        $APL_admin_page_hook = add_submenu_page('options-general.php',
                                         "Advanced Post List",
                                         "Advanced Post List",
                                         'manage_options',
                                         "advanced-post-list",
                                         array($this, 'APL_admin_page'));
        //STEP 2
        add_action('admin_print_scripts-' . $APL_admin_page_hook,
                   array($this, 'APL_admin_head'));
        //add_filter('contextual_help', 'kalinsPost_contextual_help', 10, 3);
    }
    /**
     * <p><b>Desc:</b> Admin head section that is loaded before the body,
     *                 and carries scripts and styles that are normally loaded
     *                 before the body content.</p>
     * @access public
     * 
     * @since 0.1.0
     * @version 0.3.0 - Added funtions to queue scripts and styles on WordPress
     * 
     * @uses APLCore::APL_get_postTax()
     * @uses APLCore::APL_get_taxonomy_terms()
     * @uses APLCore::APL_get_postTax_ui_parent_selection()
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Add JS files to WordPress script queue list.</li>
     * <li value="2">Add CSS files to WordPress script queue list.</li>
     * <li value="3">Get Preset Database data.</li>
     * <li value="4">Get Post Type and Taxonomy structure.</li>
     * <li value="5">Get Taxonomy and Terms structure.</li>
     * <li value="6">Get Pages for selecting a parent page, and set them
     *               in a hierarchical fashion.</li>
     * <li value="7">Store data in varibles.</li>
     * <li value="8">Send varibles to script.</li>
     * </ol>
     */
    public function APL_admin_head()
    {
        // Step 1
        //////// ADD SCRIPTS TO QUEUE LIST ////////
        wp_enqueue_script('jquery');
        wp_enqueue_script('apl-admin');
        wp_enqueue_script('jquery-ui');
        wp_enqueue_script('apl-admin-ui');
        wp_enqueue_script('jquery-ui-multiselect');
        wp_enqueue_script('jquery-ui-multiselect-filter');

        // Step 2
        //////// ADD STYLES TO QUEUE LIST ////////
        wp_enqueue_style('apl-jquery-ui-multiselect-css');
        wp_enqueue_style('apl-admin-css');
        wp_enqueue_style('apl-admin-ui-css');
        
        // Step 3
        //////// GET AND STORE PLUGIN DATA ////////
        $presetDbObj = new APLPresetDbObj('default');
        // Step 4
        $postTax = $this->APL_get_postTax();
        // Step 5
        $taxTerms = $this->APL_get_taxonomy_terms();
        // Step 6
        $postTax_parent_selector = $this->APL_get_postTax_ui_parent_selection();

        // Step 7
        $apl_admin_settings = array(
            'plugin_url' => APL_URL,
            'savePresetNonce' => wp_create_nonce('APL_handler_save_preset'),
            'deletePresetNonce' => wp_create_nonce('APL_handler_delete_preset'),
            'restorePresetNonce' => wp_create_nonce('APL_handler_restore_preset'),
            'exportNonce' => wp_create_nonce('APL_handler_export'),
            'importNonce' => wp_create_nonce('APL_import'),
            'saveSettingsNonce' => wp_create_nonce('APL_handler_save_settings'),
            'presetDb' => json_encode((array) $presetDbObj->_preset_db),
            'postTax' => $postTax,
            'taxTerms' => $taxTerms
        );
        $apl_admin_ui_settings = array(
            'post_type_amount' => sizeof((array) $post_taxonomies),
            'postTax' => $postTax, //issue may arise
            'postTax_parent_selector' => $postTax_parent_selector
        );
        
        // Step 8
        //////// SEND PLUGIN DATA TO SCRIPTS ////////
        wp_localize_script('apl-admin',
                           'apl_admin_settings',
                           $apl_admin_settings);
        wp_localize_script('apl-admin-ui',
                           'apl_admin_ui_settings',
                           $apl_admin_ui_settings);
    }
    
    /**
     * <p><b>Desc:</b> Sets up and returns the Post Type and Taxonomy structure.</p>
     * @access private
     * @return object Contains the Post Type and Taxonomy structure.
     * 
     * @since 0.3.0
     * 
     * @uses APLCore::APL_get_post_type_taxonomies($post_type_name)
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Get Post Type names contain in WordPress.</li>
     * <li value="2">Remove unwanted Post Type names.</li>
     * <li value="3">Get and setup the Post Type and Taxonomy structure.</li>
     * <li value="4">For each taxonomy contained in a Post Type, get the correct
     *               set of Taxonomies.</li>
     * <li value="5">Return object structure.</li>
     * </ol>
     */
    private function  APL_get_postTax()
    {
        $rtnObj = new stdClass();
        // Step 1
        $post_type_names = get_post_types('',
                                          'names');
        // Step 2
        $skip_post_types = array('attachment', 'revision', 'nav_menu_item');
        foreach($skip_post_types as $value)
        {
            unset($post_type_names[$value]);
        }
        // Step 3
        foreach($post_type_names as $post_type_name)
        {
            $post_type_object = get_post_type_object($post_type_name);
            // Step 4
            $post_type_object->taxonomies = $this->APL_get_post_type_taxonomies($post_type_name);
            
            $rtnObj->$post_type_name = new stdClass();
            $rtnObj->$post_type_name = $post_type_object->taxonomies;
        }

        // Step 5
        return $rtnObj;
        
    }
    /**
     * <p><b>Desc:</b> Gets the proper Taxonomy names within a Post Type.</p>
     * @access private
     * @param string $post_type_name Post Type name.
     * @return array Contains the Taxonomies within the Post Type.
     * 
     * @since 0.3.0
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Get the Taxonomy names contained in WordPress.</li>
     * <li value="2">For each taxonomy, get the Taxonomy object.</li>
     * <li value="3">If the Taxonomy object's Post Type match the Post Type name (param)
     *               given, then store that Taxonomy name.</li>
     * <li value="4">Remove unwanted Taxonomy names.</li>
     * <li value="5">Return array of Taxonomy names.</li>
     * <li value="6"></li>
     * </ol>
     */
    private function APL_get_post_type_taxonomies($post_type_name)
    {
        $rtnTaxonomyArray = array();
        
        // Step 1
        $taxonomy_names = get_taxonomies('', 'names');
        foreach ($taxonomy_names as $taxonomy_name)
        {
            // Step 2
            $taxonomy_object = get_taxonomy($taxonomy_name);
            foreach ($taxonomy_object->object_type as $object_type_name)
            {
                // Step 3
                if ($object_type_name === $post_type_name)
                {
                    $rtnTaxonomyArray[$taxonomy_name] = $taxonomy_name;
                }
            }
        }
        // Step 4
        $skip_taxonomies = array('post_format', 'nav_menu', 'link_category');
        foreach($skip_taxonomies as $value)
        {
            unset($rtnTaxonomyArray[$value]);
        }
        // Step 5
        return $rtnTaxonomyArray;
    }
    /**
     * <p><b>Desc:</b> Alternate function for getting the Taxonomy and Term values.</p>
     * @access private
     * @return object Taxonomies that contain the terms.
     * 
     * @since 0.3.0
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Get Taxonomy names from WordPress.</li>
     * <li value="2">Remove unwanted Taxonomy names.</li>
     * <li value="3">Get Terms for each taxonomy.</li>
     * <li value="4">Return the Taxonomy and Terms structure.</li>
     * </ol>
     */
    private function APL_get_taxonomy_terms()
    {
        $rtnTaxonomyTermsArray = new stdClass();
        
        // Step 1
        $taxonomy_names = get_taxonomies('', 'names');
        
        // Step 2
        $skip_taxonomies = array('post_format', 'nav_menu', 'link_category');
        foreach($skip_taxonomies as $value)
        {
            unset($taxonomy_names[$value]);
        }
        // Step 4
        foreach($taxonomy_names as $taxonomy_name)
        {
            $argTerms = array(
              'hide_empty'  =>  0,
              'taxonomy'    =>  $taxonomy_name  
            );
            $terms = get_categories($argTerms);
            $rtnTaxonomyTermsArray->$taxonomy_name = $terms;
        }
        
        // Step 5
        return $rtnTaxonomyTermsArray;
    }
    /**
     * <p><b>Desc:</b></p>
     * @access private
     * @param datatype1|datatype2 $paramname description
     * @return object description
     * 
     * @since 0.3.0
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Get Post Type names contained in WordPress.</li>
     * <li value="2">Remove unwanted Post Types.</li>
     * <li value="3">Get hierarchical value of the Post Types.</li>
     * <li value="4">Return object.</li>
     * </ol>
     */
    private function APL_get_postTax_ui_parent_selection()
    {
        $rtnObj = new stdClass();
        // Step 1
        $post_type_names = get_post_types('', 'names');
        // Step 2
        $skip_post_types = array('attachment', 'revision', 'nav_menu_item');
        foreach($skip_post_types as $value)
        {
            unset($post_type_names[$value]);
        }
        // Step 3
        foreach($post_type_names as $post_type_name)
        {
            $post_type_object = get_post_type_object($post_type_name);
            $rtnObj->$post_type_name->hierarchical = $post_type_object->hierarchical;
        }
        
        // Step 4
        return $rtnObj;
    }
    /**
     * <p><b>Desc:</b> Adds admin page body content.</p>
     * @access public
     * 
     * @since 0.1.0
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Load admin page.</li>
     * </ol>
     */
    public function APL_admin_page()
    {
        // Step 1
        require_once( $this->plugin_dir_path . 'includes/APL-admin.php');
    }

    //TODO CREATE AN AJAX FUNCTION TO IMPORT DATA TO THE PLUGIN
    // COULDN'T FIND A WAY TO CARRY THE $_FILES GLOBAL VARIBLE
    // THROUGH .post TO TARGET PHP CODE
    /**
     * <p><b>Desc:</b> <b>(<i>Un-used</i>)</b> Handles the AJAX function 
     *                 for importing data. Method used when jQuery.post is 
     *                 called in javascript for $('#frmImport').submit().</p>
     * @access public
     * 
     * @since 0.2.0
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Check wp_create_nonce value.</li>
     * <li value="2"><i>Return data</i> (if any) as a json string.</li>
     * </ol>
     */
    public function APL_handler_import()
    {
        //Step 1
        check_ajax_referer('APL_handler_import');
        //Step 2
        echo json_encode('');
    }
    /**
     * <p><b>Desc:</b></p>
     * @access public
     * 
     * @since 0.2.0
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Check AJAX security value.</li>
     * <li value="2">Store default data.</li>
     * <li value="3">Store the filename and url export file location.</li>
     * <li value="4">Echo that in json string.</li>
     * </ol>
     */
    public function APL_handler_export()
    {
        // Step 1
        $check_ajax_referer = check_ajax_referer("APL_handler_export");

        $rtnData = new stdClass();
        // Step 2
        $rtnData->_ajax_nonce = $_GET['_ajax_nonce'];
        $rtnData->action = $_GET['action'];
        $rtnData->_error = '';
        // Step 3
        $rtnData->filename = $_GET['filename'];
        $rtnData->export_url = APL_URL . 'includes/export.php';
        
        // Step 4
        echo json_encode($rtnData);
    }
    /**
     * <p><b>Desc:</b> Method used for saving APL core 'General Settings'
     *                 to the developer's wordpress database.</p>
     * @access public
     * 
     * @since 0.2.0
     * @version 0.3.0 - Added JQuery UI Theme setting.
     * 
     * @uses APLCore::APL_options_load()
     * @uses APLCore::APL_options_save($APLOptions)
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Check AJAX wp_create_nonce security value.</li>
     * <li value="2">Load APL Options.</li>
     * <li value="3">Store 'delete core db' value.</li>
     * <li value="4">Store Jquery UI Theme value and queue the style.</li>
     * <li value="5">Save APLOptions to database.</li>
     * <li value="6">Set Theme value in return varible.</li>
     * <li value="7">Echo JSON string to jQuery.post param function.</li>
     * </ol>
     */
    public function APL_handler_save_settings()
    {
        // Step 1
        $check_ajax_referer0 = check_ajax_referer("APL_handler_save_settings");
        
        $rtnData = new stdClass();
        $rtnData->error = '';
        $rtnData->theme = 'overcast';
                
        // Step 2
        $APLOptions = $this->APL_options_load();
        
        // Step 3
        $APLOptions['delete_core_db'] = $_POST['deleteDb'];
        // Step 4
        $APLOptions['jquery_ui_theme'] = $_POST['theme'];
        wp_enqueue_style('apl-admin-ui-css',
                         'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.17/themes/' . $APLOptions['jquery_ui_theme'] . '/jquery-ui.css',
                         false,
                         APL_VERSION,
                         false);
        
        // Step 5
        $this->APL_options_save($APLOptions);
        
        // Step 6
        $rtnData->theme = $APLOptions['jquery_ui_theme'];
        
        // Step 7
        echo json_encode($rtnData);
    }
    /**
     * <p><b>Desc:</b> Saves the created preset data from the APL Admin page.</p>
     * @access public
     * 
     * @since 0.1.0
     * @version 0.3.0 - Added custom post type & taxonomy support. Changed post
     *                  parent from one selection to multiple selections, and 
     *                  get other pages from multiple hierarchical post types.
     *                  Along with the Post Status setting.
     * 
     * @uses APLCore::APL_run()
     * @uses APLPresetDbObj::options_save_db()
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Check javascript ajax wp_create_nonce reference.</li>
     * <li value="2">Store preset name.</li>
     * <li value="3">Store preset's post parents, if any.</li>
     * <li value="4">Process and store the Post Type & Taxonomy structure.</li>
     * <li value="5">Store the preset's number of posts.</li>
     * <li value="6">Store the order values.</li>
     * <li value="7">Store the post status.</li>
     * <li value="8">Store the exclude current boolean.</li>
     * <li value="9">Store the Before, Content, & After HTML/JavaScript/Shortcode</li>
                     content.
     * <li value="10">Overwrite or save the preset.</li>
     * <li value="11">Create and store data for the varible returned to the</li>
                      AJAX function.
     * <li value="12">echo the data returned though a json_encode method.</li>
     * </ol>
     */
    public function APL_handler_save_preset()
    {
        // Step 1
        check_ajax_referer("APL_handler_save_preset");
        
        //DEFAULT USE
        $presetDbObj = new APLPresetDbObj('default');
        //MULTI PRESET OPTIONS
        /*
          foreach ($APLOptions['preset_db_names'] as $key => $value)
          {
          $preset_db[$key] = $value;
          }
         */
        
        // Step 2
        $preset_name = stripslashes($_POST['presetName']);

        $presetObj = new APLPresetObj();
        
        // Step 3
        $presetObj->_postParent = json_decode(stripslashes($_POST['postParent']));
        $presetObj->_postParent = array_unique($presetObj->_postParent);
        
        // Step 4
        $presetObj->_postTax = json_decode(stripslashes($_POST['postTax']));
        $tmp_postTax = new stdClass();
        foreach ($presetObj->_postTax as $post_type_name => $post_type_value)
        {
            foreach ($post_type_value->taxonomies as $taxonomy_name => $taxonomy_value)
            {
                $tmp_postTax->$post_type_name->taxonomies->$taxonomy_name->require_taxonomy = $taxonomy_value->require_taxonomy;
                $tmp_postTax->$post_type_name->taxonomies->$taxonomy_name->require_terms = $taxonomy_value->require_terms;
                $tmp_postTax->$post_type_name->taxonomies->$taxonomy_name->include_terms = $taxonomy_value->include_terms;
                foreach ($taxonomy_value->terms as $term_index => $term_value)
                {
                    $tmp_postTax->$post_type_name->taxonomies->$taxonomy_name->terms[$term_index] = intval($term_value);
                }
            }
        }
        $presetObj->_postTax = $tmp_postTax; 
        
        // Step 5
        $presetObj->_listAmount = intval($_POST['numberPosts']); //(int) howmany to display
        
        // Step 6
        $presetObj->_listOrder = $_POST['order']; //(string)
        $presetObj->_listOrderBy = $_POST['orderBy']; //(string)
        
        // Step 7
        $presetObj->_postStatus = $_POST['postStatus']; //(string)
        
//        $presetObj->_ignoreStickyPosts = true; //(boolean)
//        if ($_POST['ignoreStickyPosts'] === 'false')
//        {
//            $presetObj->_ignoreStickyPosts = false;
//        }
        // Step 8
        $presetObj->_postExcludeCurrent = true; //(boolean)
        if ($_POST['excludeCurrent'] === 'false')
        {
            $presetObj->_postExcludeCurrent = false;
        }
        
        // Step 9
        $presetObj->_before = stripslashes($_POST['before']); //(string)
        $presetObj->_content = stripslashes($_POST['content']); //(string)
        $presetObj->_after = stripslashes($_POST['after']); //(string)

        // Step 10
        $presetDbObj->_preset_db->$preset_name = $presetObj;
        $presetDbObj->options_save_db();
        
        // Step 11
        $rtnData = new stdClass();
        $rtnData->status = "success";
        $rtnData->preset_arr = $presetDbObj->_preset_db;
        $rtnData->previewOutput = $this->APL_run($preset_name);

        // Step 12
        echo json_encode($rtnData);
    }
    /**
     * <p><b>Desc:</b> Method handler for deleting presets within
     * the Preset DbOptions.</p>
     * @access public
     * 
     * @since 0.1.0
     * 
     * @uses APLPresetDbObj::options_save_db()
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Check javascript ajax wp_create_nonce reference.</li>
     * <li value="2">Load APL Preset DbOptions.</li>
     * <li value="3">Get postname from page</li>
     * <li value="4">Delete (unset) preset from preset database varible.</li>
     * <li value="5">Save preset database.</li>
     * <li value="6">echo preset database.</li>
     * </ol>
     */
    public function APL_handler_delete_preset()
    {
        //Step 1
        check_ajax_referer("APL_handler_delete_preset");
        //Step 2
        $presetDbObj = new APLPresetDbObj('default');
        //Step 3
        $preset_name = stripslashes($_POST['preset_name']);
        //Step 4
        unset($presetDbObj->_preset_db->$preset_name);
        //Step 5
        $presetDbObj->options_save_db();
        //Step 6
        echo json_encode($presetDbObj->_preset_db);
    }
    /**
     * <p><b>Desc:</b> Method handler for restoring the original plugin
     * preset defaults</p>
     * @access public
     * 
     * @since 0.1.0
     * 
     * @uses APLPresetDbObj::set_to_defaults()
     * @uses APLPresetDbObj::options_save_db()
     * 
     * @tutorial 
     *  <ol>
     * <li value="1">Grab the javascript ajax reference.</li>
     * <li value="2">Get preset options for a temp and a current varible.</li>
     * <li value="3">Set temp to default preset_database_object.</li>
     * <li value="4">Add default presets to current preset_database_object.</li>
     * <li value="5">Save current preset database varible.</li>
     * <li value="6"><i>Echo/Return</i> preset values.</li>
     * </ol>
     */
    public function APL_handler_restore_preset()
    {
        //STEP 1
        check_ajax_referer("APL_handler_restore_preset");
        //STEP 2
        $presetDbObj = new APLPresetDbObj('default');
        $tmpDbObj = new APLPresetDbObj('default');
        //STEP 3
        $tmpDbObj->set_to_defaults();
        //STEP 4
        foreach ($tmpDbObj->_preset_db as $key => $value)
        {
            $presetDbObj->_preset_db->$key = $value;
        }
        //STEP 5
        $presetDbObj->options_save_db();
        //STEP 6
        echo json_encode($presetDbObj->_preset_db);
    }
    /**
     * <p><b>Desc:</b> Method handler for 'post_list' shortcode and 
     * displaying the target post list.</p>
     * @access public
     * @param string $att Carries the preset name.
     * @return string HTML content, if param is set. Otherwise return an empty string.
     * 
     * @since 0.1.0
     * 
     * @uses APLCore::APL_disply()
     * 
     * @tutorial 
     * <ol>
     * <li value="1">If a value is set, do step 2</li>
     * <li value="2"><i>return $this->display($preset_name)</i></li>
     * <li value="3">otherwise <i>return an empty string</i></li>
     * </ol>
     */
    public function APL_handler_shortcode($att)
    {
        //STEP 1
        if (isset($att['name']))
        {
            //STEP 2
            return $this->APL_display($att['name']);
        }
        else
        {
            //STEP 3
            return '';
        }
    }

    /**
     * <p><b>Desc:</b> Public function for custom use.</p> 
     * @param string $preset_name
     * @return string
     * 
     * @since 0.1.0
     * 
     * @tutorial
     * <ol>
     * <li value="1">return APL_run().</li>
     * </ol>
     */
    /**
     * <p><b>Desc:</b> Public funtion for APL_run().</p>
     * @access public
     * @param string $preset_name Contains the name needed to display a preset.
     * @return string HTML content
     * 
     * @since 0.1.0
     * 
     * @uses APLCore::APL_run()
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Return APL_run()</li>
     * </ol>
     */
    public function APL_display($preset_name)
    {
        return $this->APL_run($preset_name);
    }
    /**
     * <p><b>Desc:</b> Method used for executing the main purpose of the
     * plugin. Creates an HTML post list string to be sent to the page
     * that it was called from. What is displayed is determined by the
     * 'post_list name' being used.</p> 
     * @param string $preset_name
     * @return string
     * 
     * @since 0.1.0
     * @version 0.2.0 - Corrected a typo in the if statement 
     *                  for _postExcludeCurrent
     * @tutorial
     * <ol>
     * <li value="1">Get preset database object</li>
     * <li value="2">Set pre-filters</li>
     * <li value="3">Get website's post data</li>
     * <li value="4">If required categories is selected, check for posts that match all
     *     selected categories</li>
     * <li value="5">If required tags is selected, check for posts that match all
     *     selected tags</li>
     * <li value="6">If no posts are remaining, return an empty string</li>
     * <li value="7">Setup output string with before, content,  and after.</li>
     * <li value="8">Add each post data according to shortcode</li>
     * <li value="9"><i>Return output</i> string</li>
     * </ol>
     */
    /**
     * <p><b>Desc:</b> Method used for executing the main purpose of the
     * plugin. Creates an HTML post list string to be sent to the page
     * that it was called from. What is displayed is determined by the
     * 'post_list name' being used.</p>
     * @access private
     * @param string $preset_name
     * @return string 
     * 
     * @since 0.1.0
     * @version 0.2.0 - Corrected a typo in the if statement 
     *                  for _postExcludeCurrent
     * @version 0.3.0 -
     * @todo clean up
     * 
     * @uses APLCore::APL_get_post_attr($postTax)
     * 
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Get Preset Object from Preset Database Object.</li>
     * <li value="2">If there is no preset data. Display a message to the admin
     *               on the current page, or display nothing to the guest/users.</li>
     * <li value="3">Get the current post attribures that may be needed.</li>
     * <li value="4">If the preset Excludes the current post, then store the
     *               Post->ID in an "exclude from list" varible.</li>
     * <li value="5">If Current Post is selected in the Page Parent selector, then
     *               add Post->ID to the list of parent IDs.</li>
     * <li value="6">If Include is selected, then add the post type & taxonomy
     *               structure in areas selected to be included.</li>
     * <li value="7">Query a list of post according to the APL Preset settings.</li>
     * <li value="8">If there are no posts queried, then return an empty string.</li>
     * <li value="9">Setup the output in an HTML format.</li>
     * <li value="10">Return the HTML string.</li>
     * </ol>
     */
    private function APL_run($preset_name)
    {
        
        $preset_db_obj = new APLPresetDbObj('default');
        // Step 1
        if(isset($preset_db_obj->_preset_db->$preset_name))
        {
            $presetObj = new APLPresetObj();
            $presetObj = $preset_db_obj->_preset_db->$preset_name;
        }
        // Step 2
        else if (current_user_can('manage_options'))
        {
            //Alert Message for admins in case the wrong preset data was used
            return '<p>Admin Alert - A problem has occured. A non-existent preset name has been passed use.</p>';
        }
        else
        {
            //Users/Visitors won't be able to see the post list if
            // the name isn't set right
            return'';
        }
        ////////////////////////////////////////////////////////////////////////////
        ////// POST/PAGE FILTER SETTINGS ///////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////
        /*
          What does this do???
          //if we're not showing a list of anything, only show the content,
          // ignore everything else, and apply the shortcodes to the page
          // being currently viewed
          if ($newVals->post_type == "none")
          {
              $output = APLInternalShortcodeReplace($newVals->content, $post, 0);
          }
         */
        // Step 3
        //// GET (GLOBAL) POST DATA OF THE CURRENT POST/PAGE THAT THE
        ////  POST LIST IS DISPLAYED ON.
        $post_obj = $this->APL_get_post_attr($presetObj->_postTax);
        
        // Step 4
        //// EXCLUDE CURRENT POST FROM DISPLAYING ON THE POST LIST
        if ($presetObj->_postExcludeCurrent == 'true')
        {
            $excludeList = $post_obj->ID;
        }
        //TODO
        //$presetObj->before = APLInternalShortcodeReplace($presetObj->before, $post, 0);
        //$presetObj->after = APLInternalShortcodeReplace($presetObj->after, $post, 0);
        // Step 5
        //// ADD THE POST/PAGE TO THE PARENT ARRAY IF CURRENT PAGE
        ////  IS SELECTED.
        $count = count($presetObj->_postParent);
        if (!isset($presetObj->_postParent))
        {
            $presetObj->_postParent = array();
            $count = count($presetObj->_postParent);
        }
        else if ($count > 0)
        {
            foreach($presetObj->_postParent as $index => $value)
            {
                if ($value === "-1")
                {
                    $presetObj->_postParent[$index] = strval($post_obj->ID);
                }
                else
                {
                    $presetObj->_postParent[$index] = $value;
                }
            }
            
            $presetObj->_postParent = array_unique($presetObj->_postParent);
        }
        
        // Step 6
        //// ADD OTHER TAXONOMY TERMS IF INCLUDED IS CHECKED
        $post_obj_post_type = $post_obj->post_type;

        if (isset($presetObj->_postTax->$post_obj_post_type))
        {
            
            $a = $presetObj->_postTax->$post_obj_post_type;
            foreach ($post_obj->taxonomies as $post_taxonomy)
            {
                
                if ($presetObj->postTax->$post_obj_post_type->taxonomies->$post_taxonomy->include == true)
                {
                    $count = count($presetObj->postTax->$post_obj_post_type->taxonomies->$post_taxonomy->terms);
                    foreach ($post_obj->taxonomies->$post_taxonomy as $term)
                    {
                        $presetObj->postTax->$post_obj_post_type->taxonomies->$post_taxonomy->terms[$count] = $term;
                        $count++;
                    }
                    $presetObj->postTax->$post_obj_post_type->taxonomies->$post_taxonomy->terms = array_unique($presetObj->postTax->$post_obj_post_type->taxonomies->$post_taxonomy->terms);
                }
                
            }
            
        }
        
        // Step 7
        //$APL_posts = $this->APL_Query($presetObj);
        $APLQuery = new APLQuery($presetObj);
        
        
        
        
        //STEP 8
        //TODO create a custom message for the dev to use
        //return nothing if no results
        if (count($APLQuery->_posts) == 0)
        {
            return "";
        }
        //STEP 9
////// BEFORE //////////////////////////////////////////////////////////////////
        $output = $presetObj->_before;

///// CONTENT //////////////////////////////////////////////////////////////////
        $count = 0;
        foreach ($APLQuery->_posts as $APL_post)
        {
            //STEP 8
            $output = $output . APLInternalShortcodeReplace($presetObj->_content,
                                                            $APL_post,
                                                            $count);
            $count++;
        }

        $finalPos = strrpos($output,
                            "[final_end]");
        //if ending exists (the last item where we don't want to add any 
        // more commas or ending brackets or whatever)
        if ($finalPos > 0)
        {
            //Cut everything off at the final position of {final_end}
            $output = substr($output,
                             0,
                             $finalPos);
            //Replace all the other instances of {final_end}, 
            // since we only care about the last one
            $output = str_replace("[final_end]",
                                  "",
                                  $output);
        }

////// AFTER ///////////////////////////////////////////////////////////////////
        $output = $output . $presetObj->_after;

        //STEP 10
        return $output;


    }
    /**
     * <p><b>Desc:</b> Get the post values needed for the plugin's </p>
     * @access private
     * @param object $postTax Post Type & Taxonomy structure.
     * @return object 
     * 
     * @since 0.3.0
     * 
     * @uses APLCore::APL_get_post_type_taxonomies($post_type_name)
     * 
     * @tutorial 
     * <ol>
     * <li value="1">Get Global post, current post.</li>
     * <li value="2">Store current post's ID.</li>
     * <li value="3">Store current post's post_type</li>
     * <li value="4">Get Post Type's Taxonomies.</li>
     * <li value="5">Get Taxonomy's Terms and store them accordingly.</li>
     * <li value="6">Return the data stored.</li>
     * </ol>
     */
    private function APL_get_post_attr($postTax)
    {
        $rtnObj = new stdClass();
        // Step 1
        global $post;
        
        // Step 2
        $rtnObj->ID = $post->ID;
        // Step 3
        $rtnObj->post_type = $post->post_type;
        // Step 4
        $taxonomies = $this->APL_get_post_type_taxonomies($rtnObj->post_type);
        foreach ($taxonomies as $taxonomy)
        {
            // Step 5
            $terms  = wp_get_post_terms($post->ID, $taxonomy);
            if (!empty($terms))
            {
                $rtnObj->taxonomies->$taxonomy = $terms;
            }
        }
        // Step 6
        return $rtnObj;
    }
}
/**
 * <p><b>Desc:</b> (Internal) plugin shortcode function for individual posts.</p>
 * @access public
 * @param string $str 
 * @param object $page WordPress post.
 * @param int $count 
 * @return string
 * 
 * @since 0.1.0
 * @todo fix all known issues and seperate this code into a different file and
 *       encapsulate it.
 * 
 * @tutorial 
 * <ol>
 * <li value="1"></li>
 * <li value="2"></li>
 * <li value="3"></li>
 * </ol>
 */
function APLInternalShortcodeReplace($str,
                                     $page,
                                     $count)
{
    //not much left of this array, since there's so little post data that I can still just grab unmodified
    $SCList = array("[ID]", "[post_name]", "[guid]", "[post_content]", "[comment_count]");

    $l = count($SCList);
    for ($i = 0;
            $i < $l;
            $i++)
    {//loop through all possible shortcodes
        $scName = substr($SCList[$i],
                         1,
                         count($SCList[$i]) - 2);
        $str = str_replace($SCList[$i],
                           $page->$scName,
                           $str);
    }
    //post_author requires an extra function call to convert the
    // userID into a name so we can't do it in the loop above
    $str = str_replace("[post_author]",
                       get_userdata($page->post_author)->user_login,
                                    $str);
    $str = str_replace("[post_permalink]",
                       get_permalink($page->ID),
                                     $str);
    $str = str_replace("[post_title]",
                       htmlspecialchars($page->post_title),
                                        $str);

    $postCallback = new APLCallback();
    $postCallback->itemCount = $count;
    $postCallback->page = $page;

    $str = preg_replace_callback('#\[ *item_number *(offset=[\'|\"]([^\'\"]*)[\'|\"])? *(increment=[\'|\"]([^\'\"]*)[\'|\"])? *\]#',
                                 array(&$postCallback, 'postCountCallback'),
                                 $str);

////// POST DATA & MODIFIED ////////////////////////////////////////////////////
    $postCallback->curDate = $page->post_date; //change the curDate param and run the regex replace for each type of date/time shortcode
    $str = preg_replace_callback('#\[ *post_date *(format=[\'|\"]([^\'\"]*)[\'|\"])? *\]#',
                                 array(&$postCallback, 'postDateCallback'),
                                 $str);
    $postCallback->curDate = $page->post_date_gmt;
    $str = preg_replace_callback('#\[ *post_date_gmt *(format=[\'|\"]([^\'\"]*)[\'|\"])? *\]#',
                                 array(&$postCallback, 'postDateCallback'),
                                 $str);
    $postCallback->curDate = $page->post_modified;
    $str = preg_replace_callback('#\[ *post_modified *(format=[\'|\"]([^\'\"]*)[\'|\"])? *\]#',
                                 array(&$postCallback, 'postDateCallback'),
                                 $str);
    $postCallback->curDate = $page->post_modified_gmt;
    $str = preg_replace_callback('#\[ *post_modified_gmt *(format=[\'|\"]([^\'\"]*)[\'|\"])? *\]#',
                                 array(&$postCallback, 'postDateCallback'),
                                 $str);

    if (preg_match('#\[ *post_excerpt *(length=[\'|\"]([^\'\"]*)[\'|\"])? *\]#',
                   $str))
    {

        $str = preg_replace_callback('#\[ *post_excerpt *(length=[\'|\"]([^\'\"]*)[\'|\"])? *\]#',
                                     array(&$postCallback, 'postExcerptCallback'),
                                     $str);

        /* if($page->post_excerpt == ""){//if there's no excerpt applied to the post, extract one
          //$postCallback->pageContent = strip_tags($page->post_content);
          $str = preg_replace_callback('#\[ *post_excerpt *(length=[\'|\"]([^\'\"]*)[\'|\"])? *\]#', array(&$postCallback, 'postExcerptCallback'), $str);
          }else{//if there is a post excerpt just use it and don't generate our own
          $str = preg_replace('#\[ *post_excerpt *(length=[\'|\"]([^\'\"]*)[\'|\"])? *\]#', $page->post_excerpt, $str);
          } */
    }

    $postCallback->post_type = $page->post_type;
    $postCallback->post_id = $page->ID;
    $str = preg_replace_callback('#\[ *post_pdf *\]#',
                                 array(&$postCallback, 'postPDFCallback'),
                                 $str);

    if (current_theme_supports('post-thumbnails'))
    {
        $arr = wp_get_attachment_image_src(get_post_thumbnail_id($page->ID),
                                                                 'single-post-thumbnail');
        $str = str_replace("[post_thumb]",
                           $arr[0],
                           $str);
    }

    $postCallback->page = $page;
    $str = preg_replace_callback('#\[ *post_meta *(name=[\'|\"]([^\'\"]*)[\'|\"])? *\]#',
                                 array(&$postCallback, 'postMetaCallback'),
                                 $str);
    $str = preg_replace_callback('#\[ *post_categories *(delimeter=[\'|\"]([^\'\"]*)[\'|\"])? *(links=[\'|\"]([^\'\"]*)[\'|\"])? *\]#',
                                 array(&$postCallback, 'postCategoriesCallback'),
                                 $str);
    $str = preg_replace_callback('#\[ *post_tags *(delimeter=[\'|\"]([^\'\"]*)[\'|\"])? *(links=[\'|\"]([^\'\"]*)[\'|\"])? *\]#',
                                 array(&$postCallback, 'postTagsCallback'),
                                 $str);
    $str = preg_replace_callback('#\[ *post_comments *(before=[\'|\"]([^\'\"]*)[\'|\"])? *(after=[\'|\"]([^\'\"]*)[\'|\"])? *\]#',
                                 array(&$postCallback, 'commentCallback'),
                                 $str);
    $str = preg_replace_callback('#\[ *post_parent *(link=[\'|\"]([^\'\"]*)[\'|\"])? *\]#',
                                 array(&$postCallback, 'postParentCallback'),
                                 $str);

    $str = preg_replace_callback('#\[ *php_function *(name=[\'|\"]([^\'\"]*)[\'|\"])? *(param=[\'|\"]([^\'\"]*)[\'|\"])? *\]#',
                                 array(&$postCallback, 'functionCallback'),
                                 $str);

    return $str;
}

?>

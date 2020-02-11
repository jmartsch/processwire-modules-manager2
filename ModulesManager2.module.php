<?php namespace ProcessWire;

/**
 * Class ProcessModuleInstall
 * Provides methods for internative module installation for ProcessModule
 *
 * extended version for AdminThemeUikit and ProcessWire 3+ by Jens Martsch - dotnetic GmbH
 * completely rethought and improved the idea of ProcessWire Modules Manager created 2012 by Soma
 *
 * @author Jens Martsch
 *
 * @TODO add filter to show only modules that are compatible with the actual PW version
 * @TODO sometimes wrong module version of installed core modules seem to be returned. for example for markup-htmlpurifier. This is not a problem with my modules but the core modules report incorrect version numbers.
 * @TODO exclude versions from core modules because of this error
 * @TODO Allow "search for module" to search in the description also, so a module can be found by its purpose and not only by its name
 * @TODO When clicking on a category under "more information", then filter by that category, instead of redirecting to the ProcessWire site
 * @TODO when clicking on an author show all modules of this author
 * @TODO add delete button, to remove files from disk
 * @TODO trigger reload of modules array, when someone opened the settings page after installing, and uninstalled the module via the checkbox
 * @TODO add function to delete the files of an uninstalled module
 * @TODO use modal for deleting files
 * @TODO generally use modals instead of panels? yes, I think thats a good idea :)
 * @TODO Display icons of installed modules
 * @TODO make filters work for installed, uninstalled, updateable, etc.
 * @TODO add button to reload modules from modules.processwire.com
 * @TODO save actual state as one word in the module status property, instead of HTML
 * @TODO add "Add module from URL" field
 * @TODO append version string to script to invalidate cache on new version
 * @TODO add multilanguage for vue
 * @TODO hook into search results to link to ModulesManager2 instead of default ProcessModule
 * @TODO make delete work if there are modules that have requires, for example continents-and-countries
 * @TODO list modules that are not installed, which are installed by other modules (they come as a package) like MarkupActivityLogService or FieldtypeContinentsAndCountries
 * @TODO change behaviour to just update the modulesArray if multiple modules are modified (installed, uninstalled) instead of reloading the whole module data
 * @TODO only load module data for the selected category or module as the getData object is already 1MB (not a problem when cached)
 * @TODO add method to vue and here to get the module data for one or more modules (instead of all modules), to update the status, actions, module info via AJAX
 *
 * Filter examples
 * https://codepen.io/jmar/pen/dxbrLQ?editors=1010 single select
 * https://codepen.io/jmar/pen/rXBPxb?editors=1000 vue-multiselect
 */
class ModulesManager2 extends Process implements ConfigurableModule
{

    protected static $defaults = array(
        'apikey' => 'pw223',
        'remoteurl' => 'http://modules.processwire.com/export-json/',
        'limit' => 400,
        'max_redirects' => 3,
    );

    // uninstallable
    protected $exclude_categories = array(
        'language-pack' => 'Language Packs',
        'site-profile' => 'Site Profiles',
        'premium' => 'Premium Modules',
    );

    protected $modulesArray = array();
    protected $modulesRemoteArray = array();
    protected $labels;
    protected $markup;
    protected $moduleServiceUrl;
    protected $moduleServiceParameters;
    protected $allModules;
    protected $uninstalledModules;
    protected $useBuiltScript;

    protected $reloadScript = "<script>var event = new Event('loadData');window.parent.window.dispatchEvent(event);</script>";

    /**
     * getModuleInfo is a module required by all modules to tell ProcessWire about them
     *
     * @return array
     *
     */
    public static function getModuleInfo()
    {
        return array(
            'title' => 'Modules Manager 2',
            'version' => "2.0.0",
            'summary' => 'Download, update, install and configure modules.',
            'icon' => 'plug',
            'href' => '/',
            'author' => "Jens Martsch, dotnetic GmbH",
            'singular' => true,
            'autoload' => false,
            'permanent' => true,
            'requires' => 'ProcessWire>=3.0.0, PHP>=6.4.3',
            'permission' => 'module-admin',
            'useNavJSON' => true,
            'nav' => array(
                array(
                    'url' => '?reset=1',
                    'label' => 'Refresh',
                    'icon' => 'refresh',
                ),
            ),
        );
    }

    public function __construct()
    {
        $this->labels['download'] = $this->_('Download');
        if ($this->input->get->update) {
            $this->labels['download_install'] = $this->_('Download and Update');
        } else {
            $this->labels['download_install'] = $this->_('Download and Install');
        }
        $this->labels['get_module_info'] = $this->_('Get Module Info');
        $this->labels['module_information'] = $this->_x("Module Information", 'edit');
        $this->labels['download_now'] = $this->_('Download Now');
        $this->labels['download_dir'] = $this->_('Add Module From Directory');
        $this->labels['upload'] = $this->_('Upload');
        $this->labels['upload_zip'] = $this->_('Add Module From Upload');
        $this->labels['download_zip'] = $this->_('Add Module From URL');
        $this->labels['check_new'] = $this->_('Check for New Modules');
        $this->labels['installed_date'] = $this->_('Installed');
        $this->labels['requires'] = $this->_x("Requires", 'list'); // Label that precedes list of required prerequisite modules
        $this->labels['installs'] = $this->_x("Also Installs", 'list'); // Label that precedes list of other modules a given one installs
        $this->labels['reset'] = $this->_('Refresh');
        $this->labels['core'] = $this->_('Core');
        $this->labels['uninstall'] = $this->_('Uninstall');
        $this->labels['site'] = $this->_('Site');
        $this->labels['configure'] = $this->_('Configure');
        $this->labels['install_btn'] = $this->_x('Install', 'button'); // Label for Install button
        $this->labels['install'] = $this->_('Install'); // Label for Install tab
        $this->labels['cancel'] = $this->_('Cancel'); // Label for Cancel button

        if ($this->wire('languages') && !$this->wire('user')->language->isDefault()) {
            // Use previous translations when new labels aren't available (can be removed in PW 2.6+ when language packs assumed updated)
            if ($this->labels['install'] == 'Install') {
                $this->labels['install'] = $this->labels['install_btn'];
            }

            if ($this->labels['reset'] == 'Refresh') {
                $this->labels['reset'] = $this->labels['check_new'];
            }

        }
        $this->moduleServiceParameters = "?apikey=" . $this->config->moduleServiceKey . "&limit=400";
        $this->moduleServiceUrl = $this->config->moduleServiceURL . $this->moduleServiceParameters;
        $this->allModules = array();
    }

    /**
     * get the config either default or overwritten by user config
     * @param string $key name of the option
     * @return mixed      return requested option value
     */
    public function getConfig($key)
    {
        return ($this->get($key)) ? $this->get($key) : self::$defaults[$key];
    }

    /**
     * this method is called when ProcessWire is read and loaded the module
     * used here to get scripts and css files loaded automatically
     */
    public function init()
    {
        parent::init();

//        $this->wire('processBrowserTitle', $title);
        $this->modal = "&modal=1";
        $this->wire('processHeadline', "ModulesManager2" . $this->getModuleInfo()['version'] . "beta");
        $this->wire('processHeadline', "ModulesManager 2 beta");

        $this->modules->get('JqueryUI')->use('vex');

        $this->labelRequires = $this->_x("Requires", 'list'); // Label that precedes list of required prerequisite modules
        $this->labelInstalls = $this->_x("Also installs", 'list'); // Label that precedes list of required prerequisite modules

        // get current installed modules in PW and store it in array
        // for later use to generate

        foreach ($this->modules as $module) {
            $this->modulesArray[$module->className()] = 1;
        }
        foreach ($this->modules->getInstallable() as $module) {
            $this->modulesArray[basename(basename($module, '.php'), '.module')] = 0;
        }
        ksort($this->modulesArray);

//        foreach ($this->modules as $module) {
//            $this->modulesArray[$module->className()] = 1;
//            wire('modules')->getModuleInfo($module->className());
//        }
//        // get current uninstalled modules with flag 0
//        foreach ($this->modules->getInstallable() as $module) {
//            $class_name = basename($module, '.php');
//            $class_name = basename($module, '.module');
//            $this->modulesArray[$class_name] = 0;
////            wire('modules')->getModuleInfo($class_name); // fixes problems
//        }
    }

    /**
     * Display the default admin screen with module list
     *
     * @return string output html string
     */
    public function execute()
    {

        // check if we have the rights to download files from other domains
        // using copy or file_get_contents
        if (!ini_get('allow_url_fopen')) {
            $this->error($this->_('The php config `allow_url_fopen` is disabled on the server, modules cannot be downloaded through Modules Manager. Enable it or ask your hosting support then try again.'));
        }

        // check if directories are writeable
        if (!is_writable($this->config->paths->assets)) {
            $this->error($this->_('Make sure your /site/assets directory is writable by PHP.'));
        }
        if (!is_writable($this->config->paths->siteModules)) {
            $this->error($this->_('Make sure your /site/modules directory is writable by PHP.'));
        }

        // reset cache to scan for new modules downloaded, manually
        // put into site modules folder and download current JSON feed
        if ($this->input->get->reset) {
            // reset PW modules cache
            $this->modules->resetCache();
            // json feed download and cache
            $this->createCache();
            // reload page without params
            //            $this->session->redirect('./');
        }

        // output javascript config vars used by ModulesManager.js
        //        $this->config->js("process_modulesmanager", $this->pages->get("parent=22,name=modulesmanager")->url . "getdata/");
        //        $this->config->js("process_modulesmanager_filter_cat", $this->input->get->cat);

        $this->prepareData();
        $count = 4;
        $this->modules_found = '<p>' . sprintf($this->_("%d modules found in this category on modules.processwire.com"), $count) . '</p>';
        //$pretext .= 'ProcessWire Version ' . $this->config->version;
        $info = $this->getModuleInfo();

//        $moduleOverview = $this->createModuleOverview();


        $this->useBuiltScript = true;
        if ($this->useBuiltScript === true) {
            // add this if using vue-cli-tools.
            // this adds the needed scripts like vue, v-select and vuetify and the corresponding CSS
//            bd('use the built script from vue-cli-tools');
            $this->config->styles->add($this->config->urls->siteModules . $this->className . '/dist/css/chunk-vendors.css');
            $this->config->styles->add($this->config->urls->siteModules . $this->className . '/dist/css/main.css');
//            $markup = <a href="./install?class=AdminOnSteroids" class="pw-panel pw-panel-reload">Testlink </a>"
            $markup = '<div id="app"></div>';
            $scriptPath = $this->config->urls->siteModules . $this->className;
            $markup .= "<script>let mode='embedded';</script>";
            $markup .= "<script src='$scriptPath/dist/js/chunk-vendors.js'></script>";
            $markup .= "<script src='$scriptPath/dist/js/main.js'></script>";
//            return $markup;
        }

        $button = $this->modules->get("InputfieldButton");
//        $button->showInHeader();
        $button->setSecondary();
        $button->href = "{$this->page->url}?reset=1";
        $button->icon = 'refresh';
        $button->value = "Refresh module list";

//        $moduleOverview = $button->render() . $moduleOverview;

        return $markup . '<p>Modules Manager v' . $info['version'] . '</p>';
    }

    protected function prepareData()
    {
        // get module feed cache,
        // if not yet cached download and cache it
        $this->modulesRemoteArray = $this->readCache();
        $count = 0;
        $this->all_categories = array();

        // loop the module list we got from the json feed and we do
        // various checks here to see if it's up to date or installed
        foreach ($this->modulesRemoteArray as $key => $module) {
            $categories = array();
            foreach ($module->categories as $cat) {
                $categories[$cat->name] = $cat->title;
            }
            $this->all_categories = array_merge($this->all_categories, $categories);
            $filterout = false;

            // filter for selected category
            if (isset(wire('input')->get->cat)) {
                $selected_cat = wire('input')->get->cat;
                if ($selected_cat) {
                    if (!array_key_exists(wire('input')->get->cat, $categories)) {
                        $filterout = true;
                    }
                }
            }

            if (!$filterout) {
                $count++;
            }
        }
    }

    /**
     * Provides a method to get data
     * @return json with all modules data
     */
    public function executeGetData()
    {
        $this->modules->resetCache();

        // get json module feed cache file,
        // if not yet cached download and cache it
        $this->modulesRemoteArray = $this->readCache();

        // get current installed modules in PW and store it in array
        // for later use to generate
        foreach ($this->modules as $module) {
            $this->modulesArray[$module->className()] = 1;
            wire('modules')->getModuleInfo($module->className()); // fixes problems
        }
        // get current uninstalled modules with flag 0
        foreach ($this->modules->getInstallable() as $module) {
            $this->modulesArray[basename($module, '.module')] = 0;
            wire('modules')->getModuleInfo(basename($module, '.module')); // fixes problems
        }

        $out = [];
        $count = 0;
        $this->all_categories = array();

        // loop the module list we got from the json feed and we do
        // various checks here to see if it's up to date or installed
        foreach ($this->modulesRemoteArray as $key => $module) {

            $categories = array();

            foreach ($module->categories as $cat) {
                $categories[$cat->name] = $cat->title;
            }

            //$all_categories = array_merge($all_categories, $categories);
            $this->all_categories = array_merge($this->all_categories, $categories);

            // exclude modules
            $filterout = false;

            $uninstallable = false;
            // filter out unwanted categories
            foreach ($this->exclude_categories as $k => $exc) {
                if (array_key_exists($k, $categories)) {
                    $module->uninstallable = true;
                    break;
                }
            }

            // filter for selected category
            if (isset(wire('input')->get->cat)) {
                $selected_cat = wire('input')->get->cat;
                if ($selected_cat) {
                    if (!array_key_exists(wire('input')->get->cat, $categories)) {
                        $filterout = true;
                    }
                }
            }

            // if filtered out no need to go any further in the loop
            if ($filterout) {
                continue;
            }


            $count++;
            // $module = (array)$module;
            $result = $this->createItemRow($module);
            array_push($out, $result);
            array_push($this->allModules, $result);

        }
//        header("Access-Control-Allow-Origin: *");
        if ($this->config->ajax) {
            header('Content-Type: application/json,charset=utf-8');
            return wireEncodeJSON($out);
        }


    }

    public function executeGetCategories()
    {
        $this->prepareData();

        $categoriesJSON = array();
        $categoriesJSON[] = ["name" => 'showall', "title" => 'Show all (takes some seconds)'];
        // @todo show number of modules in each category
        foreach ($this->all_categories as $key => $cat) {
            $categoriesJSON[] = ["name" => $key, "title" => $cat];
        }
        if ($this->config->ajax) {
            header('Content-Type: application/json,charset=utf-8');
            return wireEncodeJSON($categoriesJSON);
        }
    }

    public function createItemRow($item)
    {

        $remote_version = $this->modules->formatVersion($item->module_version);

        $item->status = [];
        $item->version = '-';
        $item->actions = [];
        $item->dependencies = '';

        $info = "";

        if (array_key_exists($item->class_name, $this->modulesArray)) {

            // get module infos, we can't use modules->get(module_name) here
            // as it would install the module, which we don't want at all
            $info = wire('modules')->getModuleInfo($item->class_name);
            $this->local_version = $this->modules->formatVersion($info['version']);

            if ($this->modulesArray[$item->class_name] == null) {
                $requires = array();

                if (count($info['requires'])) {
                    $requires = $this->modules->getRequiresForInstall($item->class_name);
                    if (count($requires)) {
                        $item->dependencies .= "<span class='notes requires'>$this->labelRequires - " . implode(', ', $requires) . "</span>";
                    }
                }

                if (count($info['installs'])) {
                    $item->dependencies .= "<span class='detail installs'>$this->labelInstalls - " . implode(', ', $info['installs']) . "</span>";
                }
                if ($info['installed'] === false) {
                    $item->status = '<span class="uk-text-muted">' . $this->_('downloaded but not installed') . ': ' . $this->local_version . '</span>';
                    $item->status = 'downloaded';
                }

            } else {
                if ($remote_version > $this->local_version) {
                    $item->status = '<span class="">' . $this->_('installed') . ': ' . $this->local_version . '</span> |';
                    $item->status .= '<span class="">' . $this->_("update to v $remote_version available!") . '</span>';
                    $item->remote_version = $remote_version;
                } else {
                    $item->status = 'installed';
                }
            }

        } else {
            $item->theme = isset($categories['admin-theme']) ? '&theme=1' : '';
        }

        $item->actions = $this->getActions($item, $info);

        $categories = array();
        foreach ($item->categories as $category) {
            $categories[] = $category->title;
        }

        // $item->categories = implode(", ", $categories);

        $authors = array();
        foreach ($item->authors as $auth) {
            $authors[] = $auth->title;
        }

        // bd($item->actions, $item->name);

//        if ($authors) $item->authors = implode(", ", $authors);
        $item->created = date("Y/m/d", $item->created);
        $item->modified = date("Y/m/d", $item->modified);
        return (array)$item;
    }

    private function getActions($module, $info)
    {
        $actions = [];
        $uninstallable = false;
        $no_install_txt = $this->_("Can not be installed with Modules Manager");
        $uninstallable_txt = $this->_("uninstallable");
        $install_text = $this->_("install");
        $uninstall_text = $this->_("uninstall");
        $delete_text = $this->_("delete");
        $configure_text = $this->_("settings");
        $download_and_install_text = $this->_("download and install");
        $download_and_install = $this->_("downloadAndInstall");
        $no_url_found_text = $this->_("No download URL found");
        $more = $this->_("module page");

        if ($info) {
            $local_version = $this->modules->formatVersion($info['version']);
        }

//        bd($this->modules->isInstalled($module->class_name), $module->name);
        foreach ($this->exclude_categories as $k => $exc) {
            if (array_key_exists($k, $module->categories)) {
                $uninstallable = true;
                break;
            }
        }

        if ($uninstallable) {
//            $actions .= $uninstallable_txt . '<br/><a href="' . $module->url . '" class="uk-button uk-button-primary uk-button-small pw-panel pw-panel-left pw-panel-reload" title="' . $no_install_txt . '">' . $more . '</a>';
            $actions[] = $uninstallable_txt;
        }
        if (isset($this->modulesArray[$module->class_name]) && $this->modulesArray[$module->class_name] == null) {
            // module is already downloaded and can be installed
            $actions[] = $install_text;

            if ($this->modules->isDeleteable($module->class_name)) {
                $actions[] = $delete_text;
            }
            return $actions;
        }

//        bd(!$this->modules->isInstalled($module->class_name), 'is not yet installed?');
//        bd($this->modulesArray[$module->class_name] != null, 'modulesArray not null?');

        if ($module->download_url && !$this->modules->isInstalled($module->class_name)) {
            if (substr($module->download_url, 0, 8) == 'https://' && !extension_loaded('openssl')) {
//                $actions .= 'module can not be downloaded because openssl extension is not installed!';
                $actions[] = 'openssl-extension-missing!';
            } else {
                // show download link
                if (!$this->modules->isInstalled($module->class_name) && $this->modulesArray[$module->class_name] == null) {
//                    $url = "{$this->page->url}download/?url=" . urlencode($module->download_url) . "&class={$module->class_name}$this->modal";
//                    $actions .= "<a href='$url' class='confirm uk-button uk-button-primary uk-button-small pw-panel pw-panel-left pw-panel-reload' ><i class='fa fa-download'></i> " . $this->_("download and install") . "</a>";
                    $actions[] = $download_and_install;
                }
            }
        }
//        else {
//            // in case a module has no dl url but is already downloaded and can be installed
//            // Installable module
//            bd('installable but not yet installed', $module->name);
//            if ($this->modules->isInstallable($module->class_name) && (!$this->modules->isInstalled($module->class_name) || $this->modulesArray[$module->class_name] == null)) {
//                $url = "{$this->page->url}install/?&class={$module->class_name}$this->modal";
////                $actions .= "<a href='$url' class='confirm uk-button uk-button-primary uk-button-small pw-panel pw-panel-left pw-panel-reload'><i class='fa fa-plug'></i> " . $this->_("Install") . "</a>";
//                $actions[] = $install_text;
//            }
//        }

        if ($this->modules->isInstalled($module->class_name) && $this->modules->isConfigurable($module->class_name)) {
//            $url = $this->modules->getModuleEditUrl("$module->class_name");
//            bd($url, "editURL");
//            $actions .= "<a href='$url' class='confirm uk-button uk-button-primary uk-button-small pw-panel pw-panel-left pw-panel-reload'><i class='fa fa-cog'></i> " . $this->_("Configure") . "</a>";
            $actions[] = $configure_text;

        }
        // if a module is already installed
        if ($this->modules->isInstalled($module->class_name)) {
//            $uninstall_url = $this->page->url . "uninstall/?class={$module->class_name}";
            // $actions .= "<a href='$uninstall_url' data-tab-text='Panel Title' data-tab-icon='trash' value='{$module->class_name}' class='uk-button-danger uk-button uk-button-small pw-panel pw-panel-left pw-panel-reload'><i class='fa fa-power-off'></i> " . $this->labels['uninstall'] . "</a>";
//            $remote_version = $this->modules->formatVersion($module->module_version);
//            $module->remote_version = $remote_version;

            if (isset($module->remote_version) && $module->remote_version > $this->local_version) {
                $actions[] = "update";
            }
            $actions[] = $uninstall_text;

        }
//        bd($actions);
        return $actions;
    }

    private function triggerReload()
    {
        echo $this->reloadScript;
    }

    public function executeDownload()
    {

        $this->modules->refresh();
        $url = $this->input->get->url;


        $url = $this->wire('sanitizer')->url($url);

        $name = $this->wire('sanitizer')->name($this->input->get->class);

        $destinationDir = $this->wire('config')->paths->siteModules . $name . '/';
        require $this->wire('config')->paths->modules . '/Process/ProcessModule/ProcessModuleInstall.php';
        $install = $this->wire(new ProcessModuleInstall());

        $completedDir = $install->downloadModule($url);
        $data = [];
        if ($completedDir) {
            // now install the module
//            return $this->executeInstall();
            if($this->executeInstall()){
                $data["status"] = "success";
                $data["message"] = "<p><span uk-icon=\"check\"></span> The module was successfully downloaded. You can install it now.</p><p>Automatic installation of the module after downloading is not possible right now.</p>";
            }
            else{
                $data["status"] = "error";
                $data["message"] = "Module could not be installed";
            }
        } else {
            $data["status"] = "error";
            $data["message"] = "Module could not be installed";
        }
        if ($this->config->ajax) {
            return json_encode($data);
        }
    }

    public function executeUninstall()
    {

        // $this->initModules(); // fix problems with modules extending modules not yet installed

        $className = $this->input->get->class;
        if (!$className) {
            return $this->_("No class parameter found in GET request");
        }
        if ($this->input->get->execute) {

            $this->modules->uninstall($className);
            $this->message("The module '{$className}' has been uninstalled");

            return $this->reloadScript;
        }

        $info = $this->modules->getModuleInfo($className);
        $title = $this->_("Uninstall module") . ": " . $info['title'];
        $this->wire->set('processHeadline', $title);
        $text = "<h2>{$title}</h2>";

        $text .= __("Are you sure you want to uninstall this module?");

//        $form = $this->modules->get('InputfieldForm');
//        $form->attr('action', $this->pages->get(21)->url);
//        $form->attr('method', 'post');
//        $form->attr('id', 'modules_form');
//
//        $field = '<input type="hidden" name="install" value="' . $className . '"/>';
//        $form->value = $field;
//
//        $button = $this->modules->get('InputfieldButton');
//        $button->attr('href', "./?class={$className}&execute=true{$this->modal}");
//        $button->attr('target', '_self');
//        $button->attr('class', 'uk-button-secondary');
//        $button->attr('value', $this->_('Ja ich bin mir janz sicha!'));
//        $button->attr('id', "backtomanagerbutton");
//        $button->columnWidth = 100;
//        $form->add($button);
//        $text .= $button->render();

        $text .= "<p><a href='./?class={$className}&execute=true{$this->modal}' class='uk-button uk-button-danger' target='_self'>Yes, uninstall the module</a>";
        return $text;
    }

    public function executeDelete()
    {
        $class = $this->input->get->class;
        if ($this->modules->isDeleteable($class)) {
            if ($this->modules->delete($class)) {
                $this->triggerReload();
//                $this->message("The module {$class} was deleted");
                return "<div class='uk-alert uk-alert-success'>The module {$class} was deleted. You can close this panel now.</div>";
            }
        }
    }

    public function executeInstall()
    {
        $status = "error";
        $name = $this->wire('sanitizer')->name($this->input->get->class);
        $info = $this->modules->getModuleInfo($name);
//        $actions = $this->getActions($name,$info);
//        if($name && isset($this->modulesArray[$name]) && !$this->modulesArray[$name]) {
        if (!$name) {
            $error = $this->_("No class parameter found in GET request");
            if ($this->config->ajax) {
                return ["message" => $error, "status" => $status];
            }
        }
        $info = $this->modules->getModuleInfo($name);

        $title = $this->_("Install module") . ": " . $info['title'];
        $this->wire->set('processHeadline', $title);
        $text = "<h2>{$title}</h2>";

        if (count($info['requires'])) {
            $requires = $this->modules->getRequiresForInstall($name);
            if (count($requires)) {
                $text .= "<p><b>" . $this->_("Sorry, you can't install this module now. It requires other module to be installed first") . ":</b><br/>";
                $text .= "<span class='notes'>$this->labelRequires - " . implode(', ', $requires) . "</span></p>";
            }
        } else {
            $requires = array();
        }

        //        check if module is installed
        if ($this->modules->isInstalled("$name")) {
            $text .= "The module is already installed";
//            $this->session->redirect($this->config->urls->admin . "module/edit?name={$name}{$this->modal}");
        }
        if (!count($requires)) {
            $success = $this->modules->install($name);
            if ($success) {
                $this->executeGetData(); // update the whole $this->allModules array to get actual info about the status of all modules

                $settingsLink = $this->config->urls->admin . "module/edit?name={$name}&modal=1";
                $text .= "<p class='uk-alert uk-alert-success'>";
                $text .= __("The module has been installed successfully.");
                $text .= "</p>";
                if ($this->modules->isConfigurable($name)) {
                    $text .= "<a href='$settingsLink' class='uk-button uk-button-default' target='_self'>" . __("Go to the module's setting page") . '</a>';
                }
//                $text .= $this->reloadScript;
                // now update the array
//                $info = $this->modules->getModuleInfo($name);
                $allActions = array_column($this->allModules, 'actions', 'class_name');
                $module = array_column($this->allModules, 'class_name');

                $moduleActions = $allActions[$name];
                $moduleActionsJson = json_encode($moduleActions);
                $text .= "<script>module = parent.window.vm.findModule('{$name}');module.actions = $moduleActionsJson;module.status='installed'</script>";
                $text .= "<script>UIkit.notification(\"<span uk-icon='icon: check'></span> The module has been installed successfully.\", 'success');</script>";
                $status = "success";
                $this->modulesArray[$name] = 1;
            }
            if ($this->config->ajax) {
                return ["message" => $text, "status" => $status];
            }
            return $text;
        }
    }

    /**
     * Install module and redirect to edit screen
     * Same a ProcessModule does but without post, so we can use it with modules manager
     * more easily
     */
    public function executeInstallModule()
    {
//        bd("install module");
        $name = $this->wire('sanitizer')->name($this->input->get->class);

//        bd($name);
        if ($name && isset($this->modulesArray[$name]) && !$this->modulesArray[$name]) {
            $module = $this->modules->install($name, array('force' => true));
            bd($module);
            $this->modulesArray[$name] = 1;
            $this->session->message($this->_("Module Install") . " - " . $module->className); // Message that precedes the name of the module installed
//            $this->session->redirect($this->config->urls->admin . "module/edit?name={$module->className}");
        }
    }

    public function ___executeDownloadModuleFromUrl($url = '')
    {
        bd($this->input->get->url);
        $url = $this->sanitizer->url($this->input->get->url);

        if (!$url) throw new WireException("This URL may not be accessed directly");

        require $this->wire('config')->paths->modules . '/Process/ProcessModule/ProcessModuleInstall.php';
        $install = $this->wire(new ProcessModuleInstall());
        $install->downloadModule($url);
        $text = "<script>UIkit.notification(\"<span uk-icon='icon: check'></span> The module has been installed successfully.\", 'success');</script>";
        $status = "success";

        if ($this->config->ajax) {
            return ["message" => $text, "status" => $status];
        }
//        $this->session->redirect('./?reset=1');
    }

    public function getModulesFromUrl($url = '')
    {
        $http = new WireHttp();
        $http->setTimeout(30);
        if ($url === "") {
//            bd("url angegeben, hole daten von {$this->moduleServiceUrl}");
            $data = $http->getJSON($this->moduleServiceUrl);
        } else {
//            bd("url angegeben, hole daten von $url");
            $data = $http->getJSON($url . $this->moduleServiceParameters);
        }
//        bd($this->allModules, 'allModules');
        //        bd($data["items"], 'items');
        //        $this->allModules[] = $data["items"];

        $this->allModules = array_merge_recursive($this->allModules, $data["items"]);
//        bd($this->allModules, 'allModules nach merge');

        if ($data['pageNum'] < $data['pageTotal']) {
            $this->getModulesFromUrl($data['next_pagination_url']);
        }
    }

    public function createCache()
    {
        bd('cache will be created');
        $this->getModulesFromUrl();
        $this->cache->save("modulemanager2", $this->allModules, WireCache::expireNever);
//        return json_decode($contents);
        return $this->allModules;
    }

    public function readCache()
    {
        $contents = $this->cache->get("modulemanager2");
        if (!$contents) {
            $contents = $this->createCache();
        }
        $contents = json_decode(json_encode($contents), false);
        return $contents;
    }

    public function install()
    {
        // page already found for some reason
        $ap = $this->pages->find('name=modulesmanager2')->first();
        if ($ap->id) {
            if (!$ap->process) {
                $ap->process = $this;
                $ap->save();
            }
            return;
        }
        $p = new Page();
        $p->template = $this->templates->get('admin');
        $p->title = "Modules Manager 2";
        $p->name = "modulesmanager2";
        $p->parent = $this->pages->get(22);
        $p->process = $this;
        $p->save();
    }

    public function uninstall()
    {
        $found = $this->pages->find('name=modulesmanager2')->first();
        if ($found->id) {
            $found->delete();
        }

        $this->cache->delete('modulesmanager2');
    }

    public static function getModuleConfigInputfields(array $data)
    {
        $data = array_merge(self::$defaults, $data);

        $fields = new InputfieldWrapper();
        $modules = wire('modules');

        $field = $modules->get('InputfieldText');
        $field->attr('name', 'apikey');
        $field->attr('size', 10);
        $field->attr('value', $data['apikey']);
        // $field->set('collapsed',Inputfield::collapsedHidden);
        $field->label = 'modules.processwire.com APIkey';
        $fields->append($field);

        $field = $modules->get('InputfieldText');
        $field->attr('name', 'remoteurl');
        $field->attr('size', 0);
        $field->attr('value', $data['remoteurl']);
        $field->label = 'URL to webservice';
        $fields->append($field);

        $field = $modules->get('InputfieldInteger');
        $field->attr('name', 'limit');
        $field->attr('value', $data['limit']);
        $field->label = 'Limit';
        $fields->append($field);

        $field = $modules->get('InputfieldInteger');
        $field->attr('name', 'max_redirects');
        $field->attr('value', $data['max_redirects']);
        $field->label = 'Max Redirects for file_get_contents stream context (in case)';
        $fields->append($field);

        return $fields;
    }
}

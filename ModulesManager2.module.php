<?php namespace ProcessWire;

/**
 * Class ProcessModuleInstall
 *
 * Provides methods for internative module installation for ProcessModule
 *
 * extended version for AdminThemeUikit and ProcessWire 3+ by Jens Martsch
 * completely rethought and improved the idea of ProcessWire Modules Manager created 2012 by Soma
 *
 * @TODO add filter to show only modules that are compatible with the actual PW version
 * @TODO sometimes wrong module version of installed core modules seem to be returned. for example for markup-htmlpurifier. This is not a problem with my modules but the core modules report incorrect version numbers.
 * @TODO Allow "search for module" to search in the description also, so a module can be found by its purpose and not only by its name
 * @TODO If there are multiple authors of a module, make each of them a link, see ProcessDiagnostics module
 * @TODO When clicking on a category under "more information", then filter by that category, instead of redirecting to the ProcessWire site
 * @TODO Display icons of installed modules
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
            'author' => "Jens Martsch",
            'singular' => true,
            'autoload' => false,
            'permanent' => true,
            'requires' => 'ProcessWire>=3.0.0, PHP>=6.4.3',
            'permission' => 'module-admin',
            'useNavJSON' => true,
            'nav' => array(
//                array(
                //                    'url' => '?site#tab_site_modules',
                //                    'label' => 'Site',
                //                    'icon' => 'plug',
                //                    'navJSON' => 'navJSON/?site=1'
                //                ),
                //                array(
                //                    'url' => '?core#tab_core_modules',
                //                    'label' => 'Core',
                //                    'icon' => 'plug',
                //                    'navJSON' => 'navJSON/?core=1',
                //                ),
                //                array(
                //                    'url' => '?configurable#tab_configurable_modules',
                //                    'label' => 'Configure',
                //                    'icon' => 'gear',
                //                    'navJSON' => 'navJSON/?configurable=1',
                //                ),
                //                array(
                //                    'url' => '?install#tab_install_modules',
                //                    'label' => 'Install',
                //                    'icon' => 'sign-in',
                //                    'navJSON' => 'navJSON/?install=1',
                //                ),
                //                array(
                //                    'url' => '?new#tab_new_modules',
                //                    'label' => 'New',
                //                    'icon' => 'plus',
                //                ),
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

        // add this if using vue-cli-tools. not ready for use yet
        //        $this->config->scripts->add($this->config->urls->siteModules . $this->className . '/dist/js/chunk-vendors.js');
        //        $this->config->scripts->add($this->config->urls->siteModules . $this->className . '/dist/js/main.js');
        //        $this->config->styles->add($this->config->urls->siteModules . $this->className . '/dist/css/chunk-vendors.css');

        $this->config->scripts->add("https://cdn.jsdelivr.net/npm/vue/dist/vue.js");
        //        $this->config->scripts->add("https://cdn.jsdelivr.net/npm/vuetify/dist/vuetify.js");
        //        $this->config->styles->add("https://cdn.jsdelivr.net/npm/vuetify/dist/vuetify.min.css");

        //        $this->config->scripts->add("https://unpkg.com/vue-multiselect@2.1.0/dist/vue-multiselect.min.js");
        //        $this->config->styles->add("https://unpkg.com/vue-multiselect@2.1.0/dist/vue-multiselect.min.css");

        $this->config->scripts->add("https://unpkg.com/vue-select@latest");
        $this->config->styles->prepend("https://unpkg.com/vue-select@latest/dist/vue-select.css");

        $this->config->styles->add($this->config->urls->siteModules . "ModulesManager2/ModulesManager2.css?v=" . $this->getModuleInfo()['version']);
        $this->config->scripts->add($this->config->urls->siteModules . "ModulesManager2/ModulesManager2.js?v=" . $this->getModuleInfo()['version']);

        $this->labelRequires = $this->_x("Requires", 'list'); // Label that precedes list of required prerequisite modules
        $this->labelInstalls = $this->_x("Also installs", 'list'); // Label that precedes list of required prerequisite modules

        // get current installed modules in PW and store it in array
        // for later use to generate
        foreach ($this->modules as $module) {
            $this->modulesArray[$module->className()] = 1;
            wire('modules')->getModuleInfo($module->className()); // fixes problems
        }
        // get current uninstalled modules with flag 0
        foreach ($this->modules->getInstallable() as $module) {
//            bd($module);
            $class_name = basename($module, '.php');
            $class_name = basename($module, '.module');
            $this->modulesArray[$class_name] = 0;
//            wire('modules')->getModuleInfo($class_name); // fixes problems
        }

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
        $this->config->js("process_modulesmanager", $this->pages->get("parent=22,name=modulesmanager")->url . "getdata/");
        $this->config->js("process_modulesmanager_filter_cat", $this->input->get->cat);

        // get module feed cache,
        // if not yet cached download and cache it
        $this->modulesRemoteArray = $this->readCache();
        $count = 0;
        $this->all_categories = array();

        // loop the module list we got from the json feed and we do
        // various checks here to see if it's up to date or installed
        foreach ($this->modulesRemoteArray as $key => $module) {
//            bd($module);

            $categories = array();
            foreach ($module->categories as $cat) {
                $categories[$cat->name] = $cat->title;
            }
//            bd($categories);
            //$all_categories = array_merge($all_categories, $categories);
            $this->all_categories = array_merge($this->all_categories, $categories);
//            bd($this->all_categories);
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

        $this->modules_found = '<p>' . sprintf($this->_("%d modules found in this category on modules.processwire.com"), $count) . '</p>';
        //$pretext .= 'ProcessWire Version ' . $this->config->version;
        $info = $this->getModuleInfo();

        $moduleOverview = $this->createModuleOverview();

        return $moduleOverview . '<p>Modules Manager v' . $info['version'] . '</p>';
    }

    /**
     * Provides a method to get data
     * @return string json with all modules data
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

            // lets add a link to the modules.processwire.com instead

            $count++;
            // $module = (array)$module;
            array_push($out, $this->createItemRow($module));

        }
        return json_encode($out);
    }

    public function createModuleOverview()
    {

        $form = $this->modules->get('InputfieldForm');
        $form->attr('action', $this->pages->get(21)->url);
        $form->attr('method', 'post');
        $form->attr('id', 'modules_form');

// refresh button
        $submit = $this->modules->get('InputfieldButton');
        $submit->attr('href', './?reset=1');
        $submit->attr('name', 'reset');
        $submit->attr('icon', 'refresh');
        $submit->attr('value', $this->_('refresh modules list from modules.processwire.com'));
        $submit->attr('class', $submit->attr('class') . ' pw-head-button');
        $form->add($submit);

        $refreshButton = $form->render();
        $data = $this->executeGetData();
//        bd( $data);
        $categoriesJSON = array();
        $categoriesJSON[] = ["name" => 'showall', "title" => 'Show all (takes some seconds)'];
        // @todo show number of modules in each category
        foreach ($this->all_categories as $key => $cat) {
            $categoriesJSON[] = ["name" => $key, "title" => $cat];
        }
        $categoriesJSON = json_encode($categoriesJSON);
//        bd($categoriesJSON);

        // @TODO make it possible to filter category or module by url
        // when the category is changed, then the URL should be replaced
        // @TODO make filters work for installed, uninstalled, updateable, etc.
        //        https://codepen.io/jmar/pen/dxbrLQ?editors=1010 single select
        //        https://codepen.io/jmar/pen/rXBPxb?editors=1000 vue-multiselect
        $this->markup .= <<<EOD
<script>
    let selectCategoryValue = {
        name: "core",
        title: "Core Modules"
    };
    
    
    let categories =
    $categoriesJSON;
</script>
$refreshButton
<div id="app" class="">
        <div class="uk-alert" uk-sticky>
            <div class="" uk-grid>
                <div class="uk-width-1-2@m">
                <label>Search for module</label>
                
                <v-select :options="allmodules" label="title" v-model="selectValue" @input="selectedModule"></v-select>
<!--                    <v-autocomplete v-model="selectValue" label="Search for module" :items="allmodules"-->
<!--                                    item-text="title" item-value="name" @change="selectedModule" open-on-clear solo-->
<!--                                    clearable auto-select-first hide-no-data-->
<!--                    ></v-autocomplete>-->
<!--                    <br>-->
<!--                    <v-autocomplete v-model="selectCategoryValue" label="Category" :items="categories" item-text="title"-->
<!--                                    item-value="name" @change="selectedCategory" return-object solo auto-select-first-->
<!--                                    hide-no-data-->
<!--                    ></v-autocomplete>-->
                </div>
                <div class="uk-width-1-2@m">
                  <label>Category</label>
                  <v-select :options="categories" label="title" v-model="selectCategoryValue" @input="selectedCategory"></v-select>
                </div>
            </div>
            <div class="uk-margin-top">
                <div class="uk-grid-small uk-child-width-auto mt-10" uk-grid>
                    <label><input type="radio" class="uk-radio" id="installed" value="installed" v-model="picked">
                        show only installed</label>
                    <label><input type="radio" class="uk-radio" id="uninstalled" value="uninstalled"
                                  v-model="picked"> show only uninstalled</label>
                    <label><input type="radio" class="uk-radio" id="updateable" value="updateable" v-model="picked">
                        show only updateable</label>
                    <label><input type="radio" class="uk-radio" id="recommended" value="recommended"
                                  v-model="picked"> show most recommended</label>
                    <span>Picked: {{ picked }}</span>
                </div>
            </div>
        </div>
        <div>
            <p>{{ modules.length }} modules in this category. Total number of modules: {{ allmodules.length }}</p>
            <div id="modules" class="js-filter uk-child-width-1-2@s uk-child-width-1-3@m uk-grid-match" uk-grid>
                <div
                        v-for="module in list"
                        :key="module.title"
                >
                    <div class="uk-card uk-card-default uk-card-body uk-card-small">

                        <div class="uk-flex uk-flex-between uk-flex-wrap">
                            <div>
                                <span class="h3 uk-card-title">{{ module.title }}</span>
                                <small>by <a tabindex="-1" v-bind:href="'https://modules.processwire.com/authors/' + author" v-for="(author, index) in module.authors.split(', ')">{{(author !='' && index !=0) ? ',' : ''}} {{ author }}</a></small>
                                <br/>
                                <small>{{ module.name }} |
                                    latest version: {{ module.module_version }} {{ module.release_state.title }} <span
                                            v-if="module.status"> | </span>
                                    <span v-if="module.status" v-html="module.status"></span>
                                </small>
                                <br>
                            </div>
                            <div class="">{{ module.likes }} <span class="fa fa-heart"></span></div>
                        </div>
                        <p>{{ module.summary }}</p>

                        <!--                    @TODO add information if this module is compatible with the current PW version -->
                        <span v-html="module.actions">{{ module.actions }}</span>

                        <p v-if="module.dependencies" v-html="module.dependencies"></p>
                        <ul uk-accordion>
                            <li>
                                <a class="uk-accordion-title" href="#">show more information</a>
                                <div class="uk-accordion-content">
                                    <p v-if="module.forum_url || module.project_url">
                                        <a class="pw-modal" v-if="module.project_url" v-bind:href="module.project_url"
                                           target="_blank">
                                            <i class="fa fa-github"></i> Project on Github
                                        </a>
                                        <br/>
                                        <a class v-if="module.forum_url" v-bind:href="module.forum_url" target="_blank"><i
                                                class="fa fa-comments"></i> Support Forum</a>
                                    </p>
                                    <p>Categories:
                                        <span v-for="(category, index) in module.categories" :key="category.title">
                            <a :href="category.url">{{ category.title }}</a><span
                                                v-if="index+1 < module.categories.length">, </span>
                        </span>
                                    </p>
                                    <p>Compatible with PW versions:<br>
                                        <span v-for="(version, index) in module.pw_versions">
                            {{ version.title }}<span v-if="index+1 < module.pw_versions.length">, </span>
                        </span>
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
</div>
<script>
    // Vue.component('vue-multiselect', window.VueMultiselect.default)
    Vue.component("v-select", VueSelect.VueSelect);
    // Vue.use(Vuetify)

    vuerender = new Vue({
        el: "#app",
        data() {
            return {
                modules: [],
                allmodules: [],
                selectValue: null,
                selectCategoryValue: selectCategoryValue,
                options: modules,
                categories: categories,
                picked: null,
            };
        },
        created() {
            // Attach onpopstate event handler
            window.onpopstate = function(event) {
//                console.log(event.state.module);
//                console.log("aktueller Wert: " + vuerender.selectValue);
                vuerender.selectValue = event.state.module;
                vuerender.selectCategoryValue = null;
            };
        },
        methods: {
            selectedModule(value) {
                if (this.selectValue !== null) {
//                    console.log('selected: ' + this.selectValue.name);
                    this.selectCategoryValue = null;
                    var stateObj = { module: this.selectValue };
                    history.pushState(stateObj, null, "?module=" + this.selectValue.name);
                }
            },
            selectedCategory() {
                if (this.selectCategoryValue !== null) {
                    this.selectValue = null;
                }
            },
            loadData(){
                // console.log('refresh');
                let self = this;
                $.ajax({
                    url: "./getdata/",
                    // url: "https://modules.processwire.com/export-json/?apikey=pw223&limit=400", // if we could query all modules at once
                    dataType: 'json',
                    type: 'get',
                    success: function (data) {
                        self.allmodules = data;
//                        self.getModuleFromUrl();
                    }
                })
            },
            getModuleFromUrl(){
                let urlParams = new URLSearchParams(window.location.search);
                let modulename = urlParams.get('module');
                if (modulename) {
//                    console.log('created');
                    console.log(this.allmodules);
                    console.log(modulename);
                    console.log(this.allmodules.indexOf(modulename));
                    this.selectValue = {
                         name: modulename,
                         title: "test"
                    };
                    this.selectCategoryValue = null;
                    console.log(this.selectValue);
                }
            }
        },
        computed: {
            list() {
                let retModule = this.allmodules.filter(module => {
                    this.options = this.modules;
                    let selectedCategory = this.selectCategoryValue;
                    let visible = false;

                    if (this.selectValue !== null) {
                        visible = this.selectValue.name === module.name;
                    }

                    if (selectedCategory !== null) {
                        // console.log(selectedCategory);
                        categoryMatch = module.categories.filter(function (category) {
                            return category.name === selectedCategory.name;
                        });
                        visible = categoryMatch.length > 0;
                    }
                    return visible;
                });
                this.modules = retModule;
                return retModule;
            },
        },
        beforeMount() {
            this.loadData(); // this loads the data via AJAX
        }
    });

    
</script>
EOD;
        return $this->markup;
    }

    public function createItemRow($item)
    {

        $remote_version = $this->modules->formatVersion($item->module_version);

        $item->status = '';
        $item->version = '-';
        $item->actions = '-';
        $item->dependencies = '';

        if (array_key_exists($item->class_name, $this->modulesArray)) {

            // get module infos, we can't use modules->get(module_name) here
            // as it would install the module, which we don't want at all
            $info = wire('modules')->getModuleInfo($item->class_name);
            $this->local_version = $this->modules->formatVersion($info['version']);

            if ($this->modulesArray[$item->class_name] == null) {

                if (count($info['requires'])) {
                    $requires = $this->modules->getRequiresForInstall($item->class_name);
                    if (count($requires)) {
                        $item->dependencies .= "<br /><span class='notes requires'>$this->labelRequires - " . implode(', ', $requires) . "</span>";
                    }

                } else {
                    $requires = array();
                }

                if (count($info['installs'])) {
                    $item->dependencies .= "<br /><span class='detail installs'>$this->labelInstalls - " . implode(', ', $info['installs']) . "</span>";
                }

                $item->status = '<span class="uk-text-muted">' . $this->_('downloaded but not installed') . ': ' . $this->local_version . '</span>';
                $item->actions = $this->getActions($item, "");

            } else {
                if ($remote_version > $this->local_version) {
                    $item->status = '<span class="">' . $this->_('installed') . ': ' . $this->local_version . '</span> |';
                    $item->status .= '<span class="">' . $this->_("update to v $remote_version available!") . '</span>';
                } else {
                    $item->status = $this->_('installed');
//                    $item->status = '<span class="">' . $this->_('installed') . ': v' . $this->local_version . '</span>';
                }
                $item->actions = $this->getActions($item, $info);
            }
        } else {
            $item->theme = isset($categories['admin-theme']) ? '&theme=1' : '';
            $item->actions = $this->getActions($item, "");
        }

        $categories = array();
        foreach ($item->categories as $category) {
            $categories[] = $category->title;
        }

        // $item->categories = implode(", ", $categories);

        $authors = array();
        foreach ($item->authors as $auth) {
            $authors[] = $auth->title;
        }

        $item->authors = implode(", ", $authors);
        $item->created = date("Y/m/d", $item->created);
        $item->modified = date("Y/m/d", $item->modified);
        return (array)$item;
    }

    private function getActions($module, $info)
    {
        $actions = "";
        $uninstallable = false;
        $no_install_txt = $this->_("Can not be installed with Modules Manager");
        $uninstallable_txt = $this->_("uninstallable");
        $install_text = $this->_("install");
        $no_url_found_text = $this->_("No download URL found");
        $more = $this->_("module page");
//        bd($module, $module->name);
        //        bd($info);
        //        bd($this->modulesArray[$module->class_name], $module->name);
        //        bd($info);
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
            $actions .= $uninstallable_txt . '<br/><a href="' . $module->url . '" class="uk-button uk-button-primary uk-button-small pw-panel pw-panel-left pw-panel-reload" title="' . $no_install_txt . '">' . $more . '</a>';
        }
//        if (count($requires)) {
        //        }
        if ($module->download_url) {
            if (substr($module->download_url, 0, 8) == 'https://' && !extension_loaded('openssl')) {
                $actions .= 'module can not be downloaded because openssl extension is not installed!';
            } else {
                // show download link
                if (!$this->modules->isInstalled($module->class_name)) {
                    $url = "{$this->page->url}download/?url=" . urlencode($module->download_url) . "&class={$module->class_name}$this->modal";
                    $actions .= "<a href='$url' class='confirm uk-button uk-button-primary uk-button-small pw-panel pw-panel-left pw-panel-reload' ><i class='fa fa-download'></i> " . $this->_("download and install") . "</a>";
                }
            }
        }
        // Installable module
        if ($this->modules->isInstallable($module->class_name) && (!$this->modules->isInstalled($module->class_name) || $this->modulesArray[$module->class_name] = null)) {
            $url = "{$this->page->url}install/?&class={$module->class_name}$this->modal";
            $actions .= "<a href='$url' class='confirm uk-button uk-button-primary uk-button-small pw-panel pw-panel-left pw-panel-reload'><i class='fa fa-plug'></i> " . $this->_("Install") . "</a>";
        }

        if ($this->modules->isInstalled($module->class_name) && $this->modules->isConfigurable($module->class_name)) {
            $url = $this->modules->getModuleEditUrl("$module->class_name");
//            $url = "{$this->page->url}install/?&class={$module->class_name}$this->modal";
            $actions .= "<a href='$url' class='confirm uk-button uk-button-primary uk-button-small pw-panel pw-panel-left pw-panel-reload'><i class='fa fa-cog'></i> " . $this->_("Configure") . "</a>";
        }
        // add configure and uninstall links
        if ($this->modules->isInstalled($module->class_name)) {
            $uninstall_url = $this->page->url . "uninstall/?class={$module->class_name}";
            $actions .= "<a href='$uninstall_url' data-tab-text='Panel Title' data-tab-icon='trash' value='{$module->class_name}' class='uk-button-danger uk-button uk-button-small pw-panel pw-panel-left pw-panel-reload'><i class='fa fa-power-off'></i> " . $this->labels['uninstall'] . "</a>";
        }
        return $actions;
    }

    private function getActionsOld($module, $uninstallable, $action = '', $theme = '')
    {

        $actions = "";
        $no_install_txt = $this->_("Can not be installed with Modules Manager");
        $uninstallable_txt = $this->_("uninstallable");
        $install_text = $this->_("install");
        $no_url_found_text = $this->_("No download URL found");
        $more = $this->_("module page");
        if ($uninstallable) {
            return '(' . $uninstallable_txt . ')<br/><a href="' . $module->url . '" class="uk-button uk-button-primary uk-button-small pw-panel pw-panel-left pw-panel-reload" title="' . $no_install_txt . '">' . $more . '</a>';
        }

        $confirm = '';

        if ($theme) {
            $install_confirm_text = $this->_('This will install the theme and delete the previous! If you have altered the /site/templates-admin/ theme or have your own, you might consider backing it up first.');
        } else {
            $install_confirm_text = $this->_('Ensure that you trust the source of the ZIP file before continuing!');
        }

        if ($module->download_url) {
            if (substr($module->download_url, 0, 8) == 'https://' && !extension_loaded('openssl')) {
                $actions .= 'no openssl installed!';
            } else {
                $button = $this->modules->get('InputfieldMarkup');
                if ($action == 'edit') {
                    $url = $this->modules->getModuleEditUrl("$module->className");
//                    $url = "{$this->pages->get(21)->url}edit?name={$module->class_name}$this->modal";
                    $button->value = "<a href='$url' id='{$module->class_name}' class='uk-button uk-button-primary uk-button-small pw-panel pw-panel-left pw-panel-reload'><i class='fa fa-cog'></i> " . $this->_("edit") . "</a>";
                }
                if ($action == 'update') {
                    $url = "{$this->page->url}download/?url=" . urlencode($module->download_url) . "&class={$module->class_name}{$theme}$this->modal";
                    $button->value = "<a href='$url' class='pw-panel pw-panel-left pw-panel-reload confirm uk-button uk-button-primary uk-button-small' data-confirmtext='$install_confirm_text' id='{$module->class_name}'><i class='fa fa-arrow-circle-up'></i> " . $this->_("update") . "</a>";
                }
                if ($action == 'download') {
                    $url = "{$this->page->url}download/?url=" . urlencode($module->download_url) . "&class={$module->class_name}{$theme}$this->modal";
                    $button->value = "<a href='$url' class='confirm uk-button uk-button-primary uk-button-small pw-panel pw-panel-left pw-panel-reload' data-confirmtext='$install_confirm_text' id='{$module->class_name}'><i class='fa fa-download'></i> " . $this->_("download and install") . "</a>";
                }
                if ($action == 'install') {
                    $install_url = $this->page->url . "install/?class={$module->class_name}" . $this->modal;
                    $button->value = "<a href='{$install_url}' class='confirm uk-button uk-button-primary uk-button-small pw-panel pw-panel-left pw-panel-reload'>" . $install_text . "</a>";
                }
                if ($action == 'not_install') {
                    $button->value = "<a href='#'><s>" . $install_text . "</s></a>";
                }

                $actions .= $button->render();
                if ($this->modules->isInstalled($module->class_name)) {
                    $url = $this->page->url . "uninstall/?class={$module->class_name}" . $this->modal;
                    $actions .= "<a href='$url' value='{$module->class_name}' class='uk-button-danger uk-button uk-button-small pw-panel pw-panel-left pw-panel-reload'><i class='fa fa-trash'></i> Uninstall" . $uninstall_text . "</a>";
                }
            }
        } else {
            // in case a module has no dl url but is already downloaded and can be installed
            if ($this->modules->isInstallable($module->class_name)) {
                $actions .= "<button name='install' value='{$module->class_name}' class='uk-button uk-button-default uk-button-small'><i class='fa fa-plus-circle'></i> " . $install_text . "</button>";
            } else {
                $more = $this->_("module page");
                $actions .= "<a href='$module->url' title='$no_url_found_text' class='pw-panel uk-button uk-button-default uk-button-small'><i class='fa fa-info-circle'></i> $more</a>";
            }
        }
        return $actions;
    }

    public function executeDownload()
    {

        $this->modules->resetCache();

        $url = $this->input->get->url;
        $className = $this->input->get->class;
        $destinationDir = $this->wire('config')->paths->siteModules . $className . '/';

        require $this->wire('config')->paths->modules . '/Process/ProcessModule/ProcessModuleInstall.php';
        $install = $this->wire(new ProcessModuleInstall());

        $completedDir = $install->downloadModule($url, $destinationDir);
        if ($completedDir) {
            // now install the module
            return $this->executeInstall();
            // return $this->buildDownloadSuccessForm($className)->render();

        } else {
            return false;
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
            $text = <<<EOF
<script>
window.parent.vuerender.loadData();
$('#pw-panel-shade', window.parent.document).click();

// window.parent.UIkit.notification({
//     message: 'my-message!',
//     status: 'primary',
//     pos: 'top-right',
//     timeout: 5000
// });
</script>
EOF;
            $this->message("The module '{$className}' has been uninstalled");
            return $text;
        }

        $info = $this->modules->getModuleInfo($className);
        $title = $this->_("Uninstall module") . ": " . $info['title'];
        $this->wire->set('processHeadline', $title);
        $text = "<h2>{$title}</h2>";

        $text .= __("Are you sure you want to uninstall this module?");
        $text .= "<script>window.parent.vuerender.loadData()</script>";

        $form = $this->modules->get('InputfieldForm');
        $form->attr('action', $this->pages->get(21)->url);
        $form->attr('method', 'post');
        $form->attr('id', 'modules_form');

        $field = '<input type="hidden" name="install" value="' . $className . '"/>';
        $form->value = $field;

        $button = $this->modules->get('InputfieldButton');
        $button->attr('href', "./?class={$className}&execute=true{$this->modal}");
        $button->attr('target', '_self');
        $button->attr('class', 'uk-button-secondary');
        $button->attr('value', $this->_('Ja ich bin mir janz sicha!'));
        $button->attr('id', "backtomanagerbutton");
        $button->columnWidth = 100;
        $form->add($button);
//        $text .= $button->render();

        $text .= "<p><a href='./?class={$className}&execute=true{$this->modal}' class='uk-button uk-button-danger' target='_self'>Yes, uninstall the module</a>";
        $text .= "<script>console.log(window.parent.document.activeElement);</script>";
        return $text;
    }

    public function executeInstall()
    {

        // $this->initModules(); // fix problems with modules extending modules not yet installed

        $className = $this->input->get->class;
        if (!$className) {
            return $this->_("No class parameter found in GET request");
        }
        $info = $this->modules->getModuleInfo($className);
        $title = $this->_("Install module") . ": " . $info['title'];
        $this->wire->set('processHeadline', $title);
        $text = "<h2>{$title}</h2>";

        if (count($info['requires'])) {
            $requires = $this->modules->getRequiresForInstall($className);
            if (count($requires)) {
                $text .= "<p><b>" . $this->_("Sorry, you can't install this module now. It requires other module to be installed first") . ":</b><br/>";
                $text .= "<span class='notes'>$this->labelRequires - " . implode(', ', $requires) . "</span></p>";
            }
        } else {
            $requires = array();
        }

        //        check if module is installed
        if ($this->modules->isInstalled("$className")) {
            $text .= "The module is already installed";
            $this->session->redirect($this->config->urls->admin . "module/edit?name={$className}{$this->modal}");
        }
        if (!count($requires)) {
            $success = $this->modules->install($className);
            if ($success) {
                $settingsLink = $this->config->urls->admin . "module/edit?name={$className}";
                $text .= "<p class='uk-alert uk-alert-success'>";
                $text .= __("The module has been installed successfully.");
                $text .= "</p>";
                if ($this->modules->isConfigurable($className)) {
                    $text .= "<a href='$settingsLink' class='uk-button uk-button-default' target='_self'>" . __("Go to the module's setting page") . '</a>';
                }
                $text .= "<script>window.parent.vuerender.loadData()</script>";
            }
            return $text;
        }

        $form = $this->modules->get('InputfieldForm');
        $form->attr('action', $this->pages->get(21)->url);
        $form->attr('method', 'post');
        $form->attr('id', 'modules_form');

        $field = '<input type="hidden" name="install" value="' . $className . '"/>';
        $form->value .= $field;

        if (!count($requires)) {

            $submit = $this->modules->get('InputfieldSubmit');
            $submit->attr('name', 'submit');
            $submit->attr('value', $this->_('install module'));
            $submit->columnWidth = 50;
            $form->add($submit);
        }

        // $button = $this->modules->get('InputfieldButton');
        // $button->attr('href', '../');
        // $button->attr('value', $this->_('back to Modules Manager'));
        // $button->attr('id', 'backtomanagerbutton');
        // $button->columnWidth = 100;
        // $form->add($button);

        $text .= $form->render();

        return $text;
    }

    /**
     * Install module and redirect to edit screen
     * Same a ProcessModule does but without post, so we can use it with modules manager
     * more easily
     */
    public function executeInstallModule()
    {
        $name = $this->input->get->install;
        if ($name && isset($this->modulesArray[$name]) && !$this->modulesArray[$name]) {
            $module = $this->modules->get($name);
            $this->modulesArray[$name] = 1;
            $this->session->message($this->_("Module Install") . " - " . $module->className); // Message that precedes the name of the module installed
            $this->session->redirect($this->config->urls->admin . "module/edit?name={$module->className}");
        }
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

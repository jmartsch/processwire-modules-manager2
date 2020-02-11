<template>
    <div id="app">
        <div id="loadingIndicator" class="uk-card uk-card-default uk-card-body uk-card-small" v-if="isError || isLoading">
            <div v-if="isError">Error while loading modules</div>
            <div v-else-if="isLoading">
                <i class="fa fa-circle-o-notch fa-spin fa-2x fa-fw"></i>
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <ul uk-tab>
            <li class="uk-active">
                <a href="#module-manager-install-from-url">Install and manage modules</a>
            </li>
            <li>
                <a href="#module-manager-options">Install module from URL or upload</a>
            </li>
        </ul>

        <div class="uk-switcher">
            <div class="module-manager-options uk-padding uk-padding-remove-top" id="module-manager-options">
                <div class="uk-navbar-container uk-navbar-transparent" uk-navbar>
                    <div class="uk-navbar-right">
                        Display as:
                        <ul class="uk-navbar-nav">
                            <li v-bind:class="{'uk-active' : layout==='cards'}">
                                <a
                                        value="cards"
                                        @click="selectedLayout('cards')"
                                >
                                    <span uk-icon="grid"></span> cards
                                </a>
                            </li>
                            <li v-bind:class="{'uk-active' : layout==='reducedCards'}">

                                <a
                                        value="cards"
                                        @click="selectedLayout('reducedCards')"
                                >
                                    <i class="fa fa-id-card"></i> reduced cards
                                </a>
                            </li>
                            <li v-bind:class="{'uk-active' : layout==='table'}">
                                <a
                                        value="table"
                                        @click="selectedLayout('table')"
                                >
                                    <span uk-icon="table"></span> table (not finished yet)
                                </a>
                            </li>

                        </ul>
                    </div>
                </div>
                <div class="">
                    <div uk-grid>
                        <div class="uk-width-1-2@m">
                            <label>Search for module</label>
                            <v-select
                                    :options="allmodules"
                                    label="title"
                                    v-model="selectValue"
                                    @input="selectedModule"
                            ></v-select>
                        </div>
                        <div class="uk-width-1-2@m">
                            <label>Category</label>
                            <v-select
                                    :options="categories"
                                    label="title"
                                    v-model="selectCategoryValue"
                                    @input="selectedCategory"
                            ></v-select>
                        </div>
                    </div>
                    <div class="uk-margin-top">
                        <div class="uk-grid-small uk-child-width-auto mt-10" uk-grid>
                            Feature not working yet:
                            <label>
                                <input type="radio" class="uk-radio" id="installed" value="installed" v-model="picked"/>show only installed
                            </label>
                            <label>
                                <input
                                        type="radio"
                                        class="uk-radio"
                                        id="uninstalled"
                                        value="uninstalled"
                                        v-model="picked"
                                /> show only uninstalled
                            </label>
                            <label>
                                <input
                                        type="radio"
                                        class="uk-radio"
                                        id="updateable"
                                        value="updateable"
                                        v-model="picked"
                                />show only updateable
                            </label>
                            <label>
                                <input
                                        type="radio"
                                        class="uk-radio"
                                        id="recommended"
                                        v-model="picked"
                                        value="recommended"
                                /> show most recommended
                            </label>
                            <span>Picked: {{ picked }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div id="module-manager-install-from-url" class="module-manager-options uk-padding">
                <h3>Download and install a module from URL (not implemented yet)</h3>
                <p>Download a ZIP file containing a module. If you download a module that is already installed, the installed version will be overwritten with the newly downloaded version.
                </p>
                <form class="uk-form-horizontal" action="./download">
                    <input type="text" name="url" class="uk-input uk-width-3-4" placeholder="URL of the module's zip file" id="module-download-url">
                    <button type="submit" class="uk-button uk-button-primary installFromURL"><span class="text">Install module</span></button>
                </form>
                <p class="uk-alert uk-alert-warning">Be absolutely certain that you trust the source of the ZIP file.</p>

            </div>
        </div>

        <div v-if="layout==='cards' || layout==='reducedCards'">
            <p v-if="isLoading !== true">{{ listLength }} modules in this category. Total number of modules: {{ allmodules.length }}</p>
            <div
                    id="modules"
                    class="js-filter uk-child-width-1-2@s uk-child-width-1-3@m uk-grid-match"
                    uk-grid
            >
                <div v-for="module in list" :key="module.title">
                    <div class="uk-card uk-card-default uk-card-body uk-card-small">
                        <div class="uk-flex uk-flex-between uk-flex-wrap">
                            <div>
                                <span class="h3 uk-card-title">{{ module.title }}</span>
                                <br/>
                                <small>
                                    {{ module.name }} by
                                    <a
                                            tabindex="-1"
                                            v-bind:key="author.name"
                                            v-bind:href="author.url"
                                            v-for="(author, index) in module.authors"
                                    >{{(author !='' && index !=0) ? ',' : ''}} {{ author.name }}</a>
                                </small>
                                <span
                                        class="uk-text-muted"
                                > / </span>
                                <small>
                                    latest version: {{ module.module_version }} {{ module.release_state.title }}
                                    <span
                                            v-show="module.status"
                                    > / </span>
                                    <span v-if="module.status" v-html="module.status"></span>
                                </small>
                                <br/>
                            </div>
                            <div v-if="layout==='cards'">
                                <span class="uk-text-muted uk-text-small">{{ module.likes }}</span>&nbsp;
                                <span class="fa fa-heart"></span>
                            </div>
                        </div>
                        <p v-if="layout==='cards'" class="uk-text-meta">{{ module.summary }}</p>

                        <!--                    @TODO add information if this module is compatible with the current PW version -->
                        <p uk-margin>
                            <ActionButtons
                                    v-for="action in module.actions"
                                    v-bind:key="action"
                                    v-bind:action="action"
                                    v-bind:module="module"
                            ></ActionButtons>
                        </p>
                        <p v-if="module.dependencies" v-html="module.dependencies"></p>
                        <ul uk-accordion>
                            <li>
                                <a class="uk-accordion-title uk-text-meta" href="#">show more information</a>
                                <div class="uk-accordion-content">
                                    <p v-if="layout==='reducedCards'">{{ module.summary }}</p>
                                    <p v-if="module.forum_url || module.project_url">
                                        <a
                                                class="pw-modal"
                                                v-if="module.project_url"
                                                v-bind:href="module.project_url"
                                                target="_blank"
                                        >
                                            <i class="fa fa-github"/> Project on Github
                                        </a>
                                        <br/>
                                        <a class v-if="module.forum_url" v-bind:href="module.forum_url" target="_blank">
                                            <i class="fa fa-comments"></i> Support Forum
                                        </a>
                                    </p>
                                    <p>
                                        Categories:
                                        <span
                                                v-for="(category, index) in module.categories"
                                                :key="category.title"
                                        >
                      <a :href="category.url">{{ category.title }}</a>
                      <span v-if="index+1 < module.categories.length">,</span>
                    </span>
                                    </p>
                                    <p>
                                        Compatible with PW versions:
                                        <br/>
                                        <span :key="index" v-for="(version, index) in module.pw_versions">
                      {{ version.title }}
                      <span v-if="index+1 < module.pw_versions.length">,</span>
                    </span>
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="layout==='table'">
            <v-data-table
                    :headers="headers"
                    :items="list"
                    :disable-pagination="true"
                    :hide-default-footer="true"
                    :fixed-header="true"
                    :show-select="true"
                    class="elevation-1"
            >
                <template v-slot:item.actions="{ item }">
                    <ActionButtons
                            v-for="action in item.actions"
                            v-bind:key="action"
                            v-bind:action="action"
                            v-bind:module="item"
                    ></ActionButtons>
                    <!--                    <v-icon small class="mr-2" @click="editItem(item)">edit</v-icon>-->
                    <!--                    <v-icon small @click="deleteItem(item)">delete</v-icon>-->
                </template>
            </v-data-table>

            <div v-for="module in list" :key="module.title" v-show="false">{{ module.name }}</div>
        </div>
    </div>
</template>
<style>
    @import "~vue-select/dist/vue-select.css";

    .vs__dropdown-toggle {
        background-color: white;
    }

    .uk-accordion-title {
        font-size: 1rem;
    }

    #loadingIndicator {
        position: absolute;
        z-index: 999;
        top: 50%;
        left: 50%;
    }

    .module-manager-options {
        background: #efefef;
        /*padding: 0 10px 20px 10px;*/
        margin-bottom: 20px;
    }
</style>

<script>
    /*eslint no-console: 0*/
    import vSelect from "vue-select";
    import ActionButtons from "./ActionButtons";

    let allmodules = [];
    let categories = [];
    let modules = [];

    let layout = localStorage.getItem("layout")
        ? localStorage.getItem("layout")
        : "cards";

    export default {
        name: "App",
        components: {
            vSelect,
            ActionButtons
        },
        data() {
            return {
                headers: [
                    {
                        text: "Module name",
                        align: "start",
                        sortable: true,
                        value: "title"
                    },
                    {
                        text: "local version",
                        value: "module_version"
                    },
                    {
                        text: "latest version",
                        value: "module_version"
                    },
                    {
                        text: "Status",
                        value: "status"
                    },
                    {
                        text: "Actions",
                        value: "actions"
                    }
                ],
                layout: layout,
                // modules: [],
                isLoading: false,
                isError: false,
                moduleCount: 0,
                allmodules: allmodules,
                selectValue: null,
                selectCategoryValue: null,
                options: null,
                categories: categories,
                picked: null
            };
        },
        created() {
            // Attach onpopstate event handler
            window.onpopstate = function (event) {
                // console.log(event.state.module);
                // console.log("aktueller Wert: " + vuerender.selectValue);
                this.selectValue = event.state.module;
                this.selectCategoryValue = null;
            };
        },
        methods: {
            findModule(moduleName) {
                // return the module's array
                return this.allmodules.find(
                    allmodules => allmodules.class_name === moduleName
                );
            },
            selectedLayout(value) {
                console.log(value);
                this.layout = value;
                localStorage.setItem("layout", value);
            },
            selectedModule() {
                if (this.selectValue !== null) {
                    console.log("selected: " + this.selectValue.name);
                    this.selectCategoryValue = null;
                    let stateObj = {module: this.selectValue};
                    history.pushState(stateObj, null, "?module=" + this.selectValue.name);
                }
            },
            selectedCategory() {
                if (this.selectCategoryValue !== null) {
                    this.selectValue = null;
                    let stateObj = {module: this.selectValue};
                    history.pushState(
                        stateObj,
                        null,
                        "?category=" + this.selectCategoryValue.name
                    );
                }
            },
            loadData() {
                console.log("loadData");
                // eslint-disable-next-line no-undef
                let modulesUrl = "getData/";
                let categoriesUrl = "getCategories/";
                this.isLoading = true;

                if (process.env["NODE_ENV"] === "development") {
                    // use this if you want to use the static files
                    console.log("development. load modules.json");
                    modulesUrl = "/modules.json";
                    categoriesUrl = "/categories.json";
                    // else you have to have a running ProcessWire installation at the URL http://pw-modules-manager.localhost
                    // this does not work because Access-Control-Allow-Origin is in effect so you can not load the data via AJAX
                    // modulesUrl = "http://localhost/pw-modules-manager/processwire/setup/modulesmanager2/getData/";
                    // categoriesUrl = "http://localhost/pw-modules-manager/processwire//setup/modulesmanager2/getCategories/";
                }
                // when loading data from processwire via axios, a specific header has to be sent, so PW knows it's AJAX

                this.$http
                    .get(modulesUrl, {
                        headers: {"X-Requested-With": "XMLHttpRequest"}
                    })
                    .then(result => {
                        console.log("modules.json loaded");
                        // console.log(result.data);
                        this.$http
                            .get(categoriesUrl, {
                                headers: {"X-Requested-With": "XMLHttpRequest"}
                            })
                            .then(categories => {
                                // console.log(result.data);
                                this.allmodules = result.data;
                                window.allmodules = this.allmodules;

                                this.categories = categories.data;
                                this.isLoading = false;
                                this.getSearchFromUrl();
                            })
                            .catch(function (error) {
                                this.isError = true;
                                console.log(error);
                            });
                    })
                    .catch(function (error) {
                        this.isError = true;
                        console.log(error);
                    });


            },
            getSearchFromUrl() {
                // let self = this;
                let urlParams = new URLSearchParams(window.location.search);
                let moduleName = urlParams.get("module");
                let categoryName = urlParams.get("category");
                let result;
                // console.log("module: " + moduleName);
                // console.log("category: " + categoryName);
                if (moduleName !== null) {
                    result = this.allmodules.find(
                        allmodules => allmodules.name === moduleName
                    );
                    // console.log("module: " + result);
                    this.selectValue = result;
                } else if (categoryName !== null) {
                    // console.log(this.categories);
                    result = this.categories.find(
                        categories => categories.name === categoryName
                    );
                    // console.log("category: " + result);

                    this.selectCategoryValue = result;

                } else {
                    // show default category
                    this.selectCategoryValue = {
                        name: "core",
                        title: "Core Modules"
                    };
                }
            },
            getModuleFromUrl(url) {
                if (url) {
                    this.isLoading = true;
                    this.$http
                        .get('./download/?url=' + url, {
                            headers: {"X-Requested-With": "XMLHttpRequest"}
                        })
                        .then(result => {
                            console.log("modules.json loaded");
                            if (result.status === 200){
                                this.isLoading = false;
                                UIkit.modal.alert(result.data.message).then(ok=> {
                                    this.loadData();
                                });
                            }
                            else{
                                this.isLoading = false;
                                UIkit.modal.alert("Error");
                            }
                        })
                        .catch(error => {
                            // this.isError = true;
                            this.isLoading = false;
                            UIkit.modal.alert("Failed to install the module: " + error);
                        });
                }
            }
        },
        computed: {
            listLength: function () {
                return this.list.length;
            },
            list: function () {
                console.log("computed list aufgerufen");
                return this.allmodules.filter(module => {
                    self.options = self.allmodules;
                    let selectedCategory = this.selectCategoryValue;
                    let visible = false;
                    // console.log("selectedCategory: " + selectedCategory);
                    // console.log("selectValue: " + this.selectValue);

                    if (this.selectValue == null && selectedCategory == null) {
                        return true;
                    }

                    if (this.selectValue !== null) {
                        visible = this.selectValue.name === module.name;
                    }

                    if (
                        selectedCategory !== null &&
                        typeof selectedCategory !== "undefined"
                    ) {
                        let categoryMatch = module.categories.filter(function (category) {
                            return category.name === selectedCategory.name;
                        });
                        visible = categoryMatch.length > 0;
                    }
                    if (visible) self.moduleCount++;
                    return visible;
                });
                // this.modules = retModule;
                // return retModule;
            }
        },
        beforeMount() {
            this.loadData(); // this loads the data via AJAX
        },
        mounted() {
            window.vm = this;
            // window.allmodules = vm.$children[0]._data.allmodules;


            window.addEventListener('loadData', this.loadData);
            $(document).on("click", "#app .pw-panel", function (e, el) {
                e.preventDefault();
                let toggler = $(this);
                pwPanels.addPanel(toggler);
                toggler.click();
            });

            $(document).on("submit", "#module-manager-install-from-url form", function (e) {
                e.preventDefault();
                let url = document.getElementById('module-download-url').value;
                window.vm.getModuleFromUrl(url);
            });

            $(document).on("click", "#app .confirm", function (e, el) {
                e.preventDefault();
                UIkit.modal.confirm('UIkit confirm!').then(function () {
                    console.log(e.target.href);
                }, function () {
                    console.log('Rejected.');
                });

                // ProcessWire.confirm("foo?", function() {
                //     ProcessWire.alert("bar yes");
                // }, function() {
                //     ProcessWire.alert("bar no");
                // });
            });
        }
    };
</script>

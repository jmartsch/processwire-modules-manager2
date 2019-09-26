<template>
    <div id="app" class="">
        <div class="uk-alert" uk-sticky>
            <div class="" uk-grid>
                <div class="uk-width-1-2@m">
                    <label>Search for module</label>
                    <v-select :options="allmodules" label="title" v-model="selectValue" @input="selectedModule"></v-select>
                </div>
                <div class="uk-width-1-2@m">
                    <label>Category</label>
                    <v-select :options="categories" label="title" v-model="selectCategoryValue" @input="selectedCategory"></v-select>
                </div>
            </div>
            <div class="uk-margin-top">
                <div class="uk-grid-small uk-child-width-auto mt-10" uk-grid>
                    <label><input type="radio" class="uk-radio" id="installed" value="installed" v-model="picked">show only installed</label>
                    <label><input type="radio" class="uk-radio" id="uninstalled" value="uninstalled" v-model="picked"> show only uninstalled</label>
                    <label><input type="radio" class="uk-radio" id="updateable" value="updateable" v-model="picked">show only updateable</label>
                    <label><input type="radio" class="uk-radio" id="recommended" v-model="picked" value="recommended"> show most recommended</label>
                    <span>Picked: {{ picked }}</span>
                </div>
            </div>
            <div class="uk-margin-top">
                Display as:
                <label><input type="radio" class="uk-radio" value="table" v-model="layout" @change="selectedLayout"> table</label>&nbsp;
                <label><input type="radio" class="uk-radio" value="cards" v-model="layout" @change="selectedLayout"> cards</label>
            </div>
        </div>
        <div v-if="layout==='cards'">
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
                                <br/>
                                <small>Authors:</small>

                                <small>{{ module.name }} |
                                    latest version: {{ module.module_version }} {{ module.release_state.title }}
                                    <span v-if="module.status"> | </span>
                                    <span v-if="module.status" v-html="module.status"></span>
                                </small>
                                <br>
                            </div>
                            <div class="">{{ module.likes }}
                                <span class="fa fa-heart"></span>
                            </div>
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
                                            <i class="fa fa-github"/> Project on Github
                                        </a>
                                        <br>
                                        <a class v-if="module.forum_url" v-bind:href="module.forum_url" target="_blank"><i
                                                class="fa fa-comments"></i> Support Forum
                                        </a>
                                    </p>
                                    <p>
                                        Categories:
                                        <span v-for="(category, index) in module.categories" :key="category.title">
                                            <a :href="category.url">{{ category.title }}</a><span v-if="index+1 < module.categories.length">, </span>
                                        </span>
                                    </p>
                                    <p>Compatible with PW versions:<br>
                                        <span :key="index" v-for="(version, index) in module.pw_versions">
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
        <div v-else>
            <v-data-table
                    :headers="headers"
                    :items="modules"
                    :disable-pagination="true"
                    :fixed-header=true
                    :height="500"
                    :show-select="true"
                    class="elevation-1"
            >
                <template v-slot:item.actions="{ item }">
                    {{item.actions}}
                    <v-icon
                            small
                            class="mr-2"
                            @click="editItem(item)"
                    >
                        edit
                    </v-icon>
                    <v-icon
                            small
                            @click="deleteItem(item)"
                    >
                        delete
                    </v-icon>
                </template>
            </v-data-table>

            <div v-for="module in list" :key="module.title" v-show="false">
                {{ module.name }}
            </div>
        </div>
    </div>
</template>
<style>
    @import "~vue-select/dist/vue-select.css";
</style>

<script>
    /*eslint no-console: 0*/
    import $ from "jquery";
    import vSelect from 'vue-select';
    import {allmodules, categories} from "./data";

    let layout = localStorage.getItem("layout") ? localStorage.getItem("layout") : 'cards';

    export default {
        name: 'App',
        components: {
            vSelect,
        },
        data() {
            return {
                headers: [
                    {
                        text: 'Module name',
                        align: 'start',
                        sortable: true,
                        value: 'title',
                    },
                    {
                        text: 'local version',
                        value: 'module_version'
                    },
                    {
                        text: 'latest version',
                        value: 'module_version'
                    },
                    {
                        text: 'Status',
                        value: 'status'
                    },
                    {
                        text: 'Actions',
                        value: 'actions'
                    },
                ],
                layout: layout,
                modules: [],
                allmodules: allmodules,
                selectValue: null,
                selectCategoryValue: null,
                options: null,
                categories: categories,
                picked: null,
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
            selectedLayout() {
                localStorage.setItem('layout', this.layout);
            },
            selectedModule() {
                if (this.selectValue !== null) {
                    console.log('selected: ' + this.selectValue.name);
                    this.selectCategoryValue = null;
                    let stateObj = {module: this.selectValue};
                    history.pushState(stateObj, null, "?module=" + this.selectValue.name);
                }
            },
            selectedCategory() {
                if (this.selectCategoryValue !== null) {
                    this.selectValue = null;
                    let stateObj = {module: this.selectValue};
                    history.pushState(stateObj, null, "?category=" + this.selectCategoryValue.name);
                }
            },
            loadData() {
                console.log('loadData');
                this.getModuleFromUrl();
                // eslint-disable-next-line no-undef
                // @TODO make this work, so the data comes via AJAX and not as an import at the top
                // $.ajax({
                //     url: "/js/data.js",
                //     // url: "https://modules.processwire.com/export-json/?apikey=pw223&limit=400", // if we could query all modules at once
                //     dataType: 'json',
                //     type: 'get',
                //     success: function (data) {
                //         this.allmodules = data;
                //         this.getModuleFromUrl();
                //     }
                // })
            },
            getModuleFromUrl() {
                let urlParams = new URLSearchParams(window.location.search);
                let moduleName = urlParams.get('module');
                let categoryName = urlParams.get('category');
                // console.log(moduleName);
                if (moduleName) {
                    let result = allmodules.find(allmodules => allmodules.name === moduleName);
                    // console.log(result);
                    this.selectValue = result;
                } else if (categoryName) {
                    let result = categories.find(categories => categories.name === categoryName);
                    // console.log(result);
                    this.selectCategoryValue = result;
                } else {
                    // show default category
                    this.selectCategoryValue = {
                        name: "core",
                        title: "Core Modules"
                    };
                }
            }
        },
        computed: {
            blubb() {
                console.log(this);
                // console.log(this.items.actions);
                // let result = this.items?.actions?.split(', ');
                // console.log(result);
                // return result;
                return false;
            },

            list() {
                let retModule = allmodules.filter(module => {
                    this.options = allmodules;
                    let selectedCategory = this.selectCategoryValue;
                    let visible = false;

                    if (this.selectValue !== null) {
                        visible = this.selectValue.name === module.name;
                    }

                    if (selectedCategory !== null) {
                        // console.log(selectedCategory);
                        let categoryMatch = module.categories.filter(function (category) {
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
        },
    };
</script>

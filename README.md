# ModulesManager 2 beta for ProcessWire 3.x

Module Manager 2 provides an easy to use interface to download, update, install, uninstall and configure modules.

It is meant to provide an optimized alternative to the ProcessModule dashboard. Maybe Ryan agrees to merge it to the core at some point.

Features:

* Seamlessly download, update, install, uninstall or delete modules
* Live-Search (aka find as you type) for module names
* Live-Search (aka find as you type) for categories
* Browse new and unkown modules from the modules directory on modules.processwire.com
* Choose your favorite layout (cards, reduced cards, table)
* Modern UIKit design (therefore only works with AdminThemeUikit)
* Caches the module list from modules.processwire.com directory locally.
There's a **refresh** button in the main menu to get actual data from online.

## BETA software

Use this module at your own risk. I am not responsible for any damage or unexpected behaviour.
Some things might not work fully, please see the [TODO](#TODO) list for this.

## Why a new module manager?
Some people including myself think that the actual module installation in ProcessWire could be improved in many places.

Make it easy for ProcessWire beginners and power users

Offer better discoverybility to find the right module.

Make it easier and faster for powerusers to manage modules. 

A manager that list all official modules is a feature, that many other frameworks/CMS's like ModX, WordPress or PrestaShop have by default.

##What are the disadvantages of the actual core module interface?
* Installation of a module is not very user-friendly: You have to be aware where to get new [modules](https://modules.processwire.com), search for a module, copy or remember the module name or URL, go back to your ProcessWire installation, paste the module name(URL, click on "get module info" and finally install the module
* It only displays installed modules, not the ones that are available in the modules directory
* Uninstalling a module requires you to go to the module detail page, click a checkbox and then submit the change. After that you have to go back to the module overview page.
* It only displays installed modules, not the ones that are available in the modules directory, so it makes discovering modules hard

## History
Back in 2012 Somacame up with ModuleManager, a module which displays modules as a table and provides functionality to download, update, install and configure modules (same as this module does).

Then Adrian came up with the idea of [autocompleting](https://processwire.com/talk/topic/20649-revamped-modules-install-interface/) the module name and Robin S developed the [AutocompleteModuleClassName](https://processwire.com/talk/topic/21853-autocomplete-module-class-name/) module which does this.
 
This approach﻿is a nice addition for devs who know which module they are looking for. But for all others we need a browsable experience, which provides more info than just the name.

This was the perfect time for me to [chime in](https://processwire.com/talk/topic/20649-revamped-modules-install-interface/?do=findComment&comment=178827), as I thought that module management is very cumbersome at its current state.

A quick proof of concept / prototype was quickly developed. But then development slowed down, as I had to get more experience with vue.js first.
Even as I was more experienced I stumbled into some problems that were to advanced for me to tackle. So I hired someone to help.

#Installation
How to [Install](http://modules.processwire.com/install-uninstall/) this module.

#### Requirements
- ProcessWire 3+
- AdminThemeUikit
- "allow_url_fopen" to be enabled in your php.ini.
- "openssl" PHP extension needs to be installed on your server.
- PHP to have read/write access to the /site/modules/ directory

## TODO
* IMPORTANT: add a method to get the module data for one or more modules (instead of all modules) after an action has been performed, to update the status, actions, module info in the modules array
* IMPORTANT: only load module data for the selected category or module as the getData object is already 1MB (not a problem when cached or on fast networks)
* IMPORTANT: also list modules that are not installed, which are installed by other modules (they come as a package) like MarkupActivityLogService or FieldtypeContinentsAndCountries
* Install multiple modules at once like it is done in [ProcessModuleToolkit](https://github.com/adrianbj/ProcessModuleToolkit)
* IMPORTANT: make delete work correctly, sometimes it won't delete the files, especially if a module comes with more than one .module file
* add filter by installed/﻿not installed / updateable / recommended
* Allow "search for module" to search in the description also, so a module can be found by its purpose and not only by its name
* Integrate the Readme or changelog of a module as it is done in [ModuleReleaseNotes](https://processwire.com/talk/topic/17767-module-release-notes/)
    * This would have the following benefits: Make﻿ discovery of a module's changes﻿ prior to an upgrade a trivial ﻿task.
    * Make breaking changes very obvious.
    * Make reading of a module's support documentation post-install a trivial task.
    * Make module authors start to think about how they can improve the change discovery process for their modules.
    * Make sure the display of information from the module support files/﻿commit messages doesn't introduce a vulnerability﻿.
* add filter to show only modules that are compatible with the actual PW version
* sometimes wrong module version of installed core modules seem to be returned. for example for markup-htmlpurifier. This is not a problem with ModulesManager 2 but the core modules report incorrect version numbers.
    * exclude versions from core modules because of this error
* Allow "search for module" to search in the description also, so a module can be found by its purpose and not only by its name
* When clicking on a category under "more information", then filter by that category, instead of redirecting to the ProcessWire site
* when clicking on an author show all modules of this author
* trigger reload of modules array, when someone opened the settings page after installing, and uninstalled the module via the checkbox
* Display icons of installed modules? 
* make filters work for installed, uninstalled, updateable, etc.
* add button to reload modules from modules.processwire.com, or leave it in the menu?
    * perform a poll if there is a new module list on modules.processwire.com. See tickets https://github.com/processwire/processwire-requests/issues/330 and https://github.com/processwire/processwire-requests/issues/320 for this
* append version string to script to invalidate cache on new version
* add multilanguage for vue
* hook into search results to link to ModulesManager2 instead of default ProcessModule
* make delete work if there are modules that have requires, for example continents-and-countries
* make settings link work in modal after installing a module
* update table after an action has been performed
* return messages if the link is called directly and not via AJAX
* add feature to send out notifications if newer versions of modules are available?

#### How does it work

When installed you'll have a new admin page "ModulesManager 2" under "Setup", and it appears in the menu under "Setup". Feel free to move it to wherever you like. On first load it will download and cache a json object from modules.processwire.com.
This is to speed up frequent requests.

Then the modules are output into a vue.js template. This enables quick filtering and reactive rendering.

### Support

You found a bug? Please create an issue here or in the [forums](https://processwire.com/talk/topic/22285-modulesmanager-2-install-update-and-uninstall-your-modules-wip) 
You want to help me improve this module? Clone it and create a pull request please.

#### Changelog

2.0.0 initial beta version


### Project setup
Please note: If running in development mode (NODE_ENV=development) the file modules.json and categories.json are used. In this file some modules have a different status than in reality.
For example the process404-logger has the installed status and Process404Search has the status updateable. This is for debugging purposes, so you can directly see all states while developing. 
```
npm install
```

### Compiles and hot-reloads for development
```
npm run dev
```
After running this command the url localhost:8080 is opened where you can test all things regarding the frontend (vue application) 

### Compiles and minifies for production
```
npm run build
```

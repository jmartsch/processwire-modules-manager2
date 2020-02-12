# Modules Manager 2 for ProcessWire 3.x

Module Manager 2 provides an easy to use interface to download, update, install, uninstall and configure modules.

It is meant to provide an optimized alternative to the ProcessModule dashboard. Maybe Ryan agrees to merge it to the core at some point.

Features:

* Live-Search (aka find as you type) for categories
* Live-Search (aka find as you type) for module names
* Modern UIKit design
* Quick uninstall of a module
* Caches the module list from modules.processwire.com directory locally.
There's a **refresh** button to get actual data.

## Why a new module manager?
Some people including myself think that the actual module installation in ProcessWire could be improved in many places.

Make it easy for everybody!
Lower the barrier for new users, and make it easier for existing users to find an install modules. 

That is one thing, that many other frameworks/CMS's have by default. Like ModX, WordPress or PrestaShop.

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
Install multiple modules at once like it is done in [ProcessModuleToolkit](https://github.com/adrianbj/ProcessModuleToolkit)

add filter by installed/﻿not installed / updateable / recommended

Allow "search for module" to search in the description also, so a module can be found by its purpose and not only by its name

Integrate the Readme or changelog of a module as it is done in [ModuleReleaseNotes](https://processwire.com/talk/topic/17767-module-release-notes/)
* This would have the following benefits: Make﻿ discovery of a module's changes﻿ prior to an upgrade a trivial ﻿task.
* Make breaking changes very obvious.
* Make reading of a module's support documentation post-install a trivial task.
* Make module authors start to think about how they can improve the change discovery process for their modules.
* Make sure the display of information from the module support files/﻿commit messages doesn't introduce a vulnerability﻿.

#### How does it work

When installed you'll have a new admin page "Modules Manager 2" under "Setup", and it appears in the menu under "Setup". Feel free to move it to wherever you like. On first load it will download and cache a json object from modules.processwire.com.
This is to spead up frequent requests.

Then the modules are output into a vue.js template. This enables quick filtering and reactive rendering.

#### Notes

This version is still beta. Feel free to try it out own your own risk. 

### Module development

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
npm run serve
```

### Compiles and minifies for production
```
npm run build
```

### Run your tests
```
npm run test
```

### Lints and fixes files
```
npm run lint
```


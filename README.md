# Modules Manager 2 for ProcessWire 3.x

Module Manager 2 enables you to easily download, update, install and configure modules.

Features:

* Quick Filtering of categories
* Modern UIkit design
* Live-Search/Filtering﻿﻿
* Quick uninstall of a module﻿(not yet)

It is meant to replace the actual ProcessModule module at a later point, if Ryan agrees to merge it to the core.
As long as this is not the case and this module is not on par with the core ProcessModule module, it can only be installed additionally.

## Why a new module mannager?
Some people including myself think that the actual module installation in ProcessWire could be improved in many places.
Firstly @adrian came up with the idea of an autocomplete for the module name:
https://processwire.com/talk/topic/20596-new-post-new-pw-website-ready/page/8/?tab=comments#comment-178781

This was the perfect time for me to [chime in](https://processwire.com/talk/topic/20649-revamped-modules-install-interface/?do=findComment&comment=178827), as I thought that module management is very cumbersome at its current state.

A quick proof of concept / prototype was quickly developed. But then development slowed down, as I had to get more experience with vue.js first.
Even as I was more experienced I stumbled into some problems that were to advanced for me to tackle. So I hired someone to help.

Why should you leave your PW installation to go to the ProcessWire modules website, of which you have to be aware of, search for a module, copy or remember the module name, go back to your ProcessWire installation, paste the module name, click on "get module info" and finally install the module?

Make it easy for everybody﻿﻿!
Lower the barrier for new users, and make it easier for existing users to find an install modules. 

That is one thing, that many other frameworks/CMS's have by default. Like ModX, WordPress or PrestaShop.

## TODO
Install multiple modules at once like it is done in [ProcessModuleToolkit](https://github.com/adrianbj/ProcessModuleToolkit)

add filter by installed/﻿not installed / updateable / recommended

Integrate the Readme or changelog of a module as it is done in [ModuleReleaseNotes](https://processwire.com/talk/topic/17767-module-release-notes/)
* This would have the following benefits: Make﻿ discovery of a module's changes﻿ prior to an upgrade a trivial ﻿task.
* Make breaking changes very obvious.
* Make reading of a module's support documentation post-install a trivial task.
* Make module authors start to think about how they can improve the change discovery process for their modules.
* Make sure the display of information from the module support files/﻿commit messages doesn't introduce a vulnerability﻿.

#### Requires

- "allow_url_fopen" to be enabled in your php.ini.
- "openssl" PHP extension needs to be installed on your server.
- PHP to have read/write access to the /site/modules/ directory

#### How does it work

When installed you'll have a new admin page called "Modules Manager 2" under "Setup", feel free to move it to wherever you like. On first load it will download and cache a json file from where it will look for modules already installed, new versions, or modules not yet installed or not downloaded and provide actions according to its state.

There's a **refresh** button to look for new modules already put in modules directory and refresh the cache file with the remote list of modules.

#### Notes

This version is still beta and in testing. Feel free to try it out own your own risk. Current version uses file_get_contents" and "copy" php methods to retrieve the json feed from external domain. This requires the php to allow it in php (allow_url_fopen). Download of the module zip is done using copy(). Also on my local install I had to adjust the /site/modules/ and /site/assets/ directory to have write permission by php.

### Module development

You want to help me improve this module? Clone it and create a pull request please.
### Project setup
This section is not complete yet, please ignore it
```
yarn install
```

### Compiles and hot-reloads for development
```
yarn run serve
```

### Compiles and minifies for production
```
yarn run build
```

### Run your tests
```
yarn run test
```

### Lints and fixes files
```
yarn run lint
```

#### Changelog

0.1.0 initial version

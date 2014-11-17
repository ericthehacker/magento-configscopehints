# EW_ConfigScopeHints

This is a Magento module to show information in the System Configuration when a field is overridden at 
more specific scope(s), along with information about these scope(s).

## Installation

Install via [modman](https://github.com/colinmollenhour/modman):

```
$ cd <magento root>
$ modman init # if you've never used modman before
$ modman clone https://github.com/ericthehacker/magento-configscopehints.git
```

## Usage

After installing the module, when viewing a system configuration field, an alert bulb will be shown next to the 
field scope when the field's value is overridden at a more specific level. 

The icon is only shown when the value is overridden at a more specific scope than the current one – that is,
if viewing at the default scope, overrides at the website or store view level are shown, but if viewing at the 
website level, only overrides below the currently selected website are shown.

Clicking on the notification bulb displays a detailed list of the exact scope(s) that override the value, 
with links directly to those scopes.

![Screenshot of system config scope hints module](https://ericwie.se/assets/img/work/config-scope-hints.png)

## Rewrites

I avoid Magento rewrites, and so should you.

In the interest of full disclosure, this module rewrites `adminhtml/system_config_form` block to alter a single method.

This method unfortunately does not dispatch any events, so I feel that this rewrite was necessary.

# _custom/

This directory is for custom code specific to your Tiki install.  It is meant to be independently versioned (it's own git repository, or other means.)

As such, it will not store credentials such as the database configuration file.

N.B. this directory and all files within should be neither web readable or writable.

## Currently supported

* **sites/**  
  * **subdomain.domain.tld/**
    * This is for [multi-tiki](https://doc.tiki.org/MultiTiki).  Each folder under this should be named subdomain.domain.tld, and will be used if the http hostname matches.
  * **default_site/**  
    * This works the same as other directories under sites, but is used for the common case where multitiki isn't used, or if the domain doesn't match any site.
    * This is ONLY used for domains NOT matching any other directory under sites.  If you want shared custom themes between your sites, use shared/themes.
  * Notes:
    * Any of the subdirectories in sites can have the following as subfolders (see the description below in shared/)
      * js
      * lang
      * templates
      * themes

* **shared/**
  * This is for themes, templates, etc. shared between all the sites.  Most people will only use this, since their tiki serves a single domain.
  * **custom.php**
    * Adds custom php code and handlers.  See [shared/custom.php](./shared/custom.php) for more details
  * **custom.xml**
    * See [The class loader documentation](../db/config/README.md)
  * **js**
    * **custom.js** Will be loaded by tiki as a normal script, you can put anything in there.
    * **\*.js** All other js files will be copied in public so they are available from your custom php code.

  * **lang/**
    * Sub-folders are named by language codes (ex: fr, en-uk), including 'en', so you can also override the text of the default english interface.  That is the typical way to change strings in a custom site
    * $lg/custom.php  See [shared/lang/en/custom.php](./shared/lang/en/custom.php) for format
    * $lg/custom.js  See [shared/lang/en/custom.js](./shared/lang/en/custom.js) for format
  * **templates/**
    * Any template under this will **replace** the corresponding templates in [templates/](../templates/)
  * **themes/**
  * **wiki-plugins/**
    * Typically client specific plugins, but any plugin with the same name as a base tiki plugin will override the tiki implementation.


## Themes

Note that themes here differ subtly from those in themes/ at the root.  They are compiled into public/_custom/**/themes, rather than being used in place.

Among other implications, to live develop them, you need to run npm run watch, not just npm run watch:scss to make sure assets (images, fonts, etc.) are copied.

Otherwise, they follow the [themes documentation](../themes/README.md), including being allowed to contain js, lang, and templates (but this is generally not recommended, it should only be used for code directly related to the template's visual.)
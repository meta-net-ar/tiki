# `lib/smarty_tiki/`

This is where all the Tiki custom [Smarty](https://www.smarty.net/) modifiers, blocks and functions live for [extending Smarty](https://smarty-php.github.io/smarty/5.x/api/extending/introduction/).

Since [Tiki-27](https://doc.tiki.org/Tiki27) we migrated from [Smarty 4](https://smarty-php.github.io/smarty/4.x/) to [Smarty 5](https://smarty-php.github.io/smarty/5.x/). The next lines explain what changed due to the [migration to Smarty 5](https://gitlab.com/tikiwiki/tiki/-/merge_requests/3529).

## `lib/smarty_tiki directory structure`

With [Smarty 5](https://smarty-php.github.io/smarty/5.x/) we migrated from the use of plugins to [Extensions](https://smarty-php.github.io/smarty/5.x/api/extending/extensions/), a new concept introduced in Smarty 5 in order to organize your [custom tags(functions, blocks, and filters)](https://smarty-php.github.io/smarty/5.x/api/extending/tags/) and [modifiers](https://smarty-php.github.io/smarty/5.x/api/extending/modifiers/).

### `lib/smarty_tiki before we migrate to smarty 5`

Before [Smarty 5](https://smarty-php.github.io/smarty/5.x/), all our custom tags and modifiers where in the same directory(lib/smarty_tiki), which was not a proper way of doing things(storing functions, blocks, filters and modifies in the same directory).

### `lib/smarty_tiki after migrated to smarty 5`

During the migration to [Smarty 5](https://smarty-php.github.io/smarty/5.x/), we added new directories in order to organize our custom tags instead of keeping all in the same directory.

Note that in the new structure of `lib/smarty_tiki directory`, we still keep the files that contained our custom tags and modifiers instead of removing(which should normally be done) them. The reason why we kept them is to avoid to have to change over hundreds and thousands of places in Tiki where those functions where called replacing their call with their new implementation as with the use of [Extensions](https://smarty-php.github.io/smarty/5.x/api/extending/extensions/), Smarty tags(functions, blocks, and filters) and modifiers are implemented in to classes and then added as part of an Extension. So we kept them to serve as bridge to the new implementation of tags and modifiers.

Here are directories added during the migration to smarty 5 and use of Extension.

* #### `lib/smarty_tiki/Extension`

Here is where we store all the extensions. You can have more than one Extension but in Tiki we have just one(`lib/smarty_tiki/Extension/SmartyTikiExtension.php`) for extending Smarty and organizing our custom tags and modifiers, as one Extension is more sufficient.

Also, note that in Smarty 5, Smarty dropped the support of using PHP functions as modifiers and recommends that if you want to use a PHP function as a modifier, create it as custom modifiers and add it to your Extension. Despite that, they implemented the most used PHP functions as modifiers and add them to the [Smarty Default Extension](https://github.com/smarty-php/smarty/blob/master/src/Extension/DefaultExtension.php), so you have just to implement those PHP functions that you had to use as modifiers and they are not part of Smarty Default Extenstion. So, all our custom modifiers that are native PHP functions are implemented in our Extension class (lib/smarty_tiki/Extension/SmartyTikiExtension).

* #### `lib/smarty_tiki/FunctionHandler`

Here is where we store our custom tags/functions.

* #### `lib/smarty_tiki/BlockHandler`

Here is where we store our custom block tags.

* #### `lib/smarty_tiki/Compile`

Here is where we store our custom [compiler tags](https://smarty-php.github.io/smarty/5.x/api/extending/tags/#compiler-tags) and modifies. This directory contains subdirectories: 

-`Tag`: for compiler tags.

-`Modifier`: for compiler modifiers.

* #### `lib/smarty_tiki/Modifier`
Here is where we store our custom modifiers proper to Tiki and not native PHP functions.

* #### `lib/smarty_tiki/Filter`
Here is where we store our custom filters. It contains 2 subdirectories:
-`Output`: for ouptut filters.
-`Pre`: for prefilters.

## `Smarty Security policy update`
Related commit: [https://gitlab.com/tikiwiki/tiki/-/merge_requests/3529/diffs?commit_id=a8b5b69ba298dd6f9eabdf11aa6a00c7e18a3285](https://gitlab.com/tikiwiki/tiki/-/merge_requests/3529/diffs?commit_id=a8b5b69ba298dd6f9eabdf11aa6a00c7e18a3285)


We updated the smarty security preferences. Due to Smarty update(drop the support of PHP functions as smarty functions and modifiers), We removed the preference smarty_security_modifiers and smarty_security_functions. We added new preferences and offer a possibility to the admin from the admin interface to define allowed tags and modifiers that should be used/accessible or not to the template.
Added preferences are:

* `smarty_security_allowed_tags`:

This is a list of allowed tags. It's the list of (registered / autoloaded) function-, block and filter plugins that should be accessible to the template. If empty, no restriction by allowed_tags. This may be needed for custom templates.

* `smarty_security_disabled_tags`:

This is a list of disabled tags. It's the list of (registered / autoloaded) function-, block and filter plugins that may not be accessible to the template. If empty, no restriction by disabled_tags. This may be needed for custom templates.

* `smarty_security_allowed_modifiers`:

This is the list of allowed modifier plugins. It's the array of (registered / autoloaded) modifiers that should be accessible to the template. If this array is non-empty, only the herein listed modifiers may be used. This is a whitelist. If empty, no restriction by allowed_modifiers. This may be needed for custom templates.

* `smarty_security_disabled_modifiers`:

This is a list of disabled modifier plugins. It's the list of (registered / autoloaded) modifiers that may not be accessible to the template. If empty, no restriction by disabled_modifiers. This may be needed for custom templates.

See [https://smarty-php.github.io/smarty/5.x/api/security/](https://smarty-php.github.io/smarty/5.x/api/security/) for info about Smarty Security.

## `How to create Tiki custom Smarty tags and modifiers`
The use of an Extension to organize custom tags and modifiers changes the way of creating custom tags and modifiers. Follow the instructions below to create ones in Tiki and add them to our custom Smarty Extension to make them autoloadable and ready to be used in templates, so you don't need to register a function or a filter before using it as it was in previous implementation.


### `Custom tags(functions)`

#### `Runtime tags`
* In the directory `lib/smarty_tiki/FunctionHandler`, create a class that extends `Smarty\FunctionHandler\Base` class, with name of the class the name of the custom tag. That class must be in the namespace `SmartyTiki\FunctionHandler`.
* The created class must implement the `handle()` method of the class `Smarty\FunctionHandler\Base`. The `handle()` method requires two parameters: the first is `$params` which represents all attributes from the template as an associative array, and the second is `$template` which is a `Smarty\Template` object representing the template where tag was used.
For example of implementation, please look into the directory `lib/smarty_tiki/FunctionHandler`, there are custom tags already implemented and used in Tiki, you can get inspiration from them.
* After you implement the custom tag class, to use it in the template you need first to add it to the Tiki custom Smarty Extension (`SmartyTiki\Extension\SmartyTikiExtension`). To do that: open the file `lib/smarty_tiki/Extension/SmartyTikiExtension.php`, then look for the function `getFunctionHandler()` and then in the `switch (){}` statement, add a  block case of the form:
 ```
 case 'tagName':
    $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TagName();
    break;
```
replacing tagName with the name of the tag you created.

#### `Compiler tags`
* In the directory `lib/smarty_tiki/Compile/Tag`, create a class that extends `Smarty\Compile\Base` class, with name of the class the name of the custom tag. That class must be in the namespace `SmartyTiki\Compile\Tag`.
* The created class must implement the `compile()` method of the interface `Smarty\Compile\CompileInterface`. 
For example of implementation, please look into the directory `lib/smarty_tiki/Compile/Tag`, there are custom compiler tags alredy implemented and use in Tiki, refer to it to get the list of parameters that the `compile` function should have.
* After you implemneted the cusotm compiler tag class, to use it in the template you need first to add it to the Tiki custom Smarty Extension (`SmartyTiki\Extension\SmartyTikiExtension`). To do that: open the file `lib/smarty_tiki/Extension/SmartyTikiExtension.php`, then look for the function getTagCompiler and then in the `switch (){}` statement, add a  block case of the form:
 ```
 case 'tagName':
    $this->tags[$tagName] = new \SmartyTiki\Compile\Tag\TagName();
    break;
```
replacing tagName with the name of the tag you created.

### `Custom block tags`

In the directory `lib/smarty_tiki/BlockHandler`, create a class that extends `Smarty\BlockHandler\Base` class. That class must be in the namespace `SmartyTiki\BlockHandler`.
* The created class must implement the `handle()` method of the class `Smarty\BlockHandler\Base`. The `handle` method requires the following parameters in the given order: \$params, \$content, Template \$template, \&$repeat. See [custom blocks doc](https://smarty-php.github.io/smarty/5.x/api/extending/block-tags/) for details about the type and details of these parameters.
For example of implementation, please look into the directory `lib/smarty_tiki/BlockHandler`, there are custom block tags alredy implemented and use in Tiki.
* After you implemneted the cusotm block tag class, to use it in the template you need first to add it to the Tiki custom Smarty Extension (`SmartyTiki\Extension\SmartyTikiExtension`). To do that: open the file `lib/smarty_tiki/Extension/SmartyTikiExtension.php`, then look for the function getBlockHandler and then in the `switch (){}` statement, add a case block of the form:
 ```
 case 'blockTagName':
    $this->tags[$tagName] = new \SmartyTiki\BlockHandler\blockTagName();
    break;
```
replacing blockTagName with the name of the tag you created.

### `Custom modifiers`

We have custom modifiers that are proper for Tiki and those which are native PHP functions created and added as part of our Extension due to the drop of the support of using PHP functions as modifiers in tempalates. So implemented them differently when extending Smarty using the Extension approach.

#### `Tiki custom modifiers`
* In the directory `lib/smarty_tiki/Modifier`, create a class that has the name of the mmodifier you want to create. The created class must be in the namespace `SmartyTiki\Modifier`.
* Create a `handle()` that accepts as its first parameter the value on which the modifier is to operate. The rest of the parameters are optional, depending on what kind of operation is to be performed. The `handle()` method has to return the result of its processing.
* Add the created modifier to Tiki custom Extension: In the class `SmartyTiki\Extension\SmartyTikiExtension`, look for the function `getModifierCallBack()`, then in the `switch(){}` statement add a `case` block of the from: 
```
case 'modifier_name':
    return [new \SmartyTiki\Modifier\modifierName(), 'handle'];
```
replacing `modifier_name` with the name of the modifier and `modifierName` with the name of the created class.

#### `Custom modifiers which are PHP native functions`
In the file `lib/smarty_tiki/Extension/SmartyTikiExtension.php`
* In the class `SmartyTiki\Extension\SmartyTikiExtension`, add a new function which name starts with `smartyModifier` to differentiate modifier function from the Extension handler function, and ends with the name of the modifier you want to create in camel case format that accepts as its first parameter the value on which the modifier is to operate. The rest of the parameters are optional, depending on what kind of operation is to be performed. The function has to return the result of its processing.
* Add the modifier to Tiki custom Extension: In the same class, in the function `getModifierCallback()`, then in the `switch(){}` statement add a `case` block of the from:
```
case 'modifier_name':
    return [$this, 'smartyModifierModifierName'];
```
replacing `modifier_name` with the name of the modifier and `smartyModifierModifierName` which is the callback function with the created function that implemts the modifier processing.

### `Custom filters`

#### `Output filters`
* In the directory `lib/smarty_tiki/Filter/Output`, create a class that extends `Smarty\Filter\FilterInterface`. The created class must be in the namespace `SmartyTiki\Filter\Output`.
* Implement the `filter()` method of the interface `Smarty\Filter\FilterInterface` that accepts two parameters: the first(`$source` or `code` or call it as you want) represents the template code on which to apply the filter, and the second, `Smarty\Template $template` is the object representing the template where tag was used. The method has to return the result of its processing.
* Add the output filter to the Extension: In the class `SmartyTiki\Extension\SmartyTikiExtension`, look for the function `getOutputFilters()` and add an instance of the created output filter class to the `outputFilters array`. The ouputFilters array is a private attribute of the class `SmartyTiki\Extension\SmartyTikiExtension`. If the created output filter has to be applied in specific conditions, add the output filter to the outputFilters array based on those conditions. Check the `getOutputFilters()` for inspiration on how to add a new output filter to the extension. Once the output filter is added to the Extension, every time is rendered, its output will be sent through the output filter.if the conditions are satisfied.

#### `Prefilters`
* In the directory `lib/smarty_tiki/Filter/Pre`, create a class that extends `Smarty\Filter\FilterInterface`. The created class must be in the namespace `SmartyTiki\Filter\Pre`.
* Implement the `filter()` method of the interface `Smarty\Filter\FilterInterface` that accepts two parameters: the first(`$source` or `code` or call it as you want) represents the template code on which to apply the filter, and the second, `Smarty\Template $template` is the object representing the template where tag was used. The method has to return the result of its processing.
* Add the prefilter to the Extension: In the class `SmartyTiki\Extension\SmartyTikiExtension`, look for the function `getPrefiliters()` and add an instance of the created prefilter class to the `preFilters array`. The preFilters array is a private attribute of the class `SmartyTiki\Extension\SmartyTikiExtension`. If the created prefilter has to be applied in specific conditions, add the prefilter to the prefiters array based on those conditions. Check the `getPreFilters()` function for inspiration on how to add a new prefilter to the extension. Once the prefilter is added to the Extension it will be executed every time before rendering a template if the conditions are satisfied.

#### `Postfilters`
Please inspire yourself to the prefilters implementation to add one.

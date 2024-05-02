# db/config/

These files control what is returned by TikiLib::lib("something...")

* [controllers.xml](controllers.xml):
* [mailin.xml](mailin.xml):
* [tiki.xml](tiki.xml):  
* custom.xml:  Do not modify the above 3 files, instead create a _custom/shared/custom.xml file here.   You can see usage examples in the bottom of [_custom_dist/shared/custom.xml](../../_custom_dist/shared/custom.xml)

This is based on <https://symfony.com/doc/current/components/dependency_injection.html> From the initial commit message, this was intended as an extension mechanism.

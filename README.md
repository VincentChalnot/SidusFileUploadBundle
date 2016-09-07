Sidus/FileUploadBundle
===========

This bundle allows you to define Doctrine entities linked to a virtual filesystem entry
through the Gaufrette storage layer.

This means that when uploading a file, it will automatically create a corresponding entity
in your database matching your resource type.

WARNING: These entities are meant to be associated to other Doctrine entities, not really
managed by themselves: If you want to add a title and a description to a file, create a
different entity and create an association to the resource.

The upload part is handled by the Oneup Uploader Bundle with very little magic added to it.

This documentation is a work in progress, the bundle is working as is but might require more
configuration than what is explained here.

## Installation

You will need to include jQuery in your project on your own.

```bash
$ composer require sidus/file-upload-bundle
```

Additional configuration in your composer.json to expose the jquery-fileupload vendor in
public directory of this bundle.

```json
{
    "scripts": {
        "post-install-cmd": [
            // Append this just after te clear-cache script
            "Sidus\\FileUploadBundle\\Composer\\ScriptHandler::symlinkJQueryFileUpload"
        ],
        "post-update-cmd": [
            // Append this just after te clear-cache script
            "Sidus\\FileUploadBundle\\Composer\\ScriptHandler::symlinkJQueryFileUpload"
        ]
    }
}

```

Add the form template in twig.

```yml
twig:
    form:
        resources:
            # ...
            - 'SidusFileUploadBundle:Form:fields.html.twig'
```

Configuration example for OneUploader using gaufrette with local filesystem

```yml
# one uploader
oneup_uploader:
    mappings:
        document:
            frontend: blueimp
            storage:
                type: gaufrette
                filesystem: gaufrette.resources_filesystem
            max_size: 64000000

knp_gaufrette:
    adapters:
        resources:
            local:
                directory: '%resources_dir%/generated'
    filesystems:
        resources:
            adapter: resources
```

Now define your uploadable entities, being careful with the configuration keys

```yml
sidus_file_upload:
    filesystem_key: resources
    configurations:
        document: # must match oneup_uploader mapping key
            entity: MyNamespace\AssetBundle\Entity\Document

```

Somewhere in your configuration, for example in your parameters.yml if you plan to
change this location depending of your installation.

```yml
parameters:
    resources_dir: '%kernel.root_dir%/data/resources'
```

Add the upload routes to your routing:

```yml
sidus_file_upload:
    resource: "@SidusFileUploadBundle/Resources/config/routing.yml"
```

Minimum requirements for your entity

```php
<?php

namespace MyNamespace\AssetBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sidus\FileUploadBundle\Entity\Resource;

/**
 * @ORM\Entity(repositoryClass="Sidus\FileUploadBundle\Entity\ResourceRepository")
 */
class Document extends Resource
{
    /**
     * @return string
     */
    public static function getType()
    {
        return 'document'; // Must match your mapping key
    }
}
```

Load the necessary CSS and JS in your layout (there are many way to do this)

    'bundles/sidusfileupload/css/fileupload.css'

    'bundles/sidusfileupload/vendor/jquery-file-upload/js/jquery.fileupload.js'
    'bundles/sidusfileupload/vendor/jquery-file-upload/js/jquery.fileupload-jquery-ui.js'
    'bundles/sidusfileupload/vendor/jquery-file-upload/js/jquery.iframe-transport.js'
    'bundles/sidusfileupload/js/jquery.fileupload.sidus.js'


To load the widget on document initialization (not exactly the proper way to do it...)

```js
$(document).find('.fileupload-widget').each(function () {
    $(this).sidusFileUpload();
});
```

When creating a form, you can simply use the "sidus_resource" type to declare the field as an
upload. You will only require to set the "resource_type" options corresponding to the entity
you want to use.

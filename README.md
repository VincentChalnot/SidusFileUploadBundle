Sidus/FileUploadBundle
===========

This bundle allows you to define Doctrine entities linked to a virtual filesystem entry
through the Flysystem storage layer.

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

Update your Kernel:

```php
<?php

class AppKernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = [
            // ...
            new Oneup\UploaderBundle\OneupUploaderBundle(),
            new Oneup\FlysystemBundle\OneupFlysystemBundle(),
            new Sidus\FileUploadBundle\SidusFileUploadBundle(),
            // ...
        ];
        // ...
    }
}
```
Note: The order is very important because we override some parameters frop Oneup.

Additional configuration in your composer.json to expose the jquery-fileupload vendor in
public directory of this bundle.

```json
{
    "scripts": {
        "symfony-scripts": [
            // Append this before the clear-cache script
            "Sidus\\FileUploadBundle\\Composer\\ScriptHandler::symlinkJQueryFileUpload"
        ]
    }
}

```

Configuration example for OneUploader using Flysystem with local filesystem.

Note that in order for Sidus/FileUpload to work, the keys for the OneUploader, Flysystem and Sidus must match.

```yml
# one uploader
oneup_uploader:
    mappings:
        document:
            frontend: blueimp
            storage:
                type: flysystem
                filesystem: oneup_flysystem.document_filesystem
            max_size: 64000000

oneup_flysystem:
    adapters:
        my_adapter:
            local:
                directory: '%kernel.root_dir%/../var/data/resources'
    filesystems:
        document:
            adapter: my_adapter
```

Note the reference in Oneup Uploader to the Flysystem filesystem :

``` document -> oneup_flysystem.document_filesystem ```

Now define your uploadable entities, being careful with the configuration keys

```yml
sidus_file_upload:
    configurations:
        document: # This key will be the entrypoint to any fileupload form widget
            entity: MyNamespace\AssetBundle\Entity\Document
            filesystem: document # Optional, defaults to configuration key
            uploader: document # Optional, defaults to configuration key
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

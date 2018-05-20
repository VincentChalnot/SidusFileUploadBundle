<?php

namespace Sidus\FileUploadBundle\Utilities;

/**
 * Transliterate filenames to escape forbidden characters
 */
class FilenameTransliterator
{
    /**
     * @param string $originalFilename
     *
     * @return string
     */
    public static function transliterateFilename($originalFilename)
    {
        $transliterator = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
        $string = $transliterator->transliterate($originalFilename);

        return trim(
            preg_replace(
                '/[^\x20-\x7E]/',
                '_',
                $string
            ),
            '_'
        );
    }
}

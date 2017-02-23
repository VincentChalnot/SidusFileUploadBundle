<?php

namespace Sidus\FileUploadBundle\Composer;

use Composer\Script\Event;
use Mopa\Bridge\Composer\Util\ComposerPathFinder;

/**
 * Composer script to symlink jQuery FileUpload plugin to the public directory of this bundle
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ScriptHandler
{
    /**
     * Symlink JQuery File Upload plugin in the public directory
     *
     * @param Event $event
     *
     * @throws \Exception
     */
    public static function symlinkJQueryFileUpload(Event $event)
    {
        $IO = $event->getIO();
        $composer = $event->getComposer();
        $cmanager = new ComposerPathFinder($composer);
        $ds = DIRECTORY_SEPARATOR;
        $options = [
            'targetSuffix' => self::getTargetSuffix('jquery-file-upload'),
            'sourcePrefix' => "..{$ds}..{$ds}..{$ds}",
        ];
        list($symlinkTarget, $symlinkName) = $cmanager->getSymlinkFromComposer(
            'sidus/file-upload-bundle',
            'blueimp/jquery-file-upload',
            $options
        );

        $IO->write('Checking Symlink', false);
        if (false === self::checkSymlink($symlinkTarget, $symlinkName, true)) {
            $IO->write('Creating Symlink: '.$symlinkName, false);
            self::createSymlink($symlinkTarget, $symlinkName);
        }
        $IO->write('<info>OK</info>');
    }

    /**
     * Checks symlink's existence.
     *
     * @param string $symlinkTarget The Target
     * @param string $symlinkName   The Name
     * @param boolean $forceSymlink Force to be a link or throw exception
     *
     * @return boolean
     * @throws \Exception
     */
    public static function checkSymlink($symlinkTarget, $symlinkName, $forceSymlink = false)
    {
        if ($forceSymlink && file_exists($symlinkName) && !is_link($symlinkName)) {
            if ('link' !== filetype($symlinkName)) {
                throw new \UnexpectedValueException("{$symlinkName} exists and is not a link");
            }
        } elseif (is_link($symlinkName)) {
            $linkTarget = readlink($symlinkName);
            if ($linkTarget !== $symlinkTarget) {
                if (!$forceSymlink) {
                    throw new \UnexpectedValueException(
                        "Symlink '{$symlinkName}' points to '{$linkTarget}' instead of '{$symlinkTarget}'"
                    );
                }
                unlink($symlinkName);

                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Create the symlink.
     *
     * @param string $symlinkTarget The Target
     * @param string $symlinkName   The Name
     *
     * @throws \Exception
     */
    public static function createSymlink($symlinkTarget, $symlinkName)
    {
        if (false === @symlink($symlinkTarget, $symlinkName)) {
            throw new \UnexpectedValueException("An error occurred while creating symlink '{$symlinkName}'");
        }
        if (false === $target = readlink($symlinkName)) {
            throw new \UnexpectedValueException("Symlink {$symlinkName} points to target {$target}");
        }
    }

    protected static function getTargetSuffix($end = '')
    {
        $ds = DIRECTORY_SEPARATOR;

        return "{$ds}Resources{$ds}public{$ds}vendor".($end ? $ds.$end : '');
    }
}

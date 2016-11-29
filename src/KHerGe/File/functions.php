<?php

namespace KHerGe\File;

use KHerGe\File\Exception\PathException;

/**
 * Returns the last modified Unix timestamp for the given path.
 *
 * ```php
 * $timestamp = modified('/path/to/file');
 * ```
 *
 * @param string $path The path to read the last modified timestamp for.
 *
 * @return integer The Unix timestamp.
 *
 * @throws PathException If the last modified Unix timestamp could not be read.
 */
function modified($path)
{
    if (!file_exists($path)) {
        throw new PathException(
            'The path "%s" does not exist.',
            $path
        );
    }

    $timestamp = filemtime($path);

    if (false === $timestamp) {
        throw new PathException(
            'The last modified Unix timestamp for the path "%s" could not be read.',
            $path
        );
    }

    return $timestamp;
}

/**
 * Returns the Unix permissions for the given path.
 *
 * ```php
 * $permissions = permissions('/path/to/file');
 * ```
 *
 * @param string $path The path to read the permissions for.
 *
 * @return integer The Unix permissions.
 *
 * @throws PathException If the permissions could not be read.
 */
function permissions($path)
{
    if (!file_exists($path)) {
        throw new PathException(
            'The path "%s" does not exist.',
            $path
        );
    }

    $permissions = fileperms($path);

    if (false === $permissions) {
        throw new PathException(
            'The permissions for the path "%s" could not be read.',
            $path
        );
    }

    return $permissions;
}

/**
 * Recursively removes a path from the file system.
 *
 * This function will delete a file path or recursively delete a directory path.
 * If the directory path is a symbolic link, it will not be followed by default
 * and the link itself will be deleted. If `$follow` is set to true, the link is
 * followed and the contents of the directory are also deleted.
 *
 * ```php
 * // Remove a directory path.
 * remove('/path/to/dir');
 *
 * // Remove a file path.
 * remove('/path/to/file');
 * ```
 *
 * @param string  $path   The path to remove.
 * @param boolean $follow Follow symlinks?
 *
 * @throws PathException If the path could not be removed.
 */
function remove($path, $follow = false)
{
    $link = is_link($path);

    if (is_dir($path) && (!$link || $follow)) {
        $dir = opendir($path);

        if (!$dir) {
            throw new PathException(
                'The directory "%s" could not be opened.',
                $path
            );
        }

        $last = null;

        while (false !== ($last = readdir($dir))) {
            if (('.' === $last) || ('..' === $last)) {
                continue;
            }

            $last = $path . DIRECTORY_SEPARATOR . $last;

            remove($last, $follow);
        }

        closedir($dir);

        if ($link) {
            remove($path);
        } elseif (!rmdir($path)) {
            throw new PathException(
                'The directory "%s" could not be removed.%s',
                $path,
                (null === $last)
                    ? ''
                    : sprintf(' The path "%s" was probably not deleted.', $last)
            );
        }
    } elseif (!unlink($path)) {
        throw new PathException(
            'The path "%s" could not be removed.',
            $path
        );
    }
}

/**
 * Recursively resolves a symbolic link.
 *
 * This function will recursively resolve a symbolic link until the final path
 * returned is a regular path or does not exist. This function will not verify
 * that the target path actually exists. To prevent recursive resolution of a
 * symbolic link, `$recursive` can be set to `false`.
 *
 * ```php
 * $real = resolve('/path/to/link');
 * ```
 *
 * @param string  $link      The name of the symbolic link.
 * @param boolean $recursive Recursively resolve the link?
 *
 * @return string The path from the resolved symbolic link.
 *
 * @throws PathException If the symbolic link could not be resolved.
 */
function resolve($link, $recursive = true)
{
    if (!is_link($link)) {
        throw new PathException(
            'The path "%s" is not a symbolic link.',
            $link
        );
    }

    $real = $link;

    do {
        $real = readlink($real);

        if (false === $real) {
            throw new PathException(
                'The symbolic link "%s" for "%s" could not be resolved.',
                $real,
                $link
            );
        }
    } while ($recursive && is_link($real));

    return $real;
}

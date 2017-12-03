<?php

/**
 * Recursive clean 'tests_upload' directory
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 02.12.2017. Last modified on 02.12.2017
 * ----------------------------------------------------------------------------
 */


echo PHP_EOL . 'Recursive clean \'tests_upload\' directory...' . PHP_EOL . PHP_EOL;
recursive_clean(__DIR__ . '/../tests_upload');


function recursive_clean($dir)
{
    //@array_map('unlink', glob("$dst/*.*")); // delete all files
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        if ($fileinfo->getFilename() !== '.gitignore') {
            @$todo($fileinfo->getRealPath());
        }
    }
}
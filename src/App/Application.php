<?php
namespace App;

class Application extends \Symfony\Component\Console\Application {

    function __construct() //string $name = 'UNKNOWN', string $version = 'UNKNOWN')
    {
        parent::__construct("SME Toolkit", "2.0");
        $this->loadCommands();
    }

    function loadCommands() {
        $dir = "./src";
        $realdir = realpath($dir);
        $handle = opendir($realdir);
        while (false !== ($entry = readdir($handle))) {
            $path = $realdir;
            if ($entry != "." && $entry != "..") {
                if (strpos($entry,"Bundle") !== false) {
                    $path .= "/".$entry."/Command";
                    $ns = '\\'.$entry.'\\Command';
                    if(file_exists($path)) {
                        $cmdhandle = opendir($path);
                        while (false !== ($command = readdir($cmdhandle ))) {
                            if ($command != "." && $command != "..") {
                                $fp = $path . "/" . $command;
                                $parts = pathinfo($fp);
                                $fc = $ns . "\\" . $parts["filename"];
                                $rc = new \ReflectionClass($fc);
                                if (!$rc->isAbstract()) {
                                    $c = new $fc();
                                    $this->add($c);
                                }
                            }
                        }
                    }
                }
            }

        }
    }
}
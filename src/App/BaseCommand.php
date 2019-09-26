<?php


namespace App;


use Dotenv\Dotenv;
use Dotenv\Environment\AbstractVariables;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BaseCommand extends Command
{
    function __construct()
    {
        parent::__construct();
        $script = $_SERVER['SCRIPT_FILENAME'];
        $basedir = dirname(realpath($script));

        $dotenv = Dotenv::create($basedir);
        $dotenv->load();
    }

    protected $useCache = true;
    protected $cacheDir = null;

    protected function configure() {
        if ($this->useCache) {
            $this->addOption('cachedir', '', null, 'Cache directory');
        }
    }
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if ($this->useCache) {
            $cd = $input->getOption('cachedir');
            if (!$cd) {
                // check for env
                $cd = getenv('CACHEDIR');
                if (!$cd) {
                    throw new \RuntimeException("Unable to initialise caching");
                }

            }
            $this->cacheDir = realpath($cd);
        }
    }
    protected function get_cache_name($filename) {
        $cachefile = $this->cacheDir. DIRECTORY_SEPARATOR. $filename. '.cache';
        return $cachefile;
    }
    protected function cache($filename, $data, OutputInterface $output = null) {
        $cachefile = $this->get_cache_name($filename);
        if (!is_null($output)) {
            $output->writeln("Caching to ". $cachefile);
        }
        file_put_contents($cachefile, serialize($data));

    }

    protected function get_from_cache($filename) {
        $cachefile = $this->get_cache_name($filename);
        return unserialize(file_get_contents($cachefile));
    }

    protected function displayPerformanceData($name, OutputInterface $output) {
        if ($perf =$this->get_from_cache($name)) {
            $table = new Table($output);
            $table->setHeaders(["Measure", "Duration"]);
            $table->addRows($perf);
            $table->render();
        } else {
            $output->writeln("No performance data available");
        }
    }


}
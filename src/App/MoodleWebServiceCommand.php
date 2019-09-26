<?php


namespace App;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class MoodleWebServiceCommand extends BaseCommand
{
    //const WEBSERVICEURL = 'webserviceurl';
    private $mwsc_initialised = false;
    private $mws_baseurl = null;
    private $mws_accesstoken = null;

    protected function configure()
    {
        parent::configure();
        $this->addOption("webserviceurl", "u", InputOption::VALUE_OPTIONAL,"Moodle Web Service URL", null);
        $this->addOption("accesstoken", "t", InputOption::VALUE_OPTIONAL,"Moodle Web Service Access Token", null);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $baseurl = $input->getOption('webserviceurl');
        if (is_null($baseurl)) {
            $baseurl = getenv('DEFAULT_MOODLE_WEBSERVICE_URL');
            if ($baseurl === false) {
                throw new InvalidArgumentException('webserviceurl is required');
            }
            $this->mws_baseurl = $baseurl;
        } else {
            $this->mws_baseurl = $baseurl;
        }
        $token = $input->getOption('accesstoken');
        if (!$token) {
            $token = getenv('MOODLE_WEBSERVICE_ACCESS_TOKEN');
            if (!$token) {
                throw new InvalidArgumentException("Access token is required");
            }
            $this->mws_accesstoken = $token;
        }
        $this->mwsc_initialised = true;
    }

    /**
     * Call a moodle web service and return the result as a JSON decoded object.
     * @param $name
     * @param $params array Key/Value pairs for request
     * @param $paramstring string A string representing a querystring request (ie. for more complex requests0
     * @param $cacheresponse bool Should the response from the server be cached. Good for large server requests.
     * @oaram $loadfromcachresponseifavailable bool If there is a cached response, use that for the data rather making a new request. Good for large requests.
     * @return mixed
     */
    function call_moodle_web_service(OutputInterface $output, $name, $params = [], $paramstring = '',  $cacheresponse = false, $loadfromcachresponseifavailable = false) {
        //global $moodle_ws_base_url, $moodle_ws_base_params;
        $moodle_ws_base_url = $this->mws_baseurl;
        $moodle_ws_base_params =  [
            'wstoken' => $this->mws_accesstoken,
            'wsfunction' => null,
            'moodlewsrestformat' => 'json'
        ];
        /*$cachefile = $this->get_cache_name($name);
        if ($loadfromcachresponseifavailable) {
            if (file_exists($cachefile)) {
                $output->write("Using Cached Response", false);
                return unserialize(file_get_contents($cachefile));
            }
        }
        */
        if (!$this->mwsc_initialised) {
            throw new RuntimeException("Web Service is not configured");
            return;
        }
        $moodle_ws_base_params['wsfunction'] = "$name";

        if (!empty($params) && empty($paramstring)) {
            $url_params = array_merge($moodle_ws_base_params, $params);
            $url = $moodle_ws_base_url . "?" . http_build_query($url_params);
        } else {
            $url_params = $moodle_ws_base_params;

            $qs = http_build_query($url_params);
            $qs .= "&{$paramstring}";
            $url = $moodle_ws_base_url . "?" . $qs;
        }

        $output->writeln("URL: {$url}", OutputInterface::VERBOSITY_VERBOSE);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0); //timeout in seconds
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output->write("Calling web service...", false, OutputInterface::VERBOSITY_VERY_VERBOSE);
        $response = curl_exec($ch);
        $output->writeln("Done", OutputInterface::VERBOSITY_VERY_VERBOSE);
        if ($this->useCache) {
            if (is_null($this->cacheDir)) {
                $output->writeln("Cachedir not working", OutputInterface::VERBOSITY_VERY_VERBOSE);
            } else {

                $cachefile = $this->get_cache_name($name);//->cacheDir . DIRECTORY_SEPARATOR . $name . '.cache';
                $output->writeln("Caching Response to " . $cachefile, OutputInterface::VERBOSITY_VERY_VERBOSE);
                file_put_contents($cachefile, $response);

            }
        }

        $result = json_decode($response);

        if (is_null($result)) {
            $error = "Unable to decode response:\n".curl_errno($ch)."\n".curl_error($ch);
            throw new RuntimeException($error);
        }
        curl_close($ch);
        return $result;
    }
}
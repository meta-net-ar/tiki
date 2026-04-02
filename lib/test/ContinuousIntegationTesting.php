<?php

/**
 * This script can be used to implement continuous integration testing.
 *
 * Just invoke it from a cron job.
 */

if (PHP_SAPI !== 'cli') {
    die("This script must be run from the command line.\n");
}


$this_file_dir = __DIR__;
$tiki_root_dir = $this_file_dir . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "..";

set_include_path(get_include_path() . PATH_SEPARATOR . $tiki_root_dir);

$this_file_dir = __DIR__;
require_once("lib/test/TestRunnerWithBaseline.php");
require_once("lib/debug/Tracer.php");

if (realpath($argv[0]) == __FILE__) {
    echo("Doing one integration test on tiki installation: $tiki_root_dir\n\n");

    $tester = new ContinuousIntegrationTesting($tiki_root_dir);
    $tester->run();
}

class ContinuousIntegrationTesting
{
    private $tiki_root_dir;
    private $testrunner;
    private $current_revision;
    private $revision_last_tested;

    public function __construct($tiki_root_dir)
    {
        $this->tiki_root_dir = $tiki_root_dir;
        $this->testrunner = new TestRunnerWithBaseline();
        $this->getRevisionLastTested();
    }

    public function run()
    {
        $current_revision = $this->svnup();
        if (! $this->needsTesting($current_revision)) {
            echo "\n\nLatest revision was already tested. No need to retest.\n\n";
        } else {
            $this->runTests();
        }
        $this->updateRevisionLastTested();
    }

    public function svnup()
    {
        $svn_command = "svn up " . $this->tiki_root_dir;
        $svn_output_lines = [];
        $svn_return_status;
        exec($svn_command, $svn_output_lines, $svn_return_status);
        $svn_output = implode("\n", $svn_output_lines);

        echo("

#################################################
# Output of '$svn_command'
#################################################

" . $svn_output);

        $current_revision = $this->extractCurrentRevisionFromSvnupOutput($svn_output);

        $this->current_revision = $current_revision;

        return $current_revision;
    }


    private function extractCurrentRevisionFromSvnupOutput($svn_output)
    {
        $matches = [];
        $matched = preg_match("/(^|\n)At revision ([\d]+)/", $svn_output, $matches);
        $revision = null;
        if ($matched) {
            $revision = $matches[2];
        }

        return $revision;
    }

    public function needsTesting($current_revision)
    {
        $answer = true;
        if ($this->revision_last_tested == $current_revision) {
            $answer = false;
        }
        return $answer;
    }

    public function runTests()
    {
        echo("\n\nRunning the tests.\n\n");

        $baseline_log = $this->revisionLogFpath("baseline");
        $current_revision_log = $this->revisionLogFpath($this->current_revision);
        $output_fpath = $this->outputFpath();
        $this->testrunner = new TestRunnerWithBaseline($baseline_log, $current_revision_log, $output_fpath);

        $this->testrunner->run();
    }

    private function revisionLogFpath($revision)
    {
        $fname = "phpunit-log." . $revision . ".json";
        return implode(DIRECTORY_SEPARATOR, [$this->tiki_root_dir, 'lib', 'test', $fname]);
    }

    private function revisionLastTestedFpath()
    {
        return implode(DIRECTORY_SEPARATOR, [$this->tiki_root_dir, 'lib', 'test', 'revision_last_tested.txt']);
    }

    private function updateRevisionLastTested()
    {
        file_put_contents($this->revisionLastTestedFpath(), $this->current_revision);
    }

    private function getRevisionLastTested()
    {
        $this->revision_last_tested = file_get_contents($this->revisionLastTestedFpath());
    }

    private function outputFpath()
    {
        return implode(DIRECTORY_SEPARATOR, [$this->tiki_root_dir, 'lib', 'test', 'phpunit-output.' . $this->current_revision . ".txt"]);
    }
}

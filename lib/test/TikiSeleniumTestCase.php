<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/*
 * Parent class of all Selenium test cases.
 */
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tiki\Lib\Test\AcceptanceTests\DBRestorerSQLDumps;

class TikiSeleniumTestCase extends PHPUnit\Framework\TestCase
{
    protected $backupGlobals = false;
    protected $webDriver;
    public $current_test_db;
    public $user_credentials = [
            'admin' => 'tiki'
            ];

    public function __construct($name = '')
    {
        parent::__construct($name);
        $this->configure();
    }

    private function configure()
    {
        $test_tiki_root_url = null;
        $config_fpath = './tests_config.php';

        if (! file_exists($config_fpath)) {
            return false;
        }

        $lines = file($config_fpath);
        $source = implode('', $lines);
        echo "-- TikiSeleniumTestCase.configure: After reading config file: \$source='$source'\n";
        eval($source);
        echo "-- TikiSeleniumTestCase.configure: After evaluating config file: \$test_site_url='$test_site_url'\n";
        if ($test_tiki_root_url == null) {
            exit("Variable \$test_tiki_root_url MUST be defined in test configuration file: '$config_fpath'");
        }

        $capabilities = \Facebook\WebDriver\Remote\DesiredCapabilities::chrome();
        $this->webDriver = \Facebook\WebDriver\Remote\RemoteWebDriver::create($test_tiki_root_url, $capabilities);
        if (! preg_match('/^http\:\/\/local/', $test_tiki_root_url)) {
            exit("Error found in test configuration file '$config_fpath'\n" .
                    "The URL specified by \$test_tiki_root_url should start with http://local, in order to prevent accidentally running tests on a non-local test site.\n" .
                    "Value was: '$test_tiki_root_url'\n");
        }
    }

    public function openTikiPage($tikiPage)
    {
        $this->webDriver->get("http://localhost/tiki-trunk/$tikiPage");
    }

    public function restoreDBforThisTest()
    {
        $dbRestorer = new DBRestorerSQLDumps();
        $error_msg = $dbRestorer->restoreDB($this->current_test_db);
        if ($error_msg != null) {
            $this->markTestSkipped($error_msg);
        }
    }

    public function logInIfNecessaryAs($my_user)
    {
        if (! $this->loginAs($my_user)) {
            die("Couldn't log in as $my_user!");
        }
    }

    public function logOutIfNecessary()
    {
        $logoutLink = $this->webDriver->findElement(WebDriverBy::linkText('Logout'));
        if ($logoutLink->isDisplayed()) {
            $logoutLink->click();
        }
    }

    public function assertSelectElementContainsItems($selectElementID, $expItems, $message)
    {
        try {
            // Assertion 1: Check if the select element exists
            $this->webDriver->findElement(WebDriverBy::id($selectElementID));
        } catch (\Exception) {
            $this->fail("$message\nMarkup element '$selectElementID' did not exist");
        }

        // Get the options from the select element
        $select = new WebDriverSelect($this->webDriver->findElement(WebDriverBy::id($selectElementID)));
        $selectElementLabels = array_map(function ($option) {
            return $option->getText();
        }, $select->getOptions());

        foreach ($expItems as $anItem => $anItemValue) {
            // Assertion 2: Check if the item is in the select element list
            $this->assertContains($anItem, $selectElementLabels, "$message\n$anItem is not in the select element list");

            // Assertion 3: Check if the option element exists
            $thisItemElementID = "$selectElementID/option[@value='$anItemValue']";
            $this->webDriver->findElement(WebDriverBy::xpath($thisItemElementID));
        }
    }

    public function assertSelectElementContainsAllTheItems($selectElementID, $expItems, $message)
    {
        try {
            // Check if the select element exists
            $this->webDriver->findElement(WebDriverBy::id($selectElementID));
        } catch (\Exception) {
            $this->fail("$message\nMarkup element '$selectElementID' did not exist");
        }

        // Get the options of the select element
        $select = new WebDriverSelect($this->webDriver->findElement(WebDriverBy::id($selectElementID)));
        $gotItemsText = [];
        foreach ($select->getOptions() as $option) {
            $gotItemsText[] = $option->getText();
        }

        // Get the expected items
        $expItemsText = array_keys($expItems);

        // Assert that the expected items are equal to the items in the select element
        $this->assertEquals($gotItemsText, $expItemsText, "$message\nItems in the Select element '$selectElementID' were wrong.");

        // Assert that each expected item is present in the select element
        foreach ($expItems as $anItemValue) {
            $thisItemElementID = "$selectElementID/option[@value='$anItemValue']";
            $this->webDriver->findElement(WebDriverBy::xpath($thisItemElementID));
        }
    }

    public function assertSelectElementDoesNotContainItems($selectElementID, $expItems, $message)
    {
        $this->assertTrue($this->webDriver->findElement(WebDriverBy::id($selectElementID))->isDisplayed(), "$message\nMarkup element '$selectElementID' did not exist");
        foreach ($expItems as $anItemValue) {
            $thisItemElementID = "$selectElementID/option[@value='$anItemValue']";
            $this->assertFalse($this->webDriver->findElement(WebDriverBy::xpath($thisItemElementID))->isDisplayed(), "$message\nElement '$thisItemElementID' should not be present.");
        }
    }

    private function loginAs($user)
    {
        $loginUserElementID = "sl-login-user";
        $loginPassElementID = "sl-login-pass";
        $loginButtonElementID = "login";

        if ($this->webDriver->findElement(WebDriverBy::id($loginUserElementID))->isDisplayed()) {
            $password = $this->user_credentials[$user];
            $this->webDriver->findElement(WebDriverBy::id($loginUserElementID))->sendKeys($user);
            $this->webDriver->findElement(WebDriverBy::id($loginPassElementID))->sendKeys($password);
            $this->webDriver->findElement(WebDriverBy::id($loginButtonElementID))->click();

            // Wait for any visible element to appear after login
            try {
                $this->webDriver->wait()->until(WebDriverExpectedCondition::visibilityOfAnyElementLocated(WebDriverBy::cssSelector('body')));
                // If any visible element appears, assume login success
                return true;
            } catch (Exception) {
                // If no visible element appears within the timeout, assume login failure
                return false;
            }
        }
        // Element not present, handle appropriately
        return false;
    }

    public function implodeWithKey($glue, $pieces, $hifen = '=>')
    {
        $return = null;
        foreach ($pieces as $tk => $tv) {
            $return .= $glue . $tk . $hifen . $tv;
        }
        return substr($return, 1);
    }

    public function assertTextPresent($expectedText, $message = '')
    {
        $foundElements = $this->webDriver->findElements(WebDriverBy::xpath("//*[contains(text(), '$expectedText')]"));
        $this->assertNotEmpty($foundElements, $message ?: "Expected text '$expectedText' not found on the page.");
    }

    /**
     * Checks if an element is present on the current page.
     *
     * @param string $locator The locator of the element.
     * @return bool True if the element is present, false otherwise.
     */
    public function isElementPresent($locator)
    {
        try {
            $element = $this->webDriver->findElement(WebDriverBy::xpath($locator));
            return $element !== null;
        } catch (\Exception) {
            return false;
        }
    }

    public function clickAndWait($locator, $timeout = 10)
    {
        // Get the current URL before clicking
        $currentUrl = $this->webDriver->getCurrentURL();

        // Find the element to click
        $element = $this->webDriver->findElement(WebDriverBy::xpath($locator));

        // Click on the element
        $element->click();

        // Wait until the URL changes (i.e., until we are redirected)
        $this->webDriver->wait($timeout)->until(
            WebDriverExpectedCondition::not(
                WebDriverExpectedCondition::urlIs($currentUrl)
            )
        );
    }
    public function isTextPresent($text)
    {
        try {
            // Find the body element
            $bodyElement = $this->webDriver->findElement(WebDriverBy::tagName('body'));
            // Check if the text exists in the body element
            return str_contains($bodyElement->getText(), $text);
        } catch (Exception) {
            return false;
        }
    }
}

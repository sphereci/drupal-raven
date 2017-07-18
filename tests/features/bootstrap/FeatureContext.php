<?php

namespace Misd\Drupal\RavenModule;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\Driver\DrupalDriver;
use Drupal\Driver\DrushDriver;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 *
 */
class FeatureContext extends RawMinkContext {

  /**
   * @var \Behat\MinkExtension\Context\MinkContext*/
  private $minkContext;

  private $basePath;

  private $drupalPath;

  private $database;

  private $username;

  private $password;

  private $mysqlhost;

  private $baseUrl;

  private $drushPath;

  private $modulePath;

  private $filesystem;

  private $drush;

  private $dns;

  private $driver;

  /**
   * FeatureContext constructor.
   *
   * @param $base_path
   * @param $drupal_path
   * @param $database
   * @param $username
   * @param $password
   * @param $mysqlhost
   * @param $base_url
   */
  public function __construct($base_path, $drupal_path, $database, $username, $password, $mysqlhost, $base_url) {

    $this->basePath = $base_path;
    $this->drupalPath = $drupal_path;
    $this->database = $database;
    $this->username = $username;
    $this->password = $password;
    $this->mysqlhost = $mysqlhost;
    $this->baseUrl = $base_url;

    $this->drushPath = sprintf('%s/vendor/bin/drush.php', $this->basePath);
    $this->modulePath = dirname($this->basePath);

    $this->filesystem = new Filesystem();

    $this->drush = new DrushDriver(NULL, $this->drupalPath, $this->drushPath);
    $this->drush->setArguments('-y');
    $this->dns = "mysql://" . $this->username . ":" . $this->password . "@" . $this->mysqlhost . "/" . $this->database;

  }

  /**
   * @return \Behat\MinkExtension\Context\MinkContext
   */
  protected function getMinkContext() {
    return $this->minkContext;
  }

  /**
   * @return string
   */
  protected function drushCommandRun($command, array $arguments = [], array $options = []) {
    return $this->drush->drush($command, $arguments, $options);
  }

  /**
   * @deprecated
   */
  protected function drushCommand($command, $quiet = TRUE) {

    $command = new Process(sprintf('%s %s --root="%s" --yes %s', $this->drushPath, $command, $this->drupalPath, $quiet ? '--quiet' : ''));
    $command->setTimeout(NULL);
    $command->run();

    if (FALSE === $command->isSuccessful()) {
      throw new Exception('Failed to execute Drush command: ' . $command->getCommandLine());
    }

    return $command->getOutput();
  }

  /**
   * @deprecated
   */
  public function clearDrupal() {
    return TRUE;
  }

  /**
   * @BeforeScenario */
  public function before(BeforeScenarioScope $scope) {
    /**
     * Get the environment
     * @var \Behat\Behat\Context\Environment\InitializedContextEnvironment $environment
     */
    $environment = $scope->getEnvironment();

    /**
     * Get all the contexts you need in this context
     * @var \Drupal\DrupalExtension\Context\MinkContext minkContext
     */
    $this->minkContext = $environment->getContext('Drupal\DrupalExtension\Context\MinkContext');
  }

  /**
   * @BeforeScenario
   */
  public function makeSite() {
    /** @var \Drupal\Driver\DrupalDriver $driver */
    $this->driver = new DrupalDriver($this->drupalPath, $this->baseUrl);
    $this->driver->setCoreFromVersion();

    // Bootstrap Drupal.
    $this->driver->bootstrap();
    $this->drushCommandRun('upwd', ['admin'], ['password' => 'password']);
  }

  /**
   * @Given /^I am logged in as the admin user$/
   * @When /^I log in as the admin user$/
   */
  public function iAmLoggedInAsTheAdminUser() {
    $minkContext = $this->getMinkContext();

    $minkContext->visit('/');

    if ($this->getSession()->getPage()->hasLink('Log out')) {
      $minkContext->visit('/user/logout');
    }

    if ($this->isVariable('raven_login_override', TRUE) && $this->isVariable('raven_backdoor_login', TRUE)) {
      $minkContext->visit('/user/backdoor/login');
    }
    else {
      $minkContext->visit('/user/login');
    }

    $minkContext->fillField('Username', 'admin');
    $minkContext->fillField('Password', 'password');
    $minkContext->pressButton('Log in');
  }

  /**
   * @Given /^the "([^"]*)" role has the "([^"]*)" "([^"]*)" permission$/
   */
  public function theRoleHasThePermission($role, $module, $permission) {
    self::drushCommandRun('role-add-perm', [
      sprintf("'%s'", $role),
      sprintf("'%s'", $permission),
    ], ['module' => $module]);
  }

  /**
   * @Given /^the "([^"]*)" role does not have the "([^"]*)" "([^"]*)" permission$/
   */
  public function theRoleDoesNotHaveThePermission($role, $module, $permission) {
    self::drushCommandRun('role-remove-perm', [
      sprintf("'%s'", $role),
      sprintf("'%s'", $permission),
    ], ['module' => $module]);
  }

  /**
   *
   */
  protected function findRidForRole($role) {
    return user_roles(TRUE)[$role]->id();
  }

  /**
   * @Given /^the "([^"]*)" module is enabled$/
   */
  public function theModuleIsEnabled($module) {
    self::drushCommandRun('pm-enable', (array) $module, ['resolve-dependencies' => TRUE]);
  }

  /**
   * @Given /^I have a Raven response with an? "([^"]*)" problem$/
   */
  public function iHaveARavenResponseWithAProblem($problem) {
    $url = rtrim($this->getMinkParameter('base_url'), '/') . '/';

    if (FALSE === in_array($problem, [
      'kid',
      'url',
      'auth',
      'sso',
      'invalid',
      'incomplete',
      'expired',
    ])
    ) {
      throw new Exception('Unknown problem');
    }

    $this->getSession()
      ->visit(create_raven_response($url, 200, 'test0001', $problem));
  }

  /**
   * @Given /^there is a user called "([^"]*)" with the e-?mail address "([^"]*)"$/
   */
  public function thereIsAUserCalledWithTheEmailAddress($username, $emailAddress) {
    if (FALSE === user_load_by_name($username)) {
      self::drushCommand(sprintf('user-create "%s" --mail="%s"', $username, $emailAddress));
    }
  }

  /**
   * @Given /^the user "([^"]*)" is blocked$/
   */
  public function theUserIsBlocked($username) {
    self::drushCommand(sprintf('user-block "%s"', $username));
  }

  /**
   * @Given /^the "([^"]*)" variable is set to "([^"]*)"$/
   */
  public function theVariableIsSetTo($variable, $value, $config_name = 'raven.raven_settings') {
    // $value = maybe_serialize($value);
    self::drushCommandRun('config-set', [$config_name, $variable, $value]);
  }

  /**
   * @Given /^Spanish is enabled$/
   */
  public function spanishIsEnabled() {
    $minkContext = $this->getMinkContext();

    $this->iAmLoggedInAsTheAdminUser();
    $minkContext->visit('/admin/config/regional/language');
    $minkContext->clickLink('Add language');
    $minkContext->selectOption('Language name', 'Spanish (Español)');
    $minkContext->pressButton('Add language');
    $minkContext->visit('/admin/config/regional/language/configure');
    $minkContext->checkOption('URL language provider');
    $minkContext->pressButton('Save settings');
    $minkContext->clickLink('Log out');
  }

  /**
   * @When /^I log in to Raven as "([^"]*)"$/
   */
  public function iLogInToRavenAs($username) {
    $minkContext = $this->getMinkContext();

    $minkContext->visit('/');

    if ($this->getSession()->getPage()->hasLink('Log out')) {
      $minkContext->visit('/user/logout');
    }

    $minkContext->visit('/raven/login');
    $minkContext->fillField('User-id', $username);
    $minkContext->fillField('Password', 'test');
    $minkContext->pressButton('Submit');
  }

  /**
   * @When /^I check the "([^"]*)" radio button$/
   */
  public function iCheckTheRadioButton($locator) {
    $field = $this->getSession()->getPage()->findField($locator);

    if (NULL === $field) {
      throw new ElementNotFoundException($this->getSession(), 'form field', 'id|name|label', $locator);
    }

    $field->selectOption($field->getAttribute('value'));
  }

  /**
   * @Then /^the "([^"]*)" variable should be "([^"]*)"$/
   */
  public function theVariableShouldBe($variable, $expected) {
    if (FALSE === $this->isVariable($variable, $expected)) {
      throw new Exception(sprintf('The variable is "%s"', $this->getVariable($variable)));
    }
  }

  /**
   * @Given /^the "([^"]*)" "([^"]*)" block is in the "([^"]*)" region$/
   */
  public function theBlockIsInTheRegion($module, $delta, $region) {
    $sth = self::getPdo()
      ->prepare('UPDATE block SET status = 1, region = :region WHERE module = :module AND delta = :delta');
    $sth->execute([
      ':region' => $region,
      ':module' => $module,
      'delta' => $delta,
    ]);
  }

  /**
   * @Given /^the "([^"]*)" path has the alias "([^"]*)"$/
   */
  public function thePathHasTheAlias($path, $alias) {
    $minkContext = $this->getMinkContext();

    $this->iAmLoggedInAsTheAdminUser();
    $minkContext->visit('/admin/config/search/path/add');
    $minkContext->fillField('Existing system path', $path);
    $minkContext->fillField('Path alias', $alias);
    $minkContext->pressButton('Save');

    $minkContext->assertPageContainsText('The alias has been saved.');
  }

  /**
   * @Then /^I should see an? "([^"]*)" "([^"]*)" Watchdog message "([^"]*)"$/
   */
  public function iShouldSeeAWatchdogMessage($severity, $type, $message) {
    $minkContext = $this->getMinkContext();

    $this->iAmLoggedInAsTheAdminUser();
    $minkContext->visit('/admin/reports/dblog');
    $minkContext->selectOption('Type', $type);
    $minkContext->selectOption('Severity', $severity);
    $minkContext->pressButton('Filter');

    foreach ($this->getSession()
      ->getPage()
      ->findAll('css', 'table tbody a') as $event) {
      $event->click();

      if (FALSE !== strpos($this->getSession()
        ->getPage()
        ->getText(), $message)
      ) {
        return;
      }

      $this->getSession()->back();
    }

    throw new Exception('Message not found');
  }

  /**
   * @When /^I restart the browser$/
   */
  public function iRestartTheBrowser() {
    $session = $this->getSession();
    $driver = $session->getDriver();

    if (FALSE === $driver instanceof BrowserKitDriver) {
      throw new UnsupportedDriverActionException('Keeping sessions cookies are not supported by %s', $driver);
    }

    /** @var \Behat\Mink\Driver\BrowserKitDriver $driver */
    $client = $driver->getClient();

    $cookies = $client->getCookieJar()->all();

    $session->restart();

    $session->visit('/');

    foreach ($cookies as $cookie) {
      if (FALSE === $cookie->isExpired() && NULL !== $cookie->getExpiresTime()) {
        $client->getCookieJar()->set($cookie);
      }
    }
  }

  /**
   * @Then /^I should see the base URL in the "(?P<element>[^"]*)" element$/
   */
  public function iShouldSeeTheBaseUrlInTheElement($element) {
    $text = rtrim($this->getMinkParameter('base_url'), '/') . '/';

    if ($this->isVariable('clean_url', FALSE)) {
      $text .= '?q=';
    }

    $element = $this->getMinkContext()
      ->assertSession()
      ->elementExists('css', $element);

    if ($element->getText() !== $text) {
      throw new Exception('Element text is "' . $element->getText() . '", but expected "' . $text . '"');
    }
  }

  /**
   *
   */
  protected function getVariable($variable, $config_name = 'raven.raven_settings') {
    self::drushCommandRun('config-get', [$config_name, $variable]);
  }

  /**
   *
   */
  protected function isVariable($variable, $expected) {
    return $this->getVariable($variable);
  }

}

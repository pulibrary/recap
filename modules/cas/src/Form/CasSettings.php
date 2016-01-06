<?php
/**
 * @file
 * Contains \Drupal\cas\Form\CasSettings.
 */

namespace Drupal\cas\Form;

use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\cas\Service\CasHelper;

/**
 * @codeCoverageIgnore
 */
class CasSettings extends ConfigFormBase {

  /**
   * @var \Drupal\system\Plugin\Condition\RequestPath
   */
  protected $gatewayPaths;

  /**
   * @var \Drupal\system\Plugin\Condition\RequestPath
   */
  protected $forcedLoginPaths;

  /**
   * Constructs a \Drupal\cas\Form\CasSettings object.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param FactoryInterface $plugin_factory
   *   The condition plugin factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FactoryInterface $plugin_factory) {
    parent::__construct($config_factory);
    $this->gatewayPaths = $plugin_factory->createInstance('request_path');
    $this->forcedLoginPaths = $plugin_factory->createInstance('request_path');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'cas_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cas.settings');

    $form['server'] = array(
      '#type' => 'details',
      '#title' => $this->t('CAS Server'),
      '#open' => TRUE,
      '#tree' => TRUE,
    );
    $form['server']['version'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Version'),
      '#options' => array(
        '1.0' => $this->t('1.0'),
        '2.0' => $this->t('2.0 or higher'),
        'S1' => $this->t('SAML Version 1.1'),
      ),
      '#default_value' => $config->get('server.version'),
    );
    $form['server']['hostname'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#description' => $this->t('Hostname or IP Address of the CAS server.'),
      '#size' => 30,
      '#default_value' => $config->get('server.hostname'),
    );
    $form['server']['port'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Port'),
      '#size' => 5,
      '#description' => $this->t('443 is the standard SSL port. 8443 is the standard non-root port for Tomcat.'),
      '#default_value' => $config->get('server.port'),
    );
    $form['server']['path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('URI'),
      '#description' => $this->t('If CAS is not at the root of the host, include a URI (e.g., /cas).'),
      '#size' => 30,
      '#default_value' => $config->get('server.path'),
    );
    $form['server']['cert'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Certificate Authority PEM Certificate'),
      '#description' => $this->t('The PEM certificate of the Certificate Authority that issued the certificate of the CAS server. If omitted, the certificate authority will not be verified.'),
      '#default_value' => $config->get('server.cert'),
    );

    $form['gateway'] = array(
      '#type' => 'details',
      '#title' => $this->t('Gateway Feature (Auto Login)'),
      '#open' => FALSE,
      '#tree' => TRUE,
      '#description' => $this->t(
        'This implements the <a href="@cas-gateway">Gateway feature</a> of the CAS Protocol. ' .
        'When enabled, Drupal will check if a visitor is already logged into your CAS server before ' .
        'serving a page request. If they have an active CAS session, they will be automatically ' .
        'logged into the Drupal site. This is done by quickly redirecting them to the CAS server to perform the ' .
        'active session check, and then redirecting them back to page they initially requested.',
        array('@cas-gateway' => 'https://wiki.jasig.org/display/CAS/gateway')
      ),
    );
    $form['gateway']['check_frequency'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Check Frequency'),
      '#default_value' => $config->get('gateway.check_frequency'),
      '#options' => array(
        CasHelper::CHECK_NEVER => 'Disable gateway feature',
        CasHelper::CHECK_ONCE => 'Once per browser session',
        CasHelper::CHECK_ALWAYS => 'Every page load (not recommended)',
      ),
    );
    $this->gatewayPaths->setConfiguration($config->get('gateway.paths'));
    $form['gateway']['paths'] = $this->gatewayPaths->buildConfigurationForm(array(), $form_state);

    $form['forced_login'] = array(
      '#type' => 'details',
      '#title' => $this->t('Forced Login'),
      '#open' => FALSE,
      '#tree' => TRUE,
      '#description' => $this->t(
        'Anonymous users will be forced to login through CAS when enabled. ' .
        'This differs from the "gateway feature" in that it will REQUIRE that a user be logged in to their CAS ' .
        'account, instead of just checking if they already are.'
      ),
    );
    $form['forced_login']['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#description' => $this->t('When enabled, every path will force a CAS login, unless specific pages are listed.'),
      '#default_value' => $config->get('forced_login.enabled'),
    );
    $this->forcedLoginPaths->setConfiguration($config->get('forced_login.paths'));
    $form['forced_login']['paths'] = $this->forcedLoginPaths->buildConfigurationForm(array(), $form_state);

    $form['user_accounts'] = array(
      '#type' => 'details',
      '#title' => $this->t('User Account Handling'),
      '#open' => FALSE,
      '#tree' => TRUE,
    );
    $form['user_accounts']['auto_register'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Auto Register Users'),
      '#description' => $this->t(
        'Enable to automatically create local Drupal accounts for first-time CAS logins. ' .
        'If disabled, users must be pre-registered before being allowed to log in.'
      ),
      '#default_value' => $config->get('user_accounts.auto_register'),
    );

    $form['redirection'] = array(
      '#type' => 'details',
      '#title' => $this->t('Redirection'),
      '#open' => FALSE,
      '#tree' => TRUE,
    );
    $form['redirection']['logout_destination'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Logout destination'),
      '#description' => $this->t(
        'Drupal path or URL. Enter a destination if you want the CAS Server to ' .
        'redirect the user after logging out of CAS.'
      ),
      '#default_value' => $config->get('redirection.logout_destination'),
    );

    $form['proxy'] = array(
      '#type' => 'details',
      '#title' => $this->t('Proxy'),
      '#open' => FALSE,
      '#tree' => TRUE,
      '#description' => $this->t(
        'These options relate to the proxy feature of the CAS protocol, ' .
        'including configuring this client as a proxy and configuring ' .
        'this client to accept proxied connections from other clients.'),
    );
    $form['proxy']['initialize'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Initialize this client as a proxy?'),
      '#description' => $this->t(
        'Initializing this client as a proxy allows it to access ' .
        'CAS-protected resources from other clients that have been ' .
        'configured to accept it as a proxy.'),
      '#default_value' => $config->get('proxy.initialize'),
    );
    $form['proxy']['can_be_proxied'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow this client to be proxied?'),
      '#description' => $this->t(
        'Allow other CAS clients to access this site\'s resources via the ' .
        'CAS proxy protocol. You will need to configure a list of allowed ' .
        'proxies below.'),
      '#default_value' => $config->get('proxy.can_be_proxied'),
    );
    $form['proxy']['proxy_chains'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Allowed proxy chains'),
      '#description' => $this->t(
        'A list of proxy chains to allow proxy connections from. Each line ' .
        'is a chain, and each chain is a whitespace delimited list of ' .
        'URLs for an allowed proxy in the chain, listed from most recent ' .
        '(left) to first (right). Each URL in the chain can be either a ' .
        'plain URL or a URL-matching regular expression (delimited only by ' .
        'slashes). Only if the proxy list returned by the CAS Server exactly ' .
        'matches a chain in this list will a proxy connection be allowed.'),
      '#default_value' => $config->get('proxy.proxy_chains'),
    );

    $form['debugging'] = array(
      '#type' => 'details',
      '#title' => $this->t('Debugging'),
      '#open' => FALSE,
      '#tree' => TRUE,
      '#description' => $this->t(
        'These options are for debugging only, and are not meant to be used ' .
        'in normal production usage.'),
    );
    $form['debugging']['log'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Log debug information?'),
      '#description' => $this->t(
        'This is not meant for production sites! Enable this to log debug ' .
        'information about the interactions with the CAS Server to the ' .
        'Drupal log.'),
      '#default_value' => $config->get('debugging.log'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $condition_values = (new FormState())
      ->setValues($form_state->getValue(['gateway', 'paths']));
    $this->gatewayPaths->validateConfigurationForm($form, $condition_values);

    $condition_values = (new FormState())
      ->setValues($form_state->getValue(['forced_login', 'paths']));
    $this->forcedLoginPaths->validateConfigurationForm($form, $condition_values);
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cas.settings');

    $server_data = $form_state->getValue('server');
    $config
      ->set('server.version', $server_data['version'])
      ->set('server.hostname', $server_data['hostname'])
      ->set('server.port', $server_data['port'])
      ->set('server.path', $server_data['path'])
      ->set('server.cert', $server_data['cert']);

    $condition_values = (new FormState())
      ->setValues($form_state->getValue(['gateway', 'paths']));
    $this->gatewayPaths->submitConfigurationForm($form, $condition_values);
    $config
      ->set('gateway.check_frequency', $form_state->getValue(['gateway', 'check_frequency']))
      ->set('gateway.paths', $this->gatewayPaths->getConfiguration());

    $condition_values = (new FormState())
      ->setValues($form_state->getValue(['forced_login', 'paths']));
    $this->forcedLoginPaths->submitConfigurationForm($form, $condition_values);
    $config
      ->set('forced_login.enabled', $form_state->getValue(['forced_login', 'enabled']))
      ->set('forced_login.paths', $this->forcedLoginPaths->getConfiguration());

    $config
      ->set('redirection.logout_destination', $form_state->getValue(['redirection', 'logout_destination']));

    $config
      ->set('proxy.initialize', $form_state->getValue(['proxy', 'initialize']))
      ->set('proxy.can_be_proxied', $form_state->getValue(['proxy', 'can_be_proxied']))
      ->set('proxy.proxy_chains', $form_state->getValue(['proxy', 'proxy_chains']));
    $config
      ->set('user_accounts.auto_register', $form_state->getValue(['user_accounts', 'auto_register']));

    $config
      ->set('debugging.log', $form_state->getValue(['debugging', 'log']));

    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('cas.settings');
  }
}

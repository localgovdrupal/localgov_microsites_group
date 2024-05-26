<?php

namespace Drupal\Tests\localgov_microsites_group\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\domain\DomainInterface;

/**
 * Log user in or out to domain.
 *
 * Maintains a single logged in user sesion per domain.
 * Based on Drupal\Tests\UiHelperTrait methods.
 *
 * For Browser functional tests.
 */
trait LoginOutTrait {

  /**
   * Array of logged in sessions per Domain ID.
   */
  protected $micrositeDomainLoggedIn = [];

  /**
   * Log an account into a microsite domanin.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   The domain to log into.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The accont to log in.
   */
  protected function micrositeDomainLogin(DomainInterface $domain, AccountInterface $account): void {
    if (!empty($this->micrositeDomainLoggedIn[$domain->id()])) {
      $this->micrositeDomainLogout($domain);
    }

    $this->drupalGet($domain->getUrl() . Url::fromRoute('user.login')->toString());
    $this->submitForm([
      'name' => $account->getAccountName(),
      'pass' => $account->passRaw,
    ], 'Log in');

    // @does \Drupal::request() get the correct request. I guess so?
    if (!isset($account->micrositeSessions)) {
      $account->micrositeSessions = [];
    }
    $account->micrositeSessions[$domain->id()] = $this->getSession()->getCookie(\Drupal::service('session_configuration')->getOptions(\Drupal::request())['name']);
    $this->assertTrue($this->micrositeDomainIsLoggedIn($domain, $account), "User {$account->getAccountName()} successfully logged in.");

    $this->micrositeDomainLoggedIn[$domain->id()] = $account;
  }

  public function micrositeDomainLogout($domain) {
    // @TODO FIX ME!
    /*    $assert_session = $this->assertSession();
    $destination = Url::fromRoute('user.page')->toString();
    $this->drupalGet($domain->getUrl() . Url::fromRoute('user.logout', [], ['query' => ['destination' => $destination]])->toString());
    drupal_flush_all_caches();
    $this->drupalGet($domain->getUrl() . $destination);
    $assert_session->fieldExists('name');
    $assert_session->fieldExists('pass');
*/
    // @see BrowserTestBase::drupalUserIsLoggedIn()
    unset($this->loggedInUser[$domain->id()]->micrositeSessions[$domain->id()]);
    unset($this->micrositeDomainLoggedIn[$domain->id()]);
  }

  /**
   * Returns whether a given user account is logged in.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   The domain to log into.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account object to check.
   *
   * @return bool
   *   Return TRUE if the user is logged in, FALSE otherwise.
   */
  protected function micrositeDomainIsLoggedIn(DomainInterface $domain, AccountInterface $account): bool {
    $logged_in = FALSE;

    if (isset($account->micrositeSessions) &&
      isset($account->micrositeSessions[$domain->id()])
    ) {
      $session_handler = \Drupal::service('session_handler.storage');
      $logged_in = (bool) $session_handler->read($account->micrositeSessions[$domain->id()]);
    }

    return $logged_in;
  }

}

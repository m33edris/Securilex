<?php
/**
 * This file is part of the Securilex library for Silex framework.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Securilex\Authentication\Factory
 * @author Muhammad Lukman Nasaruddin <anatilmizun@gmail.com>
 * @link https://github.com/MLukman/Securilex Securilex Github
 * @link https://packagist.org/packages/mlukman/securilex Securilex Packagist
 */

namespace Securilex\Authentication\Factory;

use Securilex\Authentication\AuthenticationFactoryInterface;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Security\Core\Authentication\Provider\LdapBindAuthenticationProvider;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * LdapBindAuthenticationFactory creates instances of LdapBindAuthenticationProvider using
 * information from an instance of \Silex\Application, UserProviderInterface and a provider key
 */
class LdapBindAuthenticationFactory implements AuthenticationFactoryInterface
{
    /**
     * Id of this factory
     * @var string
     */
    protected $id;

    /**
     * The LDAP client object
     * @var LdapInterface
     */
    protected $ldapClient;

    /**
     * The distinguished name string containing '{username}' phrase,
     * which will be replaced with the username entered by user
     * @var string
     */
    protected $dnString;

    /**
     * Construct an instance.
     * @staticvar int $instanceId
     * @param string $host The LDAP server host/ip
     * @param string $port The LDAP server port
     * @param string $dnString The distinguished name string containing '{username}' phrase,
     * which will be replaced with the username entered by user
     * @param integer $version The LDAP version (default = 3)
     */
    public function __construct($host, $port, $dnString, $version = 3)
    {
        static $instanceId = 0;
        $this->id          = 'ldap'.($instanceId++);
        $this->ldapClient  = Ldap::create('ext_ldap',
                array('host' => $host, 'port' => $port, 'version' => $version));
        $this->dnString    = $dnString;
    }

    /**
     * Create Authentication Provider instance.
     * @param \Silex\Application $app
     * @param UserProviderInterface $userProvider
     * @param string $providerKey
     * @return LdapBindAuthenticationProvider
     */
    public function createAuthenticationProvider(\Silex\Application $app,
                                                 UserProviderInterface $userProvider,
                                                 $providerKey)
    {
        return new LdapBindAuthenticationProvider($userProvider,
            $app['security.user_checker'], $providerKey, $this->ldapClient,
            $this->dnString);
    }

    /**
     * Get the unique id of this instance of authentication factory.
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
}
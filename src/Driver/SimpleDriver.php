<?php

namespace Securilex\Driver;

use Symfony\Component\Security\Core\Authentication\Provider\SimpleAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\SimpleAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SimpleDriver extends BaseDriver implements SimpleAuthenticatorInterface
{
    /**
     * Authentication Provider
     * @var \Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface
     */
    protected $authenticationProvider;

    /**
     * Built-in User Provider
     * @var InMemoryUserProvider
     */
    protected $userProvider;

    public function __construct()
    {
        $this->userProvider = new InMemoryUserProvider();
    }

    /**
     * Add a user to the list of authenticated users
     * @param string $userid The user id
     * @param string $password The user plain password
     * @param array $role The array of user roles: ROLE_USER, ROLE_ADMIN, ROLE_SUPERADMIN
     */
    public function addUser($userid, $password, array $role = array('ROLE_USER'))
    {
        $this->userProvider->createUser(new User($userid, $password, $role));
    }

    public function getAuthenticationProvider(\Silex\Application $app,
                                              $providerKey)
    {
        if (!$this->authenticationProvider) {
            $this->authenticationProvider = new SimpleAuthenticationProvider($this,
                $app['security.user_provider.'.$providerKey],
                $providerKey);
        }
        return $this->authenticationProvider;
    }

    public function getBuiltInUserProvider()
    {
        return $this->userProvider;
    }

    public function authenticateToken(TokenInterface $token,
                                      UserProviderInterface $userProvider,
                                      $providerKey)
    {
        if (($user = $userProvider->loadUserByUsername($token->getUsername())) && ($user->getPassword()
            == $token->getCredentials())) {
            return new UsernamePasswordToken(
                $user, $user->getPassword(), $providerKey, $user->getRoles()
            );
        }
        throw new \Symfony\Component\Security\Core\Exception\BadCredentialsException('The presented password is invalid.');
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof UsernamePasswordToken && $token->getProviderKey()
            === $providerKey;
    }

    public function getId()
    {
        return 'simple';
    }
}
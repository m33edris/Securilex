<?php
/**
 * This file is part of the Securilex library for Silex framework.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Securilex
 * @author Muhammad Lukman Nasaruddin <anatilmizun@gmail.com>
 * @link https://github.com/MLukman/Securilex Securilex Github
 * @link https://packagist.org/packages/mlukman/securilex Securilex Packagist
 */

namespace Securilex;

use Silex\Provider\SecurityServiceProvider;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Service Provider is the core class to enable Securilex service in a Silex-powered application.
 *
 * Example:
 *     $app->register(new \Securilex\ServiceProvider());
 *     $app['securilex']->addFirewall(...);
 * 
 * Important: Securilex works best if registered after all routes have been defined.
 */
class ServiceProvider extends SecurityServiceProvider
{
    /**
     * The list of added firewalls
     * @var Firewall[]
     */
    protected $firewalls = array();

    /**
     * The application instance
     * @var \Silex\Application
     */
    protected $app = null;

    /**
     * The firewall configurations
     * @var array
     */
    protected $firewallConfig = array();

    /**
     * The list of patterns to be excluded from security
     * @var array
     */
    protected $unsecuredPatterns = array();

    /**
     * The list of voters for authorization
     * @var VoterInterface[]
     */
    protected $voters = array();

    /**
     * Register with \Silex\Application.
     * @param \Silex\Application $app
     */
    public function register(\Silex\Application $app)
    {
        parent::register($app);

        // Register voters
        $app->extend('security.voters',
            function($voters) {
            return array_merge($voters, $this->voters);
        });

        // Register firewalls
        $this->app = $app;
        foreach ($this->firewalls as $firewall) {
            $firewall->register($this);
        }
        $this->refreshFirewallConfig();

        // Add reference to this in application instance
        $this->app['securilex'] = $this;
    }

    /**
     * Add Firewall
     * @param Firewall $firewall
     */
    public function addFirewall(Firewall $firewall)
    {
        $this->firewalls[$firewall->getName()] = $firewall;

        if ($this->app) {
            $firewall->register($this);
            $this->refreshFirewallConfig();
        }
    }

    /**
     * Get Firewall with the specific path.
     * @param string $path
     * @return Firewall
     */
    public function getFirewall($path)
    {
        foreach ($this->firewalls as $firewall) {
            if ($firewall->isPathCovered($path)) {
                return $firewall;
            }
        }
        return null;
    }

    /**
     * Get login check path.
     * @return string
     */
    public function getLoginCheckPath()
    {
        $login_check = $this->app['request']->getBasePath();

        if (($firewall = $this->getFirewall($this->getCurrentPathRelativeToBase()))) {
            $login_check .= $firewall->getLoginCheckPath();
        }

        return $login_check;
    }

    /**
     * Get logout path.
     * @return string
     */
    public function getLogoutPath()
    {
        $logout = $this->app['request']->getBasePath();

        if (($firewall = $this->getFirewall($this->getCurrentPathRelativeToBase()))) {
            $logout .= $firewall->getLogoutPath();
        }

        return $logout;
    }

    /**
     * Get the Application
     * @return \Silex\Application
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Add a path pattern to list of unsecured paths.
     * @param string $pattern
     */
    public function addUnsecurePattern($pattern)
    {
        $this->unsecuredPatterns[$pattern] = true;
    }

    /**
     * Prepend additional data to firewall configuration.
     * @param string $name
     * @param mixed $config
     */
    public function prependFirewallConfig($name, $config)
    {
        $this->firewallConfig = array_merge(
            array($name => $config), $this->firewallConfig);
    }

    /**
     * Append additional data to firewall configuration.
     * @param string $name
     * @param mixed $config
     */
    public function appendFirewallConfig($name, $config)
    {
        $this->firewallConfig[$name] = $config;
    }

    /**
     * Refresh and register firewall configuration.
     */
    public function refreshFirewallConfig()
    {
        if ($this->app) {
            $i         = 0;
            $firewalls = array();
            foreach ($this->unsecuredPatterns as $pattern => $v) {
                $firewalls['unsecured_'.($i++)] = array('pattern' => $pattern);
            }
            $this->app['security.firewalls'] = array_merge($firewalls,
                $this->firewallConfig);
        }
    }

    /**
     * Add additional voter to authorization module.
     * @param VoterInterface $voter
     */
    public function addAuthorizationVoter(VoterInterface $voter)
    {
        if (!in_array($voter, $this->voters)) {
            $this->voters[] = $voter;
        }
    }

    /**
     * Get current path relative to base path.
     * @param string $path Path to process. Optional, default to current path
     * @return string
     */
    protected function getCurrentPathRelativeToBase($path = null)
    {
        if (!$path) {
            // using $_SERVER instead of using Request method
            // to get original request path instead of any forwarded request
            $path = $_SERVER['REQUEST_URI'];
        }
        $base_path = $this->app['request']->getBasePath();
        return substr(strtok($path, '?'), strlen($base_path));
    }
}
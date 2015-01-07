<?php
/**
 * Created by PhpStorm.
 * User: Neil
 * Date: 03/01/2015
 * Time: 15:45
 *
 * Inspired by gist.
 * https://gist.github.com/neilmillard/43788ee0c3f4a1ab0687
 */

namespace App\Validation;

use Illuminate\Config\FileLoader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Factory;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Container\Container;
use Symfony\Component\Translation\Translator;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Connectors\ConnectionFactory;

class Manager {

    /**
     * The current globally used instance
     *
     * @var \App\Validation\Manager
     */
    protected static $instance;

    /**
     * The validation factory instance
     *
     * @var \Illuminate\Validation\Factory
     */
    protected $validator;

    /**
     * The Translator implementation.
     *
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;


    public function __construct($fallbackLocale = null, $path = null, Container $container = null)
    {
        $this->setupContainer($container);
        $this->setupTranslator($fallbackLocale, $path);
        $this->setupValidator();
    }

    /**
     * @param \Illuminate\Container\Container $container
     * return void
     */
    protected function setupContainer($container)
    {
        $this->container = $container ?: new Container;
        $this->container->instance('config', new Fluent);
    }

    protected function setupTranslator($fallbackLocale, $path)
    {
        $file = new Filesystem();
        $loader = new FileLoader($file, $path);
        $trans = new Translator($loader, $this->container['config']['app.locale']);

        $trans->setFallbackLocales(array($fallbackLocale));
        $this->translator = $trans;
    }

    /**
     * Build the validation factory instance
     */
    public function setupValidator()
    {
        $this->validator = new Factory($this->translator, $this->container);
    }

    public function setConnection(array $config)
    {
        $connection = new ConnectionFactory($this->container);
        $db = new ConnectionResolver(array(
            null => $connection->make($config)
        ));
        $this->setPresenceVerifier($db);
    }

    /**
     * Register the database presence verifier
     * @param ConnectionResolverInterface $db
     * @return void
     */
    public function setPresenceVerifier(ConnectionResolverInterface $db)
    {
        $presence = new DatabasePresenceVerifier($db);
        $this->validator->setPresenceVerifier($presence);
    }

    /**
     * Make this instance available globally
     * @return void
     */
    public function setAsGlobal()
    {
        static::$instance = $this;
    }

    /**
     * Get the validation factory instance
     *
     * @return \Illuminate\Validation\Factory
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Get the IoC container instance
     *
     * @return \Illuminate\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

}
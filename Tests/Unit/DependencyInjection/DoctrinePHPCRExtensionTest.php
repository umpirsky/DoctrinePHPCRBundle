<?php

namespace Doctrine\Bundle\PHPCRBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Doctrine\Bundle\PHPCRBundle\DependencyInjection\DoctrinePHPCRExtension;

class DoctrinePHPCRExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return array(
            new DoctrinePHPCRExtension()
        );
    }

    protected function setUp()
    {
        parent::setUp();

        $this->container->setParameter('kernel.bundles', array());
    }

    public function testLoad()
    {
        $this->load();
    }

    public function testJackrabbitSession()
    {
        $this->load(array(
            'session' => array(
                'backend' => array(
                    'url' => 'http://localhost',
                ),
                'workspace' => 'default',
                'username' => 'admin',
                'password' => 'admin',

            ),
        ));

        /** @var $repositoryFactory DefinitionDecorator */
        $repositoryFactory = $this->container->getDefinition('doctrine_phpcr.jackalope.repository.default');
        $parameters = $repositoryFactory->getArgument(0);
        $this->assertEquals(array(
            'jackalope.jackrabbit_uri',
            'jackalope.check_login_on_server',
        ), array_keys($parameters));

        $this->assertEquals('doctrine_phpcr.jackalope.repository.factory.jackrabbit', $repositoryFactory->getParent());
    }

    public function testJackrabbitSessions()
    {
        $this->load(array(
            'session' => array(
                'default_session' => 'bar',
                'sessions' => array(
                    'foo' => array(
                        'backend' => array(
                            'url' => 'http://foo',
                        ),
                        'workspace' => 'default',
                        'username' => 'admin',
                        'password' => 'admin',
                    ),
                    'bar' => array(
                        'backend' => array(
                            'url' => 'http://bar',
                        ),
                        'workspace' => 'default',
                        'username' => 'admin',
                        'password' => 'admin',
                    ),
                )
            ),
        ));

        $this->assertCount(2, $this->container->getParameter('doctrine_phpcr.sessions'));

        foreach ($this->container->getParameter('doctrine_phpcr.sessions') as $id) {
            $this->container->getDefinition($id);
        }
    }

    public function testDoctrineDbalSession()
    {
        $this->load(array(
            'session' => array(
                'backend' => array(
                    'type' => 'doctrinedbal',
                    'logging' => true,
                    'profiling' => true,
                    'parameters' => array(
                        'jackalope.factory' => 'Jackalope\Factory',
                        'jackalope.check_login_on_server' => false,
                        'jackalope.disable_stream_wrapper' => false,
                        'jackalope.auto_lastmodified' => true,
                    ),
                ),
                'workspace' => 'default',
                'username' => 'admin',
                'password' => 'admin',
                'options' => array(
                    'jackalope.fetch_depth' => 2,
                ),
            ),
        ));

        /** @var $repositoryFactory DefinitionDecorator */
        $repositoryFactory = $this->container->getDefinition('doctrine_phpcr.jackalope.repository.default');
        $parameters = $repositoryFactory->getArgument(0);
        $this->assertInternalType('array', $parameters);
        $this->assertEquals(array(
            'jackalope.doctrine_dbal_connection',
            'jackalope.factory',
            'jackalope.check_login_on_server',
            'jackalope.disable_stream_wrapper',
            'jackalope.auto_lastmodified',
            'jackalope.logger',
        ), array_keys($parameters));

        $this->assertEquals('doctrine_phpcr.jackalope.repository.factory.doctrinedbal', $repositoryFactory->getParent());

        /** @var $session Definition */
        $session = $this->container->getDefinition('doctrine_phpcr.default_session');
        $calls = $session->getMethodCalls();
        $this->assertCount(1, $calls);
        $this->assertEquals(array('setSessionOption', array('jackalope.fetch_depth', 2)), current($calls));
    }
}

<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Tests\Test;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Liip\FunctionalTestBundle\Annotations\QueryCount;

class WebTestCaseTest extends WebTestCase
{
    private $client = null;

    public function setUp()
    {
        $this->client = static::makeClient();
    }

    public function testIndex()
    {
        $this->loadFixtures(array());

        $path = '/';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

        $this->assertSame(
            'Not logged in.',
            $crawler->filter('p#user')->text()
        );

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );
    }

    /**
     * @QueryCount(100)
     */
    public function testIndexWithAnnotations()
    {
        $this->loadFixtures(array());

        $path = '/';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );
    }

    public function testIndexWithAuthentication()
    {
        $this->client = static::makeClient(array(
            'username' => 'foobar',
            'password' => '12341234',
        ));

        $this->loadFixtures(array());

        $path = '/';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

        $this->assertSame(
            'Logged in as foobar.',
            $crawler->filter('p#user')->text()
        );

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );
    }

    public function testUserWithFixtures()
    {
        $this->loadFixtures(array(
            'Liip\FunctionalTestBundle\DataFixtures\ORM\LoadUserData',
        ));

        $path = '/user/1';

        $this->client->enableProfiler();

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

        if ($profile = $this->client->getProfile()) {
            // One query
            $this->assertEquals(1,
                $profile->getCollector('db')->getQueryCount());
        } else {
            $this->markTestIncomplete(
                'Profiler is disabled.'
            );
        }

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );

        $this->assertSame(
            'Name: foo bar',
            $crawler->filter('div#content p')->eq(0)->text()
        );
        $this->assertSame(
            'Email: foo@bar.com',
            $crawler->filter('div#content p')->eq(1)->text()
        );
    }

    public function testIndexWithFixtures()
    {
        $this->loadFixtures(array(
            'Liip\FunctionalTestBundle\DataFixtures\ORM\LoadUserData',
        ));

        $path = '/';

        $this->client->enableProfiler();

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

        if ($profile = $this->client->getProfile()) {
            // No database query
            $this->assertEquals(0,
                $profile->getCollector('db')->getQueryCount());
        } else {
            $this->markTestIncomplete(
                'Profiler is disabled.'
            );
        }

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );
    }

    public function testLoadFixtures()
    {
        $this->loadFixtures(array(
            'Liip\FunctionalTestBundle\DataFixtures\ORM\LoadUserData',
        ));

        $em = $this->client->getContainer()
            ->get('doctrine.orm.entity_manager');

        $user = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findOneBy(array(
                'id' => 1,
            ));

        $this->assertSame(
            'foo@bar.com',
            $user->getEmail()
        );

        $this->assertTrue(
            $user->getEnabled()
        );
    }

    /**
     * Use nelmio/alice.
     */
    public function testLoadFixturesFiles()
    {
        $this->loadFixtureFiles(array(
            '@LiipFunctionalTestBundle/DataFixtures/ORM/user.yml',
        ));

        $em = $this->client->getContainer()
            ->get('doctrine.orm.entity_manager');

        $users = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findAll();

        $this->assertSame(
            10,
            count($users)
        );

        $user = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findOneBy(array(
                'id' => 1,
            ));

        $this->assertTrue(
            $user->getEnabled()
        );

        $user = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findOneBy(array(
                'id' => 10,
            ));

        $this->assertTrue(
            $user->getEnabled()
        );
    }

    /**
     * Use nelmio/alice with full path to the file.
     */
    public function testLoadFixturesFilesPaths()
    {
        $this->loadFixtureFiles(array(
            $this->client->getContainer()->get('kernel')->locateResource(
                '@LiipFunctionalTestBundle/DataFixtures/ORM/user.yml'
            ),
        ));

        $em = $this->client->getContainer()
            ->get('doctrine.orm.entity_manager');

        $users = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findAll();

        $this->assertSame(
            10,
            count($users)
        );

        $user = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findOneBy(array(
                'id' => 1,
            ));

        $this->assertTrue(
            $user->getEnabled()
        );
    }

    public function testForm()
    {
        if (!interface_exists('Symfony\Component\Validator\Validator\ValidatorInterface')) {
            $this->markTestSkipped('The Symfony\Component\Validator\Validator\ValidatorInterface does not exist');
        }

        $this->loadFixtures(array());

        $path = '/form';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

        $form = $crawler->selectButton('Submit')->form();
        $crawler = $this->client->submit($form);

        $this->assertStatusCode(200, $this->client);

        $this->assertValidationErrors(array('children[name].data'), $this->client->getContainer());

        // Try again with the fields filled out.
        $form = $crawler->selectButton('Submit')->form();
        $form->setValues(array('form[name]' => 'foo bar'));
        $crawler = $this->client->submit($form);

        $this->assertContains(
            'Name submitted.',
            $crawler->filter('div.flash-notice')->text()
        );

        $this->assertStatusCode(200, $this->client);
    }

    public function testFormWithException()
    {
        if (!interface_exists('Symfony\Component\Validator\Validator\ValidatorInterface')) {
            $this->markTestSkipped('The Symfony\Component\Validator\Validator\ValidatorInterface does not exist');
        }

        $this->loadFixtures(array());

        $path = '/form';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

        $form = $crawler->selectButton('Submit')->form();
        $crawler = $this->client->submit($form);

        $this->assertStatusCode(200, $this->client);

        try {
            $this->assertValidationErrors(array(''), $this->client->getContainer());
        } catch (\PHPUnit_Framework_ExpectationFailedException $expected) {
            return;
        }

        $this->fail('PHPUnit_Framework_ExpectationFailedException has not been raised');
    }

    public function testAdminWithoutAuthentication()
    {
        $this->client = static::makeClient();

        $this->loadFixtures(array());

        $path = '/admin';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(401, $this->client);
    }

    /**
     * Log in as the user defined in the configuration file.
     */
    public function testAuthenticationTrue()
    {
        $this->client = static::makeClient(true);

        $this->loadFixtures(array());

        $path = '/';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

        $this->assertSame(
            'Logged in as foobar.',
            $crawler->filter('p#user')->text()
        );

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );
    }

    /**
     * Log in as the user defined in the Bundle configuration.
     */
    public function testAdminWithAuthenticationTrue()
    {
        $this->client = static::makeClient(true);

        $this->loadFixtures(array());

        $path = '/admin';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(403, $this->client);
    }

    /**
     * Log in as the admin defined in the in_memory array.
     */
    public function testAdminWithAuthenticationRoleAdmin()
    {
        $this->client = static::makeClient(array(
            'username' => 'roleadmin',
            'password' => '12341234',
        ));

        $this->loadFixtures(array());

        $path = '/admin';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);
    }

    public function testAdminWithAuthenticationLoginAs()
    {
        $fixtures = $this->loadFixtures(array(
            'Liip\FunctionalTestBundle\DataFixtures\ORM\LoadUserData',
        ))->getReferenceRepository();

        $this->loginAs($fixtures->getReference('user'),
            'secured_area');
        $this->client = static::makeClient();

        $path = '/admin';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

        $this->assertSame(
            'Logged in as foo bar.',
            $crawler->filter('p#user')->text()
        );

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );

        $this->assertSame(
            'Admin',
            $crawler->filter('h2')->text()
        );
    }
}
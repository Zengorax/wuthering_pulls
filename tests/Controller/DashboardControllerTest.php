<?php

namespace App\Tests\Controller;

use App\Entity\MajorVersion;
use App\Entity\Version;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardControllerTest extends WebTestCase
{
    public function testCanRecordAnInvocationAndSeeItsTotals(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $crawler = $client->request('GET', '/major-versions/new');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Enregistrer')->form();
        $form['major_version[name]'] = '3.X';
        $client->submit($form);
        self::assertResponseRedirects('/');
        $client->followRedirect();
        $majorVersionId = $entityManager->getRepository(MajorVersion::class)->findOneBy(['name' => '3.X'])?->getId();
        self::assertNotNull($majorVersionId);

        $crawler = $client->request('GET', '/versions/new');
        $form = $crawler->selectButton('Enregistrer')->form();
        $form['version[name]'] = '3.0';
        $form['version[majorVersion]'] = (string) $majorVersionId;
        $client->submit($form);
        self::assertResponseRedirects('/');

        $versionId = $entityManager->getRepository(Version::class)->findOneBy(['name' => '3.0'])?->getId();
        self::assertNotNull($versionId);

        $crawler = $client->request('GET', '/');
        $form = $crawler->selectButton('Enregistrer la ligne')->form();
        $form['banner_target[version]'] = (string) $versionId;
        $form['banner_target[name]'] = 'Lynae';
        $form['banner_target[pullsCharacter]'] = '125';
        $form['banner_target[pullsWeapon]'] = '64';
        $form['banner_target[fiftyFiftyResult]'] = 'lost';
        $form['banner_target[isEstimate]']->tick();
        $form['banner_target[notes]'] = 'Calcul de test';
        $client->submit($form);

        self::assertResponseRedirects('/');
        $client->followRedirect();
        self::assertSelectorTextContains('h2', '3.X');
        self::assertSelectorTextContains('td', 'Lynae');
        self::assertSelectorTextContains('td:nth-child(4)', '189');
        self::assertSelectorTextContains('.badge-lost', 'Perdu');
        self::assertSelectorTextContains('.badge-estimate', 'Estimation');
        self::assertSelectorTextContains('.period-total', '189 pulls');
        self::assertSelectorTextContains('.stats-grid .stat-card:nth-child(2) .stat-value', '30 240 astrites');
        self::assertSelectorTextContains('.avg-character', '— pulls');
        self::assertSelectorTextContains('.avg-weapon', '— pulls');

        foreach ([
            ['Aemeath', '100', '0', 'won'],
            ['Luci', '0', '40', ''],
        ] as [$name, $characterPulls, $weaponPulls, $result]) {
            $crawler = $client->request('GET', '/');
            $form = $crawler->selectButton('Enregistrer la ligne')->form();
            $form['banner_target[version]'] = (string) $versionId;
            $form['banner_target[name]'] = $name;
            $form['banner_target[pullsCharacter]'] = $characterPulls;
            $form['banner_target[pullsWeapon]'] = $weaponPulls;
            $form['banner_target[fiftyFiftyResult]'] = $result;
            $client->submit($form);
            self::assertResponseRedirects('/');
            $crawler = $client->followRedirect();
        }

        self::assertSelectorTextContains('.avg-character', '100,0 pulls');
        self::assertSelectorTextContains('.avg-weapon', '40,0 pulls');
        self::assertSelectorTextContains('.stat-hint', '1 cible comptabilisée');
        self::assertSelectorTextContains('.period-total', '329 pulls');

        $crawler = $client->request('GET', '/versions/'.$versionId.'/stock');
        $form = $crawler->selectButton('Enregistrer')->form();
        $form['version_stock[startAstrite]'] = '12000';
        $form['version_stock[startPulls]'] = '75';
        $form['version_stock[estimatedEndAstrite]'] = '6400';
        $form['version_stock[estimatedEndPulls]'] = '40';
        $form['version_stock[equivalence]'] = 'S9R8';
        $client->submit($form);
        self::assertResponseRedirects('/');
        $client->followRedirect();
        self::assertSelectorTextContains('.equivalence', 'S9R8');
        self::assertSelectorTextContains('.stock-box', '12 000 astrites');

        $crawler = $client->request('GET', '/resources/new?major='.$majorVersionId);
        $form = $crawler->selectButton('Enregistrer')->form();
        $form['resource_breakdown[majorVersion]'] = (string) $majorVersionId;
        $form['resource_breakdown[name]'] = 'Daily';
        $form['resource_breakdown[amount]'] = '10000';
        $form['resource_breakdown[unit]'] = 'astrites';
        $client->submit($form);
        self::assertResponseRedirects('/');
        $client->followRedirect();
        self::assertSelectorTextContains('.resource-list', 'Daily');
        self::assertSelectorTextContains('.resource-list', '10 000 astrites');
    }
}

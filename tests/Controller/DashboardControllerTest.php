<?php

namespace App\Tests\Controller;

use App\Entity\BannerTarget;
use App\Entity\MajorVersion;
use App\Entity\ResourceBreakdown;
use App\Entity\Version;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Parcours fonctionnels : chaque test est isolé par une transaction (DAMA Doctrine Test Bundle).
 */
class DashboardControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testEmptyHistoryInvitesToCreateAPeriod(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.empty a[href="/major-versions/new"]');
        self::assertSelectorTextContains('html', 'Votre historique commence ici.');
        self::assertSame('fr', $this->client->getCrawler()->filter('html')->attr('lang'));
    }

    public function testCanRecordAnInvocationAndSeeItsTotals(): void
    {
        $crawler = $this->client->request('GET', '/major-versions/new');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Enregistrer')->form();
        $form['major_version[name]'] = '3.X';
        $this->client->submit($form);
        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.toast', 'La période a été créée.');
        $majorVersionId = $this->entityManager->getRepository(MajorVersion::class)->findOneBy(['name' => '3.X'])?->getId();
        self::assertNotNull($majorVersionId);

        $crawler = $this->client->request('GET', '/versions/new?major='.$majorVersionId);
        $form = $crawler->selectButton('Enregistrer')->form();
        $form['version[name]'] = '3.0';
        $form['version[startsAt]'] = '2026-05-01';
        $form['version[endsAt]'] = '2026-06-12';
        $this->client->submit($form);
        self::assertResponseRedirects('/');
        $versionId = $this->entityManager->getRepository(Version::class)->findOneBy(['name' => '3.0'])?->getId();
        self::assertNotNull($versionId);

        $this->submitTarget($versionId, 'Lynae', 125, 64, result: 'lost', estimate: true, notes: 'Calcul de test');
        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertSelectorTextContains('[data-testid="target-name"]', 'Lynae');
        self::assertSelectorTextContains('[data-testid="target-total"]', '189');
        self::assertSelectorTextContains('.badge--lost', '50/50 perdu');
        self::assertSelectorTextContains('[data-testid="target-card"] .badge--estimate', 'Estimation');
        self::assertSelectorTextContains('[data-testid="period-total"]', '189 pulls');
        self::assertSelectorTextContains('[data-testid="total-astrites"]', '30 240');
        self::assertSelectorTextContains('[data-testid="avg-character"]', '— pulls');
        self::assertSelectorTextContains('[data-testid="avg-weapon"]', '— pulls');
        self::assertSelectorTextContains('[data-testid="estimated-pulls"]', '189 estimés (100 %)');
        self::assertSelectorNotExists('.convene', 'Une estimation ne déclenche pas la révélation 5★.');
        self::assertSelectorTextContains('.version__dates', '1 mai 2026');

        $this->submitTarget($versionId, 'Aemeath', 100, 0, result: 'won');
        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.convene__name', 'Aemeath');

        $this->submitTarget($versionId, 'Luci', 0, 40, rarity: 4);
        $this->client->followRedirect();
        self::assertSelectorNotExists('.convene');

        self::assertSelectorTextContains('[data-testid="avg-character"]', '100,0 pulls');
        self::assertSelectorTextContains('[data-testid="avg-weapon"]', '40,0 pulls');
        self::assertSelectorTextContains('[data-testid="avg-character-hint"]', '1 cible comptabilisée');
        self::assertSelectorTextContains('[data-testid="period-total"]', '329 pulls');
        self::assertSelectorTextContains('[data-testid="fifty-fifty"] strong', '100 %');
        self::assertSelectorTextContains('[data-testid="estimated-pulls"]', '189 estimés (57 %)');
        self::assertCount(3, $this->client->getCrawler()->filter('[data-testid="target-card"]'));

        $crawler = $this->client->request('GET', '/versions/'.$versionId.'/stock');
        $form = $crawler->selectButton('Enregistrer')->form();
        $form['version_stock_'.$versionId.'[startAstrite]'] = '12000';
        $form['version_stock_'.$versionId.'[startPulls]'] = '75';
        $form['version_stock_'.$versionId.'[estimatedEndAstrite]'] = '6400';
        $form['version_stock_'.$versionId.'[estimatedEndPulls]'] = '40';
        $form['version_stock_'.$versionId.'[equivalence]'] = 's9r8';
        $this->client->submit($form);
        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertSelectorTextContains('[data-testid="equivalence"]', 'S9');
        self::assertSelectorTextContains('[data-testid="equivalence"]', 'R8');
        self::assertSelectorTextContains('[data-testid="stock-box"]', '12 000');
        // Gain implicite : (40 + 40) - (75 + 75) + 329 pulls dépensés = 259.
        self::assertSelectorTextContains('.stock__income', '259');
        self::assertSelectorTextContains('[data-testid="projection"]', '80');

        $crawler = $this->client->request('GET', '/resources/new?major='.$majorVersionId);
        $form = $crawler->selectButton('Enregistrer')->form();
        $form['resource_breakdown[name]'] = 'Daily';
        $form['resource_breakdown[amount]'] = '10000';
        $form['resource_breakdown[unit]'] = 'astrites';
        $this->client->submit($form);
        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertSelectorTextContains('[data-testid="resource-list"]', 'Daily');
        self::assertSelectorTextContains('[data-testid="resource-list"]', '10 000 astrites');
    }

    public function testEditRenamesAndDeleteRemovesEachKindOfRecord(): void
    {
        [$period, $version, $target, $resource] = $this->seed();

        // Modification de la période.
        $crawler = $this->client->request('GET', '/major-versions/'.$period->getId().'/edit');
        $form = $crawler->selectButton('Enregistrer')->form();
        $form['major_version[name]'] = '4.X';
        $this->client->submit($form);
        self::assertResponseRedirects('/');
        self::assertSame('4.X', $this->reload(MajorVersion::class, $period->getId())->getName());

        // Modification de la version, avec dates incohérentes puis valides.
        $crawler = $this->client->request('GET', '/versions/'.$version->getId().'/edit');
        $form = $crawler->selectButton('Enregistrer')->form();
        $form['version[name]'] = '4.0';
        $form['version[startsAt]'] = '2026-06-12';
        $form['version[endsAt]'] = '2026-05-01';
        $this->client->submit($form);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.field__errors', 'La date de fin doit être postérieure');
        $form = $this->client->getCrawler()->selectButton('Enregistrer')->form();
        $form['version[endsAt]'] = '2026-07-20';
        $this->client->submit($form);
        self::assertResponseRedirects('/');
        self::assertSame('4.0', $this->reload(Version::class, $version->getId())->getName());

        // Modification de la source de ressources.
        $crawler = $this->client->request('GET', '/resources/'.$resource->getId().'/edit');
        $form = $crawler->selectButton('Enregistrer')->form();
        $form['resource_breakdown[amount]'] = '4200';
        $this->client->submit($form);
        self::assertResponseRedirects('/');
        self::assertSame(4200, $this->reload(ResourceBreakdown::class, $resource->getId())->getAmount());

        // Suppression de chaque élément via les formulaires protégés par jeton CSRF.
        $this->client->request('GET', '/');
        foreach ([
            'form[action$="/targets/'.$target->getId().'/delete"]' => BannerTarget::class,
            'form[action$="/resources/'.$resource->getId().'/delete"]' => ResourceBreakdown::class,
            'form[action$="/versions/'.$version->getId().'/delete"]' => Version::class,
        ] as $selector => $class) {
            $crawler = $this->client->request('GET', '/');
            $deleteForm = $crawler->filter($selector);
            self::assertCount(1, $deleteForm, $selector);
            self::assertNotEmpty($deleteForm->attr('data-turbo-confirm'), 'La suppression demande confirmation.');
            $this->client->submit($deleteForm->form());
            self::assertResponseRedirects('/');
            $this->entityManager->clear();
            self::assertSame(0, $this->entityManager->getRepository($class)->count([]), $class);
        }

        $crawler = $this->client->request('GET', '/');
        $this->client->submit($crawler->filter('form[action$="/major-versions/'.$period->getId().'/delete"]')->form());
        self::assertResponseRedirects('/');
        $this->entityManager->clear();
        self::assertSame(0, $this->entityManager->getRepository(MajorVersion::class)->count([]));
    }

    public function testDeleteWithoutAValidCsrfTokenIsRejected(): void
    {
        [, , $target] = $this->seed();

        $this->client->request('POST', '/targets/'.$target->getId().'/delete', ['_token' => 'invalide']);

        self::assertResponseStatusCodeSame(401);
        $this->entityManager->clear();
        self::assertSame(1, $this->entityManager->getRepository(BannerTarget::class)->count([]));
    }

    public function testDeletingAPeriodRemovesItsVersionsAndTargets(): void
    {
        [$period] = $this->seed();

        $crawler = $this->client->request('GET', '/');
        $this->client->submit($crawler->filter('form[action$="/major-versions/'.$period->getId().'/delete"]')->form());

        $this->entityManager->clear();
        foreach ([MajorVersion::class, Version::class, BannerTarget::class, ResourceBreakdown::class] as $class) {
            self::assertSame(0, $this->entityManager->getRepository($class)->count([]), $class);
        }
    }

    public function testTurboFrameRequestsReceiveOnlyTheFrame(): void
    {
        [, $version, $target] = $this->seed();

        $this->client->request('GET', '/targets/'.$target->getId().'/edit', server: ['HTTP_TURBO_FRAME' => 'target-'.$target->getId()]);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('turbo-frame#target-'.$target->getId().' form[data-turbo-frame="_top"]');
        self::assertSelectorNotExists('header.topbar');

        $this->client->request('GET', '/versions/'.$version->getId().'/stock', server: ['HTTP_TURBO_FRAME' => 'version-stock-'.$version->getId()]);
        self::assertSelectorExists('turbo-frame#version-stock-'.$version->getId());
        self::assertSelectorNotExists('header.topbar');

        // Sans en-tête Turbo-Frame, la même page s'affiche dans la mise en page complète.
        $this->client->request('GET', '/targets/'.$target->getId().'/edit');
        self::assertSelectorExists('header.topbar');
    }

    public function testInvalidEquivalenceIsRejected(): void
    {
        [, $version] = $this->seed();

        $crawler = $this->client->request('GET', '/versions/'.$version->getId().'/stock');
        $form = $crawler->selectButton('Enregistrer')->form();
        $form['version_stock_'.$version->getId().'[equivalence]'] = 'beaucoup';
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.field__errors', 'Utilisez le format S9R8');
    }

    public function testCannotCreateTheSamePeriodTwice(): void
    {
        $this->seed();

        $crawler = $this->client->request('GET', '/major-versions/new');
        $form = $crawler->selectButton('Enregistrer')->form();
        $form['major_version[name]'] = '3.X';
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.field__errors', 'Cette période majeure existe déjà.');
    }

    public function testDashboardQueryCountDoesNotGrowWithTheHistory(): void
    {
        for ($major = 1; $major <= 3; ++$major) {
            $period = (new MajorVersion())->setName($major.'.X');
            $this->entityManager->persist($period);
            for ($minor = 0; $minor < 3; ++$minor) {
                $version = (new Version())->setName($major.'.'.$minor)->setMajorVersion($period);
                $this->entityManager->persist($version);
                for ($i = 0; $i < 3; ++$i) {
                    $this->entityManager->persist((new BannerTarget())->setName('Cible '.$i)->setPullsCharacter(70)->setVersion($version));
                }
            }
            $this->entityManager->persist((new ResourceBreakdown())->setName('Daily')->setAmount(100)->setMajorVersion($period));
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->client->enableProfiler();
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertCount(27, $this->client->getCrawler()->filter('[data-testid="target-card"]'));
        // La connexion est partagée avec la préparation des données (DAMA) : on ne compte que les lectures.
        $selects = array_filter(
            $this->client->getProfile()->getCollector('db')->getQueries()['default'] ?? [],
            static fn (array $query): bool => str_starts_with(ltrim($query['sql']), 'SELECT'),
        );
        $sql = implode("
", array_map(static fn (array $query): string => substr($query['sql'], 0, 140), $selects));
        self::assertLessThanOrEqual(6, count($selects), sprintf("%d requêtes SELECT pour le tableau de bord :
%s", count($selects), $sql));
    }

    private function submitTarget(int $versionId, string $name, int $character, int $weapon, ?string $result = null, bool $estimate = false, int $rarity = 5, ?string $notes = null): void
    {
        $crawler = $this->client->request('GET', '/');
        $form = $crawler->selectButton('Enregistrer la ligne')->form();
        $form['banner_target[version]'] = (string) $versionId;
        $form['banner_target[name]'] = $name;
        $form['banner_target[rarity]'] = (string) $rarity;
        $form['banner_target[pullsCharacter]'] = (string) $character;
        $form['banner_target[pullsWeapon]'] = (string) $weapon;
        $form['banner_target[fiftyFiftyResult]'] = $result ?? '';
        if ($estimate) {
            $form['banner_target[isEstimate]']->tick();
        }
        if (null !== $notes) {
            $form['banner_target[notes]'] = $notes;
        }
        $this->client->submit($form);
    }

    /**
     * @return array{0: MajorVersion, 1: Version, 2: BannerTarget, 3: ResourceBreakdown}
     */
    private function seed(): array
    {
        $period = (new MajorVersion())->setName('3.X');
        $version = (new Version())->setName('3.0')->setMajorVersion($period);
        $target = (new BannerTarget())->setName('Lynae')->setPullsCharacter(70)->setPullsWeapon(30)->setVersion($version);
        $resource = (new ResourceBreakdown())->setName('Daily')->setAmount(3200)->setMajorVersion($period);
        foreach ([$period, $version, $target, $resource] as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();

        return [$period, $version, $target, $resource];
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function reload(string $class, ?int $id): object
    {
        $this->entityManager->clear();

        return $this->entityManager->find($class, $id) ?? self::fail('Entité introuvable.');
    }
}

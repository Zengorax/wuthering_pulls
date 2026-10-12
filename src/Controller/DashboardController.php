<?php

namespace App\Controller;

use App\Entity\BannerTarget;
use App\Entity\MajorVersion;
use App\Entity\ResourceBreakdown;
use App\Entity\Version;
use App\Form\BannerTargetType;
use App\Form\MajorVersionType;
use App\Form\ResourceBreakdownType;
use App\Form\VersionStockType;
use App\Form\VersionType;
use App\Repository\MajorVersionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'dashboard', methods: ['GET', 'POST'])]
    public function index(Request $request, MajorVersionRepository $majorVersions, EntityManagerInterface $entityManager): Response
    {
        $targetForm = $this->createForm(BannerTargetType::class, new BannerTarget());
        $targetForm->handleRequest($request);

        if ($targetForm->isSubmitted() && $targetForm->isValid()) {
            $entityManager->persist($targetForm->getData());
            $entityManager->flush();

            $this->addFlash('success', 'La ligne d’invocation a été enregistrée.');

            return $this->redirectToRoute('dashboard');
        }

        $periods = [];
        $hasVersions = false;
        $characterPullsTotal = 0;
        $characterPullsCount = 0;
        $weaponPullsTotal = 0;
        $weaponPullsCount = 0;
        foreach ($majorVersions->findBy([], ['id' => 'DESC']) as $period) {
            $versions = $period->getVersions()->toArray();
            usort($versions, static fn (Version $left, Version $right): int => strnatcmp($right->getName() ?? '', $left->getName() ?? ''));
            $hasVersions = $hasVersions || $versions !== [];

            $orderedVersions = [];
            foreach ($versions as $version) {
                $targets = $version->getBannerTargets()->toArray();
                usort($targets, static fn (BannerTarget $left, BannerTarget $right): int => $left->getId() <=> $right->getId());
                foreach ($targets as $target) {
                    if ($target->isEstimate()) {
                        continue;
                    }

                    if ($target->getPullsCharacter() > 0) {
                        $characterPullsTotal += $target->getPullsCharacter();
                        ++$characterPullsCount;
                    }

                    if ($target->getPullsWeapon() > 0) {
                        $weaponPullsTotal += $target->getPullsWeapon();
                        ++$weaponPullsCount;
                    }
                }

                $orderedVersions[] = [
                    'entity' => $version,
                    'targets' => $targets,
                ];
            }

            $periods[] = [
                'entity' => $period,
                'versions' => $orderedVersions,
            ];
        }

        return $this->render('dashboard/index.html.twig', [
            'periods' => $periods,
            'has_versions' => $hasVersions,
            'character_pulls_average' => $characterPullsCount > 0 ? $characterPullsTotal / $characterPullsCount : null,
            'character_pulls_count' => $characterPullsCount,
            'weapon_pulls_average' => $weaponPullsCount > 0 ? $weaponPullsTotal / $weaponPullsCount : null,
            'weapon_pulls_count' => $weaponPullsCount,
            'target_form' => $targetForm,
        ]);
    }

    #[Route('/major-versions/new', name: 'major_version_new', methods: ['GET', 'POST'])]
    public function newMajorVersion(Request $request, EntityManagerInterface $entityManager): Response
    {
        $majorVersion = new MajorVersion();
        $form = $this->createForm(MajorVersionType::class, $majorVersion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($majorVersion);
            $entityManager->flush();

            $this->addFlash('success', sprintf('La période %s a été créée.', $majorVersion->getName()));

            return $this->redirectToRoute('dashboard');
        }

        return $this->render('dashboard/form.html.twig', [
            'title' => 'Nouvelle période majeure',
            'description' => 'Créez un regroupement, par exemple 3.X, pour organiser ses versions.',
            'form' => $form,
        ]);
    }

    #[Route('/versions/new', name: 'version_new', methods: ['GET', 'POST'])]
    public function newVersion(Request $request, EntityManagerInterface $entityManager): Response
    {
        $version = new Version();
        $form = $this->createForm(VersionType::class, $version);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($version);
            $entityManager->flush();

            $this->addFlash('success', sprintf('La version %s a été créée.', $version->getName()));

            return $this->redirectToRoute('dashboard');
        }

        return $this->render('dashboard/form.html.twig', [
            'title' => 'Nouvelle version',
            'description' => 'Associez une version de jeu (3.0, 3.1…) à une période majeure.',
            'form' => $form,
        ]);
    }

    #[Route('/targets/{id}/edit', name: 'target_edit', methods: ['GET', 'POST'])]
    public function editTarget(BannerTarget $target, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BannerTargetType::class, $target);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La ligne d’invocation a été modifiée.');

            return $this->redirectToRoute('dashboard');
        }

        return $this->render('dashboard/form.html.twig', [
            'title' => 'Modifier une invocation',
            'description' => 'Mettez à jour les pulls, le statut ou les remarques de cette cible.',
            'form' => $form,
        ]);
    }

    #[Route('/versions/{id}/stock', name: 'version_stock_edit', methods: ['GET', 'POST'])]
    public function editVersionStock(Version $version, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VersionStockType::class, $version);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Le stock de la version a été mis à jour.');

            return $this->redirectToRoute('dashboard');
        }

        return $this->render('dashboard/form.html.twig', [
            'title' => sprintf('Stock · %s', $version->getName()),
            'description' => 'Les champs sont facultatifs. Saisissez vos stocks de début et estimations de fin.',
            'form' => $form,
        ]);
    }

    #[Route('/resources/new', name: 'resource_new', methods: ['GET', 'POST'])]
    public function newResource(Request $request, EntityManagerInterface $entityManager, MajorVersionRepository $majorVersions): Response
    {
        $resource = new ResourceBreakdown();
        $majorId = $request->query->getInt('major');
        if ($majorId > 0) {
            $resource->setMajorVersion($majorVersions->find($majorId));
        }

        $form = $this->createForm(ResourceBreakdownType::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($resource);
            $entityManager->flush();
            $this->addFlash('success', 'La source de ressources a été ajoutée.');

            return $this->redirectToRoute('dashboard');
        }

        return $this->render('dashboard/form.html.twig', [
            'title' => 'Ajouter une origine de ressources',
            'description' => 'Consignez une source d’astrites ou de marées pour une période majeure.',
            'form' => $form,
        ]);
    }

    #[Route('/targets/{id}/delete', name: 'target_delete', methods: ['POST'])]
    public function deleteTarget(BannerTarget $target, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete-target-'.$target->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $entityManager->remove($target);
        $entityManager->flush();
        $this->addFlash('success', 'La ligne d’invocation a été supprimée.');

        return $this->redirectToRoute('dashboard');
    }
}

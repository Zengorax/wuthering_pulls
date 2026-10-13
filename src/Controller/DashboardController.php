<?php

namespace App\Controller;

use App\Entity\BannerTarget;
use App\Form\BannerTargetType;
use App\Repository\MajorVersionRepository;
use App\Statistics\DashboardBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'dashboard', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        MajorVersionRepository $majorVersions,
        DashboardBuilder $dashboardBuilder,
        EntityManagerInterface $entityManager,
    ): Response {
        $target = new BannerTarget();
        $targetForm = $this->createForm(BannerTargetType::class, $target);
        $targetForm->handleRequest($request);

        if ($targetForm->isSubmitted() && $targetForm->isValid()) {
            $entityManager->persist($target);
            $entityManager->flush();

            $this->addFlash('success', 'flash.target.created');
            if ($target->isFiveStar() && !$target->isEstimate() && $target->getPullsCharacter() > 0) {
                // Déclenche l'animation de révélation 5★ côté interface.
                $this->addFlash('convene', (string) $target->getName());
            }

            return $this->redirectToRoute('dashboard');
        }

        return $this->render('dashboard/index.html.twig', [
            'dashboard' => $dashboardBuilder->build($majorVersions->findAllWithHistory()),
            'target_form' => $targetForm,
        ]);
    }
}

<?php

namespace App\Controller;

use App\Entity\BannerTarget;
use App\Form\BannerTargetType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[Route('/targets')]
final class TargetController extends AbstractController
{
    use RendersFormPage;

    #[Route('/{id}/edit', name: 'target_edit', methods: ['GET', 'POST'])]
    public function edit(BannerTarget $target, Request $request, EntityManagerInterface $entityManager, FormFactoryInterface $formFactory): Response
    {
        // Nom de formulaire unique : plusieurs cartes peuvent être éditées en place sur la même page.
        $form = $formFactory->createNamed(sprintf('target_%d', $target->getId()), BannerTargetType::class, $target);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'flash.target.updated');

            return $this->redirectToRoute('dashboard');
        }

        return $this->renderFormPage($form, 'page.target_edit.title', 'page.target_edit.description', 'target-'.$target->getId());
    }

    #[Route('/{id}/delete', name: 'target_delete', methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"delete-target-" ~ args["target"].getId()'))]
    public function delete(BannerTarget $target, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($target);
        $entityManager->flush();
        $this->addFlash('success', 'flash.target.deleted');

        return $this->redirectToRoute('dashboard');
    }
}

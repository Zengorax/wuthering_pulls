<?php

namespace App\Controller;

use App\Entity\MajorVersion;
use App\Form\MajorVersionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[Route('/major-versions')]
final class MajorVersionController extends AbstractController
{
    use RendersFormPage;

    #[Route('/new', name: 'major_version_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $majorVersion = new MajorVersion();
        $form = $this->createForm(MajorVersionType::class, $majorVersion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($majorVersion);
            $entityManager->flush();
            $this->addFlash('success', 'flash.major_version.created');

            return $this->redirectToRoute('dashboard');
        }

        return $this->renderFormPage($form, 'page.major_version_new.title', 'page.major_version_new.description');
    }

    #[Route('/{id}/edit', name: 'major_version_edit', methods: ['GET', 'POST'])]
    public function edit(MajorVersion $majorVersion, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MajorVersionType::class, $majorVersion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'flash.major_version.updated');

            return $this->redirectToRoute('dashboard');
        }

        return $this->renderFormPage($form, 'page.major_version_edit.title', 'page.major_version_edit.description');
    }

    #[Route('/{id}/delete', name: 'major_version_delete', methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"delete-major-version-" ~ args["majorVersion"].getId()'))]
    public function delete(MajorVersion $majorVersion, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($majorVersion);
        $entityManager->flush();
        $this->addFlash('success', 'flash.major_version.deleted');

        return $this->redirectToRoute('dashboard');
    }
}

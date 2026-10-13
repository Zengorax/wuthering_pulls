<?php

namespace App\Controller;

use App\Entity\ResourceBreakdown;
use App\Form\ResourceBreakdownType;
use App\Repository\MajorVersionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[Route('/resources')]
final class ResourceController extends AbstractController
{
    use RendersFormPage;

    #[Route('/new', name: 'resource_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MajorVersionRepository $majorVersions): Response
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
            $this->addFlash('success', 'flash.resource.created');

            return $this->redirectToRoute('dashboard');
        }

        return $this->renderFormPage($form, 'page.resource_new.title', 'page.resource_new.description');
    }

    #[Route('/{id}/edit', name: 'resource_edit', methods: ['GET', 'POST'])]
    public function edit(ResourceBreakdown $resource, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ResourceBreakdownType::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'flash.resource.updated');

            return $this->redirectToRoute('dashboard');
        }

        return $this->renderFormPage($form, 'page.resource_edit.title', 'page.resource_edit.description');
    }

    #[Route('/{id}/delete', name: 'resource_delete', methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"delete-resource-" ~ args["resource"].getId()'))]
    public function delete(ResourceBreakdown $resource, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($resource);
        $entityManager->flush();
        $this->addFlash('success', 'flash.resource.deleted');

        return $this->redirectToRoute('dashboard');
    }
}

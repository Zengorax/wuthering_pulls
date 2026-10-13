<?php

namespace App\Controller;

use App\Entity\Version;
use App\Form\VersionStockType;
use App\Form\VersionType;
use App\Repository\MajorVersionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[Route('/versions')]
final class VersionController extends AbstractController
{
    use RendersFormPage;

    #[Route('/new', name: 'version_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MajorVersionRepository $majorVersions): Response
    {
        $version = new Version();
        $majorId = $request->query->getInt('major');
        if ($majorId > 0) {
            $version->setMajorVersion($majorVersions->find($majorId));
        }

        $form = $this->createForm(VersionType::class, $version);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($version);
            $entityManager->flush();
            $this->addFlash('success', 'flash.version.created');

            return $this->redirectToRoute('dashboard');
        }

        return $this->renderFormPage($form, 'page.version_new.title', 'page.version_new.description');
    }

    #[Route('/{id}/edit', name: 'version_edit', methods: ['GET', 'POST'])]
    public function edit(Version $version, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VersionType::class, $version);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'flash.version.updated');

            return $this->redirectToRoute('dashboard');
        }

        return $this->renderFormPage($form, 'page.version_edit.title', 'page.version_edit.description');
    }

    #[Route('/{id}/stock', name: 'version_stock_edit', methods: ['GET', 'POST'])]
    public function editStock(Version $version, Request $request, EntityManagerInterface $entityManager, FormFactoryInterface $formFactory): Response
    {
        $form = $formFactory->createNamed(sprintf('version_stock_%d', $version->getId()), VersionStockType::class, $version);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'flash.version.stock_updated');

            return $this->redirectToRoute('dashboard');
        }

        return $this->renderFormPage($form, 'page.version_stock.title', 'page.version_stock.description', 'version-stock-'.$version->getId());
    }

    #[Route('/{id}/delete', name: 'version_delete', methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"delete-version-" ~ args["version"].getId()'))]
    public function delete(Version $version, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($version);
        $entityManager->flush();
        $this->addFlash('success', 'flash.version.deleted');

        return $this->redirectToRoute('dashboard');
    }
}

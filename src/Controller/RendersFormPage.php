<?php

namespace App\Controller;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rend la page de formulaire commune. Le gabarit s'adapte tout seul aux requêtes Turbo Frame
 * (édition en place) comme aux navigations classiques.
 */
trait RendersFormPage
{
    private function renderFormPage(FormInterface $form, string $title, string $description, ?string $frameId = null): Response
    {
        return $this->render('form/page.html.twig', [
            'form' => $form,
            'title' => $title,
            'description' => $description,
            'frame_id' => $frameId,
        ]);
    }
}

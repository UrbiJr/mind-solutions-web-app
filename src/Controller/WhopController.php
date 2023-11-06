<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class WhopController extends AbstractController
{
    #[Route('/whop', name: 'whop')]
    public function index(Request $request): RedirectResponse
    {
        $code = $request->query->get('code');

        if (!$code) {
            $this->addFlash(
                'error',
                "Missing authorization code"
            );
            $url = $this->generateUrl('error_500');

            return $this->redirect($url);
        }

        $url = $this->generateUrl('whop_callback', [
            'code' => $code,
        ]);

        return $this->redirect($url);
    }
}
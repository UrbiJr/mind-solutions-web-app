<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class WhopController extends AbstractController
{
    #[Route('/whop', name: 'whop')]
    public function index(Request $request): RedirectResponse
    {
        $code = $request->query->get('code');

        if (!$code) {
            throw new \Exception('Missing authorization code');
        }

        $url = $this->generateUrl('whop_callback', [
            'code' => $code,
        ]);

        return $this->redirect($url);
    }
}
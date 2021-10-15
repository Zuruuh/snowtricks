<?php

namespace App\Controller\Api;

use App\Repository\TrickRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/tricks', name: 'api.tricks.')]
class TricksApiController extends AbstractController
{
    #[Route('/', name: 'get', methods: ['GET'])]
    public function index(Request $request, TrickRepository $repo): Response
    {
        $total = intval($request->get('total', 0));
        $index = intval($request->get('index', 0));
        $max = intval($request->get('max', 3));

        $max = $max > 6 ? 6 : $max;

        $real_total = $repo->countAll();

        $tricks = $repo->getPaginatedTricks($index, $max);

        $json = json_encode([
            'total' => $total === 0 ? $real_total : $real_total - $total,
            'index' => $index + 3 > $real_total ? $real_total : $index + 3,
            'tricks' => $real_total - $total >= 0 ? $tricks : [],
        ]);

        return new Response($json, 200, [
            'Content-Type' => 'application/json',
        ]);
    }
}

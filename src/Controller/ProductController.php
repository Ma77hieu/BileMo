<?php

namespace App\Controller;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;


class ProductController extends AbstractController
{
    #[Route('/products', name: 'list_products', methods: 'GET')]
    public function index(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $em = $doctrine->getManager();
        $totalProducts = $em->getRepository(Product::class)->getNbrOfproducts();
        $resultsPerPage = $request->query->get('perPage') ? $request->query->get('perPage') : 2;
        $offset = $request->query->get('page') ? $request->query->get('page') - 1 : 0;
        $maxPage = ceil($totalProducts / $resultsPerPage);
        if ($maxPage <= $offset) {
            return $this->json("Impossible d'accéder à la page demandée, page maximale: $maxPage");
        }
        if ($offset < 0) {
            $offset = 0;
        }
        $firstResult = $offset * $resultsPerPage;
        $productList = $em->getRepository(Product::class)->getProductPage($firstResult, $resultsPerPage);
        $response=$this->json($productList);
        //cache management
        $response->setCache([
            'public'           => true,
            'max_age'          => 3600
        ]);
        return $response;
    }

    #[Route('/product/{id}', name: 'details_product', methods: 'GET')]
    public function productDetails(ManagerRegistry $doctrine, $id): JsonResponse
    {
        if (gettype($id)!='integer') {
            $response = new JsonResponse('Merci d\'entrer un nombre entier en tant qu\'identifiant.');
            $response->setStatusCode(Response::HTTP_NOT_ACCEPTABLE);
            return $response;
        }
        $em = $doctrine->getManager();
        $selectedProduct = $em->getRepository(Product::class)->find($id);
        $response=$this->json($selectedProduct);
        //cache management
        $response->setCache([
            'public'           => true,
            'max_age'          => 3600
        ]);
        return $response;
    }
}

<?php

namespace App\Controller;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\SerializerInterface;


class ProductController extends AbstractController
{
    #[Route('/products', name: 'list_products')]
    public function index(ManagerRegistry $doctrine): JsonResponse
    {
        $em=$doctrine->getManager();
        $allProducts= $em->getRepository(Product::class)->findAll();
        /*$allProdJson=$serializer->serialize($allProducts,'json');*/

        return $this->json($allProducts);

        /*return $this->json([
            'message' => $json,
            'path' => 'src/Controller/ProductController.php',
        ]);*/
    }

    #[Route('/product/{id}', name: 'details_product')]
    public function productDetails(ManagerRegistry $doctrine,$id): JsonResponse
    {
        $em=$doctrine->getManager();
        $selectedProduct= $em->getRepository(Product::class)->find($id);
        return $this->json($selectedProduct);
    }
}

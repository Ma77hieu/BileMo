<?php

namespace App\Controller;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\SerializerInterface;


class ProductController extends AbstractController
{
    #[Route('/products', name: 'list_products')]
    public function index(ManagerRegistry $doctrine,Request $request): JsonResponse
    {
        $em=$doctrine->getManager();
        $totalProducts=$em->getRepository(Product::class)->getNbrOfproducts();
        $resultsPerPage=$request->query->get('perPage')?$request->query->get('perPage'):2;
        $offset=$request->query->get('page')?$request->query->get('page')-1:0;
        $maxPage=ceil($totalProducts/$resultsPerPage);
        if ($maxPage<=$offset){
            return $this->json("Impossible d'accéder à la page demandée, page maximale: $maxPage");
        }
        if ($offset<0){$offset=0;}
        $resultsPerPage=$request->query->get('perPage')?$request->query->get('perPage'):2;
        $firstResult=$offset*$resultsPerPage;
        $productList=$em->getRepository(Product::class)->getProductPage($firstResult,$resultsPerPage);
        return $this->json($productList);
    }

    #[Route('/product/{id}', name: 'details_product')]
    public function productDetails(ManagerRegistry $doctrine,$id): JsonResponse
    {
        $em=$doctrine->getManager();
        $selectedProduct= $em->getRepository(Product::class)->find($id);
        return $this->json($selectedProduct);
    }
}

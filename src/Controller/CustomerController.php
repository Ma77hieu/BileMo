<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class CustomerController extends AbstractController
{
    #[Route('/customer/{id}', name: 'customer_details', methods: 'GET')]
    public function index(ManagerRegistry $doctrine, Request $request,$id): JsonResponse
    {
        $em = $doctrine->getManager();
        $customer = $em->getRepository(Customer::class)->find($id);
        $encoder = new JsonEncoder();
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                if ($object instanceof User) {
                    return $object->getLastName();
                } else if ($object instanceof Customer) {
                    return $object->getUsername();
                } else {
                    return $object->getName();
                }
            },
        ];
        $normalizer = new ObjectNormalizer(null, null, null, null, null, null, $defaultContext);
        $serializer = new Serializer([$normalizer], [$encoder]);
        $jsonCustomer = $serializer->normalize($customer, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['users','roles','salt','password','userIdentifier']]);
        $response = new JsonResponse($jsonCustomer);
        //cache management
        $response->setCache([
            'public'           => true,
            'max_age'          => 3600
        ]);
        return $response;
    }
}

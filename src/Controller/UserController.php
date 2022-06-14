<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    #[Route('/users/{custId}', name: 'list_users')]
    public function index(ManagerRegistry $doctrine, $custId, Request $request): Response
    {
        $em = $doctrine->getManager();
        $totalUsers = $em->getRepository(User::class)->getNbrOfUsersOfCust($custId);
        $resultsPerPage = $request->query->get('perPage') ? $request->query->get('perPage') : 2;
        $offset = $request->query->get('page') ? $request->query->get('page') - 1 : 0;
        $maxPage = ceil($totalUsers / $resultsPerPage);
        if ($maxPage <= $offset) {
            return $this->json("Impossible d'accéder à la page demandée, page maximale: $maxPage");
        }
        if ($offset < 0) {
            $offset = 0;
        }
        $firstResult = $offset * $resultsPerPage;
        $usersList = $em->getRepository(User::class)->getUserPageofCust($firstResult, $resultsPerPage, $custId);
        $encoder = new JsonEncoder();
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                if ($object instanceof User) {
                    return $object->getLastName();
                } else if ($object instanceof Customer) {
                    return $object->getUserName();
                } else {
                    return $object->getName();
                }
            },
        ];
        $normalizer = new ObjectNormalizer(null, null, null, null, null, null, $defaultContext);
        $serializer = new Serializer([$normalizer], [$encoder]);
        $jsonUsers = $serializer->serialize($usersList, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['customer']]);
        $response = new Response($jsonUsers);
        return $response;
    }

    #[Route('/user/{userId}', name: 'detail_user', methods: 'GET')]
    public function detailsUser(ManagerRegistry $doctrine, $userId): Response
    {
        $em = $doctrine->getManager();
        $selectedUser = $em->getRepository(User::class)->find($userId);

        $encoder = new JsonEncoder();
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                if ($object instanceof User) {
                    return $object->getLastName();
                } else if ($object instanceof Customer) {
                    return $object->getUserName();
                } else {
                    return $object->getName();
                }
            },
        ];
        $normalizer = new ObjectNormalizer(null, null, null, null, null, null, $defaultContext);
        $serializer = new Serializer([$normalizer], [$encoder]);
        $jsonUser = $serializer->serialize(
            $selectedUser,
            'json',
            [AbstractNormalizer::IGNORED_ATTRIBUTES => ['customer']]
        );
        $response = new Response($jsonUser);
        return $response;
    }

    #[Route('/user/{custId}', name: 'create_user', methods: 'PUT')]
    public function createUser(ManagerRegistry $doctrine, $custId, Request $request, SerializerInterface $serializer, ValidatorInterface $validator): Response
    {
        $data = $request->getContent();
        $em = $doctrine->getManager();
        $userCreate = new User();
        $cust = $em->getRepository(Customer::class)->find($custId);
        $serializer->deserialize($data, User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $userCreate]);
        $errors = $validator->validate($userCreate);

        if (count($errors) > 0) {
            $errorsString = '';
            foreach ($errors as $error) {
                $errorsString .= $error->getPropertyPath() . ": ";
                $errorsString .= $error->getMessage() . " ";
            }
            $response = new Response($errorsString);
            $response->setStatusCode(Response::HTTP_NOT_ACCEPTABLE);
            return $response;
        }
        //TODO remove when auth is functionnal
        $userCreate->setCustomer($cust);
        $em->persist($userCreate);
        $em->flush();
        $response = new Response();
        $response->setStatusCode(Response::HTTP_CREATED);
        return $response;
    }

    #[Route('/user/{userId}', name: 'delete_user', methods: 'DELETE')]
    public function deleteUser(ManagerRegistry $doctrine, $userId, Request $request, SerializerInterface $serializer): Response
    {
        $em = $doctrine->getManager();
        $userDelete = $em->getRepository(User::class)->find($userId);
        $response = new Response();

        try {
            $em->remove($userDelete);
            $em->flush();
            $response->setStatusCode(Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        return $response;
    }
}

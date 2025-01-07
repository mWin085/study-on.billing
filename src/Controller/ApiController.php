<?php

namespace App\Controller;

use App\Dto\UserDto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiController extends AbstractController
{
    private $serializer;
    private $validator;
    private $tokenStorage;
    private $JWTTokenManager;


    public function __construct(
        ValidatorInterface $validator,
    {
        $this->serializer = SerializerBuilder::create()->build();
        $this->validator = $validator;
    }
    #[Route('/api/v1/auth', name: 'app_api_auth', methods: ['POST'])]
    public function index()
    {
    }
    #[Route('/api/v1/register', name: 'app_api_register', methods: ['POST'])]
    public function register(Request $request,
                             EntityManagerInterface $entityManager,
                             JWTTokenManagerInterface $tokenManager,
                             UserPasswordHasherInterface $hasher)
    {
        $userDto = $this->serializer->deserialize(
            $request->getContent(),
            UserDto::class,
            'json'
        );
        $errors = $this->validator->validate($userDto);

        $userByEmail = $entityManager->getRepository(User::class)->findBy(['email' => $userDto->username]);

        if ($userByEmail) {
            return $this->json(['error' => 'User with this email already exists'], Response::HTTP_BAD_REQUEST);
        }

        if (count($errors) > 0)
        {
            return $this->json(['error' => $errors->get(0)->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $user = User::fromDto($userDto);
        $user->setPassword($hasher->hashPassword($user, $userDto->password));
        $entityManager->persist($user);
        $entityManager->flush();

        $token = $tokenManager->create($user);
        return $this->json(['token' => $token, 'roles' => $user->getRoles()], Response::HTTP_CREATED);
    }

    /**
     * @throws JWTDecodeFailureException
     */
    #[Route('/api/v1/users/current', name: 'app_api_current_user', methods: ['POST'])]
    public function currentUser(Request $request,
                                EntityManagerInterface $entityManager,
                                JWTTokenManagerInterface $JWTTokenManager,
                                TokenStorageInterface $tokenStorage)
    {
        $tokenData = $JWTTokenManager->decode($tokenStorage->getToken());

        $userByEmail = $entityManager->getRepository(User::class)->findOneBy(['email' => $tokenData['username']]);

        if (!$userByEmail) {
            return $this->json(['error' => 'No user with this email address exists'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(
            ["username" => $userByEmail->getEmail(), "roles" => $userByEmail->getRoles(), "balance" => $userByEmail->getBalance()]
        );
        //$jwt = (array)JWTManager::decode($token);
    }
}

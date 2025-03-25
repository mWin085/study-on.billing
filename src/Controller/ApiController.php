<?php

namespace App\Controller;

use App\Dto\UserDto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;

class ApiController extends AbstractController
{
    private $serializer;
    private $validator;


    public function __construct(
        ValidatorInterface $validator
    )
    {
        $this->serializer = SerializerBuilder::create()->build();
        $this->validator = $validator;
    }
    #[Route('/api/v1/auth', name: 'app_api_auth', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/auth',description: 'Авторизация пользователя'
    )]
    #[OA\RequestBody(
        request: true, description: 'JSON payload', required: true,
        content: [
            new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "username",
                        description: "Email",
                        type: "string",
                        example: "user@user.com"
                    ),
                    new OA\Property(
                        property: "password",
                        description: "Пароль",
                        type: "string",
                        example: "useruser"
                    ),
                ]
            ),
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Success',
        content: [
            new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "token", type:"string", example: '123123123123123'),
                    ]
                )


        ]
    )]
    #[OA\Response(
        response: 400,
        description: 'Error',
        content: [
            new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",example: 'error message'
                    ),
                ]
            )

        ]
    )]
    #[OA\Tag(name: "user")]
    public function index()
    {
    }

    #[Route('/api/v1/register', name: 'app_api_register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/register',description: 'Регистрация пользователя'
    )]
    #[OA\RequestBody(
        request: true, description: 'JSON payload', required: true,
        content: [
            new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "username",
                        description: "Email",
                        type: "string",
                        example: "user@user.com"
                    ),
                    new OA\Property(
                        property: "password",
                        description: "Пароль",
                        type: "string",
                        example: "useruser"
                    ),
                ]
            ),
        ]
    )]
    #[OA\Response(
        response: 201,
        description: 'Success',
        content: [
            new OA\JsonContent(
                properties: [
                    new OA\Property(property: "token", type:"string", example: '123123123123123'),
                    new OA\Property(property: "refreshToken", type:"string", example: '123123123123123'),
                    new OA\Property(property: "roles", type: "string", example: ['ROLE']),
                ]
            )


        ]
    )]
    #[OA\Response(
        response: 400,
        description: 'Error',
        content: [
            new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",example: 'error message'
                    ),
                ]
            )

        ]
    )]
    #[OA\Tag(name: "user")]
    public function register(Request $request,
                             EntityManagerInterface $entityManager,
                             JWTTokenManagerInterface $tokenManager,
                             UserPasswordHasherInterface $hasher,
                             RefreshTokenGeneratorInterface $refreshTokenGenerator,
                             RefreshTokenManagerInterface $refreshTokenManager
    ): JsonResponse
    {
        $userDto = $this->serializer->deserialize(
            $request->getContent(),
            UserDto::class,
            'json'
        );
        $errors = $this->validator->validate($userDto);

        $userByEmail = $entityManager->getRepository(User::class)->findBy(['email' => $userDto->username]);

        if ($userByEmail) {
            return $this->json(['error' => 'Пользователь с таким email уже существует'], Response::HTTP_BAD_REQUEST);
        }

        if (count($errors) > 0)
        {
            return $this->json(['error' => $errors->get(0)->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $user = User::fromDto($userDto);
        $user->setPassword($hasher->hashPassword($user, $userDto->password));
        $user->setBalance($_ENV['BALANCE']);
        $entityManager->persist($user);
        $entityManager->flush();

        $token = $tokenManager->create($user);


        $refreshToken = $refreshTokenGenerator->createForUserWithTtl(
            $user,
            (new \DateTime())->modify('+1 month')->getTimestamp()
        );
        $refreshTokenManager->save($refreshToken);
        return $this->json([
            'token' => $token,
            'roles' => $user->getRoles(),
            'refreshToken' => $refreshToken->getRefreshToken()
        ], Response::HTTP_CREATED);
    }

    /**
     * @throws JWTDecodeFailureException
     * @Security(name="Bearer")
     */
    #[Route('/api/v1/users/current', name: 'app_api_current_user', methods: ['POST'])]
    #[OA\Tag(name: "user")]
    #[Security(name: "Bearer")]
    #[OA\Post(
        path: '/api/v1/users/current',description: 'Информация о пользователе'
    )]
    #[OA\Response(
        response: 201,
        description: 'Success',
        content: [
            new OA\JsonContent(
                properties: [
                    new OA\Property(property: "username", type:"string", example: 'user@user.com'),
                    new OA\Property(property: "roles", type: "string", example: ['ROLE']),
                    new OA\Property(property: "balance", type:"string", example: '152.2'),
                ]
            )


        ]
    )]
    #[OA\Response(
        response: 401,
        description: 'Error',
        content: [
            new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",example: 'Expired JWT token'
                    ),
                ]
            )

        ]
    )]

    public function currentUser(): JsonResponse
    {

        $user = $this->getUser();
        return $this->json(
            ["code" => Response::HTTP_OK, "username" => $user->getEmail(), "roles" => $user->getRoles(), "balance" => $user->getBalance()]
        );
        //$jwt = (array)JWTManager::decode($token);
    }
}

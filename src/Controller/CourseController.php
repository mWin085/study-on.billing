<?php

namespace App\Controller;

use App\Dto\CourseDto;
use App\Entity\Course;
use App\Repository\CourseRepository;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CourseController extends AbstractController
{
    private CourseRepository $courseRepository;

    private PaymentService $paymentService;
    private $serializer;
    private $validator;


    public function __construct(
        CourseRepository $courseRepository,
        PaymentService $paymentService,
        ValidatorInterface $validator
    )
    {
        $this->courseRepository = $courseRepository;
        $this->paymentService = $paymentService;
        $this->serializer = SerializerBuilder::create()->build();
        $this->validator = $validator;
    }

    #[Route('/api/v1/courses', name: 'app_api_courses', methods: ['GET'])]

    #[OA\Tag(name: "courses")]
    #[OA\Get(
        path: '/api/v1/courses',description: 'Список курсов'
    )]

    #[OA\Response(
        response: 201,
        description: 'Success',
        content: [
            new OA\JsonContent(
                type: 'array',
                items: new OA\Items(
                    properties: [
                        new OA\Property(property: "code", type:"string", example: 'course_3'),
                        new OA\Property(property: "type", type: "string", example: 'buy'),
                        new OA\Property(property: "price", type:"number", example: 999),
                    ]
                ),

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
    public function courses(Request $request)
    {
        $courses = $this->courseRepository->findAll();
        $result = [];
        foreach ($courses as $course) {
            $data = [
                'code' => $course->getCode(),
                'type' => CourseRepository::COURSE_TYPES[$course->getType()],
            ];
            if ($course->getPrice()){
                $data['price'] = $course->getPrice();
            }
            $result[] = $data;
        }
        return $this->json($result);
    }


    #[Route('/api/v1/courses/{code}', name: 'app_api_course', methods: ['GET'])]

    #[OA\Tag(name: "courses")]
    #[OA\Get(
        path: '/api/v1/courses/{code}',description: 'Информация о курсе'
    )]

    #[OA\Response(
        response: 201,
        description: 'Success',
        content: [
            new OA\JsonContent(
                properties: [
                    new OA\Property(property: "code", type:"string", example: 'course_code'),
                    new OA\Property(property: "type", type: "string", example: 'rent'),
                    new OA\Property(property: "price", type:"number", example: 199),
                ]
            )


        ]
    )]
    #[OA\Response(
        response: 404,
        description: 'Error',
        content: [
            new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",example: 'Courses not found'
                    ),
                ]
            )

        ]
    )]
    public function courseByCode(string $code)
    {
        $course = $this->courseRepository->findOneBy(['code' => $code]);

        if (!$course) {
            return $this->json(['code' => Response::HTTP_NOT_FOUND, 'error' => 'Courses not found'], Response::HTTP_NOT_FOUND);
        }
        $result = [
            'code' => $course->getCode(),
            'type' => CourseRepository::COURSE_TYPES[$course->getType()],
        ];
        if ($course->getPrice()){
            $result['price'] = $course->getPrice();
        }
        return $this->json($result);
    }


    #[Route('/api/v1/courses/{code}/pay', name: 'app_api_course_pay', methods: ['GET'])]
    #[Security(name: "Bearer")]

    #[OA\Tag(name: "courses")]
    #[OA\Get(
        path: '/api/v1/courses/{code}/pay',description: 'Покупка курса'
    )]

    #[OA\Response(
        response: 201,
        description: 'Success',
        content: [
            new OA\JsonContent(
                type: 'array',
                items: new OA\Items(
                    properties: [
                        new OA\Property(property: "code", type:"string", example: 'course_3'),
                        new OA\Property(property: "type", type: "string", example: 'buy'),
                        new OA\Property(property: "price", type:"number", example: 999),
                    ]
                ),
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
    #[OA\Response(
        response: 406,
        description: 'Error',
        content: [
            new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",example: 'На вашем счету недостаточно средств'
                    ),
                ]
            )

        ]
    )]

    public function pay(string $code)
    {
        $user = $this->getUser();
        $course = $this->courseRepository->findOneBy(['code' => $code]);
        if (!$course) {
            return $this->json(['code' => Response::HTTP_NOT_FOUND, 'error' => 'Courses not found'], Response::HTTP_NOT_FOUND);
        }

        if (CourseRepository::COURSE_TYPES[$course->getType()] == 'free' ){
            return $this->json(['code' => Response::HTTP_NOT_FOUND, 'error' => 'Courses not found'], Response::HTTP_NOT_FOUND);
        }

        if ($course->getPrice() > $user->getBalance()) {
            return $this->json(
                ['code' => Response::HTTP_NOT_ACCEPTABLE, 'error' => 'На вашем счету недостаточно средств'],
                Response::HTTP_NOT_ACCEPTABLE
            );
        }

        return $this->json($this->paymentService->payCourse($course, $user), Response::HTTP_CREATED);
    }

    #[Route('/api/v1/courses', name: 'app_add_course', methods: ['POST'])]
    #[Security(name: "Bearer")]
    #[IsGranted('ROLE_SUPER_ADMIN')]

    #[OA\Tag(name: "courses")]
    #[OA\Post(
        path: '/api/v1/courses',description: 'Добавление курса'
    )]

    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "code", type:"string", example: 'course_3'),
                new OA\Property(property: "title", type: "string", example: 'Великие географические открытия и их последствия'),
                new OA\Property(property: "type", type: "string", example: 'rent'),
                new OA\Property(property: "amount", type:"number", example: 999),
            ]
        )
    )]

    #[OA\Response(
        response: 201,
        description: 'Success',
        content: [
            new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "success",
                        type: "boolean",example: true
                    ),
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
    #[OA\Response(
        response: 400,
        description: 'Error',
        content: [
            new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",example: 'Курс с таким кодом уже существует'
                    ),
                    new OA\Property(
                        property: "success",
                        type: "string",example: false
                    ),
                ]
            )

        ]
    )]
    public function add(Request $request, EntityManagerInterface $entityManager){

        $courseDto = $this->serializer->deserialize(
            $request->getContent(),
            CourseDto::class,
            'json'
        );

        $errors = $this->validator->validate($courseDto);

        if (count($errors) > 0)
        {
            return $this->json(['success' => false, 'error' => $errors->get(0)->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $courseByCode = $entityManager->getRepository(Course::class)->findBy(['code' => $courseDto->code]);

        if ($courseByCode) {
            return $this->json(['success' => false, 'error' => 'Курс с таким кодом уже существует'], Response::HTTP_BAD_REQUEST);
        }

        if ($courseDto->type !== 'free' && $courseDto->price == null){
            return $this->json(['success' => false, 'error' => 'Не указана цена курса'], Response::HTTP_BAD_REQUEST);
        }
        if ($courseDto->type == 'free'){
            $courseDto->price = null;
        }


        $course = Course::fromDto($courseDto);
        $entityManager->persist($course);
        $entityManager->flush();

        return $this->json([
            'success' => true
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/v1/courses/{code}', name: 'app_edit_course', methods: ['POST'])]
    #[Security(name: "Bearer")]
    #[IsGranted('ROLE_SUPER_ADMIN')]

    #[OA\Tag(name: "courses")]
    #[OA\Post(
        path: '/api/v1/courses/{code}',description: 'Добавление курса'
    )]

    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "code", type:"string", example: 'course_3'),
                new OA\Property(property: "title", type: "string", example: 'Великие географические открытия и их последствия'),
                new OA\Property(property: "type", type: "string", example: 'rent'),
                new OA\Property(property: "amount", type:"number", example: 999),
            ]
        )
    )]

    #[OA\Response(
        response: 201,
        description: 'Success',
        content: [
            new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "success",
                        type: "boolean",example: true
                    ),
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
    #[OA\Response(
        response: 400,
        description: 'Error',
        content: [
            new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",example: 'Курса с таким кодом не существует'
                    ),
                    new OA\Property(
                        property: "success",
                        type: "string",example: false
                    ),
                ]
            )

        ]
    )]


    public function edit(Request $request, EntityManagerInterface $entityManager){

        $courseDto = $this->serializer->deserialize(
            $request->getContent(),
            CourseDto::class,
            'json'
        );

        $errors = $this->validator->validate($courseDto);

        if (count($errors) > 0)
        {
            return $this->json(['success' => false, 'error' => $errors->get(0)->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $course = $entityManager->getRepository(Course::class)->findBy(['code' => $courseDto->code])[0];

        if (!$course) {
            return $this->json(['success' => false, 'error' => 'Курса с таким кодом не существует'], Response::HTTP_BAD_REQUEST);
        }

        if ($courseDto->type !== 'free' && $courseDto->price == null){
            return $this->json(['success' => false, 'error' => 'Не указана цена курса'], Response::HTTP_BAD_REQUEST);
        }
        if ($courseDto->type == 'free'){
            $course->setPrice(null);
        } else {
            $course->setPrice($courseDto->price);
        }

        $course->setCode($courseDto->code);
        $course->setType(array_flip(CourseRepository::COURSE_TYPES)[$courseDto->type]);
        $course->setTitle($courseDto->title);

        $entityManager->flush();

        return $this->json([
            'success' => true
        ], Response::HTTP_OK);

    }

}

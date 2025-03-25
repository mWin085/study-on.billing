<?php

namespace App\Controller;

use App\Repository\CourseRepository;
use App\Repository\TransactionRepository;
use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;


class TransactionsController extends AbstractController{

    private TransactionRepository $transactionRepository;

    private PaymentService $paymentService;

    public function __construct(
        PaymentService $paymentService,
        TransactionRepository $transactionRepository,
    )
    {
        $this->paymentService = $paymentService;
        $this->transactionRepository = $transactionRepository;
    }
    #[Route('/api/v1/transactions', name: 'app_api_transactions', methods: ['GET'])]
    #[Security(name: "Bearer")]

    #[OA\Tag(name: "transactions")]
    #[OA\Get(
        path: '/api/v1/transactions',description: 'Список транзакций'
    )]

    #[OA\Parameter(
        name: 'course_code',
        description: 'Фильтр по коду курса',
        in: 'query',
        schema: new OA\Schema(type: 'string'),
        example: 'course_3'
    )]
    #[OA\Parameter(
        name: 'type',
        description: 'Фильтр по типу транзакции',
        in: 'query',
        schema: new OA\Schema(type: 'string'),
        example: 'payment'
    )]
    #[OA\Parameter(
        name: 'skip_expired',
        description: 'Фильтр по активным оплатам',
        in: 'query',
        schema: new OA\Schema(type: 'boolean'),
        example: true
    )]

    #[OA\Response(
        response: 201,
        description: 'Success',
        content: [
            new OA\JsonContent(
                type: 'array',
                items: new OA\Items(
                    properties: [
                        new OA\Property(property: "id", type:"number", example: '1'),
                        new OA\Property(property: "code", type:"string", example: 'course_3'),
                        new OA\Property(property: "type", type: "string", example: 'payment'),
                        new OA\Property(property: "amount", type:"number", example: 999),
                        new OA\Property(property: "createdAt", type: "string", example: '2025-03-10T08:06:44+00:00'),
                        new OA\Property(property: "expires_at", type: "string", example: '2025-04-10T08:06:44+00:00'),
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
    public function transactions(Request $request){
        $reqData = $request->query->all();
        $result = [];
        if ($user = $this->getUser()){
            $transactions = $this->transactionRepository->findByFilter($user, $reqData);
            foreach ($transactions as $transaction) {
                $data = [
                    'id' => $transaction->getId(),
                    'code' => ($transaction->getCourse()) ? $transaction->getCourse()->getCode() : null,
                    'type' => TransactionRepository::TRANSACTION_NAMES[$transaction->getType()],
                    'amount' => $transaction->getValue(),
                    'createdAt' => $transaction->getCreatedAt()->format('c'),
                ];
                if ($transaction->getCourse() && CourseRepository::COURSE_TYPES[$transaction->getCourse()->getType()] == 'rent'){
                    $data['expires_at'] = $transaction->getValidAt()->format('c');
                }
                $result[] = $data;
            }
        }

        return $this->json($result);
    }

    /*#[Route('/api/v1/deposit', name: 'app_api_deposit', methods: ['POST'])]
    #[Security(name: "Bearer")]
    public function deposit(Request $request)
    {
        $amount = (float)$request->request->get('amount');
        return $this->json($this->paymentService->deposit($amount, $this->getUser()));
    }*/
}
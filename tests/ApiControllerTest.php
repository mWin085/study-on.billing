<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiControllerTest extends WebTestCase
{

    private string $userName;
    private string $password;
    private string $wrongPassword;

    protected function setUp(): void
    {
        $this->userName = "usertest123123123@user.com";
        $this->password = "testtest";
        $this->wrongPassword = "test";
    }

    public function testRegister(){
        $client = static::createClient();

        //Некорректный пароль
        $client->jsonRequest('POST', '/api/v1/register', [
            'username' => $this->userName,
            'password' => $this->wrongPassword,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        //Успешное создание пользователя
        $crawler = $client->jsonRequest('POST', '/api/v1/register', [
            'username' => $this->userName,
            'password' => $this->password,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $userRepository = $client->getContainer()->get('doctrine')->getManager()->getRepository(User::class);
        $userByEmail = $userRepository->findOneBy(['email' => $this->userName]);
        $this->assertNotNull($userByEmail);

        $token = json_decode($client->getResponse()->getContent(), true)["token"];
        $this->assertNotNull($token);

        //Регистрация пользователя с занятым email
        $client->jsonRequest('POST', '/api/v1/register', [
            'username' => $this->userName,
            'password' => $this->password,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testAuth(){
        $client = static::createClient();

        //Некорректные данные
        $crawler = $client->jsonRequest('POST', '/api/v1/auth', [
            'username' => $this->userName,
            'password' => $this->wrongPassword,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        //Корректные данные
        $crawler = $client->jsonRequest('POST', '/api/v1/auth', [
            'username' => $this->userName,
            'password' => $this->password,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $token = json_decode($client->getResponse()->getContent(), true)["token"];
        $this->assertNotNull($token);
    }

    public function testCurrentUser(){
        $client = static::createClient();

        //Некорректный токен
        $crawler = $client->request('POST', '/api/v1/users/current');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);


        //Корректный токен
        $crawler = $client->jsonRequest('POST', '/api/v1/auth', [
            'username' => $this->userName,
            'password' => $this->password,
        ]);
        $token = json_decode($client->getResponse()->getContent(), true)["token"];

        $crawler = $client->request('POST', '/api/v1/users/current', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '. $token]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertNotNull($responseData['username']);
        $this->assertNotNull($responseData['roles']);
        $this->assertNotNull($responseData['balance']);

    }
}

<?php
namespace App\Dto;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

class UserDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $username,

        #[Assert\NotBlank]
        #[Assert\Length(min: 6)]
        public readonly string $password,
    ) {
    }
}
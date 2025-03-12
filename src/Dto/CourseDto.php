<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CourseDto{

    #[Assert\NotBlank(message: "Не передано поле символьного кода")]
    #[Assert\Length(max: 255)]
    public string $code;

    #[Assert\NotBlank(message: "Не передан тип курса")]
    public string $type;


    public ?float $price = null;

    #[Assert\NotBlank(message: "Не передано поле названия курса")]
    #[Assert\Length(max: 255)]

    public string $title;
}
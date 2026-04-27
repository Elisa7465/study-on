<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterDto
{
    #[Assert\NotBlank(message: 'Введите email')]
    #[Assert\Email(message: 'Введите корректный email')]
    private ?string $email = null;

    #[Assert\NotBlank(message: 'Введите пароль')]
    #[Assert\Length(
        min: 6,
        minMessage: 'Пароль должен быть не короче {{ limit }} символов'
    )]
    private ?string $password = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }
}
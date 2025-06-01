<?php

namespace App\Project;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Data extends AbstractController
{
    private SessionInterface $session;

    /** @var array<string, mixed> */
    private array $state = [];

    private const SESSION_KEY = 'game_state';

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
        $this->load();
    }

    public function set(string $key, mixed $value): void
    {
        $this->state[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->state[$key] ?? $default;
    }

    public function save(): void
    {
        $this->session->set(self::SESSION_KEY, $this->state);
    }

    public function load(): void
    {
        $loadedState = $this->session->get(self::SESSION_KEY, []);
        $this->state = is_array($loadedState) ? $loadedState : [];
    }

    public function reset(): void
    {
        $this->state = [];
        $this->save();
    }

    /** @return array<string, mixed> */
    public function getAll(): array
    {
        return $this->state;
    }
}
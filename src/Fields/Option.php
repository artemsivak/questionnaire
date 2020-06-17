<?php
declare(strict_types = 1);
namespace Questionner\Fields;

class Option
{
    private $label;
    private $value;
    private $score;

    public function __construct(string $label, string $value, ?array $score)
    {
        $this->label = $label;
        $this->value = $value;
        if (isset($score)) {
            $this->score = new Score($score);
        }
    }

    public function label(): string
    {
        return $this->label;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function score(): ?Score
    {
        return $this->score;
    }
}

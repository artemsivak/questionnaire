<?php
declare(strict_types = 1);
namespace Questionner\Fields;

class Score
{
    private $ascore;
    private $aml;
    private $tmscore;
    private $cscore;

    public function __construct(array $score)
    {
        $this->ascore = floatval($score["ascore"]);
        $this->aml = floatval($score["aml"]);
        $this->tmscore = floatval($score["tmscore"]);
        $this->cscore = (isset($score["cscore"])) ? floatval($score["cscore"]) : 0;
    }

    public function ascore(): float
    {
        return $this->ascore;
    }

    public function aml(): float
    {
        return $this->aml;
    }

    public function tmscore(): float
    {
        return $this->tmscore;
    }

    public function cscore(): float
    {
        return $this->cscore;
    }
}

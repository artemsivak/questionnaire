<?php
declare(strict_types = 1);
namespace Questionner\CRM;

class Values
{
    protected $ascore;
    protected $tmscore;
    protected $cscore;
    protected $aml;
    protected $country;
    protected $citizen;
    protected $accId;

    public function __construct(object $response)
    {
        $response = $response->GetAccountDetailsResult->AccountsInfo->AccountInfo;
        $atts = $response->AdditionalAttributes->DynamicAttributeInfo;
        $this->accId = $response->Id;
        foreach ($atts as $key => $value) {
            if ($value->Name === ASCORE) {
                $this->ascore = floatval($value->Value);
            } elseif ($value->Name === TMSCORE) {
                $this->tmscore = floatval($value->Value);
            } elseif ($value->Name === CSCORE) {
                $this->cscore = floatval($value->Value);
            } elseif ($value->Name === ECONOMICRISK) {
                $this->aml = floatval($value->Value);
            }
        }

        $this->country = $response->Country;
        $this->citizen = $response->CountryOfCitizenship;
    }   

}
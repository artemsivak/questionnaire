<?php
declare(strict_types = 1);
namespace Questionner\Fields;

class Field
{
    private $label;
    private $name;
    private $values;
    private $crmType;
    private $type;
    private $required;
    private $reqStar;
    private $width;
    private $labelPosition;
    private $labelExplanation;
    private $condition;

    public function __construct(array $field, ?array $selected = null)
    {
    //    echo "<pre>",print_r($field),"</pre>";
        $this->label = $field["label"];
        $this->name = $field["crm_name"];
        $this->crmType = $field["crm_type"];
        $this->type = $field["type"];
        $this->required = intval($field["required"]);
        $this->reqStar = $this->requiredStar($field["required"]);
        $this->width = intval($field["width"]);
        $this->generateValue($field["value"], $selected);
        $this->setLabelPosition($field);
        $this->condition = $field["conditions_"]["condition_"];
        $this->labelExplanation = $field["conditions_"]["explanation"];
    }

    public static function postField(int $stageId, string $name, array $selected): Field
    {
        $fields = get_field("field", $stageId);
        $key = array_search($name, array_column($fields, "crm_name"), true);
        return new Field($fields[$key], $selected);
    }

    public function label(): string
    {
        return $this->label.$this->reqStar;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function required(): int
    {
        return $this->required;
    }

    public function width(): int
    {
        return $this->width;
    }

    public function values(): array
    {
        return $this->values;
    }

    public function crmType(): string
    {
        return $this->crmType;
    }

    public function postValue()
    {
        if (is_array($this->values)) {
            $this->values = $this->values[0];
        }

        if ($this->crmType === "bool") {
            return ($this->values->value() === "1") ? "true" : "";
        } elseif ($this->crmType !== "bool" and $this->crmType !== "pickList") {
            return $this->values->value();
        } elseif ($this->crmType === "pickList") {
            return intval($this->values->value()) + 1;
        }
    }

    public function postScores(): ?Score
    {
        if (is_array($this->values)) {
            $this->values = $this->values[0];
        }
        return $this->values->score();
    }

    public function labelPosition(): string
    {
        return $this->labelPosition;
    }

    public function condition(): string
    {
        return $this->condition;
    }

    public function labelExplanation(): string
    {
        return $this->labelExplanation;
    }

    protected function generateValue(array $allValues, ?array $selected)
    {
        $withOptions = ["select", "radio", "checkbox"];
        $simpleInput = ["text", "date", "email", "hidden", "password", "number"];
        $informational = ["information", "note"];

        if (in_array($this->type, $withOptions, true)) {
            $value = $this->generateOptions($allValues, $selected);
            $this->values = $value;
        } elseif (isset($selected) and in_array($this->type, $simpleInput, true)) {
            $this->values = new Option($this->label, $selected[0], null);
        } elseif (in_array($this->type, $informational, true)) {
            $subHeader = $allValues["information"]["sub_header"];
            $text = $allValues["information"]["text"];
            $this->values[0] = new Option($subHeader, $text, null);
        }
    }

    private function generateOptions(array $allValues, ?array $selected): array
    {
        $thisVal = $allValues[$this->type];
        $options = "";
        switch ($thisVal["define"]) {
            case "custom":
                $options = $this->customOptions($thisVal["options"], $selected);
                break;
            case "yesNo":
                $options = $this->yesNoOptions($thisVal, $selected);
                break;
            case "countryId":
                $options = $this->countriesList($thisVal, "country_long_id", $selected);
                break;
            case "countryName":
                $options = $this->countriesList($thisVal, "country_name", $selected);
                break;
            case "agree":
                $options = $this->agreeOption($thisVal["agree"]);
                break;
        }
        return $options;
    }

    private function customOptions(array $custom, ?array $selected): array
    {
        $allOptions = (isset($selected)) ? $selected : $custom;
        $filteredOptions = [];

        foreach ($allOptions as $key => $option) {
            $value = (isset($selected)) ? $custom[$option] : $option;
            $key = (isset($selected)) ? $option : $key;
            $filteredOptions[] = new Option($value["option"], "$key", $value["score"]);
        }

        return $filteredOptions;
    }

    private function yesNoOptions(array $yesNo, ?array $selected): array
    {
        $options = [];
        $yes = new Option("Yes", "1", $yesNo["yes_weight"]);
        $no = new Option("No", "0", $yesNo["no_weight"]);

        if (isset($selected)) {
            $options[] = ($selected[0] === "0") ? $no : $yes;
        } elseif (!isset($selected)) {
            $options[] = $yes;
            $options[] = $no;
        }

        return $options;
    }

    private function countriesList(array $thisVal, string $return, ?array $selected): array
    {
        $options = [];
        global $wpdb;
        $sql = "SELECT * FROM `".$wpdb->prefix."leverate_country_list_codes`";
        $countries = $wpdb->get_results($sql);
        foreach ($countries as $key => $country) {
            if (isset($thisVal["accepted_countires"][0])) {
                $accepted = $this->acceptedCountries("countriesOnly");
                if (!in_array($country->country_name, $accepted, true)) {
                    continue;
                }
                $score = $this->acceptedCountries()[$country->country_name];
                $score = [
                    "ascore" => 0,
                    "aml" => 0,
                    "tmscore" => 0,
                    "cscore" => $score
                ];
            } elseif (!isset($thisVal["accepted_countires"][0])) {
                $score = null;
            }

            $option = new Option($country->country_name, $country->$return, $score);
            if (isset($selected) and $selected[0] === $country->$return) {
                $options[] = $option;
                break;
            } elseif (!isset($selected)) {
                $options[] = $option;
            }
        }
        return $options;
    }

    private function agreeOption(array $agreeValue): array
    {
        $options = [];
        $options[] = new Option($agreeValue["agreement_text"], "1", $agreeValue["score"]);
        return $options;
    }

    private function setLabelPosition(array $field)
    {
        if ($this->type === "select") {
            $this->labelPosition = $field["value"]["select"]["label_position"];
        }
    }

    private function requiredStar(string $required): ?string
    {
        return ($required === "1" and !empty($this->label)) ? " *" : null;
    }

    private function acceptedCountries(?string $return = null): array
    {
        $accepted = [
            "Austria" => 39.6,
            "Bulgaria" => 41.1,
            "Croatia" => 40.5,
            "Cyprus" => 40.7,
            "Czech Republic" => 40.6,
            "Denmark" => 35.8,
            "Estonia" => 36.7,
            "Finland" => 33.2,
            "France" => 33.2,
            "Germany" => 39.0,
            "Greece" => 43.8,
            "Hungary" => 40.4,
            "Iceland" => 37.3,
            "Ireland" => 37.8,
            "Italy" => 43.1,
            "Latvia" => 41.4,
            "Liechtenstein" => 51.8,
            "Lithuania" => 38.6,
            "Luxembourg" => 39.8,
            "Malta" => 39.8,
            "Netherlands" => 37.6,
            "Poland" => 38.9,
            "Portugal" => 39.0,
            "Romania" => 41.8,
            "Slovakia" => 41.3,
            "Slovenia" => 38.3,
            "Spain" => 41.1,
            "Sweden" => 35.7,
        ];
        $countries = [];
        foreach ($accepted as $key => $value) {
            $countries[] = $key;
        }
        return ($return === "countriesOnly") ? $countries : $accepted;
    }
}

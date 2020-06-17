<?php
declare(strict_types = 1);
namespace Questionner\Constructor;

use Questionner\Fields\Field;
use Questionner\Fields\RegisterFields;
use Questionner\CRM\Integrate;

class Constructor extends RegisterFields
{
    public static function thisStage(): int
    {
        if (!isset($_POST["stage"])) {
            $stage = 2;
            unset($_SESSION["stages"]);
        }else{
            $stage = intval($_POST["stage"]);
        }
        return $stage;
    }

    public static function stage(int $stageNum, ?string $next = null)
    {
        $register = new RegisterFields();
        $stages = $register->generateStages();
        $stageId = $stages[$stageNum];
        $fields = $register->generateFields($stageId);
        new Constructor($fields, $stageId, $stageNum, $next);
    }
 
    public function __construct(array $fields, int $stagesId, int $stageNum, ?string $next)
    {
        $this->addStyleScripts();
        if (isset($_POST["stage"]) and $next !== "next") {
            new Integrate($_POST, $stagesId);
        } elseif(!isset($_POST["stage"]) or $next === "next") {
            $header = get_field("header", $stagesId);
            $buttonText = get_field("button_text", $stagesId);
            ?>
            <div id="ConstructorBlock">
            <div class="borderLine"><div></div></div>
            <h1><?= esc_html($header) ?></h1>
            <form action="" method="post">
            <input type="hidden" name="stage" value="<?= esc_attr($stageNum) ?>">
            <?php
            foreach ($fields as $field) {
                $simpleInput = ["text", "date", "email", "hidden", "number"];
                $type = $field->type();
                ?>
                <div 
                    class="field-group type_<?= esc_attr($type) ?>" 
                    style="width: <?= esc_attr($field->width()) ?>%"
                    <?= $this->checkCondition($field) ?>
                >
                <?php
                if (in_array($type, $simpleInput, true)) {
                    $this->inputType($field);
                } elseif ($type === "select") {
                    $this->selectType($field);
                } elseif ($type === "radio" || $type === "checkbox") {
                    $this->radioType($field);
                } elseif ($type === "information" || $type === "note") {
                    $this->informationType($field);
                }
                ?>
                </div>
                <?php
                if ($type === "password") {
                    $this->passwordType();
                } elseif ($type === "captcha") {
                    $this->captchaType();
                } elseif ($type === "groupName") {
                    $this->groupNameType();
                }
            }
            $this->submitButton($buttonText);
            ?>
            </form>
            </div>
            <?php
        }
      //  echo "<pre>",print_r($fields),"</pre>";
    }

    private function isRequired(Field $field): string
    {
        return ($field->required() === 1) ? "required" : "";
    }

    private function inputType(Field $field)
    {
        ?>
        <input 
            type='<?= esc_attr($field->type()) ?>' 
            name='<?= esc_attr($field->name()) ?>' 
            id='<?= esc_attr($field->name()) ?>' 
            placeholder='<?= esc_attr($field->label()) ?>' 
            <?= esc_attr($this->isRequired($field)) ?>
        >
        <?php
    }

    private function selectType(Field $field)
    {
        if ($field->labelPosition() == "top") {
            $firstOption = "Select";
            ?>
            <label for="<?= esc_attr($field->name()) ?>">
                <?= esc_html($field->label()) ?>
            </label>
            <?php
        } elseif ($field->labelPosition() == "inside") {
            $firstOption = $field->label();
        }
        ?>
        <select 
            name='<?= esc_attr($field->name()) ?>' 
            id='<?= esc_attr($field->name()) ?>' 
            <?= esc_attr($this->isRequired($field)) ?>
        >
            <option selected='' disabled='' value=''><?= esc_html($firstOption) ?></option>
        <?php
        foreach ($field->values() as $option) {
            ?>
            <option value="<?= esc_attr($option->value()) ?>">
                <?= esc_html($option->label()) ?>
            </option>
            <?php
        }
        ?>
        </select>
        <?php
    }

    private function radioType(Field $field)
    {
        $hasExplanation = (!empty($field->labelExplanation())) ? "hasExpl" : "";
        $allowI = ['i' => [], 'a' => ['href' => [], 'target' => []]];
        if (!empty($field->label())) {
            ?>
            <div class="name <?= $hasExplanation ?>">
                <h4><?= wp_kses($field->label(), $allowI) ?></h4>
                <?php
                if (!empty($field->labelExplanation())) {
                    echo "<div>".esc_html($field->labelExplanation())."</div>";
                }
                ?>
            </div>
            <?php
        }
        ?>
        <div class="checkboxArea type_<?= esc_attr($field->type()) ?>">
        <?php
        foreach ($field->values() as $key => $option) {

            $type = $field->type();
            $radio = ($type === "radio") ? "form-check-radio" : "";
            $checkBox = ($type === "checkbox") ? "[]" : "";
            $allowA = ['a' => ['href' => [], 'target' => []]];
            ?>
            <div class="form-check <?= esc_attr($radio) ?>">
                <input
                    class="form-check-input"
                    type="<?= esc_attr($type) ?>" 
                    name="<?= esc_attr($field->name().$checkBox) ?>" 
                    class="<?= esc_attr($field->name()) ?>" 
                    value="<?= esc_attr($option->value()) ?>" 
                    id="<?= esc_attr($field->name().$key) ?>"
                    <?= esc_attr($this->isRequired($field)) ?>
                >
                <label for="<?= esc_attr($field->name().$key) ?>">
                <?php 
                if ($field->type() === "checkbox") {
                    echo '<i class="fa fa-check" aria-hidden="true"></i>';
                }
                    echo "<div>".wp_kses($option->label(), $allowA)."</div>";
                ?>
                </label>
            </div>
            <?php
        }
        ?>
        </div>
        <?php
    }

    private function informationType($field)
    {
        $header = $field->label();
        $subheader = $field->values()[0]->label();
        $text = $field->values()[0]->value();
        $info = '<i class="fa fa-info-circle" aria-hidden="true"></i>';

        echo ($field->type() === "note") ? $info : "";
        echo (!empty($header)) ? "<h2>".esc_html($header)."</h2>" : "";
        echo (!empty($subheader)) ? "<h3>".esc_html($subheader)."</h3>" : "";
        echo (!empty($text)) ? "<p>".esc_html($text)."</p>" : "";
    }

    private function passwordType()
    {
        ?>
        <div class="field-group password" style="width: 49%">
            <input 
                type="password" 
                name="Password" 
                id="password" 
                placeholder="Password *" 
                required="" 
                class=""
            >
        </div>
        <div class="field-group password" style="width: 49%">
            <input 
                type="password" 
                name="confirm_password" 
                id="confirm_password" 
                placeholder="Confirm Password *" 
                required="" 
                class=""
            >
        </div>
        <?php
    }

    private function captchaType()
    {
        ?>
        <!-- <div class="field-group captcha" style="width: 49%">
            <span>Captcha</span>
            <div class="showCaptha">
                <span>Show</span>
            </div>
        </div>
        <div class="field-group captcha" style="width: 49%">
            <input 
                type="text" 
                name="captcha" 
                id="captcha" 
                placeholder="Re-type the captcha *" 
                required="" 
                class=""
            >
        </div> -->
        <?php 
    }

    private function groupNameType()
    {
        global $wpdb;
        $tradingPlatform = Integrate::currentPlatform()."REAL";
        $sql = "SELECT * FROM `".$wpdb->prefix."leverate_groups` 
                WHERE `platformName` LIKE '".$tradingPlatform."' 
                AND `defaultGroup` = 1";
        $result = $wpdb->get_results($sql);
        ?>
        <div class="field-group type_select" style="width: 100%">
            <select name="groupname" id="groupname" required="">
                <option selected="" disabled="" value="">Currency *</option>
                <?php 
                foreach ($result as $group) {
                    ?>
                    <option value='<?= esc_attr($group->groupcurrency) ?>'>
                    <?= esc_html($group->groupcurrency) ?>
                    </option>
                    <?php
                }
                ?>
            </select>
        </div>
        <?php
    }

    private function submitButton(string $buttonText)
    {
        ?>
        <div class="submitArea">
            <input type="submit" value="<?= esc_attr($buttonText) ?>">
        </div>
        <?php
    }

    private function checkCondition(Field $field)
    {
        if (!empty($field->condition())) {
            echo "data-cond='1' data-condition='".esc_attr($field->condition())."'";
        }
    }

    private function addStyleScripts()
    {
        ?>
        <link rel="stylesheet" href="<?= plugins_url('questionner') ?>/assets/css/all.css">
        <script src="<?= plugins_url('questionner') ?>/assets/js/scripts.js"/></script>
        <?php
    }
}

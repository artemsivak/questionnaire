<?php
declare(strict_types = 1);
namespace Questionner\Fields;

class RegisterFields
{
    protected function generateStages(): array
    {
        global $wpdb;
        $stagesId = [];
        $tableName = $wpdb->prefix."posts";
        $sql = "SELECT * FROM `$tableName` 
                WHERE `post_status` LIKE 'publish' 
                AND `post_type` LIKE 'questionner'";
        $stages = $wpdb->get_results($sql);
        $stages = $this->currentLangStages($stages);

        foreach ($stages as $stage) {
            $num = explode(" ", $stage->post_title)[0];
            $stagesId[$num] = intval($stage->ID);
        }

        return $stagesId;
    }

    private function currentLangStages(array $stages): array
    {
        $currentLang = apply_filters('wpml_current_language', null);
        global $wpdb;
        foreach ($stages as $key => $stage) {
            $sql = "SELECT * FROM `".$wpdb->prefix."icl_translations` WHERE `element_id` = $stage->ID";
            $stageLang = $wpdb->get_results($sql)[0]->language_code;
            if ($stageLang !== $currentLang) {
                unset($stages[$key]);
            }
        }
        return $stages;
    }

    protected function generateFields(int $stageId): array
    {
        $fields = get_field("field", $stageId);
        $stage = [];
        foreach ($fields as $field) {
            $stage[] = new Field($field);
        }
        return $stage;
    }
}

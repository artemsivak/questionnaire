<?php
declare( strict_types = 1 );
namespace Questionner;

use Questionner\Fields\RegisterFields;
use Questionner\Constructor\Constructor;
use Questionner\CRM\Integrate;

class SetUp
{

    public function __construct()
    {
        add_action('init', [$this, 'addPostType']);
        add_action('admin_head', [$this, 'acfStyling']);
        add_shortcode("register_questionnaire", [$this, 'firstStage']);
        add_shortcode("questionner", [$this, 'otherStages']);
        add_shortcode("allowToDeposit", [$this, 'allowToDeposit']);
    }

    public function firstStage()
    {
        Constructor::stage(1);
    }

    public function otherStages()
    {
        $stage = Constructor::thisStage();
        if ($stage == 5) {
            Integrate::UpgradeAccount();
        }else{
            Constructor::stage($stage);
        }
    }

    public function addPostType()
    {
        $args = [
            'labels' => [
                'name' => __('Questionner'),
                'singular_name' => __('Questionner'),
            ],
            'public' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => 'questionner'],
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-list-view',
        ];
        register_post_type('questionner', $args);
        remove_post_type_support('questionner', 'editor');
    }

    public function acfStyling()
    {
        ?>
        <link rel="stylesheet" href="<?= esc_url(plugins_url('questionner')) ?>/assets/css/cq_admin.css">
        <?php
    }

    public function allowToDeposit()
    {
        if (!Integrate::isUpgraded()) {
            ?>
            <div class="notAllowedDeposit">
                <h3>You can't deposit untill your account upgraded to Live.</h3>
                <a class="upgradeToLive" href="/upgrade-account">Upgrade account to Live</a>
            </div>
            <?php
            exit;
        }
    }
}

<?php
declare(strict_types = 1);
namespace Questionner\CRM;

use Questionner\Fields\Field;
use Questionner\Fields\Score;
use Questionner\Constructor\Constructor;

class Integrate extends Values
{
    private $postFields;
    private $allStages;

    public function __construct(array $post, int $stageId, ?int $upgrade = null) 
    {
        if (isset($upgrade)) {
            $this->allStages = $this->completeObjects($_SESSION["stages"]);
            $score = $this->completeObj($_SESSION["score"]);
            $this->upgradeToLive($score);
        }else{
            $stageNum = intval($post["stage"]);
            $postFields = [];
            foreach ($post as $name => $selected) {
                $passBy = ["stage", "confirm_password", "captcha", "groupname", "promo"];
                if (in_array($name, $passBy, true)) {
                    continue;
                }
                $selected = (!is_array($selected)) ? (array)$selected : $selected;
                $postFields[] = Field::postField($stageId, $name, $selected);
            }
            $this->postFields = $postFields;

            if ($stageNum === 1) {
                $this->registerAccount($post);
            }else{
                $this->nextStage($stageNum);
            }
        }
    }

    private function registerAccount(array $post)
    {
        $groupname = $this->getGroupByCurrency($post["groupname"], "REAL");
        $CrmRegistration = $this->CrmRegistration($groupname);
        if ($CrmRegistration->Result->Code === "Success") {
            $this->createWebSiteAccount($CrmRegistration, $post, $groupname);
            $this->RegisterDemoAccount($CrmRegistration->AccountId, $post);
            $this->startSession($groupname, $post);
        }
    }

    private function CrmRegistration(string $groupname)
    {
        $config = \AdminModel::Instance()->GetConfigVar();
        try {
            $platform = self::currentPlatform()."REAL";
            $request = new \realAccountRegistrationRequest();
            $request->TradingPlatformId = $config['tradingPlatforms'][$platform]['id'];
            $request->GroupName = $groupname;
            $request->EnvironmentInfo = new \EnvironmentInfo();
            $request->MarketingInfo = new \MarketingInfo();
            $da = [];

            foreach ($this->postFields as $field) {
                $crmName = $field->name();
                $value = $field->postValue();
                $type = $field->crmType();
                $model = \Model::Instance();

                if ($type === "built") {
                    $request->$crmName = $value;
                } elseif ($type !== "built" and $type !== "not") {
                    $da[] = $model->CreateDynamicAttribute($crmName, $type, $value);
                }
            }

            $score = $this->countAllScores(1);
            $score = $this->daScores($score);
            $da = array_merge($da, $score);
            $request->AdditionalAttributes = $da; 
            $registerRealAccount = new \RegisterRealAccount();
            $registerRealAccount->businessUnitName = $config['businessUnitName'];
            $registerRealAccount->ownerUserId = $config['ownerUserId'];
            $registerRealAccount->organizationName = $config['organization'];
            $registerRealAccount->realAccountRegistrationRequest = $request;
            $leverateCrm = self::getCrm();
            $response = $leverateCrm->RegisterRealAccount($registerRealAccount);
            $response = $response->RegisterRealAccountResult;

            return $response;

        } catch (\Throwable $err) {
            echo "<pre>",print_r($err),"</pre>";
        }
    }

    private function RegisterDemoAccount(string $accId, array $post)
    {
        $platform = $this->currentPlatform()."DEMO";
        $groupname = $this->getGroupByCurrency($post["groupname"], "DEMO");
        $config = \AdminModel::Instance()->GetConfigVar();
        
        try{
            $request = new \DemoAccountRegistrationRequest();
            $request->TradingPlatformId = $config['tradingPlatforms'][$platform]['id'];
            $request->GroupName = $groupname;
            $request->EnvironmentInfo = new \EnvironmentInfo();
            $request->MarketingInfo = new \MarketingInfo();
            $request->LoggedInAccountId = $accId;
            
            foreach ($this->postFields as $field) {
                $crmName = $field->name();
                $value = $field->postValue();
                $type = $field->crmType();
                $model = \Model::Instance();

                if ($type === "built") {
                    $request->$crmName = $value;
                }
            }

            $demo = new \RegisterDemoAccount();
            $demo->ownerUserId = $config['ownerUserId'];
            $demo->organizationName = $config['organization'];
            $demo->businessUnitName = $config['businessUnitName'];
            $demo->demoAccountRegistrationRequest = $request;
            $leverateCrm = self::getCrm();
            $response = $leverateCrm->RegisterDemoAccount($demo)->RegisterDemoAccountResult;
            $responseCode = $response->Result->Code;

            if ($responseCode === "Success") {
                $this->createTpWebsiteAccount($response, $post);
            }
            
        }catch (\Throwable $err) {
            echo "<pre>",print_r($err),"</pre>";
        }
    }

    private function createWebSiteAccount(object $Crm, array $post, string $groupName)
    {
        global $wpdb;
        $wpdb->insert($wpdb->prefix."leverate_users",
            [
                "firstname" 	=> $post["FirstName"],
                "lastname" 		=> $post["LastName"],
                "email" 		=> $post["Email"],
                "countryId"		=> $post["CountryId"],
                "countryCode" 	=> "",
                "phoneNumber" 	=> $post["PhoneNumber"],
                "password"		=> hash("sha256", $post["Password"]),
                "groupName" 	=> $groupName,
                "accountId"		=> $Crm->AccountId,
            ]
        );
       $this->createTpWebsiteAccount($Crm, $post);
    }

    private function createTpWebsiteAccount(object $Crm, array $post)
    {
        global $wpdb;

        $wpdb->insert($wpdb->prefix."leverate_tp_accounts",
        [
            "tp" 				=> $Crm->TradingPlatform,
            "tp_id" 			=> $Crm->TradingPlatformAccountId,
            "tp_name" 			=> $Crm->TradingPlatformAccountName,
            "tp_account_type" 	=> $Crm->TradingPlatformAccountType,
            "currencyCode" 		=> $Crm->BaseCurrency->Code,
            "currencyId" 		=> $Crm->BaseCurrency->Id,
            "currencyName" 		=> $Crm->BaseCurrency->Name,
            "currencySymbol" 	=> $Crm->BaseCurrency->Symbol,
            "email"				=> $post["Email"],
        ]
    );
    
    }

    private function startSession(string $groupName, array $post)
    {
        $_SESSION['user'] = [
            "firstName" => $post["FirstName"],
            "lastname"	=> $post["LastName"],
            "email" 	=> $post["Email"],
            "groupName" => $groupName,
            "password"	=> $post["Password"]
        ];
        \View::Instance()->LoginPageForVerifyUsers();
    }

    private static function getCrm()
    {
        $config = \AdminModel::Instance()->GetConfigVar();
        
        return new \LeverateCrm($config['wsdl'], array(
            'login' => $config['username'],
            'password' => $config['password'],
            'location' => $config['apiLocation'],
            'encoding'=>'UTF-8',
            'cache_wsdl' => $config['wsdlCache'],
            'trace' => false,
        ));
    }

    private function updateAccount()
    {
        echo "see";
    }

    private function nextStage(int $stage)
    {
        $_SESSION["stages"][$stage] = $this->postFields;
        $stage = $stage + 1;

        if ($stage === 5) {
            $this->allStages = $this->completeObjects($_SESSION["stages"]);
            $valuesFromCrm = $this->GetValuesFromCrm();
            $score = $this->countAllScores(5, $valuesFromCrm);
            $this->conditions($valuesFromCrm, $score);
        }

        Constructor::stage($stage, "next");
    }

    private function countAllScores(int $stageNum, ?Values $valuesFromCrm = null): Score
    {
        $ascore = 0;
        $aml = 0;
        $tmscore = 0;
        $cscore = 0;

        if ($stageNum === 1) {
            $this->allStages = [$this->postFields];
        }
        
        foreach ($this->allStages as $key => $stage) {
            foreach ($stage as $field) {
                $score = $field->postScores();
                if (!isset($score)) continue;
                $ascore = $ascore + $score->ascore();
                $aml = $aml + $score->aml();
                $tmscore = $tmscore + $score->tmscore();
                $cscore = $cscore + $score->cscore();
            }
        }

        if ($stageNum === 5) {
            $accepted = unserialize(ACCEPTED);
            $ascore = $ascore + $valuesFromCrm->ascore;
            $aml = $aml + $valuesFromCrm->aml;
            $tmscore = $tmscore + $valuesFromCrm->tmscore;
            $cscore = $cscore + $valuesFromCrm->cscore;

            if ($valuesFromCrm->country !== $valuesFromCrm->citizen) {
                $aml = $aml + 10;
            }
            if (!isset($accepted[$valuesFromCrm->citizen])) {
                $aml = $aml + 10;
            }
            $aml = $aml + $this->depositBigger();
        }

        $score = [
            "ascore" => $ascore,
            "aml" => $aml,
            "tmscore" => $tmscore,
            "cscore" => $cscore,
        ];

        return new Score($score);
    }

    private function daScores(Score $score): array
    {
        $model = \Model::Instance();
        $da = [];

        $AmlEconomicRisk = ($score->aml() * 50) / 100;
        $AmlCountryRisk = ($score->cscore() * 50) / 100;
        $AmlScore = $AmlEconomicRisk + $AmlCountryRisk;

        $da[] = $model->CreateDynamicAttribute(AML, "string", $AmlScore);
        $da[] = $model->CreateDynamicAttribute(ASCORE, "string", $score->ascore());
        $da[] = $model->CreateDynamicAttribute(TMSCORE, "string", $score->tmscore());
        $da[] = $model->CreateDynamicAttribute(CSCORE, "string", $score->cscore());
        $da[] = $model->CreateDynamicAttribute(ECONOMICRISK, "string", $score->aml());

        return $da;
    }

    private function getGroupByCurrency(string $currency, string $type): string
    {
        global $wpdb;
        $tradingPlatform = self::currentPlatform().$type;
        $sql = "SELECT * FROM `".$wpdb->prefix."leverate_groups` 
                WHERE `platformName` LIKE '".$tradingPlatform."' 
                AND `defaultGroup` = 1
                AND `groupcurrency` LIKE '".$currency."'";
        return $wpdb->get_results($sql)[0]->groupname;
    }

    public static function currentPlatform()
    {
        global $wpdb;
        $sql = "SELECT * FROM `".$wpdb->prefix."leverate_active_platforms` 
                WHERE `isActive` = 1";
        $result = $wpdb->get_results($sql);
        return $result[0]->platformName;
    }

    private function completeObjects(array $sessionStages): array
    {
        $allSages = [];
        foreach ($sessionStages as $stage => $values) {
            $fields = [];
            foreach ($values as $key => $field) {
                $fields[] = unserialize(serialize($field));
            }
            $allSages[$stage] = $fields;
        }
        return $allSages;
    }

    private function conditions(Values $valuesFromCrm, Score $score)
    {
        $_SESSION["score"] = $score;
        $ascore = $score->ascore();
        $country = $valuesFromCrm->country;

        if ($country !== "Spain" and $ascore > 13) {
            ?>
            <style>.field-group.type_information {display: none;}</style>
            <?php
        } elseif ($country !== "Spain" and $ascore < 14) {
            ?>
            <script>
                $(document).ready(function() {
                    var form = $("div#ConstructorBlock form");
                    form.prepend("<input type='hidden' name='lowScore' value='1'>");
                });
            </script>
            <?php
        } elseif ($country === "Spain" and $ascore > 13) {
            ?>
            <style>.field-group.type_information {display: none;}</style>
            <script>
                $(document).ready(function() {
                    var form = $("div#ConstructorBlock form");
                    var additional = "<div class='spanishAdd' style='width:100%'><h2>Please write to proceed:</h2><h3>I have read, understand and acknowledged all the above statements</h3><div class='field-group type_number' style='width: 100%'><input id='SpanishUnderstand' style='width: 100%;' type='text' name='understand' required='required'/></div></div>";
                    form.append(additional);
                    validateSpanish();
                });
            </script>
            <?php
           
            // continue
            // display i agree text
        } elseif ($country === "Spain" and $ascore < 14) {
            ?>
            <div class="popUpCoverDeal">
                <div>
                    <p>Coverdeal Holdings Ltd (the “Company”) would like to warn you and bring to your attention that, on the basis of the information provided to us by you, you do not seem to possess the appropriate experience and knowledge, as derived from the Law, to trade in the investment services (i.e. brokerage services) and financial instruments the Company offers (i.e. CFDs), which carry a high degree of risk. In this respect, the Company does not consider these investment service(s)/financial instrument(s) appropriate for you.</p>
                    <a class="gotit" href="/logout">Got it</a>
                </div>
            </div>
            <?php
            $this->removeAccountSpain();
            // rejected lead status
            // Delete account
           // exit;
        }
    }

    private function GetValuesFromCrm(): Values
    {
        $atts = [ASCORE, AML, TMSCORE, CSCORE, ECONOMICRISK];
        try {
			$config = \AdminModel::Instance()->GetConfigVar();
			$leverateCrm = self::getCrm();

			$request = new \AccountDetailsRequest();
			$request->FilterType = 'Email';
			$request->FilterValue = $_SESSION["user"]["email"];
			$request->AdditionalAttributesNames = $atts;

			$getAccountDetails = new \GetAccountDetails();
			$getAccountDetails->ownerUserId = $config['ownerUserId'];
			$getAccountDetails->organizationName = $config['organization'];
			$getAccountDetails->businessUnitName = $config['businessUnitName'];

			$getAccountDetails->accountDetailsRequest = $request;
            $result = $leverateCrm->GetAccountDetails($getAccountDetails);

			return new Values($result);

		} catch (\Throwable $err) {
			echo "<pre>",print_r($err),"</pre>";
		}
    }

    private function depositBigger(): int
    {
        $stage2 = $this->allStages[2];

        $deposit = 0;
        $income = 0;
        $netWorth = 0;

        foreach ($stage2 as $key => $field) {
            if ($field->name() === "new_expecteddepositsperyear") {
                $deposit = $field->postValue();
            } elseif ($field->name() === "new_yourtotalestimatedincome") {
                $income = $field->postValue();
            } elseif ($field->name() === "lv_estimatednetworth") {
                $netWorth = $field->postValue();
            }
        }

        $i = 0; $o = 0;

        if ($income === 1 and $deposit === 4) {
            $i = 10;
        } elseif($income === 1 and $deposit === 5) {
            $i = 10;
        } elseif($income === 2 and $deposit === 5) {
            $i = 10;
        }

        if ($deposit === $netWorth or $deposit > $netWorth) {
            $o = 10;
        }

        $aml = $i + $o;
        return $aml;
    }

    public static function completeObj($object) {
        return unserialize(serialize($object));
    }

    public static function UpgradeAccount()
    {
        new Integrate([], 0, 1);
    }

    public function upgradeToLive(Score $score)
    {
        $config = \AdminModel::Instance()->GetConfigVar();
        $leverateCrm = self::getCrm();
        $accId = $this->GetValuesFromCrm()->accId;
        
        try {
            $request = new \UpdateAccountDetailsRequest();            
            $request->AccountId = $accId;
			
            $da = [];
            foreach ($this->allStages as $stage => $fields) {
                foreach ($fields as $field) {
                    $crmName = $field->name();
                    $value = $field->postValue();
                    $type = $field->crmType();
                    $model = \Model::Instance();
                    if ($type === "built") {
                        $request->$crmName = $value;
                    } elseif ($type !== "built" and $type !== "not") {
                        $da[] = $model->CreateDynamicAttribute($crmName, $type, "$value");
                    }
                }
            }

            if (isset($_POST["lowScore"])) {
                $da[] = $model->CreateDynamicAttribute(
                    "new_lowscorewarningaccepted", 
                    "bool", 
                    "true"
                );
            }

            $score = $this->daScores($score);
            $da = array_merge($da, $score);
            $request->AdditionalAttributes = $da; 
            $update = new \UpdateAccountDetails();
            $update->ownerUserId = $config['ownerUserId'];
            $update->organizationName = $config['organization'];
            $update->businessUnitName = $config['businessUnitName'];
            $update->updateAccountDetailsRequest = $request;
            $response = new \UpdateAccountDetailsResponse();
            $response = $leverateCrm->UpdateAccountDetails($update);
            $code = $response->UpdateAccountDetailsResult->Result->Code;

            if ($code === "Success") {
                echo "<h1>Upgrading account to Live</h1>";
                ?>
                <script>
                    $(document).ready(function() {
                        window.location.href = "<?= home_url() ?>/dashboard/";
                    });
                </script>
                <?php
            }else{
                "Something went wrong, contact support.";
            }
        
        } catch (\Throwable $err) {
			echo "<pre>",print_r($err),"</pre>";
		}
    }

    private function removeAccountSpain() {
        $config = \AdminModel::Instance()->GetConfigVar();
        $leverateCrm = self::getCrm();
        $accId = $this->GetValuesFromCrm()->accId;
        try {
            $request = new \UpdateAccountDetailsRequest();            
            $request->AccountId = $accId;
            $model = \Model::Instance();
            $da = [];
            $da[] = $model->CreateDynamicAttribute("lv_leadstatus", "pickList", "20");
            $request->AdditionalAttributes = $da;
            $update = new \UpdateAccountDetails();
            $update->ownerUserId = $config['ownerUserId'];
            $update->organizationName = $config['organization'];
            $update->businessUnitName = $config['businessUnitName'];
            $update->updateAccountDetailsRequest = $request;
            $response = new \UpdateAccountDetailsResponse();
            $response = $leverateCrm->UpdateAccountDetails($update);
            $code = $response->UpdateAccountDetailsResult->Result->Code;

            if ($code === "Success") {
                $this->removeAccountWebsite();
            } elseif ($code !== "Success") {
                echo "Something went wrong";
            }

        } catch (\Throwable $err) {
            echo "<pre>",print_r($err),"</pre>";
        }
    }

    private function removeAccountWebsite()
    {
        global $wpdb;
        $users = $wpdb->prefix."leverate_users";
        $tpAcc = $wpdb->prefix."leverate_tp_accounts";
        $email = $_SESSION["user"]["email"];
        $wpdb->query("UPDATE $users SET `password` = '0' WHERE email LIKE '$email'");

        unset($_SESSION["user"]);
        exit;
    }

    public static function isUpgraded(): ?bool
    {
        try {
			$config = \AdminModel::Instance()->GetConfigVar();
			$leverateCrm = self::getCrm();
			$request = new \AccountDetailsRequest();
			$request->FilterType = 'Email';
			$request->FilterValue = $_SESSION["user"]["email"];
			$request->AdditionalAttributesNames = ["new_employmentstatus"];

			$getAccountDetails = new \GetAccountDetails();
			$getAccountDetails->ownerUserId = $config['ownerUserId'];
			$getAccountDetails->organizationName = $config['organization'];
			$getAccountDetails->businessUnitName = $config['businessUnitName'];

			$getAccountDetails->accountDetailsRequest = $request;
            $result = $leverateCrm->GetAccountDetails($getAccountDetails)->GetAccountDetailsResult->AccountsInfo->AccountInfo->AdditionalAttributes->DynamicAttributeInfo->Value;
            
            if (!empty($result)) {
                return true;
            }else{
                return null;
            }
		} catch (\Throwable $err) {
            echo "<pre>",print_r($err),"</pre>";
            return null;
        }    
    }
}
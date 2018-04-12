<?php
/**
 * Created by PhpStorm.
 * User: secret
 * Date: 06.04.18
 * Time: 13:41
 */
include_once('libphp/base.obj.inc.php');
include_once('libphp/delivery/deliveryAPI/DeliveryAPI.php');


class TelegramBot extends ConnectionAPI
{
    //сылка на бота
    //https://telegram.me/zatsepinserg_bot/start

    public $db;
    public $message;
    public $siteLabel;

    const API = "489029372:AAGrrmtKSQbRSZ9Pl4PdlsVsy9aEetmighY";
    const MESSAGE = 'У Вас новый заказ для сбора, перейдите по ссылке в личный кабинет ';


    public function __construct()
    {
        $this->db = new NeadsBaseClass();
        $this->updateInfoProviders();
        $this->siteLabel = 'С уважением '.$_SERVER['HTTP_HOST'];
    }

    private function allProviderTelegrammID()
    {
        $QS = "SELECT `telegramm_id` FROM `main__users` WHERE `telegramm_id` IS NOT NULL ORDER BY `telegramm_id` DESC";

        $this->db->Query($QS);

        return  $this->db->Items;
    }

    public function getUpdates()
    {
        $url = 'https://api.telegram.org/bot' . TelegramBot::API . '/getUpdates';
        $data = '';
        $type = "GET";

        $resp = $this->connect($data, $url, $type);

        return $resp;
    }
    /**
     * @param $userID
     * @param $url
     */
    public function sendPrivatURLtoTelegram($userID, $url)
    {
        $url = 'https://api.telegram.org/bot' . TelegramBot::API . '/sendMessage?chat_id=' . $userID . '&text='.TelegramBot::MESSAGE. ' http://'.$url ;

        $data = '';
        $type = "GET";
        $this->connect($data, $url, $type);
    }


    /**
     * @param $userID
     * @param $message
     */
    public function sendNewtoTelegram($userID, $message)
    {
        $url = 'https://api.telegram.org/bot' . TelegramBot::API . '/sendMessage?chat_id=' . $userID . '&text='.$message.$this->siteLabel ;

        $data = '';
        $type = "GET";
        $this->connect($data, $url, $type);
    }


    /**
     * @param $mail
     * @return mixed
     */
    public function searchProviderID($code)
    {
        $QS = "SELECT `id`  FROM `main__providers` WHERE `code` LIKE '{$code}' AND `active` !=0";
        $this->db->Query($QS);

        return $this->db->Items[0]->id;
    }

    /**
     * @param $providerID
     * @return mixed
     */
    public function getTelegramProviderInfo($providerID)
    {

        $QS = "SELECT `telegramm_id`,`telegram_token`  FROM `main__users` WHERE `provider_id` = {$providerID}";

        $this->db->Query($QS);

        return $this->db->Items[0];
    }


    /**
     * @param $providerID
     * @param $telegrammID
     * @return mixed
     */
    public function additionTelegramsID($providerID, $telegrammID)
    {

        $QS = "UPDATE `main__users` SET `telegramm_id` = '{$telegrammID}' WHERE `main__users`.`provider_id` = {$providerID};";

        $this->db->Query($QS);

        return $this->db->Items[0]->provider_id;
    }


    public function updateInfoProviders()
    {
        $res = $this->getUpdates();
        $res = json_decode($res);

        if ($res->ok) {
            $results = $res->result;
            $codeContainers = array();
            foreach ($results AS $result) {

                $codeContainer = trim($result->message->text);

                $confirmCodeContainer = preg_match('/^[0-9]+/', $codeContainer);

                if ($confirmCodeContainer) {
                    if ($result->message->chat->type == "private") {
                        $codeContainers[$result->message->from->id] = $codeContainer;
                    }
                }
            }

            if (!empty($codeContainers)) {

                foreach ($codeContainers AS $kay => $codeContainer) {
                    $telegrammID = $kay;

                    $providerID = $this->searchProviderID($codeContainer);

                    if (!empty($providerID)) {
                        $this->additionTelegramsID($providerID, $telegrammID);
                    }
                }
            }
        }
    }


    /**
     * @param $email
     */
    public function sendTelegramToProviders($email)
    {
        $telegramProvider = $this->getTelegramProviderInfo($email);

        $url=$_SERVER['HTTP_HOST'].'/cpanel?telegramToken='.$telegramProvider->telegram_token;

        if($telegramProvider->telegramm_id){

            $this->sendPrivatURLtoTelegram($telegramProvider->telegramm_id, $url);
        }
    }

    /**
     * @param $message
     */
    public function sendNewsFromProvider($message)
    {
        $idTelegramFromProviders = $this->allProviderTelegrammID();

        foreach ($idTelegramFromProviders AS $idTelegramFromProvider)
        {
            $userID = $idTelegramFromProvider->telegramm_id;

            $this->sendNewtoTelegram($userID, $message);
        }
    }
    
}







<?php

//#SUR# composer fail
//use srag\DIC\DhbwTraining\DICTrait;
use srag\Plugins\DhbwTraining\Config\Config;


/**
 * Class ReccomenderCurl
 *
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
class RecommenderCurl
{
    //#SUR# composer fail
    //use DICTrait;

    const PLUGIN_CLASS_NAME = ilDhbwTrainingPlugin::class;
    const KEY_RESPONSE_TIME_START = ilDhbwTrainingPlugin::PLUGIN_PREFIX . "_response_time_start";
    const KEY_RESPONSE_PROGRESS_METER = ilDhbwTrainingPlugin::PLUGIN_PREFIX . "_response_progress_meter";
    const KEY_RESPONSE_PROGRESS_BAR = ilDhbwTrainingPlugin::PLUGIN_PREFIX . "_response_progress_bar";
    /**
     * @var xdhtObjectFacadeInterface
     */
    protected $facade;
    /**
     * @var RecommenderResponse
     */
    protected $response;


    /**
     * RecommenderCurl constructor
     *
     * @param xdhtObjectFacadeInterface $facade
     * @param RecommenderResponse       $response
     */
    public function __construct(xdhtObjectFacadeInterface $facade, RecommenderResponse $response)
    {
        $this->facade = $facade;
        $this->response = $response;
    }


    /**
     *
     */
    public function start()/*:void*/
    {
        global $DIC;

        ilSession::clear(self::KEY_RESPONSE_TIME_START);

        $headers = [
            "Accept: application/json",
            "Content-Type: application/json"
        ];
        $data = [
            "secret"               => $this->facade->settings()->getSecret(),
            "installation_key"     => $this->facade->settings()->getInstallationKey(),
            "user_id"              => $this->getAnonymizedUserHash(),
            "lang_key"             => $DIC->user()->getLanguage(),
            "training_obj_id"      => $this->facade->settings()->getDhbwTrainingObjectId(),
            "question_pool_obj_id" => $this->facade->settings()->getQuestionPoolId()
        ];

        $this->doRequest("/v1/start", $headers, $data);
    }


    /**
     * @return string
     */
    protected function getAnonymizedUserHash() : string
    {
        global $DIC;
	    $alg = 'sha256'; // put new desired hashing algo here
	    if (array_search($alg,hash_algos()) === false) {
		    $alg = 'md5'; // Fallback to md5 if $alg not included in php
	    }

	    return hash($alg,Config::getField(Config::KEY_SALT) . $DIC->user()->getId());
    }



    /**
     * @param string $rest_url
     * @param array  $headers
     * @param array  $post_data
     */
    protected function doRequest(string $rest_url, array $headers, array $post_data = [])/*:void*/
    {
        global $DIC;
        $curlConnection = null;

        $component_repository = $DIC["component.repository"];
        $plugin_name = ilDhbwTrainingPlugin::PLUGIN_NAME;
        $info = $component_repository->getPluginByName($plugin_name);

        $component_factory = $DIC["component.factory"];

        $plugin_obj = $component_factory->getPlugin($info->getId());

        try {
            $curlConnection = $this->initCurlConnection($rest_url, $headers);

            $response_time_start = intval(ilSession::get(self::KEY_RESPONSE_TIME_START));
            if (!empty($response_time_start)) {
                $post_data["response_time"] = (time() - $response_time_start);
            }

            if ($this->facade->settings()->getLog()) {
                $this->response->addSendInfo('<pre>post_data:
' . json_encode($post_data, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT) . ' </pre>');
            }

            $curlConnection->setOpt(CURLOPT_POST, true);
            $curlConnection->setOpt(CURLOPT_POSTFIELDS, json_encode($post_data, JSON_FORCE_OBJECT));

            $raw_response = $curlConnection->exec();

            if (empty($raw_response)) {
                $this->response->addSendFailure($plugin_obj->txt("error_recommender_system_not_reached"));

                return;
            }

            $result = json_decode($raw_response, true);

            if (empty($result) || !is_array($result)) {
                if ($this->facade->settings()->getLog()) {
                    $this->response->addSendInfo('<pre>raw_response:
' . $raw_response . ' </pre>');
                }

                $this->response->addSendFailure($plugin_obj->txt("error_recommender_system_not_reached"));

                return;
            }

            if ($this->facade->settings()->getLog()) {
                if ($this->facade->settings()->getRecommenderSystemServer() === xdhtSettingsInterface::RECOMMENDER_SYSTEM_SERVER_BUILT_IN_DEBUG) {
                    if (!empty($result['debug_server'])) {

                        $this->response->addSendInfo('<pre>' . $plugin_obj->txt("recommender_system_server_built_in_debug") . ':
' . json_encode($result['debug_server'], JSON_PRETTY_PRINT) . '</pre>');
                    }
                    unset($result['debug_server']);
                }

                $this->response->addSendInfo('<pre>response:
' . json_encode($result, JSON_PRETTY_PRINT) . ' </pre>');
            }

            if (!empty($result['status'])) {
                $this->response->setStatus(strval($result['status']));
            } else {

                $this->response->addSendFailure($plugin_obj->txt("error_recommender_system_no_status"));

                return;
            }

            if (!empty($result['recomander_id'])) {
                $this->response->setRecomanderId(strval($result['recomander_id']));
            }

            if (!empty($result['response_type'])) {
                $this->response->setResponseType(intval($result['response_type']));
            }

            if (isset($result['answer_response'])) {
                $this->response->setAnswerResponse(strval($result['answer_response']));
            }

            if (!empty($result['answer_response_type'])) {
                $this->response->setAnswerResponseType(strval($result['answer_response_type']));
            }

            if (!empty($result['message'])) {
                $this->response->setMessage(strval($result['message']));
            }

            if (!empty($result['message_type'])) {
                $this->response->setMessageType(strval($result['message_type']));
            }

            if(!empty($result['progress_display'])) {
                $this->response->setProgressDisplay((bool)($result['progress_display']));
            }

            $this->response->setProgress(null);
            $this->response->setProgressType("");
            if (isset($result['progress']) && !empty($result['progress_type'])) {
                $this->response->setProgress(floatval($result['progress']));
                $this->response->setProgressType(strval($result['progress_type']));

                ilSession::set(self::KEY_RESPONSE_PROGRESS_BAR, serialize(['progress' => $result['progress'],'progress_type' => $result['progress_type']]));
            } else {
                if(strlen(ilSession::get(self::KEY_RESPONSE_PROGRESS_BAR)) > 0) {
                    $progress_bar = unserialize(ilSession::get(self::KEY_RESPONSE_PROGRESS_BAR));

                    if(isset($progress_bar['progress'])) {
                        $this->response->setProgress(floatval($progress_bar['progress']));
                    }
                    if (isset($progress_bar['progress_type'])){
                    $this->response->setProgressType(floatval($progress_bar['progress_type']));
                    }
                }
            }


            if (isset($result['learning_progress_status'])) {
                $this->response->setLearningProgressStatus($result['learning_progress_status'] !== null ? intval($result['learning_progress_status']) : null);
            }

            if (!empty($result['competences'])) {
                $this->response->setCompetences((array) $result['competences']);
            }

            if (!empty($result['progress_meters'])) {


                $progress_meter_list = [];
                foreach($result['progress_meters'] as $key => $value){
                    $progress_meter_list[] = ProgressMeter::newFromArray($value);
                }

                ilSession::set(self::KEY_RESPONSE_PROGRESS_METER, serialize($progress_meter_list));


                $this->response->setProgressmeters((array) $progress_meter_list);
            } else {
                if(strlen(ilSession::get(self::KEY_RESPONSE_PROGRESS_METER)) > 0) {
                   $this->response->setProgressmeters((array) unserialize(ilSession::get(self::KEY_RESPONSE_PROGRESS_METER)));
                }
            }

            if (isset($result['correct'])) {
                $this->response->setCorrect($result['correct']);
            }


        } catch (Exception $ex) {
            if ($this->facade->settings()->getLog()) {
                $this->response->addSendFailure($ex->getMessage());
            } else {
                global $DIC;

                $component_repository = $DIC["component.repository"];

                $info = null;
                $plugin_name = ilDhbwTrainingPlugin::PLUGIN_NAME;
                $info = $component_repository->getPluginByName($plugin_name);

                $component_factory = $DIC["component.factory"];

                $plugin_obj = $component_factory->getPlugin($info->getId());
                $this->response->addSendFailure($plugin_obj->txt("error_recommender_system_not_reached"));
            }
        } finally {
            // Close Curl connection
            if ($curlConnection !== null) {
                $curlConnection->close();
                $curlConnection = null;
            }
        }

        ilSession::set(self::KEY_RESPONSE_TIME_START, time());
    }


    /**
     * Init a Curl connection
     *
     * @param array  $headers
     * @param string $rest_url
     *
     * @return ilCurlConnection
     * @throws ilCurlConnectionException
     */
    protected function initCurlConnection(string $rest_url, array $headers) : ilCurlConnection
    {
        $curlConnection = new ilCurlConnection();

        $curlConnection->init();

        $curlConnection->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curlConnection->setOpt(CURLOPT_VERBOSE, true);
        $curlConnection->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curlConnection->setOpt(CURLOPT_SSL_VERIFYHOST, false);

        switch ($this->facade->settings()->getRecommenderSystemServer()) {
            case xdhtSettingsInterface::RECOMMENDER_SYSTEM_SERVER_EXTERNAL:
                $url = rtrim($this->facade->settings()->getUrl(), "/") . $rest_url;
                break;

            case xdhtSettingsInterface::RECOMMENDER_SYSTEM_SERVER_BUILT_IN_DEBUG:
                $url = ILIAS_HTTP_PATH . substr(self::plugin()->directory(), 1) . "/classes/Recommender/debug/" . trim($rest_url, "/") . ".php?ref_id=" . $this->facade->refId();
                $curlConnection->setOpt(CURLOPT_COOKIE, session_name() . '=' . session_id() . ";XDEBUG_SESSION=" . $_COOKIE["XDEBUG_SESSION"]);
                break;

            default:
                break;
        }

        $curlConnection->setOpt(CURLOPT_URL, $url);

        $curlConnection->setOpt(CURLOPT_HTTPHEADER, $headers);

        return $curlConnection;
    }


    /**
     * @param string $recomander_id
     * @param int $question_type
     * @param int $question_max_points
     * @param array $skill
     * @param mixed $answer
     */
    public function answer(string $recomander_id, int $question_type, int $question_max_points, array $skill, $answer)/*:void*/
    {
        global $DIC;

        $headers = [
            "Accept: application/json",
            "Content-Type: application/json"
        ];

        $data = [
            "secret"               => $this->facade->settings()->getSecret(),
            "installation_key"     => $this->facade->settings()->getInstallationKey(),
            "user_id"              => $this->getAnonymizedUserHash(),
            "lang_key"             => $DIC->user()->getLanguage(),
            "training_obj_id"      => $this->facade->settings()->getDhbwTrainingObjectId(),
            "question_pool_obj_id" => $this->facade->settings()->getQuestionPoolId(),
            "recomander_id"        => $recomander_id,
            "question_type"        => $question_type,
            "question_max_points"  => $question_max_points,
            "answer"               => $answer,
            "skills"               => $skill
        ];

        $this->doRequest("/v1/answer", $headers, $data);
    }


    /**
     * @param string $recomander_id
     * @param int    $rating
     */
    public function sendRating(string $recomander_id, int $rating)/*:void*/
    {
        global $DIC;

        $headers = [
            "Accept: application/json",
            "Content-Type: application/json"
        ];

        $data = [
            "secret"               => $this->facade->settings()->getSecret(),
            "installation_key"     => $this->facade->settings()->getInstallationKey(),
            "user_id"              => $this->getAnonymizedUserHash(),
            "lang_key"             => $DIC->user()->getLanguage(),
            "training_obj_id"      => $this->facade->settings()->getDhbwTrainingObjectId(),
            "question_pool_obj_id" => $this->facade->settings()->getQuestionPoolId(),
            "recomander_id"        => $recomander_id,
            "rating"               => $rating
        ];

        $this->doRequest("/v1/rating", $headers, $data);
    }
}

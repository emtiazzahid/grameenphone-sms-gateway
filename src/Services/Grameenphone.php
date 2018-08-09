<?php

namespace Emtiaz\GrameenphoneSmsGateway\Services;

use Apiz\AbstractApi;

class Grameenphone extends AbstractApi
{
    protected $sms = [];

    protected $mobiles = [];

    protected $config;

    protected $debug = false;

    protected $template = true;

    protected $sender = null;

    protected $autoParse = false;

    protected $responseDetails = false;

    protected $numberPrefix = '88';

    protected $prefix = 'sendSMS';

    protected $sendingUrl = '/sendSMS';


    protected $sendingParameters = [];

    /**
     * Banglalink constructor.
     *
     * @param $config
     */
    public function __construct( $config )
    {
        $this->config = $config;
        parent::__construct();
    }

    /**
     * Set Number Prefix
     *
     * @param string $prefix
     * @return $this
     */
    public function numberPrefix( $prefix = '88' )
    {
        $this->numberPrefix = $prefix;

        return $this;
    }

    /**
     * Set Message
     *
     * @param string $message
     * @param null $to
     *
     * @return $this
     */
    public function message( $message = '', $to = null )
    {
        $this->sms[] = $message;
        if ( !is_null($to) ) {
            $this->to($to);
        }

        return $this;
    }

    /**
     * Set Phone Numbers
     *
     * @param $to
     *
     * @return $this
     */
    public function to( $to )
    {
        if ( is_array($to) ) {
            $this->mobiles = array_merge($this->mobiles, $to);
        } else {
            $this->mobiles[] = $to;
        }

        return $this;
    }

    /**
     * Send Method
     *
     * @param array $array
     *
     * @return mixed
     */
    public function send( $array = [] )
    {
        return $this->makingSmsFormatAndSendingSMS($array);
    }

    /**
     * Formatting Given Data
     *
     * @param array $array
     *
     * @return array
     */
    protected function makingSmsFormatAndSendingSMS( $array = [] )
    {
        if ( $array ) {
            $this->sms = array_merge($this->sms, $this->splitSmsAndNumbers($array));
        } else {
            $this->sms = $this->splitSmsAndNumbers($array);
        }

        if ( count($this->sms) == 1 ) {
            $this->sms = $this->sms[ 0 ];

            return $this->singleSMSOrTemplate();
        } else {
            return $this->makeMultiSmsMultiUser();
        }
    }

    /**
     * Formatting sms and mobiles property
     *
     * @param $array
     *
     * @return array
     */
    private function splitSmsAndNumbers( $array )
    {
        if ( $array && !isset($array[ 'message' ]) && !isset($array[ 'to' ]) ) {
            $this->sms = array_merge($this->sms, array_values($array));
            $arrayKeys = array_keys($array);
            if ( $arrayKeys[ 0 ] != 0 ) {
                $this->mobiles = array_merge($this->mobiles, $arrayKeys);
            }
        } else {
            $this->sms = array_merge($this->sms, $array);
        }
        $sms = $mobiles = [];
        if ( is_array($this->sms) ) {
            foreach ( $this->sms as $key => $message ) {
                if ( is_array($message) && isset($message[ 'message' ]) && isset($message[ 'to' ]) ) {
                    $sms[]     = $message[ 'message' ];
                    $mobiles[] = $message[ 'to' ];
                } elseif ( $key === 'to' ) {
                    $mobiles[] = $message;
                } else {
                    $sms[] = $message;
                }
            }
        }

        if ( $mobiles ) {
            $this->mobiles = array_merge($this->mobiles, $mobiles);
        }

        return $sms;
    }

    /**
     * Rendering template
     *
     * @return array|bool
     */
    private function singleSMSOrTemplate()
    {
        try {
            $this->mobiles = implode(',', $this->mobiles);

            return $this->makeSingleSmsToUser();
        } catch ( \ErrorException $exception ) {
            if ( $this->template ) {
                $template          = $this->sms;
                $putDataInTemplate = $sms = $mobiles = [];

                if ( is_array($this->mobiles) ) {
                    foreach ( $this->mobiles as $mobile => $message ) {
                        try {
                            $putData                      = vsprintf($template, $message);
                            $sms[]                        = $putData;
                            $mobiles[]                    = $mobile;
                            $putDataInTemplate[ $mobile ] = $putData;
                        } catch ( \ErrorException $exception ) {
                            $putData                            = vsprintf($template, $message[ 1 ]);
                            $sms[]                              = $putData;
                            $mobiles[]                          = $message[ 0 ];
                            $putDataInTemplate[ $message[ 0 ] ] = $putData;
                        }
                    }
                }
                if ( $sms ) {
                    $this->sms = $sms;
                }
                if ( $mobiles ) {
                    $this->mobiles = $mobiles;
                }

                return $this->makeMultiSmsMultiUser();
            }

            return false;
        }
    }

    /**
     * Sending Single SMS
     *
     * @return array
     */
    protected function makeSingleSmsToUser()
    {
        $this->gettingParameters($this->sms, $this->numberPrefix . $this->mobiles);

        return $this->sendToServer();
    }

    /**
     * Prepare Sending parameters
     *
     * @param $sms
     * @param $mobiles
     *
     * @return $this
     */
    private function gettingParameters( $sms, $mobiles )
    {
        $this->sendingParameters = [
            'username'  => $this->config[ 'username' ],
            'password'  => $this->config[ 'password' ],
            'apicode'  => $this->config[ 'apicode' ],
            'countrycode'  => $this->config[ 'countrycode' ],
            'cli'  => $this->config[ 'cli' ],
            'messagetype'  => $this->config[ 'messagetype' ],
            'messageid'  => $this->config[ 'messageid' ],
            'message' => $sms,
            'msisdn'  => $mobiles,
        ];

        return $this;
    }

    /**
     * Getting response from api
     *
     * @return mixed
     */
    private function sendToServer()
    {
        try {
            $ch = curl_init();

            if (FALSE === $ch)
                throw new \Exception('failed to initialize');


            $params = http_build_query($this->sendingParameters);

            curl_setopt($ch, CURLOPT_URL, $this->setBaseUrl().'?'.$params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_PROXYPORT, "80");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $content = curl_exec($ch);

            if (FALSE === $content) {
                throw new \Exception(curl_error($ch), curl_errno($ch));
            }
            else{
                $rtype = explode(",", $content);
                return [
                    'status' => 'success',
                    'code' => $rtype[0],
                    'message' => $rtype[1],
                ];
            }
        } catch(\Exception $e)
        {
            return [
                'status' => 'error',
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];

        }
    }

    /**
     * Sending Multiple SMS
     *
     * @return array
     */
    protected function makeMultiSmsMultiUser()
    {
        $response = [];
        $count    = 1;
        if ( is_array($this->sms) ) {
            foreach ( $this->sms as $key => $message ) {
                if ( isset($this->mobiles[ $key ]) ) {
                    $number = $this->numberPrefix . $this->mobiles[ $key ];
                    $this->gettingParameters($message, $number);
                    $response[ 'res-' . $count++ . '-' . $number ] = $this->sendToServer();
                }
            }
        }

        return $response;
    }

    /**
     * Set Sender Details
     *
     * @param $sender
     * @return $this
     */
    public function sender( $sender )
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * Set Debug
     *
     * @param bool $debug
     *
     * @return $this
     */
    public function debug( $debug = true )
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Set Auto Parse
     *
     * @param bool $autoParse
     *
     * @return $this
     */
    public function autoParse( $autoParse = true )
    {
        $this->autoParse = $autoParse;

        return $this;
    }

    /**
     * Set Response Details
     *
     * @param bool $responseDetails
     * @return $this
     */
    public function details( $responseDetails = true )
    {
        $this->responseDetails = $responseDetails;

        return $this;
    }

    /**
     * set base URL for guzzle client
     *
     * @return string
     */
    protected function setBaseUrl()
    {
        return 'https://cmp.grameenphone.com/gpcmpapi/messageplatform/controller.home';
    }

    /**
     * Set sending url
     *
     * @return string
     */
    public function setSendingUrl( $sendingUrl )
    {
        $this->sendingUrl = $sendingUrl;

        return $this;
    }

    public function getSendingUrl()
    {
        return $this->sendingUrl;
    }

    /**
     * Set Template
     *
     * @param bool $template
     * @return $this
     */
    public function template( $template = true )
    {
        $this->template = $template;

        return $this;
    }

}
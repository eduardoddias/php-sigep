<?php
/**
 * prestashop Project ${PROJECT_URL}
 *
 * @link      ${GITHUB_URL} Source code
 */

namespace PhpSigep\Services\Real;

use PhpSigep\Bootstrap;
use PhpSigep\Config;

class SoapClientFactory
{
    const WEB_SERVICE_CHARSET = 'ISO-8859-1';

    /**
     * @var \SoapClient
     */
    protected static $_soapClient;
    /**
     * @var \SoapClient
     */
    protected static $_soapCalcPrecoPrazo;

    public static function getSoapClient()
    {
        if (!self::$_soapClient) {
            $wsdl = Bootstrap::getConfig()->getWsdlAtendeCliente();

            $opts = [
                'http'=> [
                    'protocol_version'=>'1.0',
                    'header' => 'Connection: Close'
                ]
            ];

            // SOAP 1.1 client
            $params = array (
                'encoding'              => self::WEB_SERVICE_CHARSET,
                'verifypeer'            => false,
                'verifyhost'            => false,
                'soap_version'          => SOAP_1_1,
                'trace'                 => Bootstrap::getConfig()->getEnv() != Config::ENV_PRODUCTION,
                'exceptions'            => Bootstrap::getConfig()->getEnv() != Config::ENV_PRODUCTION,
                "connection_timeout"    => 180,
                'stream_context'        => stream_context_create($opts)
            );

            self::$_soapClient = new \SoapClient($wsdl, $params);
        }

        return self::$_soapClient;
    }

    public static function getSoapCalcPrecoPrazo($soapArgs)
    {
        if (!self::$_soapCalcPrecoPrazo) {
//            $wsdl = Bootstrap::getConfig()->getWsdlCalcPrecoPrazo();
//
//            $opts = [
//                'http'=> [
//                    'protocol_version'=>'1.0',
//                    'header' => 'Connection: Close'
//                ]
//            ];
//
//            self::$_soapCalcPrecoPrazo = new \SoapClient($wsdl, array(
//                'encoding'              => self::WEB_SERVICE_CHARSET,
//                'verifypeer'            => false,
//                'verifyhost'            => false,
//                'soap_version'          => SOAP_1_1,
//                'trace'                 => true,//Bootstrap::getConfig()->getEnv() != Config::ENV_PRODUCTION,
//                'exceptions'            => true,//Bootstrap::getConfig()->getEnv() != Config::ENV_PRODUCTION,
//                "connection_timeout"    => 180,
//                'stream_context'        => stream_context_create($opts)
//            ));

            $ch = curl_init();

            $soapArgs['StrRetorno'] = 'xml';
//            echo 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx?'.http_build_query($soapArgs);

//            curl_setopt($ch, CURLOPT_URL, 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx?nCdEmpresa=08082650&sDsSenha=564321&sCepOrigem=18540000&sCepDestino=04547000&nVlPeso=1&nCdFormato=1&nVlComprimento=20&nVlAltura=20&nVlLargura=20&sCdMaoPropria=n&nVlValorDeclarado=0&sCdAvisoRecebimento=n&nCdServico=04510&nVlDiametro=0&StrRetorno=xml&nIndicaCalculo=3');
            curl_setopt($ch, CURLOPT_URL,
                'http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx?' .
                http_build_query($soapArgs)
            );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');


            $headers = array();
            $headers[] = 'Content-Type: text/xml';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch); die();
            } else {
                $xml = simplexml_load_string($result);
                $json = json_encode($xml);
                $array = json_decode($json,TRUE);
                $nArray = [];
                $nArray['CalcPrecoPrazoResult']['Servicos'] = $array;
                self::$_soapCalcPrecoPrazo = json_decode(json_encode($nArray));
            }

            curl_close($ch);

        }

        return self::$_soapCalcPrecoPrazo;
    }

    /**
     * Se possível converte a string recebida.
     * @param $string
     * @return bool|string
     */
    public static function convertEncoding($string)
    {
        $to     = 'UTF-8';
        $from   = self::WEB_SERVICE_CHARSET;
        $str = false;

//        if (function_exists('iconv')) {
//            $str = iconv($from, $to . '//TRANSLIT', $string);
//        } elseif (function_exists('mb_convert_encoding')) {
            $str = mb_convert_encoding($string, $to, $from);
//        }

        if ($str === false) {
            $str = $string;
        }

        return $str;
    }
} 

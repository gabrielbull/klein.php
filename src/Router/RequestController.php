<?php
namespace Router;

class RequestController
{
    /**
     * @return PsrRequest
     */
    public function createRequestFromGlobals()
    {
        $requestBody = file_get_contents('php://input');

        $request = new PsrRequest();
        $request->setBody(new Body($requestBody));
        $request->setMethod($this->getMethodFromServerParameters());
        $request->setUrl($this->getUrlFromServerParameters());
        $request->setServerParams(isset($_SERVER) ? $_SERVER : []);
        $request->setCookieParams(isset($_COOKIE) ? $_COOKIE : []);
        $request->setQueryParams(isset($_GET) ? $_GET : []);
        $request->setFileParams(isset($_FILES) ? $_FILES : []);
        $request->setBodyParams($this->getBodyParametersFromGlobals($requestBody));
        $request->setHeaders($this->getHeaders());
        $request->setProtocolVersion($this->getProtocolVersionFromServerParameters());

        return $request;
    }

    /**
     * @return string
     */
    private function getMethodFromServerParameters()
    {
        if (isset($_SERVER['REQUEST_METHOD'])) {
            return $_SERVER['REQUEST_METHOD'];
        }
        return null;
    }

    /**
     * @return string
     */
    private function getUrlFromServerParameters()
    {
        $ssl = false;
        $httpHost = '';
        $requestUri = '';
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            $ssl = true;
        }
        if (isset($_SERVER['HTTP_HOST'])) {
            $httpHost = $_SERVER['HTTP_HOST'];
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
        }

        return
            ($ssl ? 'https://' : 'http://') .
            $httpHost .
            $requestUri;
    }

    /**
     * @return string
     */
    private function getProtocolVersionFromServerParameters()
    {
        if (isset($_SERVER['SERVER_PROTOCOL'])) {
            return (string)preg_replace('/[^\/]*\/(.*)$/', '$1', $_SERVER['SERVER_PROTOCOL']);
        }
        return null;
    }

    /**
     * @param string $requestBody
     * @return array
     */
    private function getBodyParametersFromGlobals($requestBody)
    {
        $parameters = isset($_POST) ? $_POST : [];

        if (is_string($requestBody)) {
            $requestBodyParameters = json_decode($requestBody, true);
            if (!is_array($requestBodyParameters) && is_string($requestBody)) {
                preg_match_all('/[ &]?([^ =&]+)=["|\']?([^"\',&]+)["|\']?/', $requestBody, $matches);
                if (isset($matches[1]) && isset($matches[2]) && count($matches[1])) {
                    $requestBodyParameters = [];
                    foreach ($matches[1] as $count => $key) {
                        $requestBodyParameters[$key] =
                            urldecode(isset($matches[2][$count]) ? $matches[2][$count] : null);
                    }
                }
            }
            if (is_array($requestBodyParameters)) {
                $parameters = array_merge($parameters, $requestBodyParameters);
            }
        }

        return $parameters;
    }

    /**
     * @return array
     */
    private function getHeaders()
    {
        $headers = [];
        if (isset($_SERVER)) {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$name] = $value;
                }
            }
        }
        return $headers;
    }
}

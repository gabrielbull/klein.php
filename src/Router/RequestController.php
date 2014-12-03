<?php
namespace Router;

class RequestController
{
    public function createRequestFromGlobals()
    {
        $request = new PsrRequest();
        $request->setBody(new Body(http_get_request_body_stream()));
        $request->setMethod($this->getMethodFromServerParameters());
        $request->setUrl($this->getUrlFromServerParameters());
        $request->setServerParams(isset($_SERVER) ? $_SERVER : []);
        $request->setCookieParams(isset($_COOKIE) ? $_COOKIE : []);
        $request->setQueryParams(isset($_GET) ? $_GET : []);
        $request->setFileParams(isset($_FILES) ? $_FILES : []);
        $request->setBodyParams($this->getBodyParametersFromGlobals());
        $request->setHeaders($this->getHeaders());
        $request->setProtocolVersion($this->getProtocolVersionFromServerParameters());
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
     * @return array
     */
    private function getBodyParametersFromGlobals()
    {
        $parameters = isset($_POST) ? $_POST : [];
        $requestBody = file_get_contents('php://input');

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
        foreach(getallheaders() as $key => $value) {
            $headers[$key] = array_map('trim', explode(',', $value));
        }
        return $headers;
    }
}

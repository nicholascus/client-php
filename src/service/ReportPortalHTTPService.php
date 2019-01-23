<?php

namespace ReportPortalBasic\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use ReportPortalBasic\Enum\ItemStatusesEnum;
use Symfony\Component\Yaml\Yaml;

/**
 * Report portal HTTP service.
 * Provides basic methods to collaborate with Report portal.
 *
 * @author Mikalai_Kabzar
 */
class ReportPortalHTTPService
{

    /**
     *
     * @var string
     */
    const ERROR_FINISH_LAUNCH = 'Finish launch is not allowed.';

    /**
     *
     * @var string
     */
    const ERROR_FINISH_TEST_ITEM = 'Finish test item is not allowed.';

    /**
     *
     * @var string
     */
    const DEFAULT_LAUNCH_MODE = 'DEFAULT';

    /**
     *
     * @var string
     */
    const EMPTY_ID = 'empty id';

    /**
     *
     * @var string
     */
    const DEFAULT_FEATURE_DESCRIPTION = '';

    /**
     *
     * @var string
     */
    const DEFAULT_SCENARIO_DESCRIPTION = '';

    /**
     *
     * @var string
     */
    const DEFAULT_STEP_DESCRIPTION = '';

    /**
     *
     * @var string
     */
    const FORMAT_DATE = 'Y-m-d\TH:i:s';

    /**
     *
     * @var string
     */
    const BASE_URI_TEMPLATE = '%s/api/';

    /**
     *
     * @var string
     */
    protected static $timeZone;

    /**
     *
     * @var string
     */
    protected static $UUID;

    /**
     *
     * @var string
     */
    protected static $baseURI;

    /**
     *
     * @var string
     */
    protected static $host;

    /**
     *
     * @var string
     */
    protected static $projectName;

    /**
     *
     * @var string
     */
    protected static $launchID = self::EMPTY_ID;

    /**
     *
     * @var string
     */
    protected static $rootItemID = self::EMPTY_ID;

    /**
     *
     * @var string
     */
    protected static $featureItemID = self::EMPTY_ID;

    /**
     *
     * @var string
     */
    protected static $scenarioItemID = self::EMPTY_ID;

    /**
     *
     * @var string
     */
    protected static $stepItemID = self::EMPTY_ID;

    /**
     *
     * @var boolean
     */
    private static $isHTTPErrorsAllowed = true;

    /**
     *
     * @var \GuzzleHttp\Client
     */
    protected static $client;

    function __construct()
    {
        self::$client = new Client([
            'base_uri' => self::$baseURI,
            'http_errors' => false,
            'verify' => false,
            'headers' => [
                'Authorization' => 'bearer ' . self::$UUID
            ]
        ]);
    }

    /**
     * @param string $timeZone
     */
    public static function setTimeZone($timeZone)
    {
        self::$timeZone = $timeZone;
    }

    /**
     * @param string $UUID
     */
    public static function setUUID($UUID)
    {
        self::$UUID = $UUID;
    }

    /**
     * @param string $baseURI
     */
    public static function setBaseURI($baseURI)
    {
        self::$baseURI = $baseURI;
    }

    /**
     * @param string $host
     */
    public static function setHost($host)
    {
        self::$host = $host;
    }

    /**
     * @param bool $isHTTPErrorsAllowed
     */
    public static function setIsHTTPErrorsAllowed($isHTTPErrorsAllowed)
    {
        self::$isHTTPErrorsAllowed = $isHTTPErrorsAllowed;
    }

    /**
     * Check if any suite has running status
     *
     * @return boolean - true if any suite has running status
     */
    public static function isSuiteRunned()
    {
        return self::$rootItemID != self::EMPTY_ID;
    }

    /**
     * @return string
     */
    public static function getStepItemID()
    {
        return self::$stepItemID;
    }

    /**
     * @param string $stepItemID
     */
    public static function setStepItemID($stepItemID)
    {
        self::$stepItemID = $stepItemID;
    }

    /**
     * Set Step Item to empty value
     */
    public static function setStepItemIDToEmpty()
    {
        self::$stepItemID = self::EMPTY_ID;
    }

    /**
     * @param string $UUID
     * @param string $baseURI
     * @param string $host
     * @param string $timeZone
     * @param string $projectName
     * @param bool $isHTTPErrorsAllowed
     */
    public static function configureClient($UUID, $baseURI, $host, $timeZone, $projectName, $isHTTPErrorsAllowed)
    {
        self::$UUID = $UUID;
        self::$baseURI = $baseURI;
        self::$host = $host;
        self::$timeZone = $timeZone;
        self::$projectName = $projectName;
        self::$isHTTPErrorsAllowed = $isHTTPErrorsAllowed;
    }

    /**
     * Check if any step has running status
     *
     * @return boolean - true if any step has running status
     */
    public static function isStepRunned()
    {
        return self::$stepItemID != self::EMPTY_ID;
    }

    /**
     * Check if any scenario has running status
     *
     * @return boolean - true if any scenario has running status
     */
    public static function isScenarioRunned()
    {
        return self::$scenarioItemID != self::EMPTY_ID;
    }

    /**
     * Check if any feature has running status
     *
     * @return boolean - true if any feature has running status
     */
    public static function isFeatureRunned()
    {
        return self::$featureItemID != self::EMPTY_ID;
    }

    /**
     * Set configuration for Report portal from yaml file
     *
     * @param string $yamlFilePath
     *            - path to configuration file
     */
    public static function configureReportPortalHTTPService($yamlFilePath)
    {
        $yamlArray = Yaml::parse($yamlFilePath);
        self::$UUID = $yamlArray['UUID'];
        self::$host = $yamlArray['host'];
        self::$baseURI = sprintf(self::BASE_URI_TEMPLATE, self::$host);
        self::$projectName = $yamlArray['projectName'];
        self::$timeZone = $yamlArray['timeZone'];
    }

    /**
     * Launch test run
     *
     * @param string $name
     *            - name of test launch
     * @param string $description
     *            - description of test run
     * @param string $mode
     *            - mode
     * @param array $tags
     *            - array with tags of test run
     * @return ResponseInterface - result of request
     */
    public static function launchTestRun($name, $description, $mode, $tags)
    {
        $result = self::$client->post('v1/' . self::$projectName . '/launch', array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'json' => array(
                'description' => self::to_utf8($description),
                'mode' => $mode,
                'name' => self::to_utf8($name),
                'start_time' => self::getTime(),
                'tags' => $tags
            )
        ));
        self::$launchID = self::getValueFromResponse('id', $result);
        return $result;
    }

    /**
     * Finish test run
     *
     * @param string $runStatus
     *            - status of test run
     * @return ResponseInterface - result of request
     */
    public static function finishTestRun($runStatus)
    {
        $result = self::$client->put('v1/' . self::$projectName . '/launch/' . self::$launchID . '/finish', array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'json' => array(
                'end_time' => self::getTime(),
                'status' => $runStatus
            )
        ));
        return $result;
    }

    /**
     * Force finish test run
     *
     * @param string $runStatus
     *            - status of test run
     * @return ResponseInterface - result of request
     */
    public static function forceFinishTestRun($runStatus)
    {
        $result = self::$client->put('v1/' . self::$projectName . '/launch/' . self::$launchID . '/stop', array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'json' => array(
                'end_time' => self::getTime(),
                'status' => $runStatus
            )
        ));
        return $result;
    }

    /**
     * Create root item
     *
     * @param string $name
     *            - root item name
     * @param string $description
     *            - root item description
     * @param array $tags
     *            - array with tags
     * @return ResponseInterface - result of request
     */
    public static function createRootItem($name, $description, $tags)
    {
        $result = self::$client->post('v1/' . self::$projectName . '/item', array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'json' => array(
                'description' => self::to_utf8($description),
                'launch_id' => self::$launchID,
                'name' => self::to_utf8($name),
                'start_time' => self::getTime(),
                "tags" => $tags,
                "type" => "SUITE"
            )
        ));
        self::$rootItemID = self::getValueFromResponse('id', $result);
        return $result;
    }

    /**
     * Finish root item
     *
     * @return ResponseInterface - result of request
     */
    public static function finishRootItem()
    {
        $result = self::finishItem(self::$rootItemID, ItemStatusesEnum::PASSED, '');
        self::$rootItemID = self::EMPTY_ID;
        return $result;
    }

    /**
     * Add a log message to item
     *
     * @param string $item_id
     *            - item id to add log message
     * @param string $message
     *            - log message
     * @param string $logLevel
     *            - log level of log message
     * @return ResponseInterface - result of request
     */
    public static function addLogMessage($item_id, $message, $logLevel)
    {
        $result = self::$client->post('v1/' . self::$projectName . '/log', array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'json' => array(
                'item_id' => $item_id,
                'message' => self::to_utf8($message),
                'time' => self::getTime(),
                'level' => $logLevel
            )
        ));
        return $result;
    }

    /**
     * Add log with picture.
     *
     * @param string $item_id - current step item_id
     * @param string $message - message for log
     * @param string $logLevel - log level
     * @param string $pictureAsString - picture as string
     * @param string $pictureContentType - picture content type (png, jpeg, etc.)
     *
     * @return ResponseInterface - response
     */
    public static function addLogMessageWithPicture($item_id, $message, $logLevel, $pictureAsString, $pictureContentType)
    {
        if (self::isStepRunned()) {
            $multipart = new MultipartStream([
                [
                    'name' => 'json_request_part',
                    'contents' => json_encode([['file' => ['name' => 'picture'],
                        'item_id' => $item_id,
                        'message' => self::to_utf8($message),
                        'time' => self::getTime(),
                        'level' => $logLevel]]),
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Content-Transfer-Encoding' => '8bit'
                    ]
                ],
                [
                    'name' => 'binary_part',
                    'contents' => $pictureAsString,
                    'filename' => 'picture',
                    'headers' => [
                        'Content-Type' => 'image/' . $pictureContentType,
                        'Content-Transfer-Encoding' => 'binary'
                    ]
                ]
            ]);
            $request = new Request(
                'POST',
                'v1/' . self::$projectName . '/log',
                [],
                $multipart
            );
            $result = self::$client->send($request);
            return $result;
        }
    }

    /**
     * Finish item by id
     *
     * @param string $itemID
     *            - test item ID
     * @param string $status
     *            - status of test item
     * @param string $description
     *            - description of test item
     * @return ResponseInterface - result of request
     */
    public static function finishItem($itemID, $status, $description)
    {
        $result = self::$client->put('v1/' . self::$projectName . '/item/' . $itemID, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'json' => array(
                'description' => self::to_utf8($description),
                'end_time' => self::getTime(),
                'status' => $status
            )
        ));
        return $result;
    }

    /**
     * Get value from response.
     *
     * @param string $lookForRequest
     *            - string to find value
     * @param ResponseInterface $response
     * @return string value by $lookForRequest.
     */
    public static function getValueFromResponse($lookForRequest, $response)
    {
        $array = json_decode($response->getBody()->getContents());
        return $array->{$lookForRequest};
    }

    /**
     * Start child item.
     *
     * @param string $parentItemID
     *            - id of parent item.
     * @param string $description
     *            - item description
     * @param string $name
     *            - item name
     * @param string $type
     *            - item type
     * @param array $tags
     *            - array with tags
     * @return ResponseInterface - result of request
     */
    public static function startChildItem($parentItemID, $description, $name, $type, $tags)
    {
        $result = self::$client->post('v1/' . self::$projectName . '/item/' . $parentItemID, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'json' => array(
                'description' => self::to_utf8($description),
                'launch_id' => self::$launchID,
                'name' => self::to_utf8($name),
                'start_time' => self::getTime(),
                'tags' => $tags,
                'type' => $type
            )
        ));
        return $result;
    }

    /**
     * Get local time
     *
     * @return string with local time
     */
    protected static function getTime()
    {
        return date(self::FORMAT_DATE) . self::$timeZone;
    }

    /**
     * Force finish items
     *
     * @param $result
     *            - response of request with result
     *
     * @return true if there is no errors
     */
    public static function finishAll($result)
    {
        $status = true;
        $body = $result->getBody();
        $array = json_decode($body->getContents());
        if ((strpos($body, self::ERROR_FINISH_LAUNCH) > -1) or (strpos($body, self::ERROR_FINISH_TEST_ITEM) > -1)) {

            $message = $array->{'message'};
            $items = mb_split(',', explode(']', explode('[', $message)[1])[0]);
            foreach ($items as $itemID) {
                self::finishItem($itemID, ItemStatusesEnum::CANCELLED, 'Cancelled due to error.');
            }
            $status = false;
            self::forceFinishTestRun(ItemStatusesEnum::CANCELLED);
        }
        return $status;
    }

    /**
     * https://stackoverflow.com/questions/1401317/remove-non-utf8-characters-from-string
     * @var string
     */
    static $regex = <<<'END'
/
  (
    (?: [\x00-\x7F]               # single-byte sequences   0xxxxxxx
    |   [\xC0-\xDF][\x80-\xBF]    # double-byte sequences   110xxxxx 10xxxxxx
    |   [\xE0-\xEF][\x80-\xBF]{2} # triple-byte sequences   1110xxxx 10xxxxxx * 2
    |   [\xF0-\xF7][\x80-\xBF]{3} # quadruple-byte sequence 11110xxx 10xxxxxx * 3
    ){1,100}                      # ...one or more times
  )
| ( [\x80-\xBF] )                 # invalid byte in range 10000000 - 10111111
| ( [\xC0-\xFF] )                 # invalid byte in range 11000000 - 11111111
/x
END;

    public static function utf8replacer($captures) {
        if ($captures[1] != "") {
            // Valid byte sequence. Return unmodified.
            return $captures[1];
        } elseif ($captures[2] != "") {
            // Invalid byte of the form 10xxxxxx.
            // Encode as 11000010 10xxxxxx.
            return "\xC2".$captures[2];
        } else {
            // Invalid byte of the form 11xxxxxx.
            // Encode as 11000011 10xxxxxx.
            return "\xC3".chr(ord($captures[3])-64);
        }
    }

    protected static function to_utf8($s) {
        try {
            return mb_convert_encoding($s, "UTF-8", "auto");
        } catch (\Exception $e) {
            return preg_replace_callback(self::$regex, [self::class,"utf8replacer"], $s);
        }
    }
}

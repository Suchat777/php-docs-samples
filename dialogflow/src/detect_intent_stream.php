<?php
/**
 * Copyright 2018 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// [START dialogflow_detect_intent_stream]
namespace Google\Cloud\Samples\Dialogflow;

use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\AudioEncoding;
use Google\Cloud\Dialogflow\V2\InputAudioConfig;
use Google\Cloud\Dialogflow\V2\QueryInput;
use Google\Cloud\Dialogflow\V2\StreamingDetectIntentRequest;
use Ramsey\Uuid\Uuid;

/**
* Returns the result of detect intent with streaming audio as input.
* Using the same `session_id` between requests allows continuation
* of the conversation.
*/
function detect_intent_stream($projectId, $path, $sessionId, $languageCode)
{
    // random session id if not provided
    if (! $sessionId) {
        $sessionId = (string) Uuid::uuid4();
    }

    // set default language to en-US
    if (! $languageCode) {
        $languageCode = 'en-US';
    }

    // new session
    $sessionsClient = new SessionsClient();
    $session = $sessionsClient->sessionName($projectId, $sessionId);
    printf('Session path: %s' . PHP_EOL, $session);

    // hard coding audio_encoding and sample_rate_hertz for simplicity
    $audioConfig = new InputAudioConfig();
    $audioConfig->setAudioEncoding(AudioEncoding::AUDIO_ENCODING_LINEAR_16);
    $audioConfig->setLanguageCode($languageCode);
    $audioConfig->setSampleRateHertz(16000);

    // create query input
    $queryInput = new QueryInput();
    $queryInput->setAudioConfig($audioConfig);

    // first request contains the configuration
    $request = new StreamingDetectIntentRequest();
    $request->setSession($session);
    $request->setQueryInput($queryInput);
    $requests = [$request];

    // we are going to read small chunks of audio data from
    // a local audio file. in practice, these chunks should
    // come from an audio input device.
    $audioStream = fopen($path, 'rb');
    while (true) {
        $chunk = stream_get_contents($audioStream, 4096);
        if (! $chunk) {
            break;
        }
        $request = new StreamingDetectIntentRequest();
        $request->setInputAudio($chunk);
        $requests[] = $request;
    }
    
    // get response and relevant info
    $responses = $sessionsClient->streamingDetectIntent($requests);
    // $queryResult = $response->getQueryResult();
    // $queryText = $queryResult->getQueryText();
    // $intent = $queryResult->getIntent();
    // $displayName = $intent->getDisplayName();
    // $confidence = $queryResult->getIntentDetectionConfidence();
    // $fulfilmentText = $queryResult->getFulfillmentText();

    // // output relevant info
    // print(str_repeat("=", 20) . PHP_EOL);
    // printf('Query text: %s' . PHP_EOL, $queryText);
    // printf('Detected intent: %s (confidence: %f)' . PHP_EOL, $displayName,
    //     $confidence);
    // print(PHP_EOL);
    // printf('Fulfilment text: %s' . PHP_EOL, $fulfilmentText);

    $sessionsClient->close();
}
// [END dialogflow_detect_intent_stream]

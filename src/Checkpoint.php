<?php

namespace Checkpoint;

use InstagramAPI\Instagram;
use Exception;

 /***
  ** This is not intend to use for spam purpose
  ** Use it with correct limitations
  **/

class Checkpoint extends Instagram
{
    public $c_url;

    public $c_username;

    public $c_password;

    public $c_csrf;

    public $c_hash;

    public $media_url =  'https://i.instagram.com/graphql/query/?query_hash=50d3631032cf38ebe1a2d758524e3492&variables={"id":"{id}","first":"{first}","after":"{after}"}';

    public function __construct($options, $debug = false, $truncatedDebug = false)
    {
        parent::$allowDangerousWebUsageAtMyOwnRisk = true;
        $this->c_url = isset($options['url']) ? $options['url'] : '';
        $this->c_username = isset($options['username']) ? $options['username'] : '';
        $this->c_password = isset($options['password']) ? $options['password'] : '';
        $this->c_csrf = isset($options['csrf']) ? $options['csrf'] : '';
        $this->c_hash = isset($options['hash']) ? $options['hash'] : '';

        parent::__construct($debug, $truncatedDebug);

    }

    public function isCheckpointRequired($message)
    {
        if (strpos($message, 'Challenge required') !== false || strpos($message, 'checkpoint') !== false) {
            return true;
        }

        return false;
    }

    public function getCheckpointMethods($ig)
    {
        // print_R($ig->getResponse()->getChallenge());die;

        $challenge_url = $ig->getResponse()->getChallenge()->getUrl();
        $this->settings->set('devicestring', '000');
        $this->_setUser($this->c_username, $this->c_password);

        //$challenge_url = str_replace('i.', 'www.', $challenge_url);

        $response = $this->request($challenge_url)
                    ->setNeedsAuth(false)
                    ->getRawResponse();

        preg_match('/\bwindow\._sharedData\s*=(.+)(?=;|<\/script)/', $response, $m);
        $data = json_decode(str_replace('};', '}', trim($m[1])) , true);
        $result = array(
            'url' => $challenge_url
        );
        $result['hash'] = $data['rollout_hash'];

        if (isset($data['entry_data']) && isset($data['entry_data']['Challenge'])) {
            $result['csrf_token'] = $data['config']['csrf_token'];
            if (isset($data['entry_data']['Challenge']['0']['extraData'])) {
                foreach($data['entry_data']['Challenge'][0]['extraData']['content'] as $method) {
                    if ($method['__typename'] == "GraphChallengePageForm") {
                        $result['methods'] = $method['fields'];
                    }
                }
            }
        }

        return $result;
    }

    public function send_verification_code($method)
    {
        $this->_setUserWithoutPassword($this->c_username);

        $url = str_replace('i.', 'www.', $this->c_url);


        //print_R(json_decode($cookies));die;

        $response = $this->request($this->c_url)
                     ->setNeedsAuth(false);

        $this->settings->setCookies($this->settings->getCookies());

        $response =  $response->addHeader('referer', $this->c_url)
                     ->addHeader('x-instagram-ajax', $this->c_hash)
                     ->addHeader('x-csrftoken', $this->c_csrf)
                     ->addUnsignedPost('choice', $method);
        $response = $response->getRawResponse();
        return $response;
    }

    public function ConfirmVerificationCode($code)
    {
        $this->_setUser($this->c_username, $this->c_password);

        $url = str_replace('i.', 'www.', $this->c_url);

        $this->isMaybeLoggedIn = true;

        $response = $this->request($this->c_url)
        ->setNeedsAuth(true);
        $this->settings->setCookies($this->settings->getCookies());
        $response = $response->addHeader('x-csrftoken', $this->c_csrf)
        ->addHeader('x-instagram-ajax', $this->c_hash)
        ->addHeader('referer',$this->c_url)
        ->addUnsignedPost('security_code', $code);
        //print_R($response);die;
        $response = $response->getRawResponse();

        $response = json_decode($response);

        if ($response->status == 'ok') {
            $cookies = json_decode($this->client->getCookieJarAsJSON());
            $id = $this->client->getCookie('ds_user_id')->getValue();
            if ($id) {
                $this->settings->set('account_id', $id);
                $this->settings->set('last_login', time());
                $this->isMaybeLoggedIn = true;
                $this->account_id = $id;

                $username = $this->people->getInfoById($id)->getUser()->getUsername();

                $expires = strtotime('+100 days');

                try {
                    $this->story->getReelsTrayFeed();
                    $this->_sendLoginFlow(true);
                    //$this->login($this->c_username, $this->c_password);
                }catch(Exception $e) {
                    return (object)['status' => false, 'message' => $e->getMessage()];
                }
            } else {
                return (object)['status' => false];
            }
        }

        // Full (re-)login successfully completed. Return server response.
        return $response;
    }

    public function scrape_media($user_id, $start = 0, $after = 0){

      $url  = $this->media_url;

      $url = str_replace('{id}', urlencode($user_id), $url);
      $url = str_replace('{first}', urlencode($start), $url);

      if($after){
        $url = str_replace('{after}', urlencode($after), $url);
      }else{
        $url = str_replace(',"after":"{after}"', '', $url);
      }

      $request = $this->request($url);
      $response = json_decode($request->getRawResponse());


      if($response->status = 'ok'){
        return ['has_next_page' => $response->data->user->edge_owner_to_timeline_media->page_info->has_next_page,
               'max_id' => $response->data->user->edge_owner_to_timeline_media->page_info->end_cursor,
               'media' => $response->data->user->edge_owner_to_timeline_media->edges];
      }else{
          throw new Exception('Response code is ' . $response->code . '. Body: ' . json_encode($response->body) . ' Something went wrong. Please report issue.', $response->code);
      }
    }
}

?>

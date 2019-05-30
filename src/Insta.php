<?php

namespace App\Instagram;

use InstagramAPI\Instagram;

use App\Instagram\Response\LikesResponse;


class Insta extends Instagram{

  public $likes_url = 'https://i.instagram.com/graphql/query/?query_id=17864450716183058&variables={"shortcode":"{{shortcode}}","first":{{first}},"after":"{{after}}"}';

  public $comments_url =  'https://i.instagram.com/graphql/query/?query_hash=33ba35852cb50da46f5b5e889df7d159&variables={variables}';

  public $media_url =  'https://i.instagram.com/graphql/query/?query_hash=50d3631032cf38ebe1a2d758524e3492&variables={"id":"{id}","first":"{first}","after":"{after}"}';

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct($debug = false, $truncatedDebug = false)
  {
      parent::$allowDangerousWebUsageAtMyOwnRisk = true;
      parent::__construct($debug, $truncatedDebug);
  }


  public function get_likes($mediaId, $start = 0){
    $request = $this->request("media/{$mediaId}/likers/");
    //$request = $request->addParam('can_support_threading', true);

    //$request = $request->addParam('first', 10);
    //$request =  $request->addParam('next_max_id', '1433792516');
    //$request =  $request->addParam('max_id', '1433792516');

    //if($start != 0) $request =  $request->addParam('after', $start);

    return $request->getResponse(new LikesResponse());
  }

  public function get_media($mediaId, $start = 0){
    $request = $this->request("media/{$mediaId}/likers/");
    //$request = $request->addParam('can_support_threading', true);

    //$request = $request->addParam('first', 10);
    //$request =  $request->addParam('next_max_id', '1433792516');
    //$request =  $request->addParam('max_id', '1433792516');

    //if($start != 0) $request =  $request->addParam('after', $start);

    return $request->getResponse(new LikesResponse());
  }

  public function scrape_likes($mediaId, $start = 0, $after = 0){

    $url  = $this->likes_url;

    $url = str_replace('{{shortcode}}', urlencode($mediaId), $url);
    $url = str_replace('{{first}}', urlencode($start), $url);

    if($after){
      $url = str_replace('{{after}}', urlencode($after), $url);
    }else{
      $url = str_replace(',"after":"{{after}}"', '', $url);
    }

    $request = $this->request($url);
    $response = json_decode($request->getRawResponse());


    if($response->status = 'ok'){
      return ['has_next_page' => $response->data->shortcode_media->edge_liked_by->page_info->has_next_page,
             'max_id' => $response->data->shortcode_media->edge_liked_by->page_info->end_cursor,
             'likes' => $response->data->shortcode_media->edge_liked_by->edges];
    }else{
        throw new Exception('Response code is ' . $response->code . '. Body: ' . json_encode($response->body) . ' Something went wrong. Please report issue.', $response->code);
    }
  }

  public function scrape_media($user_id, $start = 0, $after = 0){

    $url  = $this->likes_url;

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
      return ['has_next_page' => $response->data->shortcode_media->edge_liked_by->page_info->has_next_page,
             'max_id' => $response->data->shortcode_media->edge_liked_by->page_info->end_cursor,
             'likes' => $response->data->shortcode_media->edge_liked_by->edges];
    }else{
        throw new Exception('Response code is ' . $response->code . '. Body: ' . json_encode($response->body) . ' Something went wrong. Please report issue.', $response->code);
    }
  }

  public function scrape_comments($shortcode, $start = 0, $after = 0){

    $url  = $this->comments_url;
    $variables = ['shortcode' => $shortcode,
                  'first' => $start];

    if($after){
      $variables['after'] = $after;
    }

    $variable = urlencode(json_encode($variables));
    $url  = str_replace('{variables}', $variable, $url);

    $request = $this->request($url);
    $response = json_decode($request->getRawResponse());

    if($response->status = 'ok'){
      return ['has_next_page' => $response->data->shortcode_media->edge_media_to_comment->page_info->has_next_page,
             'max_id' => $response->data->shortcode_media->edge_media_to_comment->page_info->end_cursor,
             'comments' => $response->data->shortcode_media->edge_media_to_comment->edges];
    }else{
        throw new Exception('Response code is ' . $response->code . '. Body: ' . json_encode($response->body) . ' Something went wrong. Please report issue.', $response->code);
    }
  }
}


?>

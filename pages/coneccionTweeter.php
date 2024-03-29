<?php

    function tweeterApiExchange($tipoBusqueda, $busqueda, $numTweets){
        $settings = array(
            'oauth_access_token' => "1318549926406664195-EbsgYH8ssGIkObFFZGHHxJnQFsbVZQ",
            'oauth_access_token_secret' => "0TDyTAdId7aCzaVLTbwYIWyuLorm9CmWvULRZnkkGaJkR",
            'consumer_key' => "6wy5NZbnBC3h0xLrJcVtSFifm",
            'consumer_secret' => "SoJxKi7zppue3xLxlR8c5IxnA8ID6oNSOG4MK3qIBkzRdTla4D"
        );

        $url = 'https://api.twitter.com/1.1/search/tweets.json';


        if($tipoBusqueda == "screen_name"){
            $busqueda = "%40" . $busqueda ;
        }else if($tipoBusqueda == "hashtag"){
            $busqueda = "%23" . $busqueda;
        }


        //$url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';

        $requestMethod = 'GET';
        $resultType =  "popular"; //"popular"; //"mixed"; // "recent";

        
        $getfield = "?q=".$busqueda . "&result_type=". $resultType ."&count=". $numTweets ."&include_entities=true&lang=en&tweet_mode=extended";

        $twitter = new TwitterAPIExchange($settings);
        $tweets =  json_decode( $twitter->setGetfield($getfield)
                                        ->buildOauth($url, $requestMethod)
                                        ->performRequest()
                                , $assoc = TRUE);
                                
        return $tweets["statuses"];
    }



    function extraerTweets( $connection, $busqueda, $tweets, $recordId ){
        foreach($tweets as $tweet){
            //console_log($tweet);
            $tweet_id = $tweet["id"];
            $screen_name = $tweet["user"]["screen_name"];
            $date = $tweet["created_at"];
            $hashtagList = $tweet["entities"]["hashtags"];
            $hashtags = "";
            
            for($i=0; $i<sizeof($hashtagList); $i++){
                $hashtags = $hashtags . $hashtagList[$i]["text"];
                if($i!=sizeof($hashtagList) -1)
                    $hashtags = $hashtags . " , ";
            }

            $user_mentionList = $tweet["entities"]["user_mentions"];
            $user_mentions = "";
            //console_log($user_mentionList);
            
            for($i=0; $i<sizeof($user_mentionList); $i++){
                $user_mentions = $user_mentions . $user_mentionList[$i]["screen_name"];
                if($i!=sizeof($user_mentionList) -1)
                    $user_mentions = $user_mentions . " , ";
            }
            //console_log($user_mentions);
            
            $date = date("Y-m-d H:i:s", strtotime($date));
            $fulltext = $tweet["full_text"];
            $index_link = strrpos($fulltext, "http");
            $text =  substr($fulltext, 0, $index_link);
            $link = substr($fulltext, $index_link);

            $favorite_count = $tweet["favorite_count"];
            $retweet_count = $tweet["retweet_count"];
            
            insertTweet($connection, $tweet_id, $busqueda, $screen_name, $date, $text, $link, $favorite_count, $retweet_count, $hashtags, $user_mentions);
            insertRecordTweet($connection, $recordId, $tweet_id);
        }
    }



    function crearComparacion($connection, $user, $tipoBusqueda, $numTweets, $busqueda1, $busqueda2){
        
        $tweets1 = tweeterApiExchange($tipoBusqueda, $busqueda1, $numTweets);
        $tweets2 = tweeterApiExchange($tipoBusqueda, $busqueda2, $numTweets);
        if(!$tweets1 || !$tweets2 ){
            return false;
        }else{
            insertRecord($connection, $user, $tipoBusqueda, $busqueda1, $busqueda2);
            $recordId = getLastRecordId($connection);
            extraerTweets( $connection, $busqueda1, $tweets1, $recordId);
            extraerTweets( $connection, $busqueda2, $tweets2, $recordId);
            //return false;
            return $recordId;
        }     
    }

?>
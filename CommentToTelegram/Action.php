<?php

use Typecho\Widget;
use Widget\Comments\Edit as CommentsEdit;
use Widget\Options;
use Utils\Helper;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

require_once __DIR__ . '/Telegram.php';

class  CommentToTelegram_Action extends  Typecho_Widget implements Widget_Interface_Do 
{
    public function action()
    {
        $this->on($this->request->is('do=callback'))->callback();
    }

    private function callback()
    {

        $callbackRaw = $this->request->get('@json');
        if (empty($callbackRaw)) {
            return;
        }

        $apiToken = Helper::options()->plugin('CommentToTelegram')->apiToken;
        $chatId = Helper::options()->plugin('CommentToTelegram')->chatId;
        $proxy = Helper::options()->plugin('CommentToTelegram')->proxyInfo;
        $replyUid = Helper::options()->plugin('CommentToTelegram')->replyUid;

        $replyToMessage = $callbackRaw['message']['reply_to_message'];
        if(!empty($replyToMessage)){
            $action = 'reply';
            $replyMessageText = $callbackRaw['message']['reply_to_message']['text'];
            $replyText = $callbackRaw['message']['text'];
            $receivedChatId = $callbackRaw['message']['chat']['id'];

            if ((int) $receivedChatId !== (int) $chatId) {
                return;
            }

            preg_match('/#comment-(\d+)/', $replyMessageText, $matches);
            if (isset($matches[1])) {
                $coid = $matches[1];
            } 

            $comment = Helper::widgetById('comments', $coid);
            if(!$comment->have()){
                $commentStatus = '评论已删除或不存在！';
            } else {
                $commentStatus = $comment->status;
            }

            if($comment->status !== 'approved') {
                $dataSendMessage = [
                    'chat_id' => $chatId,
                    'text' => '评论不支持回复,请先通过评论或检查评论状态！'.PHP_EOL.'被回复的评论ID：' . $coid . ' 当前状态: ' . $commentStatus
                ];
                self::sendTelegramRequest($apiToken, $dataSendMessage, 'sendMessage',$proxy);
                return;
            } 
        } else {

            $callbackData = json_decode($callbackRaw['callback_query']['data'],true);
            $action = $callbackData['action'];
            $coid = $callbackData['coid'];
            $receivedKey = $callbackData['key'];

            $messageId = $callbackRaw['callback_query']['message']['message_id'];
            $messageText = $callbackRaw['callback_query']['message']['text'].PHP_EOL.'评论已经处理: '.$action;

            $key =  substr(md5($apiToken), 0, 8);
            if ($receivedKey !== $key) {
                return; 
            }

            $dataSendMessage = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $messageText
            ];
        }

        Typecho_Widget::widget('Widget_User')->to($user);
        if(!$user->simpleLogin($replyUid)){
            return;
        }

        $comment = CommentsEdit::alloc(null, ['coid' => $coid,  'text' =>  $replyText], function (CommentsEdit $comment) use ($action) {
            switch ($action) {
                case 'approved':
                    $comment->approvedComment();
                    break;
                case 'spam':
                    $comment->spamComment();
                    break;
                case 'delete':
                    $comment->deleteComment();
                    break;
                case 'reply':
                    $comment->replyComment();
                    break;
            }

        });

        if ($action == 'reply') {
            $dataSendMessage = [
                'chat_id' => $chatId,
                'text' => '回复评论ID：' . $coid .' 已成功!'
            ];
            self::sendTelegramRequest($apiToken, $dataSendMessage, 'sendMessage',$proxy);
        } else {
            self::sendTelegramRequest($apiToken, $dataSendMessage, 'editMessageText', $proxy);
        }
        echo json_encode(['status' => 'success']);
    }

    public static function sendTelegramRequest($apiToken, $data, $action, $proxy = NULL)
    {
        return sendTelegramRequest($apiToken, $data, $action, $proxy);
    }
}

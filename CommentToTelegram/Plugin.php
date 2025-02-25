<?php

namespace TypechoPlugin\CommentToTelegram;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Plugin\Exception;
use Utils\Helper;
use Widget\Service;
use Widget\Feedback;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

require_once __DIR__ . '/Telegram.php';

/**
 * 将评论推送到Telegram Bot，支持Telegram Inline Button管理评论状态,支持在Telegram通知上回复评论，支持配置代理。
 *
 * @package CommentToTelegram
 * @author opswill
 * @version 1.1.0
 * @since 1.2.1
 * @link https://opswill.com
 *
 */

class Plugin implements PluginInterface
{
    public static function activate()
    {
        if (!extension_loaded('curl')) {
            throw new Exception(_t('无法启用插件,服务器必须安装php-curl扩展!'));
        }
        Feedback::pluginHandle()->finishComment =  [__CLASS__, 'sendToTelegram'];
        Service::pluginHandle()->sendComment = [__CLASS__, 'sendComment'];
        Helper::addRoute("telegramCallback", "/telegram/comment", Action::class, "action","index");
    }

    public static function deactivate()
    {
        self::telegramWebhook('deleteWebhook');
        Helper::removeRoute("telegramCallback");
    }

    public static function config(Form $form)
    {
        $form->addInput(new Form\Element\Text('apiToken', null, '', _t('Telegram Bot API Token')));
        $form->addInput(new Form\Element\Text('chatId', null, '', _t('Telegram Chat ID')));
        $form->addInput(new Form\Element\Radio('excludeAdmin', [1 => '是', 0 => '否'], 1, _t('当评论者为博主时不推送通知'), _t('启用后，若评论者为博主，则不会推送评论通知到 Telegram 机器人')));
        $form->addInput(new Form\Element\Radio('enableInlineButton', [1 => '是', 0 => '否'], 1, _t('是否启用Telegram Inline Button管理评论'), _t('启用后，评论通知下会有：打开评论/通过评论/标记垃圾/删除评论 四个按钮,点击可以管理评论,每条评论只能管理一次。')));
        $form->addInput(new Form\Element\Text('replyUid', null, '1', _t('同步Telegeam回复到博客的用户UID'),_t('启用Telegram Inline Button后,在Telegram通知上点击回复，回复的内容会同步到博客文章评论。此功能需要设置真实用户的UID，默认为1')));
        $form->addInput(new Form\Element\Text('proxyInfo', null, '', _t('代理服务器配置'), _t('格式为: socks5://username:password@proxyserver:port ,点击查看<a href="https://curl.se/libcurl/c/CURLOPT_PROXY.html">版本要求及使用文档</a>, 如不使用代理请留空')));
        $form->addInput(new Form\Element\Textarea('template', null, "博客评论通知!\n你的文章:《{title}》\n收到了 <{user}> 的评论:\n\n{text}\n\n文章地址: {url}", _t('通知正文模版')));

    }

    public static function personalConfig(Form $form)
    {
    }

    public static function configCheck(array $settings)
    {
        if (empty($settings['apiToken'])) {
            return _t('Telegram Bot API Token 不能为空!');
        }
        if (empty($settings['chatId'])) {
            return _t('Telegram Chat ID 不能为空!');
        }

        if (!empty($settings['proxyInfo'])) {
            $proxyData = self::telegramWebhook('getWebhookInfo',$settings['apiToken'],$settings['proxyInfo']);
            if (empty($proxyData)) {
                return _t('代理无法访问 Telegram, 请检查代理信息和格式是否正确！');
            }
        }

        if ($settings['enableInlineButton']) {
            $replyUser = Helper::widgetById('Users', $settings['replyUid']);
            if(!$replyUser->uid){
                return _t('用户 uid 为空或不存在，请在后台: 管理->用户 查看 uid');
            }
            $responseData = self::telegramWebhook('setWebhook',$settings['apiToken'],$settings['proxyInfo']);
            if (empty($responseData)) {
                return _t('请检查服务器是否能访问 Telegram！');
            }else {
                $responseData = json_decode($responseData,true);
                if (isset($responseData['ok']) && $responseData['result'] === true) {
                    return _t('设置 Telegram Bot Webhook 成功！');
                } elseif (isset($responseData['error_code'])) {
                    return _t('请检查 Telegram Bot API Token 是否正确!');
                }
            }
        }  
      
    }

    public static function sendComment(int $commentId)
    {
        $options = Helper::options();
        $pluginOptions = $options->plugin('CommentToTelegram');
        $comment = Helper::widgetById('comments', $commentId);

        $apiToken = $pluginOptions->apiToken;
        $chatId = $pluginOptions->chatId;
        $excludeAdmin = $pluginOptions->excludeAdmin;
        $enableInlineButton = $pluginOptions->enableInlineButton;
        $proxy = $pluginOptions->proxyInfo;
        $key =  substr(md5($apiToken), 0, 8);

        if ($excludeAdmin && $comment->ownerId == $comment->authorId) {
            return;
        }

        $msg = str_replace(['{user}', '{title}', '{url}', '{text}'],
               [$comment->author, $comment->title, $comment->permalink, $comment->text], $pluginOptions->template);

        $inlineKeyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '👀 打开评论', 'url' => $comment->permalink ],
                ],
                [
                    ['text' => '✅ 通过评论', 'callback_data' => json_encode(['action' => 'approved', 'coid' => $commentId, 'key' => $key ])],
                    ['text' => '🚫 标记垃圾', 'callback_data' => json_encode(['action' => 'spam', 'coid' => $commentId, 'key' => $key ])]
                ],
                [
                ['text' => '🗑️ 删除评论', 'callback_data' => json_encode(['action' => 'delete', 'coid' => $commentId , 'key' => $key ])]
                ]
            ]   
        ];
        $dataSendMessage = [
            'chat_id' => $chatId,
            'text' => $msg
        ];
        if ($enableInlineButton) {
             $dataSendMessage['reply_markup'] = json_encode($inlineKeyboard);
        }

        self::sendTelegramRequest($apiToken, $dataSendMessage, 'sendMessage',$proxy);

    }

    public static function telegramWebhook($action,$token = NULL,$proxy = NULL)
    {
        $options = Helper::options();
        $pluginOptions = $options->plugin('CommentToTelegram');
        if(!isset($proxy)) {
            $proxy = $pluginOptions->proxyInfo;
        } 

        if(!isset($token)) {
            $token = $pluginOptions->apiToken;
        }

        $webhookUrl = $options->siteUrl.'telegram/comment?do=callback';
        $dataWebHook = [
            'url' => $webhookUrl
        ];    

        $response = self::sendTelegramRequest($token, $dataWebHook, $action, $proxy);
        return $response;
    }

    public static function sendTelegramRequest($apiToken, $data, $action, $proxy = NULL)
    {
        return sendTelegramRequest($apiToken, $data, $action, $proxy);
    }

    public static function sendToTelegram($comment)
    {
        Helper::requestService('sendComment', $comment->coid);
    }
}


# CommentToTelegram

一个全新的 typecho 插件，使用 Telegram API 将评论推送到 Telegram Bot 通知并管理

围观地址： [详细介绍](https://opswill.com/articles/my-typecho-plugin-CommentToTelegram.html) 

## 功能及特性
- 支持 Telegram Inline Button 管理评论状态，可以将评论 批准/删除/标记垃圾
- 支持在 Telegram 通知上回复评论，在评论通知上点击回复，回复的内容会同步到博客，无需登录博客
- 支持配置代理，支持使用 socks5、https、http 代理访问 Telegram
- 支持异步回调，不会阻塞评论

## 插件版本要求
1. 建议 Typecho 版本大于 1.2.1，其他版本未进行测试
2. php: >=8.1.0, 本插件依赖 php-curl 和 php-json
3. 如果使用代理功能，建议使用新版本的 php-curl 扩展

## 安装教程

1. 下载后将压缩包解压到 /usr/plugins 目录
2. 文件夹名改为 CommentToTelegram
3. 登录管理后台，激活插件
4. 配置插件 填写 Telegram Bot Token 及 Telegram Chat ID，并设置其他参数
5. 保存配置，并注意是否有错误信息

## 插件升级

1. 禁用旧版本插件
2. 删除旧版本的文件，并上传然后传新版本解压，文件夹名改为 CommentToTelegram
3. 激活插件并设置参数

## 使用教程
### 一、 使用前提：
1. 申请 Telegram bot Token： [官方教程](https://core.telegram.org/bots/tutorial)
2. 通过 Telegam bot api 获取 chat_id: [getUpdates API](https://core.telegram.org/bots/api#getupdates)
如果看不懂，请自行百度谷歌相关教程

### 二、可配置选项
<img width="948" alt="后台可配置选项" src="https://github.com/opswill/CommentToTelegram/assets/7550211/4a4f2693-763d-4b90-be02-c8c4a96b8174">

如果插件配置有错误，会在点击 **保存设置** 后提示错误信息。如网络错误、代理信息错误、用户uid错误等等

### 三、通知详情及评论管理按钮

#### 3.1 评论通知

<img width="349" alt="评论通知" src="https://github.com/opswill/CommentToTelegram/assets/7550211/99a6f831-4145-4398-833c-9d10a99f1416">

每条评论只能管理一次，在点击管理按钮后，通知下方的管理按钮会消失并提示通知状态：

<img width="336" alt="评论管理" src="https://github.com/opswill/CommentToTelegram/assets/7550211/ba680e8f-0ec2-4b56-b41d-478a21a76f9d">

#### 3.2 评论回复
如果不启用 Telegram Inline Button管理评论，也无法启用回复功能。使用回复功能需要先设置正确的typecho后台的用户uid，查看uid：

<img width="405" alt="后台用户uid" src="https://github.com/opswill/CommentToTelegram/assets/7550211/162b7e11-8266-4abf-9635-06565349ce7f">

在Telegam的通知上进行评论回复：

<img width="408" alt="评论回复-reply" src="https://github.com/opswill/CommentToTelegram/assets/7550211/2a709ec8-b93d-4d49-a2e7-aee9e50b478b">

回复成功会有提示成功：

<img width="584" alt="评论回复-success" src="https://github.com/opswill/CommentToTelegram/assets/7550211/807304ba-66fd-4941-821f-60bd78151093">

typecho 博客上的评论状态：

<img width="644" alt="博客评论" src="https://github.com/opswill/CommentToTelegram/assets/7550211/3ae4be1e-77de-4cc5-944a-91e72f0db878">

#### 3.3 使用建议

1. 建议将所有评论设置为先需要审核（后台->设置->评论->评论提交->所有评论必须经过审核），然后通过本插件进行管理。

2. 建议使用typecho前台通过主题的评论框进行回复，本插件回复虽然使用typecho内置接口，但被回复的访客无法收到评论提醒（如邮件提醒），怀疑是 Typecho 的 bug，目前无法解决。

更多问题可以通过 issue 页面提交，或者通过  [博客](https://opswill.com)、邮件向我反馈

# 感谢
- https://github.com/joyqi/typecho-plugin-sitemap
- https://github.com/Adoream/typecho-plugin-comment2telegram



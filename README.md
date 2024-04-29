# CommentToTelegram
一个typecho插件，将评论推送到Telegram Bot。

## 功能及特性
- 支持Telegram Inline Button管理评论状态
- 支持在Telegram通知上回复评论
- 支持配置代理
- 支持异步回调
  
## 安装教程

1. 下载后将压缩包解压到 /usr/plugins 目录
2. 文件夹名改为CommentToTelegram
3. 登录管理后台，激活插件
4. 配置插件 填写Telegram Bot Token及Telegram Chat ID，并设置其他参数
5. 保存配置，并注意是否有错误信息

## 插件升级

1. 禁用旧版本插件
2. 删除旧版本的文件，并上传然后传新版本解压，文件夹名改为CommentToTelegram
3. 激活插件并设置参数

## 插件版本要求
1. 建议typecho版本为1.2.1，其他版本未测试
2. php: >=8.1.0, 本插件依赖 php-curl 和php-json
3. 如果使用代理功能，建议使用新版本的php-curl扩展





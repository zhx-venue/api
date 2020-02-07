<?php

namespace app\procedure;

use think\facade\Db;
use app\model\Corp;
use app\model\CorpAgent;
use app\model\CorpHistory;
use app\model\VenueUser;
use shophy\wxwork\structs\Message;
use shophy\wxwork\structs\NewsArticle;
use shophy\wxwork\structs\NewsMessageContent;
use app\wxwork\Service;

/**
 * InstallForm is the model behind the Intall action.
 */
class InstallForm
{
    public $permanentInfo;
    /**
     * 安装完成给管理员推送的消息内容
     * 发送内容不能超过8条
     * $newsArticle = [
     *      ['title' => '标题', 'description' => '描述', 'url' => '', 'picurl' => '', 'btntxt' => ''],
     *      ...
     * ]
     */
    public $newsArticle = [];

    public function record()
    {
        // 通过永久授权码获取授权信息时没有此字段
        // if(! isset($this->permanentInfo->permanent_code)) {
        //     throw new \Exception('Incorrect permanent_code in permanentInfo.');
        // }
        if(! isset($this->permanentInfo->auth_info->agent)) {
            throw new \Exception('Incorrect agent in permanentInfo.');
        }
        if(! isset($this->permanentInfo->auth_corp_info->corpid)) {
            throw new \Exception('Incorrect corpid in permanentInfo.');
        }

        Db::startTrans();
        try {
            // 存储corp信息
            $corp = Corp::find(['corpid' => $this->permanentInfo->auth_corp_info->corpid]);
            $corp || $corp = new Corp;
            $corp->corpid = $this->permanentInfo->auth_corp_info->corpid;
            isset($this->permanentInfo->auth_corp_info->corp_name) && $corp->corp_name = $this->permanentInfo->auth_corp_info->corp_name;
            isset($this->permanentInfo->auth_corp_info->corp_type) && $corp->corp_type = $this->permanentInfo->auth_corp_info->corp_type;
            isset($this->permanentInfo->auth_corp_info->corp_square_logo_url) && $corp->corp_square_logo_url = $this->permanentInfo->auth_corp_info->corp_square_logo_url;
            isset($this->permanentInfo->auth_corp_info->corp_user_max) && $corp->corp_user_max = $this->permanentInfo->auth_corp_info->corp_user_max;
            isset($this->permanentInfo->auth_corp_info->corp_agent_max) && $corp->corp_agent_max = $this->permanentInfo->auth_corp_info->corp_agent_max;
            isset($this->permanentInfo->auth_corp_info->corp_full_name) && $corp->corp_full_name = $this->permanentInfo->auth_corp_info->corp_full_name;
            isset($this->permanentInfo->auth_corp_info->verified_end_time) && $corp->verified_end_time = $this->permanentInfo->auth_corp_info->verified_end_time;
            isset($this->permanentInfo->auth_corp_info->subject_type) && $corp->subject_type = $this->permanentInfo->auth_corp_info->subject_type;
            isset($this->permanentInfo->auth_corp_info->corp_wxqrcode) && $corp->corp_wxqrcode = $this->permanentInfo->auth_corp_info->corp_wxqrcode;
            isset($this->permanentInfo->auth_corp_info->corp_scale) && $corp->corp_scale = $this->permanentInfo->auth_corp_info->corp_scale;
            isset($this->permanentInfo->auth_corp_info->corp_industry) && $corp->corp_industry = $this->permanentInfo->auth_corp_info->corp_industry;
            isset($this->permanentInfo->auth_corp_info->corp_sub_industry) && $corp->corp_sub_industry = $this->permanentInfo->auth_corp_info->corp_sub_industry;
            isset($this->permanentInfo->auth_corp_info->location) && $corp->location = $this->permanentInfo->auth_corp_info->location;
            if(! $corp->save()) {
                throw new \Exception('Failed to save corp info for unknown reason.');
            }

            // 存储安装agent的信息
            foreach($this->permanentInfo->auth_info->agent as $agentInfo) {
                $agent = CorpAgent::find($this->permanentInfo->auth_corp_info->corpid);
                $agent || $agent = new CorpAgent;
                $agent->corpid = $this->permanentInfo->auth_corp_info->corpid;
                $agent->agentid = $agentInfo->agentid;
                isset($agentInfo->name) && $agent->name = $agentInfo->name;
                isset($agentInfo->round_logo_url) && $agent->round_logo_url = $agentInfo->round_logo_url;
                isset($agentInfo->square_logo_url) && $agent->square_logo_url = $agentInfo->square_logo_url;
                isset($this->permanentInfo->permanent_code) && $agent->permanent_code = $this->permanentInfo->permanent_code;
                if($agent->save() && isset($this->permanentInfo->access_token)) {
                    // 缓存ACCESS_TOKEN
                    $expires = intval($this->permanentInfo->expires_in);
                    cache($agent->corpid.'-'.$agent->permanent_code, strval($this->permanentInfo->access_token), $expires > 0 ? $expires : null);
                }

                $agentPrivilege = CorpPrivilege::find($this->permanentInfo->auth_corp_info->corpid);
                $agentPrivilege || $agentPrivilege = new CorpPrivilege;
                $agentPrivilege->corpid = $this->permanentInfo->auth_corp_info->corpid;
                $agentPrivilege->agentid = $agentInfo->agentid;
                isset($agentInfo->privilege->level) && $agentPrivilege->level = $agentInfo->privilege->level;
                isset($agentInfo->privilege->allow_party) && $agentPrivilege->allow_party = $agentInfo->privilege->allow_party;
                isset($agentInfo->privilege->allow_tag) && $agentPrivilege->allow_tag = $agentInfo->privilege->allow_tag;
                isset($agentInfo->privilege->allow_user) && $agentPrivilege->allow_user = $agentInfo->privilege->allow_user;
                isset($agentInfo->privilege->extra_party) && $agentPrivilege->extra_party = $agentInfo->privilege->extra_party;
                isset($agentInfo->privilege->extra_user) && $agentPrivilege->extra_user = $agentInfo->privilege->extra_user;
                isset($agentInfo->privilege->extra_tag) && $agentPrivilege->extra_tag = $agentInfo->privilege->extra_tag;
                $agentPrivilege->save();
            }

            // 存储agent安装记录
            if(isset($this->permanentInfo->auth_user_info)) {
                $corpHistory = new CorpHistory;
                $corpHistory->corpid = $this->permanentInfo->auth_corp_info->corpid;
                $corpHistory->optime = time();
                $corpHistory->optype = 1;
                $corpHistory->opdata = json_encode($this->permanentInfo);
                isset($this->permanentInfo->auth_user_info->email) && $corpHistory->opuser = $this->permanentInfo->auth_user_info->email;
                if(isset($this->permanentInfo->auth_user_info->userid)) {
                    $corpHistory->opuser = $this->permanentInfo->auth_user_info->userid;

                    if(! empty($this->newsArticle)) {
                        $newsArticles = [];
                        foreach($this->newsArticle as $msgInfo) {
                            $newsArticles[] = new NewsArticle($msgInfo['title'], $msgInfo['description'], $msgInfo['url'], $msgInfo['picurl'], $msgInfo['btntxt']);
                        }

                        // 给管理员推送说明页面
                        $message = new Message();
                        $message->totag = [];
                        $message->toparty = [];
                        $message->touser = [$this->permanentInfo->auth_user_info->userid];
                        $message->agentid = $this->permanentInfo->auth_info->agent[0]->agentid;
                        $message->messageContent = new NewsMessageContent($newsArticles);

                        $invalidUserIdList = $invalidPartyIdList = $invalidTagIdList = [];
                        (new Service(
                            $this->permanentInfo->auth_corp_info->corpid,
                            $this->permanentInfo->permanent_code
                        ))->MessageSend($message, $invalidUserIdList, $invalidPartyIdList, $invalidTagIdList);
                    }
                }

                $corpHistory->save();

                //存储管理员用户信息
                $user = VenueUser::where(['corpid' => $this->permanentInfo->auth_corp_info->corpid, 'userid' => $this->permanentInfo->auth_user_info->userid])->find();
                if (empty($user)) {
                    $user = new VenueUser;
                    $user->name     = $this->permanentInfo->auth_user_info->name;
                    $user->avatar   = $this->permanentInfo->auth_user_info->avatar;
                    $user->userid   = $this->permanentInfo->auth_user_info->userid;
                    $user->corpid   = $this->permanentInfo->auth_corp_info->corpid;
                    $user->save();
                }
            }

            Db::commit(); // 提交事务
        } catch (\Exception $e) {
            Db::rollback(); // 回滚事务
            trace('应用安装失败：'.$e->getMessage(), 'error');
        }
    }

    public static function uninstall($corpId, $suitId)
    {
        $agent = CorpAgent::find($corpId);
        $agent && $agent->delete();
        $agentPrivilege = CorpPrivilege::find($corpId);
        $agentPrivilege && $agentPrivilege->delete();
    }
}

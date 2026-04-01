<?php

namespace Blankkids\ThinkphpDdd\Foundation\Behavior;

use Blankkids\ThinkphpDdd\Foundation\Error\BaseError;
use Blankkids\ThinkphpDdd\Foundation\Exception\Exception;
use Blankkids\ThinkphpDdd\Foundation\Middleware\Auth;

/*
 * 管理以上权限
 */
class AdminAuthBehavior extends BaseAuthBehavior{

    public function run()
    {
        $scopes = 'admin_auth,system_auth';
        $scopes = explode(',',$scopes);

        //检查授权
        $auth = $this->apiAuth();
        $scopeId = $auth->scope_id;

        if( !in_array($scopeId,$scopes) ){
            Exception::app(BaseError::code('AUTH_SCOPE_FAIL'),BaseError::msg('AUTH_SCOPE_FAIL'));
        }

        //建立auth授权数据对象
        $authData = Auth::instance();
        $authData->adminId = $auth->admin_id;
        $authData->scopeId = $auth->scope_id;

        return true;
    }

}
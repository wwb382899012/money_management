<?php
class Params{
    public static function GetcsrfTokenServer(){
        return [
            'com.jyblife.logic.bg.user.UserLogin',
        ];
    }

    public static function noNeedSessionCheckServer(){
        return [
            'com.jyblife.logic.bg.user.UserLogin',
            'com.jyblife.logic.bg.user.UserLogout',
            'com.jyblife.logic.bg.module.ModuleInit',
            'com.jyblife.logic.bg.order.PayOrderTest',
            'com.jyblife.logic.bg.loan.LoanOrderTest',
            'com.jyblife.logic.bg.flow.Test',
            'com.jyblife.logic.bg.pay.Test',
            'com.jyblife.logic.bg.order.PayOrder',
            'com.jyblife.logic.bg.order.PayOrderQuery',
            'com.jyblife.logic.bg.loan.LoanOrder',
            'com.jyblife.logic.bg.loan.LoanOrderQuery',
            'com.jyblife.logic.bg.loan.RepayOrder',
            'com.jyblife.logic.bg.base.BankBaseList',
        ];
    }

    public static function noNeedTargetServiceCheck()
    {
        return [
            'com.jyblife.logic.bg.news.ListNews',
            'com.jyblife.logic.bg.news.ReadNews',
            'com.jyblife.logic.bg.user.UserDetail',
            'com.jyblife.logic.bg.base.BaseBankQuery',
            'com.jyblife.logic.bg.flow.Approve',
            'com.jyblife.logic.bg.flow.DetailList',
            'com.jyblife.logic.bg.flow.Start',
            'com.jyblife.logic.bg.flow.Stop',
            'com.jyblife.logic.bg.pay.Order',
            'com.jyblife.logic.bg.pay.NoticeResult',
            'com.jyblife.logic.bg.pay.GetUKPwd',
            'com.jyblife.logic.bg.pay.SetUKPwd',
        ];
    }
}
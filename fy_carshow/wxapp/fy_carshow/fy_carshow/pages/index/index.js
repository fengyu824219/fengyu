var app = getApp();
var util = require('../../../we7/resource/js/util.js');
Page({
  data:{
    content:"",
    avatarurl: "",
    openid: "",
    content: "",
    content: "",
    content: "",
    content: "",
    content: "",
    content: "",
  },
  bindFormSubmit:function(e){
    var that = this;
    var content = e.detail.value.content;
    if(!content){
      wx.showToast({
        title: '空白如何共勉之？',
        icon:'none',
        duration:1500
      })
      return false;
    }
    app.util.getUserInfo(function(userinfo){console.log(userinfo)});
    app.util.request({
      url: 'entry/wxapp/content',
      data: {
        content: content
      },
      method: 'post',
      success: function (res) {
        console.log(324233)
       
      }
    })
    that.setData({
      content: ''
    })
  },
  onLoad:function(options){
    // 页面初始化 options为页面跳转所带来的参数
  },
  onReady:function(){
    // 页面渲染完成
  },
  onShow:function(){
    // 页面显示
  },
  onHide:function(){
    // 页面隐藏
  },
  onUnload:function(){
    // 页面关闭
  }
})